<?php
/**
 * Uninstall routine: clean up plugin options.
 *
 * @package Garantio
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove this plugin's options from the current site.
 */
function garantio_uninstall_site(): void {
	delete_option( 'garantio_settings' );
}

// Options are per site, so a network uninstall has to visit every site.
if ( is_multisite() ) {
	$garantio_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $garantio_site_ids as $garantio_site_id ) {
		switch_to_blog( (int) $garantio_site_id );
		garantio_uninstall_site();
		restore_current_blog();
	}
	unset( $garantio_site_ids, $garantio_site_id );
} else {
	garantio_uninstall_site();
}
