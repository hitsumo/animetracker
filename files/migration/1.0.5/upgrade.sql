-- Anime Tracker 1.0.5 migration (Faz 2, Milestone 2 - Dilim 5: suggestions)
-- Adds the suggestions table: free-text correction notes about a catalog
-- anime, submitted via suggest.php by anyone (anonymous or signed-in) and
-- reviewed in a moderation queue (admin_suggestions.php). Purely additive; no
-- existing table or column is touched.
--
-- Columns:
--   anime_id          - the catalog row this is about. FK to animes with
--                       ON DELETE CASCADE: removing the anime removes its
--                       suggestions.
--   note              - the free-text suggestion body. Field-level / structured
--                       suggestions (field + proposed_value) are a later
--                       milestone; for now this is a note only.
--   submitter_user_id - users.id of a signed-in submitter, or NULL when the
--                       submission was anonymous. NO foreign key on purpose:
--                       anonymous (NULL) and deleted-user suggestions must be
--                       preserved.
--   ip                - submitter IP (IPv6-safe length). Used only for abuse
--                       handling and per-IP rate limiting on submit; never
--                       shown to end users. (Deviation from the FAZ sec.6 draft
--                       schema, which omitted it; added so the rate limit the
--                       same section asks for is possible without a second
--                       migration.)
--   status            - pending (queue) -> accepted / rejected by a moderator.
--                       Applying an accepted suggestion to the catalog is
--                       manual; there is no auto-apply.
--
-- Indexes: idx_ip_created backs the per-IP rate-limit count; idx_anime and
-- idx_status back the per-anime view and the moderation queue.
--
-- IDEMPOTENCY: CREATE TABLE IF NOT EXISTS (migration_manager also swallows
-- error 1050). Re-running is a no-op; the FK is only created with the table.
--
-- SCOPE: multi-user database. In self-host (MULTI_USER_MODE=false) there is no
-- suggestion UI - the owner edits the catalog directly - so the table simply
-- stays empty. The sicakcikolata.com catalog server is not touched here.

CREATE TABLE IF NOT EXISTS `suggestions` (
  `id`                int(11)     NOT NULL AUTO_INCREMENT,
  `anime_id`          int(11)     NOT NULL,
  `note`              text        NOT NULL,
  `submitter_user_id` int(11)     DEFAULT NULL,
  `ip`                varchar(45) DEFAULT NULL,
  `status`            enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at`        timestamp   NOT NULL DEFAULT current_timestamp(),
  `updated_at`        timestamp   NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_anime` (`anime_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ip_created` (`ip`, `created_at`),
  CONSTRAINT `fk_sug_anime` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
