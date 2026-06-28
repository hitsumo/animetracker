-- Anime Tracker 1.0.20 migration.
--
-- 1.0.20 adds a public invite-request flow: a visitor without an invite can ask
-- the operator for one (request_invite.php), the request is queued for review on
-- admin_invites.php, and a best-effort notification mail is sent to the operator
-- (settings.invite_notify_email). This is the first real (schema-changing)
-- migration since 1.0.15; the ring is NOT a no-op.
--
-- New table invite_requests mirrors the suggestions protection model (per-IP
-- rate limit via idx_ip_created). CREATE TABLE IF NOT EXISTS is safe if an
-- install somehow already has it; migration_manager also treats 1050 (table
-- exists) as idempotent. The same table lives in schema.sql so a fresh install
-- gets it without replaying this migration.

CREATE TABLE IF NOT EXISTS `invite_requests` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `email`       varchar(255) NOT NULL,
  `reason`      text         NOT NULL,
  `ip`          varchar(45)  DEFAULT NULL,
  `status`      enum('pending','invited','rejected') NOT NULL DEFAULT 'pending',
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ip_created` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
