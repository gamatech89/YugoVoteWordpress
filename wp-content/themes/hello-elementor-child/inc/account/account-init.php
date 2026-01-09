<?php
if (!defined('ABSPATH')) exit;

$acc = get_stylesheet_directory() . '/inc/account/';

require_once $acc . 'helpers.php';
require_once $acc . 'account-hooks.php';
require_once $acc . 'account-scripts.php';

$sc = $acc . 'shortcodes';
if (is_dir($sc)) foreach (glob($sc.'/*.php') as $f) require_once $f;

$api = $acc . 'api';
if (is_dir($api)) foreach (glob($api.'/*.php') as $f) require_once $f;
