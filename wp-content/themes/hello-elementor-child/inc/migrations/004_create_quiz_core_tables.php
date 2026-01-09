<?php
/**
 * 004_create_quiz_core_tables.php
 *
 * Creates core tables for quizzes/tokens/progress (idempotent via dbDelta):
 *  - {$wpdb->prefix}ygv_user_tokens
 *  - {$wpdb->prefix}ygv_user_category_progress
 *  - {$wpdb->prefix}ygv_quiz_attempts
 *  - {$wpdb->prefix}ygv_user_overall_progress
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$collate = $wpdb->get_charset_collate();

$t_tokens   = $wpdb->prefix . 'ygv_user_tokens';
$t_cat_prog = $wpdb->prefix . 'ygv_user_category_progress';
$t_attempts = $wpdb->prefix . 'ygv_quiz_attempts';
$t_overall  = $wpdb->prefix . 'ygv_user_overall_progress';

$sql = [];

// 1) Token wallet (global)
$sql[] = "CREATE TABLE {$t_tokens} (
  user_id BIGINT(20) UNSIGNED NOT NULL,
  tokens SMALLINT UNSIGNED NOT NULL DEFAULT 48,
  max_tokens SMALLINT UNSIGNED NOT NULL DEFAULT 48,
  regen_rate SMALLINT UNSIGNED NOT NULL DEFAULT 2,
  regen_interval_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (user_id)
) {$collate};";

// 2) Per-category progress
$sql[] = "CREATE TABLE {$t_cat_prog} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  category_term_id BIGINT(20) UNSIGNED NOT NULL,
  xp INT UNSIGNED NOT NULL DEFAULT 0,
  level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  streak TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_cat (user_id, category_term_id),
  KEY cat_idx (category_term_id),
  KEY user_idx (user_id)
) {$collate};";

// 3) Attempts log (immutable)
$sql[] = "CREATE TABLE {$t_attempts} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  quiz_id BIGINT(20) UNSIGNED NOT NULL,
  category_term_id BIGINT(20) UNSIGNED NOT NULL,
  level_required TINYINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL,
  submitted_at DATETIME NULL DEFAULT NULL,
  correct_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  total_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  score_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  xp_awarded SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  tokens_spent SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ip_hash CHAR(64) NULL DEFAULT NULL,
  ua_hash CHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY user_idx (user_id),
  KEY quiz_idx (quiz_id),
  KEY cat_idx (category_term_id)
) {$collate};";

// 4) Overall progress (roll-up)
$sql[] = "CREATE TABLE {$t_overall} (
  user_id BIGINT(20) UNSIGNED NOT NULL,
  overall_xp INT UNSIGNED NOT NULL DEFAULT 0,
  overall_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  last_recalc_at DATETIME NULL DEFAULT NULL,
  badges_json LONGTEXT NULL,
  PRIMARY KEY  (user_id)
) {$collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
foreach ($sql as $statement) {
    dbDelta($statement);
}
