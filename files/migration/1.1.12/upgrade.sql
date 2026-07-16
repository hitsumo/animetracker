-- =====================================================================
-- 1.1.12 - Invite request limit + registration announcement
-- =====================================================================
-- This release adds two admin-configurable registration policies, both
-- stored in the generic settings key/value table:
--
--   * invite_request_limit  - how many PENDING invite requests may queue
--       before the public request form (request_invite.php) closes. 0 (or
--       unset) means no cap. Counting PENDING makes it self-healing: as the
--       operator invites/rejects queued requests, slots reopen.
--
--   * register_announcement - free text shown on the registration screen
--       (register.php). Empty means no notice.
--
-- Neither needs a schema change; they are ordinary settings keys created on
-- first write from the admin panel and read with a safe default in code. We
-- seed the defaults here with INSERT IGNORE purely so they exist as rows on
-- upgrade (harmless, never clobbers an existing value). The real purpose of
-- this migration folder is to advance settings.version to 1.1.12 (the
-- migration runner stamps the version after the SQL runs).
--
-- NOTE: purely app-side. The central catalog server (catalog_server/) never
-- sees these keys, so - unlike the enum/is_adult work - there is NO manual
-- ALTER to run on the catalog host.
-- =====================================================================

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES
('invite_request_limit', '0'),
('register_announcement', '');
