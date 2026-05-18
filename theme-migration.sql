-- ═══════════════════════════════════════════════════════════════════
-- LearnFlow LMS — Theme Settings Table Migration
-- Run this once against your learnflow_db database.
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `theme_settings` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(80)      NOT NULL DEFAULT 'Rose Pink',
  `primary_color`  VARCHAR(60)      NOT NULL DEFAULT '336 67% 52%',
  `primary_dark`   VARCHAR(60)      NOT NULL DEFAULT '336 67% 40%',
  `primary_light`  VARCHAR(60)      NOT NULL DEFAULT '336 100% 97%',
  `bg_color`       VARCHAR(60)      NOT NULL DEFAULT '336 100% 97%',
  `surface_color`  VARCHAR(60)      NOT NULL DEFAULT '0 0% 100%',
  `border_color`   VARCHAR(60)      NOT NULL DEFAULT '336 60% 87%',
  `text_color`     VARCHAR(60)      NOT NULL DEFAULT '336 60% 10%',
  `text_secondary` VARCHAR(60)      NOT NULL DEFAULT '336 40% 47%',
  `accent_color`   VARCHAR(60)               DEFAULT '207 80% 60%',
  `is_dark`        TINYINT(1)       NOT NULL DEFAULT 0,
  `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the default (Rose Pink) theme — only inserts if table was empty.
INSERT IGNORE INTO `theme_settings`
  (`id`, `name`, `primary_color`, `primary_dark`, `primary_light`,
   `bg_color`, `surface_color`, `border_color`, `text_color`,
   `text_secondary`, `accent_color`, `is_dark`)
VALUES
  (1, 'Rose Pink', '336 67% 52%', '336 67% 40%', '336 100% 97%',
   '336 100% 97%', '0 0% 100%', '336 60% 87%', '336 60% 10%',
   '336 40% 47%', '207 80% 60%', 0);
