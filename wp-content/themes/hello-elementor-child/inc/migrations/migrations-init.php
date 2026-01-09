<?php
/**
 * Migrations Initializer
 *
 * Loads and runs all database migrations for the voting system.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/run-migrations.php';

if (function_exists('run_voting_migrations')) {
    run_voting_migrations();
}
