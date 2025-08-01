<?php
/**
 * JTL EAN Admin Class
 * 
 * Handles admin interface for EAN/GTIN functionality
 *
 * @package JTL_EAN_Plugin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * JTL EAN Admin class
 */
class JTL_EAN_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        // Product meta boxes
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'output_product_fields' ) );
        add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_fields' ), 10, 1 );

        // Variable product fields
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'output_variable_fields' ), 10, 3 );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_fields' ), 10, 2 );

        // Bulk edit
        add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'bulk_edit_fields' ) );
        add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'bulk_edit_save' ) );

        // Quick edit
        add_action( 'woocommerce_product_quick_edit_end', array( $this, 'quick_edit_fields' ) );
        add_action( 'add_inline_data', array( $this, 'quick_edit_data' ), 10, 2 );
        add_action( 'woocommerce_product_quick_edit_save', array( $this, 'quick_edit_save' ) );

        // Admin styles
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        // Admin menu and settings
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_manual_uninstall' ) );
    }

    /**
     * Output GTIN fields in product general tab
     */
    public function output_product_fields() {
        global $product_object;

        if ( ! $product_object ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product_object );

        echo '<div class="options_group show_if_simple show_if_external show_if_variable">';
        echo '<div class="jtl-ean-fields-header">';
        echo '<h4>' . esc_html__( 'Product Identifiers', 'jtl-ean-plugin' ) . '</h4>';
        echo '<p class="description">' . esc_html__( 'Product identification codes used for inventory management and e-commerce platforms.', 'jtl-ean-plugin' ) . '</p>';
        echo '</div>';

        // GTIN field
        woocommerce_wp_text_input(
            array(
                'id'          => '_ts_gtin',
                'value'       => $jtl_product->get_gtin( 'edit' ),
                'label'       => __( 'GTIN / EAN', 'jtl-ean-plugin' ),
                'placeholder' => '4250123456789',
                'description' => __( 'Global Trade Item Number (GTIN) or European Article Number (EAN) that uniquely identifies your product worldwide.', 'jtl-ean-plugin' ),
                'desc_tip'    => true,
                'type'        => 'text',
            )
        );

        // MPN field
        woocommerce_wp_text_input(
            array(
                'id'          => '_ts_mpn',
                'value'       => $jtl_product->get_mpn( 'edit' ),
                'label'       => __( 'MPN', 'jtl-ean-plugin' ),
                'placeholder' => 'ABC-123-XYZ',
                'description' => __( 'Manufacturer Part Number (MPN) assigned by the product manufacturer.', 'jtl-ean-plugin' ),
                'desc_tip'    => true,
                'type'        => 'text',
            )
        );

        echo '</div>';
    }

    /**
     * Save product fields
     * 
     * @param WC_Product $product Product object
     */
    public function save_product_fields( $product ) {
        if ( ! $product instanceof WC_Product ) {
            return;
        }

        // Security: Check user capability
        if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
            return;
        }

        // Security: Verify nonce (WordPress handles this in product save process)
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        // Save GTIN with enhanced validation
        if ( isset( $_POST['_ts_gtin'] ) ) {
            $gtin = sanitize_text_field( wp_unslash( $_POST['_ts_gtin'] ) );
            // Validate GTIN format (optional: basic validation)
            $gtin = $this->validate_gtin( $gtin );
            $jtl_product->set_gtin( $gtin );
        }

        // Save MPN with enhanced validation
        if ( isset( $_POST['_ts_mpn'] ) ) {
            $mpn = sanitize_text_field( wp_unslash( $_POST['_ts_mpn'] ) );
            // Validate MPN format
            $mpn = $this->validate_mpn( $mpn );
            $jtl_product->set_mpn( $mpn );
        }
    }

    /**
     * Output variable product fields
     * 
     * @param int $loop Loop index
     * @param array $variation_data Variation data
     * @param WP_Post $variation Variation post object
     */
    public function output_variable_fields( $loop, $variation_data, $variation ) {
        $variation_product = wc_get_product( $variation->ID );
        if ( ! $variation_product ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $variation_product );

        echo '<div class="jtl-ean-variation-fields">';

        // GTIN field for variation
        woocommerce_wp_text_input(
            array(
                'id'            => "variable_gtin_{$loop}",
                'name'          => "variable_gtin[{$loop}]",
                'value'         => $jtl_product->get_gtin( 'edit' ),
                'label'         => __( 'GTIN / EAN', 'jtl-ean-plugin' ),
                'placeholder'   => '4250123456789',
                'description'   => __( 'GTIN for this variation', 'jtl-ean-plugin' ),
                'desc_tip'      => true,
                'wrapper_class' => 'form-row form-row-first',
            )
        );

        // MPN field for variation
        woocommerce_wp_text_input(
            array(
                'id'            => "variable_mpn_{$loop}",
                'name'          => "variable_mpn[{$loop}]",
                'value'         => $jtl_product->get_mpn( 'edit' ),
                'label'         => __( 'MPN', 'jtl-ean-plugin' ),
                'placeholder'   => 'ABC-123-XYZ',
                'description'   => __( 'MPN for this variation', 'jtl-ean-plugin' ),
                'desc_tip'      => true,
                'wrapper_class' => 'form-row form-row-last',
            )
        );

        echo '</div>';
    }

    /**
     * Save variable product fields
     * 
     * @param int $variation_id Variation ID
     * @param int $i Loop index
     */
    public function save_variable_fields( $variation_id, $i ) {
        // Security: Check user capability
        if ( ! current_user_can( 'edit_product', $variation_id ) ) {
            return;
        }

        $variation_product = wc_get_product( $variation_id );
        if ( ! $variation_product ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $variation_product );

        // Save variation GTIN with validation
        if ( isset( $_POST['variable_gtin'][ $i ] ) ) {
            $gtin = sanitize_text_field( wp_unslash( $_POST['variable_gtin'][ $i ] ) );
            $gtin = $this->validate_gtin( $gtin );
            $jtl_product->set_gtin( $gtin );
        }

        // Save variation MPN with validation
        if ( isset( $_POST['variable_mpn'][ $i ] ) ) {
            $mpn = sanitize_text_field( wp_unslash( $_POST['variable_mpn'][ $i ] ) );
            $mpn = $this->validate_mpn( $mpn );
            $jtl_product->set_mpn( $mpn );
        }

        $jtl_product->save();
    }

    /**
     * Output bulk edit fields
     */
    public function bulk_edit_fields() {
        ?>
        <div class="inline-edit-group">
            <label class="alignleft">
                <span class="title"><?php esc_html_e( 'GTIN / EAN', 'jtl-ean-plugin' ); ?></span>
                <span class="input-text-wrap">
                    <input type="text" name="_ts_gtin" class="text gtin" placeholder="<?php esc_attr_e( 'GTIN', 'jtl-ean-plugin' ); ?>" value="">
                </span>
            </label>
            <label class="alignright">
                <span class="title"><?php esc_html_e( 'MPN', 'jtl-ean-plugin' ); ?></span>
                <span class="input-text-wrap">
                    <input type="text" name="_ts_mpn" class="text mpn" placeholder="<?php esc_attr_e( 'MPN', 'jtl-ean-plugin' ); ?>" value="">
                </span>
            </label>
        </div>
        <?php
    }

    /**
     * Save bulk edit fields
     * 
     * @param WC_Product $product Product object
     */
    public function bulk_edit_save( $product ) {
        if ( ! $product instanceof WC_Product ) {
            return;
        }

        // Security: Check user capability
        if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        // Bulk save GTIN with validation
        if ( ! empty( $_REQUEST['_ts_gtin'] ) ) {
            $gtin = sanitize_text_field( wp_unslash( $_REQUEST['_ts_gtin'] ) );
            $gtin = $this->validate_gtin( $gtin );
            $jtl_product->set_gtin( $gtin );
        }

        // Bulk save MPN with validation
        if ( ! empty( $_REQUEST['_ts_mpn'] ) ) {
            $mpn = sanitize_text_field( wp_unslash( $_REQUEST['_ts_mpn'] ) );
            $mpn = $this->validate_mpn( $mpn );
            $jtl_product->set_mpn( $mpn );
        }
    }

    /**
     * Output quick edit fields
     */
    public function quick_edit_fields() {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <span class="title"><?php esc_html_e( 'GTIN / EAN', 'jtl-ean-plugin' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="_ts_gtin" class="text gtin" value="">
                    </span>
                </label>
                <label class="alignright">
                    <span class="title"><?php esc_html_e( 'MPN', 'jtl-ean-plugin' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="_ts_mpn" class="text mpn" value="">
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Add inline data for quick edit
     * 
     * @param WP_Post $post Post object
     * @param WC_Product $product Product object
     */
    public function quick_edit_data( $post, $product = null ) {
        if ( ! $product ) {
            $product = wc_get_product( $post->ID );
        }

        if ( ! $product ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        echo '<div class="hidden jtl_ean_inline_data" id="jtl_ean_inline_' . esc_attr( $product->get_id() ) . '">';
        echo '<div class="gtin">' . esc_html( $jtl_product->get_gtin( 'edit' ) ) . '</div>';
        echo '<div class="mpn">' . esc_html( $jtl_product->get_mpn( 'edit' ) ) . '</div>';
        echo '</div>';
    }

    /**
     * Save quick edit fields
     * 
     * @param WC_Product $product Product object
     */
    public function quick_edit_save( $product ) {
        if ( ! $product instanceof WC_Product ) {
            return;
        }

        // Security: Check user capability
        if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        // Quick save GTIN with validation
        if ( isset( $_REQUEST['_ts_gtin'] ) ) {
            $gtin = sanitize_text_field( wp_unslash( $_REQUEST['_ts_gtin'] ) );
            $gtin = $this->validate_gtin( $gtin );
            $jtl_product->set_gtin( $gtin );
        }

        // Quick save MPN with validation
        if ( isset( $_REQUEST['_ts_mpn'] ) ) {
            $mpn = sanitize_text_field( wp_unslash( $_REQUEST['_ts_mpn'] ) );
            $mpn = $this->validate_mpn( $mpn );
            $jtl_product->set_mpn( $mpn );
        }
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook_suffix Current admin page hook suffix
     */
    public function admin_scripts( $hook_suffix ) {
        // Only load on product pages
        if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
            return;
        }

        global $post_type;
        if ( 'product' !== $post_type ) {
            return;
        }

        // Inline CSS for admin styling
        ?>
        <style type="text/css">
            .jtl-ean-fields-header {
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            
            .jtl-ean-fields-header h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
                font-weight: 600;
            }
            
            .jtl-ean-fields-header .description {
                margin: 0;
                font-style: italic;
                color: #666;
            }
            
            .jtl-ean-variation-fields {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 10px;
                margin: 10px 0;
            }
            
            .jtl-ean-variation-fields .form-row {
                margin-bottom: 0;
            }
        </style>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Quick edit functionality
                $('body').on('click', '.editinline', function() {
                    var postId = $(this).closest('tr').attr('id').replace('post-', '');
                    var inlineData = $('#jtl_ean_inline_' + postId);
                    
                    if (inlineData.length) {
                        var gtin = inlineData.find('.gtin').text();
                        var mpn = inlineData.find('.mpn').text();
                        
                        $('.inline-edit-row input[name="_ts_gtin"]').val(gtin);
                        $('.inline-edit-row input[name="_ts_mpn"]').val(mpn);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Add admin menu for plugin settings
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'JTL EAN Settings', 'jtl-ean-plugin' ),
            __( 'JTL EAN', 'jtl-ean-plugin' ),
            'manage_woocommerce',
            'jtl-ean-settings',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Display admin settings page
     */
    public function admin_page() {
        // Get statistics
        $stats = $this->get_plugin_statistics();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'JTL EAN Plugin Settings', 'jtl-ean-plugin' ); ?></h1>
            
            <div class="notice notice-info">
                <p><?php esc_html_e( 'This plugin provides EAN/GTIN functionality for WooCommerce with JTL Connector compatibility.', 'jtl-ean-plugin' ); ?></p>
            </div>

            <div class="jtl-ean-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Statistics Card -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php esc_html_e( 'Plugin Statistics', 'jtl-ean-plugin' ); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Plugin Version:', 'jtl-ean-plugin' ); ?></strong></td>
                                    <td><?php echo esc_html( get_option( 'jtl_ean_plugin_version', '1.0.0' ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Products with GTIN:', 'jtl-ean-plugin' ); ?></strong></td>
                                    <td><?php echo esc_html( $stats['gtin_count'] ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Products with MPN:', 'jtl-ean-plugin' ); ?></strong></td>
                                    <td><?php echo esc_html( $stats['mpn_count'] ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Database Entries:', 'jtl-ean-plugin' ); ?></strong></td>
                                    <td><?php echo esc_html( $stats['total_meta_entries'] ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Uninstall Card -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle" style="color: #d63638;"><?php esc_html_e( 'Complete Uninstallation', 'jtl-ean-plugin' ); ?></h2>
                    </div>
                    <div class="inside">
                        <p><strong><?php esc_html_e( 'Warning:', 'jtl-ean-plugin' ); ?></strong> <?php esc_html_e( 'This will completely remove ALL EAN/GTIN data from your database!', 'jtl-ean-plugin' ); ?></p>
                        
                        <p><?php esc_html_e( 'This action will:', 'jtl-ean-plugin' ); ?></p>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li><?php esc_html_e( 'Delete all GTIN/EAN values from all products', 'jtl-ean-plugin' ); ?></li>
                            <li><?php esc_html_e( 'Delete all MPN values from all products', 'jtl-ean-plugin' ); ?></li>
                            <li><?php esc_html_e( 'Remove all plugin settings', 'jtl-ean-plugin' ); ?></li>
                            <li><?php esc_html_e( 'Deactivate the plugin', 'jtl-ean-plugin' ); ?></li>
                        </ul>

                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin: 15px 0;">
                            <strong><?php esc_html_e( 'Data Recovery:', 'jtl-ean-plugin' ); ?></strong><br>
                            <?php esc_html_e( 'After uninstallation, this data cannot be recovered unless you have a database backup!', 'jtl-ean-plugin' ); ?>
                        </div>

                        <form method="post" onsubmit="return confirm('<?php esc_attr_e( 'Are you absolutely sure you want to delete ALL EAN/GTIN data? This cannot be undone!', 'jtl-ean-plugin' ); ?>');">
                            <?php wp_nonce_field( 'jtl_ean_manual_uninstall', 'jtl_ean_nonce' ); ?>
                            <input type="hidden" name="jtl_ean_manual_uninstall" value="1">
                            
                            <label style="display: block; margin: 15px 0;">
                                <input type="checkbox" name="confirm_data_deletion" required>
                                <?php esc_html_e( 'I understand that this will permanently delete all EAN/GTIN data', 'jtl-ean-plugin' ); ?>
                            </label>
                            
                            <label style="display: block; margin: 15px 0;">
                                <input type="checkbox" name="confirm_no_backup" required>
                                <?php esc_html_e( 'I confirm that I have a database backup or accept the data loss', 'jtl-ean-plugin' ); ?>
                            </label>

                            <button type="submit" class="button button-primary" style="background: #d63638; border-color: #d63638;">
                                <?php esc_html_e( 'Completely Uninstall Plugin & Delete All Data', 'jtl-ean-plugin' ); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="postbox" style="margin-top: 20px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e( 'Export Data (Backup)', 'jtl-ean-plugin' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php esc_html_e( 'Before uninstalling, you can export your EAN/GTIN data for backup purposes.', 'jtl-ean-plugin' ); ?></p>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field( 'jtl_ean_export_data', 'jtl_ean_export_nonce' ); ?>
                        <input type="hidden" name="jtl_ean_export_data" value="1">
                        <button type="submit" class="button button-secondary">
                            <?php esc_html_e( 'Export EAN/GTIN Data (CSV)', 'jtl-ean-plugin' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get plugin statistics
     * 
     * @return array
     */
    private function get_plugin_statistics() {
        global $wpdb;

        $gtin_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ts_gtin' AND meta_value != ''"
        );

        $mpn_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ts_mpn' AND meta_value != ''"
        );

        $total_meta_entries = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_ts_gtin', '_ts_mpn')"
        );

        return array(
            'gtin_count'         => (int) $gtin_count,
            'mpn_count'          => (int) $mpn_count,
            'total_meta_entries' => (int) $total_meta_entries,
        );
    }

    /**
     * Handle manual uninstallation
     */
    public function handle_manual_uninstall() {
        // Handle data export
        if ( isset( $_POST['jtl_ean_export_data'] ) && check_admin_referer( 'jtl_ean_export_data', 'jtl_ean_export_nonce' ) ) {
            $this->export_ean_data();
            return;
        }

        // Handle manual uninstall
        if ( ! isset( $_POST['jtl_ean_manual_uninstall'] ) ) {
            return;
        }

        if ( ! check_admin_referer( 'jtl_ean_manual_uninstall', 'jtl_ean_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'jtl-ean-plugin' ) );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'jtl-ean-plugin' ) );
        }

        // Verify confirmations
        if ( ! isset( $_POST['confirm_data_deletion'] ) || ! isset( $_POST['confirm_no_backup'] ) ) {
            wp_die( esc_html__( 'Please confirm both checkboxes to proceed.', 'jtl-ean-plugin' ) );
        }

        // Perform the uninstallation
        $this->perform_manual_uninstall();
    }

    /**
     * Perform manual uninstallation
     */
    private function perform_manual_uninstall() {
        global $wpdb;

        // Get statistics before deletion
        $stats_before = $this->get_plugin_statistics();

        // 1. Remove plugin options
        delete_option( 'jtl_ean_plugin_version' );
        delete_option( 'jtl_ean_plugin_settings' );

        // 2. Remove all GTIN meta data
        $gtin_deleted = $wpdb->delete(
            $wpdb->postmeta,
            array( 'meta_key' => '_ts_gtin' ),
            array( '%s' )
        );

        // 3. Remove all MPN meta data
        $mpn_deleted = $wpdb->delete(
            $wpdb->postmeta,
            array( 'meta_key' => '_ts_mpn' ),
            array( '%s' )
        );

        // 4. Clear cache
        wp_cache_flush();

        // 5. Clean up transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_jtl_ean_%' 
             OR option_name LIKE '_transient_timeout_jtl_ean_%'"
        );

        // 6. Optimize tables
        $wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );
        $wpdb->query( "OPTIMIZE TABLE {$wpdb->options}" );

        // 7. Deactivate plugin
        deactivate_plugins( plugin_basename( JTL_EAN_PLUGIN_BASENAME ) );

        // 8. Show success message and redirect
        $message = sprintf(
            esc_html__( 'JTL EAN Plugin successfully uninstalled! Deleted: %d GTIN entries, %d MPN entries. Database cleaned and plugin deactivated.', 'jtl-ean-plugin' ),
            $gtin_deleted,
            $mpn_deleted
        );

        wp_redirect(
            add_query_arg(
                array(
                    'message' => urlencode( $message ),
                    'type'    => 'success'
                ),
                admin_url( 'plugins.php' )
            )
        );
        exit;
    }

    /**
     * Export EAN/GTIN data to CSV
     */
    private function export_ean_data() {
        global $wpdb;

        // Get all EAN/GTIN data
        $results = $wpdb->get_results(
            "SELECT 
                p.ID as product_id,
                p.post_title as product_name,
                p.post_type as product_type,
                gtin.meta_value as gtin,
                mpn.meta_value as mpn
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} gtin ON p.ID = gtin.post_id AND gtin.meta_key = '_ts_gtin'
             LEFT JOIN {$wpdb->postmeta} mpn ON p.ID = mpn.post_id AND mpn.meta_key = '_ts_mpn'
             WHERE p.post_type IN ('product', 'product_variation')
             AND (gtin.meta_value IS NOT NULL OR mpn.meta_value IS NOT NULL)
             ORDER BY p.ID",
            ARRAY_A
        );

        if ( empty( $results ) ) {
            wp_die( esc_html__( 'No EAN/GTIN data found to export.', 'jtl-ean-plugin' ) );
        }

        // Set headers for CSV download
        $filename = 'jtl-ean-export-' . date( 'Y-m-d-H-i-s' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Create file pointer
        $output = fopen( 'php://output', 'w' );

        // Add CSV header
        fputcsv( $output, array(
            'Product ID',
            'Product Name',
            'Product Type',
            'GTIN/EAN',
            'MPN',
            'Export Date'
        ) );

        // Add data rows
        foreach ( $results as $row ) {
            fputcsv( $output, array(
                $row['product_id'],
                $row['product_name'],
                $row['product_type'],
                $row['gtin'] ?? '',
                $row['mpn'] ?? '',
                current_time( 'Y-m-d H:i:s' )
            ) );
        }

        fclose( $output );
        exit;
    }

    /**
     * Validate GTIN format
     * 
     * @param string $gtin GTIN to validate
     * @return string Validated GTIN
     */
    private function validate_gtin( $gtin ) {
        // Remove all non-numeric characters
        $gtin = preg_replace( '/[^0-9]/', '', $gtin );
        
        // GTIN can be 8, 12, 13, or 14 digits
        $valid_lengths = array( 8, 12, 13, 14 );
        
        if ( ! in_array( strlen( $gtin ), $valid_lengths, true ) ) {
            // If invalid length, return empty (or could log error)
            return '';
        }
        
        return $gtin;
    }

    /**
     * Validate MPN format
     * 
     * @param string $mpn MPN to validate
     * @return string Validated MPN
     */
    private function validate_mpn( $mpn ) {
        // Remove dangerous characters, allow alphanumeric and common symbols
        $mpn = preg_replace( '/[^a-zA-Z0-9\-_.]/', '', $mpn );
        
        // Limit length to prevent abuse
        if ( strlen( $mpn ) > 50 ) {
            $mpn = substr( $mpn, 0, 50 );
        }
        
        return $mpn;
    }
}