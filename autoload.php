<?php
/**
 * Autoloading: prefer Composer classmap when present. Fall back to a
 * minimal PSR-4 autoloader so the plugin still boots if vendor/ is absent.
 *
 * @package Garantio
 */

declare(strict_types=1);

namespace Garantio;

defined('ABSPATH') || exit;

$garantio_composer = __DIR__ . '/vendor/autoload.php';
if (is_readable($garantio_composer)) {
    require_once $garantio_composer;
    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix  = 'Garantio\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});
