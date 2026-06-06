-- Anime Tracker 1.0.4 migration (Faz 2, Milestone 2 - Dilim 4: registration)
-- Adds the invite table and the registration_mode instance setting that the
-- new account-registration flow (register.php) needs. No existing table or
-- column is altered - this migration is purely additive.
--
-- registration_mode: 'invite' (default) or 'open'. It lives in settings, not
-- user_pref, because it is operator/admin policy, not a user preference. It is
-- seeded to 'invite' so a fresh online instance starts closed and the operator
-- opens registration deliberately. INSERT IGNORE leaves an existing row (and
-- any chosen value) untouched, so re-running the migration is a no-op.
--
-- invites: one row per invite token. token is the single-use secret a new
-- account must present in invite mode. created_by / used_by reference users.id
-- by value but carry NO foreign key on purpose - an invite must survive the
-- deletion of the admin who made it or the user who used it (audit trail), and
-- the soft-delete user routine never hard-deletes rows anyway. A row with NULL
-- used_by and NULL used_at is an unused invite.
--
-- IDEMPOTENCY: CREATE TABLE IF NOT EXISTS (and migration_manager also swallows
-- error 1050, table-exists) plus INSERT IGNORE make this safe to re-run after a
-- partial apply.
--
-- SCOPE: self-host / multi-user database only. registration_mode has no effect
-- in self-host (MULTI_USER_MODE=false has no registration path); the row sits
-- inert until an instance runs in multi-user mode. The sicakcikolata.com
-- catalog server is not touched by this migration.

CREATE TABLE IF NOT EXISTS `invites` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `token`      varchar(64)  NOT NULL,
  `email`      varchar(255) DEFAULT NULL,
  `created_by` int(11)      DEFAULT NULL,
  `used_by`    int(11)      DEFAULT NULL,
  `used_at`    timestamp    NULL DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES ('registration_mode', 'invite');
