<?php
/**
 * JTL EAN API Class
 * 
 * Handles REST API extensions for JTL Connector compatibility
 *
 * @package JTL_EAN_Plugin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * JTL EAN API class
 */
class JTL_EAN_API {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize API hooks
     */
    private function init_hooks() {
        // REST API hooks for products
        add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prepare_product' ), 10, 3 );
        add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'prepare_product' ), 10, 3 );

        // REST API hooks for inserting/updating products
        add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'insert_update_product' ), 10, 3 );
        add_filter( 'woocommerce_rest_pre_insert_product_variation_object', array( $this, 'insert_update_product' ), 10, 3 );

        // REST API schema extensions
        add_filter( 'woocommerce_rest_product_schema', array( $this, 'extend_product_schema' ) );
        add_filter( 'woocommerce_rest_product_variation_schema', array( $this, 'extend_variation_schema' ) );
    }

    /**
     * Prepare product data for REST API response
     * 
     * @param WP_REST_Response $response Response object
     * @param WC_Product $product Product object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function prepare_product( $response, $product, $request ) {
        if ( ! $product instanceof WC_Product ) {
            return $response;
        }

        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $jtl_product = new JTL_EAN_Product( $product );

        $data = $response->get_data();

        // Add GTIN to response
        $data['gtin'] = $jtl_product->get_gtin( $context );

        // Add MPN to response
        $data['mpn'] = $jtl_product->get_mpn( $context );

        $response->set_data( $data );

        return $response;
    }

    /**
     * Handle product insertion/update via REST API
     * 
     * @param WC_Product $product Product object
     * @param WP_REST_Request $request Request object
     * @param bool $creating Whether creating or updating
     * @return WC_Product
     */
    public function insert_update_product( $product, $request, $creating ) {
        if ( ! $product instanceof WC_Product ) {
            return $product;
        }

        // Security: Check user capability
        if ( ! current_user_can( 'edit_products' ) ) {
            return $product;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        // Update GTIN if provided with validation
        if ( isset( $request['gtin'] ) ) {
            $gtin = sanitize_text_field( $request['gtin'] );
            $gtin = $this->validate_gtin( $gtin );
            $jtl_product->set_gtin( $gtin );
        }

        // Update MPN if provided with validation
        if ( isset( $request['mpn'] ) ) {
            $mpn = sanitize_text_field( $request['mpn'] );
            $mpn = $this->validate_mpn( $mpn );
            $jtl_product->set_mpn( $mpn );
        }

        return $product;
    }

    /**
     * Extend product schema for REST API
     * 
     * @param array $schema Product schema
     * @return array
     */
    public function extend_product_schema( $schema ) {
        // Add GTIN to schema
        $schema['properties']['gtin'] = array(
            'description' => __( 'Global Trade Item Number (GTIN)', 'jtl-ean-plugin' ),
            'type'        => 'string',
            'context'     => array( 'view', 'edit' ),
            'default'     => '',
        );

        // Add MPN to schema
        $schema['properties']['mpn'] = array(
            'description' => __( 'Manufacturer Part Number (MPN)', 'jtl-ean-plugin' ),
            'type'        => 'string',
            'context'     => array( 'view', 'edit' ),
            'default'     => '',
        );

        return $schema;
    }

    /**
     * Extend product variation schema for REST API
     * 
     * @param array $schema Variation schema
     * @return array
     */
    public function extend_variation_schema( $schema ) {
        // Add GTIN to variation schema
        if ( isset( $schema['properties'] ) ) {
            $schema['properties']['gtin'] = array(
                'description' => __( 'Global Trade Item Number (GTIN)', 'jtl-ean-plugin' ),
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'default'     => '',
            );

            $schema['properties']['mpn'] = array(
                'description' => __( 'Manufacturer Part Number (MPN)', 'jtl-ean-plugin' ),
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
                'default'     => '',
            );
        }

        return $schema;
    }

    /**
     * Get product data for JTL Connector (legacy support)
     * This method provides compatibility with older JTL Connector versions
     * 
     * @param WC_Product $product Product object
     * @return array
     */
    public function get_jtl_product_data( $product ) {
        if ( ! $product instanceof WC_Product ) {
            return array();
        }

        $jtl_product = new JTL_EAN_Product( $product );

        return array(
            'gtin' => $jtl_product->get_gtin(),
            'mpn'  => $jtl_product->get_mpn(),
            'ean'  => $jtl_product->get_gtin(), // EAN alias for GTIN
        );
    }

    /**
     * Set product data from JTL Connector (legacy support)
     * 
     * @param WC_Product $product Product object
     * @param array $data JTL data
     */
    public function set_jtl_product_data( $product, $data ) {
        if ( ! $product instanceof WC_Product || ! is_array( $data ) ) {
            return;
        }

        $jtl_product = new JTL_EAN_Product( $product );

        // Set GTIN (prefer 'gtin', fallback to 'ean')
        if ( isset( $data['gtin'] ) && ! empty( $data['gtin'] ) ) {
            $jtl_product->set_gtin( $data['gtin'] );
        } elseif ( isset( $data['ean'] ) && ! empty( $data['ean'] ) ) {
            $jtl_product->set_gtin( $data['ean'] );
        }

        // Set MPN
        if ( isset( $data['mpn'] ) && ! empty( $data['mpn'] ) ) {
            $jtl_product->set_mpn( $data['mpn'] );
        }

        $jtl_product->save();
    }

    /**
     * Register custom REST API endpoints for JTL Connector
     */
    public function register_custom_endpoints() {
        // Custom endpoint for bulk GTIN updates
        register_rest_route(
            'jtl-ean/v1',
            '/products/(?P<id>\d+)/gtin',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_product_gtin' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'id' => array(
                        'description' => __( 'Product ID', 'jtl-ean-plugin' ),
                        'type'        => 'integer',
                        'required'    => true,
                    ),
                    'gtin' => array(
                        'description' => __( 'GTIN value', 'jtl-ean-plugin' ),
                        'type'        => 'string',
                        'required'    => true,
                    ),
                ),
            )
        );

        // Custom endpoint for bulk MPN updates
        register_rest_route(
            'jtl-ean/v1',
            '/products/(?P<id>\d+)/mpn',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_product_mpn' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'id' => array(
                        'description' => __( 'Product ID', 'jtl-ean-plugin' ),
                        'type'        => 'integer',
                        'required'    => true,
                    ),
                    'mpn' => array(
                        'description' => __( 'MPN value', 'jtl-ean-plugin' ),
                        'type'        => 'string',
                        'required'    => true,
                    ),
                ),
            )
        );
    }

    /**
     * Update product GTIN via custom endpoint
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function update_product_gtin( $request ) {
        $product_id = (int) $request['id'];
        $gtin = sanitize_text_field( $request['gtin'] );

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'product_not_found', __( 'Product not found.', 'jtl-ean-plugin' ), array( 'status' => 404 ) );
        }

        $jtl_product = new JTL_EAN_Product( $product );
        $jtl_product->set_gtin( $gtin );
        $jtl_product->save();

        return rest_ensure_response(
            array(
                'id'   => $product_id,
                'gtin' => $jtl_product->get_gtin(),
            )
        );
    }

    /**
     * Update product MPN via custom endpoint
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function update_product_mpn( $request ) {
        $product_id = (int) $request['id'];
        $mpn = sanitize_text_field( $request['mpn'] );

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'product_not_found', __( 'Product not found.', 'jtl-ean-plugin' ), array( 'status' => 404 ) );
        }

        $jtl_product = new JTL_EAN_Product( $product );
        $jtl_product->set_mpn( $mpn );
        $jtl_product->save();

        return rest_ensure_response(
            array(
                'id'  => $product_id,
                'mpn' => $jtl_product->get_mpn(),
            )
        );
    }

    /**
     * Check REST API permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_permission( $request ) {
        return current_user_can( 'edit_products' );
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
            // If invalid length, return empty
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