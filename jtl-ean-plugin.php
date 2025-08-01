<?php
/**
 * Plugin Name: JTL EAN Plugin
 * Plugin URI: https://visuell-code.de
 * Description: Lightweight EAN/GTIN support for WooCommerce with JTL Connector compatibility. Extracted functionality from WooCommerce Germanized without the additional features.
 * Version: 1.0.0
 * Author: Visuell Code
 * Author URI: https://visuell-code.de
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Text Domain: jtl-ean-plugin
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.5
 *
 * @package JTL_EAN_Plugin
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'JTL_EAN_PLUGIN_VERSION', '1.0.0' );
define( 'JTL_EAN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'JTL_EAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JTL_EAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main JTL EAN Plugin Class
 */
final class JTL_EAN_Plugin {

    /**
     * Plugin instance
     * @var JTL_EAN_Plugin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     * @return JTL_EAN_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Activation, deactivation and uninstall hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        register_uninstall_hook( __FILE__, 'jtl_ean_plugin_uninstall' );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        $this->load_includes();
        $this->init_classes();
        
        // Add fallback filter for WooCommerce Core GTIN
        add_filter( 'woocommerce_product_get_global_unique_id', array( $this, 'add_gtin_fallback' ), 10, 2 );
        add_filter( 'woocommerce_product_variation_get_global_unique_id', array( $this, 'add_gtin_fallback' ), 10, 2 );
    }

    /**
     * Load plugin includes
     */
    private function load_includes() {
        require_once JTL_EAN_PLUGIN_PATH . 'includes/class-jtl-ean-product.php';
        require_once JTL_EAN_PLUGIN_PATH . 'includes/class-jtl-ean-admin.php';
        require_once JTL_EAN_PLUGIN_PATH . 'includes/class-jtl-ean-api.php';
    }

    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        new JTL_EAN_Admin();
        new JTL_EAN_API();
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'jtl-ean-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Check if WooCommerce is active
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Display notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'JTL EAN Plugin requires WooCommerce to be installed and active.', 'jtl-ean-plugin' ); ?></p>
        </div>
        <?php
    }

    /**
     * GTIN fallback for WooCommerce Core
     * This maintains compatibility with the original Germanized approach
     * 
     * @param string $gtin Current GTIN value
     * @param WC_Product $product Product object
     * @return string
     */
    public function add_gtin_fallback( $gtin, $product ) {
        if ( empty( $gtin ) ) {
            $jtl_product = new JTL_EAN_Product( $product );
            $gtin = $jtl_product->get_gtin( 'edit' );
        }
        
        return $gtin;
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Check WooCommerce dependency
        if ( ! $this->is_woocommerce_active() ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( 
                esc_html__( 'JTL EAN Plugin requires WooCommerce to be installed and active.', 'jtl-ean-plugin' ),
                esc_html__( 'Plugin Activation Error', 'jtl-ean-plugin' ),
                array( 'back_link' => true )
            );
        }

        // Create version option
        add_option( 'jtl_ean_plugin_version', JTL_EAN_PLUGIN_VERSION );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }

    /**
     * Get product EAN/GTIN helper function
     * 
     * @param int|WC_Product $product Product ID or object
     * @return JTL_EAN_Product
     */
    public static function get_product( $product ) {
        return new JTL_EAN_Product( $product );
    }
}

/**
 * Helper function to get JTL EAN product instance
 * 
 * @param int|WC_Product $product Product ID or object
 * @return JTL_EAN_Product
 */
function jtl_ean_get_product( $product ) {
    return JTL_EAN_Plugin::get_product( $product );
}

/**
 * Uninstall hook function
 * Called when plugin is deleted via WordPress admin
 */
function jtl_ean_plugin_uninstall() {
    // Include the uninstall file which handles the complete cleanup
    require_once JTL_EAN_PLUGIN_PATH . 'uninstall.php';
}

/**
 * Initialize the plugin
 */
JTL_EAN_Plugin::get_instance();