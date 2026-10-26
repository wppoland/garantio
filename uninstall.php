<?php
/**
 * Uninstall routine: clean up plugin options.
 *
 * @package Garantio
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('garantio_settings');
