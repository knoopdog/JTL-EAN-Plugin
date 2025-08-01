<?php
/**
 * JTL EAN Plugin Uninstall
 * 
 * Fired when the plugin is uninstalled.
 * Removes all plugin data from the database.
 *
 * @package JTL_EAN_Plugin
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Security check - only run if plugin is being uninstalled
if ( ! current_user_can( 'activate_plugins' ) ) {
    exit;
}

/**
 * Complete database cleanup
 * Removes all traces of the JTL EAN Plugin from the database
 */

global $wpdb;

// 1. Remove plugin options from wp_options table
$plugin_options = array(
    'jtl_ean_plugin_version',
    'jtl_ean_plugin_settings', // Future settings if added
);

foreach ( $plugin_options as $option ) {
    delete_option( $option );
    delete_site_option( $option ); // For multisite
}

// 2. Remove all GTIN meta data from wp_postmeta
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_ts_gtin' ),
    array( '%s' )
);

// 3. Remove all MPN meta data from wp_postmeta
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_ts_mpn' ),
    array( '%s' )
);

// 4. Remove any cached meta data
wp_cache_flush();

// 5. Clean up any transients that might have been created
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_jtl_ean_%' 
     OR option_name LIKE '_transient_timeout_jtl_ean_%'"
);

// 6. For multisite: clean up site options
if ( is_multisite() ) {
    $wpdb->query(
        "DELETE FROM {$wpdb->sitemeta} 
         WHERE meta_key LIKE 'jtl_ean_%'"
    );
}

// 7. Log the uninstallation (optional, can be removed in production)
if ( WP_DEBUG && WP_DEBUG_LOG ) {
    error_log( 'JTL EAN Plugin: Complete uninstallation completed at ' . current_time( 'mysql' ) );
}

// 8. Clear any object cache
if ( function_exists( 'wp_cache_flush_group' ) ) {
    wp_cache_flush_group( 'jtl_ean' );
}

// 9. Final database optimization (optional)
$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );
$wpdb->query( "OPTIMIZE TABLE {$wpdb->options}" );

/**
 * Generate uninstall report (for debugging purposes)
 * Remove this section in production if not needed
 */
if ( WP_DEBUG ) {
    $cleanup_report = array(
        'timestamp' => current_time( 'mysql' ),
        'plugin_version' => '1.0.0',
        'actions_performed' => array(
            'removed_plugin_options' => count( $plugin_options ),
            'removed_gtin_meta_entries' => $wpdb->rows_affected ?? 0,
            'removed_mpn_meta_entries' => 'completed',
            'cleared_transients' => 'completed',
            'optimized_tables' => 'completed',
        ),
        'database_status' => 'clean'
    );
    
    // Store report temporarily (will be cleaned up by WordPress)
    set_transient( 'jtl_ean_uninstall_report', $cleanup_report, HOUR_IN_SECONDS );
}