<?php

/**
 * A shop manager sees the settings page (manage_woocommerce), so options.php
 * must accept the save with the same capability, not manage_options.
 *
 * Run: php tests/settings-capability-check.php
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);

require_once __DIR__ . '/../autoload.php';

$filters = [];
$menuCap = null;

function add_action(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void {}
function add_filter(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void { $GLOBALS['filters'][$tag] = $callback; }
function __(string $text, string $domain = 'default'): string { return $text; }
function add_submenu_page(string $parent, string $title, string $menu, string $cap, string $slug, $cb): void { $GLOBALS['menuCap'] = $cap; }

$page = new Garantio\Admin\SettingsPage();
$page->registerHooks();
$page->registerMenu();

// options.php: $capability = apply_filters("option_page_capability_{$option_page}", 'manage_options').
$hook = 'option_page_capability_' . Garantio\Admin\SettingsPage::PAGE_SLUG;
$saveCap = isset($filters[$hook]) ? $filters[$hook]('manage_options') : 'manage_options';

if ($saveCap !== $menuCap) {
    echo "FAIL: page needs {$menuCap} but options.php save needs {$saveCap}\n";
    exit(1);
}

echo "OK: options.php save capability matches the menu capability ({$menuCap})\n";
