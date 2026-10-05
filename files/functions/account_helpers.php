<?php
/**
 * Anime Tracker - Account helpers (account deletion)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.2.5. One routine deletes an account, whoever asks for it:
 * an admin on admin/admin_users.php or the member on account.php. Both go
 * through account_delete_check() first and account_delete() second, so the
 * rules below live in exactly one place.
 *
 * WHAT HAPPENS TO EACH PIECE OF DATA
 *
 *   Deleted with the account (ON DELETE CASCADE): user_anime (the list,
 *   notes, personal synopses), user_watch_log, user_pref,
 *   anilist_import_sources.
 *
 *   Deleted by this routine: invite_requests rows carrying the account's
 *   e-mail address (address + reason + IP are personal data, and the
 *   request has served its purpose).
 *
 *   Kept, detached from the account:
 *   - user_anime_emotion: the marks stay in the anonymous counts (detail
 *     page distribution, What to Watch), which never show who marked what.
 *     user_id becomes the NEGATIVE of the old id. Not NULL: it is part of
 *     the primary key. Not 0: two deleted members who marked the same
 *     anime with the same emotion would collide and one mark would be lost.
 *     A negative id belongs to nobody, keeps the voter count right
 *     (COUNT(DISTINCT user_id)) and can never be inherited by a future
 *     account even if an AUTO_INCREMENT value is ever reused.
 *   - invites.used_by: also NEGATED, never NULL. register.php treats a code
 *     with used_by IS NULL as unused; clearing it would make the deleted
 *     member's invite code valid again. The code's e-mail column is cleared.
 *   - invites.created_by, suggestions.submitter_user_id: NULL (no meaning
 *     beyond "who"). A suggestion's IP is cleared with it - the note stays.
 *   - catalog_requests.suggested_by / reviewed_by and
 *     import_blacklist.created_by: NULL through their own ON DELETE SET
 *     NULL foreign keys.
 *
 *   `updated_at = updated_at` keeps ON UPDATE timestamps still: detaching a
 *   row is housekeeping, not an edit (the admin lists sort by them).
 *
 * WHO CANNOT BE DELETED (account_delete_check)
 *   - id 1: the seeded owner row. Self-host identifies the single user as
 *     id 1 (current_user_id()), so the row must survive a switch back.
 *   - the last active admin: the site would be left without anyone able to
 *     manage it.
 */

/**
 * Can this account be deleted?
 *
 * @param PDO $pdo
 * @param int $userId
 * @return string '' when allowed, otherwise a reason:
 *                'not_found' | 'owner' | 'last_admin'
 */
function account_delete_check($pdo, $userId) {
    $userId = (int)$userId;
    if ($userId <= 0) {
        return 'not_found';
    }
    if ($userId === 1) {
        return 'owner';
    }
    $stmt = $pdo->prepare("SELECT role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 'not_found';
    }
    if ($row['role'] === 'admin' && $row['status'] === 'active') {
        $c = $pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND id <> ?"
        );
        $c->execute([$userId]);
        if ((int)$c->fetchColumn() === 0) {
            return 'last_admin';
        }
    }
    return '';
}

/**
 * Delete an account in one transaction (see the file header for what goes
 * and what stays). Call account_delete_check() first; this function trusts
 * the caller on the rules and only guards against a missing row.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return bool true when the account row was deleted.
 */
function account_delete($pdo, $userId) {
    $userId = (int)$userId;
    if ($userId <= 1) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();
        if ($email === false) {
            $pdo->rollBack();
            return false;
        }
        $email = trim((string)$email);

        // Emotion marks: detach (negate). IGNORE + the DELETE after it cover
        // the only possible collision - a row already negated for this id.
        $pdo->prepare("UPDATE IGNORE user_anime_emotion SET user_id = -user_id WHERE user_id = ?")
            ->execute([$userId]);
        $pdo->prepare("DELETE FROM user_anime_emotion WHERE user_id = ?")
            ->execute([$userId]);

        // Suggestions: keep the note, drop the submitter and the IP.
        $pdo->prepare(
            "UPDATE suggestions
                SET submitter_user_id = NULL, ip = NULL, updated_at = updated_at
              WHERE submitter_user_id = ?"
        )->execute([$userId]);

        // Invites: the code this member redeemed stays USED (negated id) and
        // loses its e-mail; codes they created lose the creator.
        $pdo->prepare("UPDATE invites SET used_by = -used_by, email = NULL WHERE used_by = ?")
            ->execute([$userId]);
        $pdo->prepare("UPDATE invites SET created_by = NULL WHERE created_by = ?")
            ->execute([$userId]);

        // Invite requests sent from this member's address.
        if ($email !== '') {
            $pdo->prepare("DELETE FROM invite_requests WHERE LOWER(email) = LOWER(?)")
                ->execute([$email]);
        }

        // The account itself; CASCADE / SET NULL handle the rest.
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$userId]);
        $deleted = ($del->rowCount() === 1);

        if (!$deleted) {
            $pdo->rollBack();
            return false;
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[anime_tracker] account_delete(' . $userId . '): ' . $e->getMessage());
        return false;
    }
}
