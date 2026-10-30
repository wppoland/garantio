<?php

/**
 * Deleting the plugin on a network must clean every site, not only the one
 * the uninstall runs in: the settings option and the four product meta keys.
 *
 * Run: php tests/uninstall-multisite-check.php
 */

declare(strict_types=1);

define('WP_UNINSTALL_PLUGIN', 'garantio/garantio.php');

$blog    = 1;
$deleted = [];

function is_multisite(): bool { return true; }
function get_sites(array $args): array { return [1, 2, 3]; }
function switch_to_blog(int $id): void { $GLOBALS['blog'] = $id; }
function restore_current_blog(): void { $GLOBALS['blog'] = 1; }
function delete_option(string $name): void { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':' . $name; }
function delete_transient(string $name): void { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':transient:' . $name; }
function delete_post_meta_by_key(string $key): void { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':postmeta:' . $key; }
function delete_metadata(string $type, int $id, string $key, string $value = '', bool $all = false): void { $GLOBALS['deleted'][] = 'user:' . $key; }

require __DIR__ . '/../uninstall.php';

$expected = [];
foreach ([1, 2, 3] as $site) {
    array_push(
        $expected,
        "{$site}:garantio_settings",
        "{$site}:postmeta:_garantio_durability_years",
        "{$site}:postmeta:_garantio_durability_producer",
        "{$site}:postmeta:_garantio_repair_score",
        "{$site}:postmeta:_garantio_exempt_guarantee"
    );
}
sort($expected);
sort($deleted);

if ($deleted !== $expected) {
    echo 'FAIL: uninstall deleted ' . implode(', ', $deleted) . "\n";
    exit(1);
}

echo "OK: uninstall removes settings and product meta on every site of the network\n";
