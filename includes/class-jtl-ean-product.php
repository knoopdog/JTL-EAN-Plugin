<?php
/**
 * JTL EAN Product Class
 * 
 * Handles EAN/GTIN functionality for WooCommerce products
 * Based on WooCommerce Germanized approach but simplified
 *
 * @package JTL_EAN_Plugin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * JTL EAN Product class
 */
class JTL_EAN_Product {

    /**
     * The WooCommerce product object
     * @var WC_Product
     */
    protected $product;

    /**
     * Constructor
     * 
     * @param WC_Product|int $product Product object or ID
     */
    public function __construct( $product ) {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }
        
        $this->product = $product;
    }

    /**
     * Get the WooCommerce product object
     * 
     * @return WC_Product
     */
    public function get_wc_product() {
        return $this->product;
    }

    /**
     * Get product ID
     * 
     * @return int
     */
    public function get_id() {
        return $this->product ? $this->product->get_id() : 0;
    }

    /**
     * Get GTIN/EAN value
     * 
     * @param string $context View or edit context
     * @return string
     */
    public function get_gtin( $context = 'view' ) {
        if ( ! $this->product ) {
            return '';
        }

        // Get GTIN from our custom meta field
        $gtin = $this->get_meta( '_ts_gtin', $context );

        // Fallback to WooCommerce Core GTIN if available and our field is empty
        if ( 'view' === $context && empty( $gtin ) && is_callable( array( $this->product, 'get_global_unique_id' ) ) ) {
            $wc_core_gtin = $this->product->get_global_unique_id();
            if ( ! empty( $wc_core_gtin ) ) {
                $gtin = $wc_core_gtin;
            }
        }

        /**
         * Filter the product GTIN
         * 
         * @param string $gtin The GTIN value
         * @param JTL_EAN_Product $jtl_product This product instance
         * @param WC_Product $product The WooCommerce product object
         * @param string $context The context (view or edit)
         */
        return apply_filters( 'jtl_ean_product_get_gtin', $gtin, $this, $this->product, $context );
    }

    /**
     * Set GTIN/EAN value
     * 
     * @param string $gtin The GTIN value
     */
    public function set_gtin( $gtin ) {
        if ( ! $this->product ) {
            return;
        }

        // Enhanced sanitization and validation
        $gtin = sanitize_text_field( $gtin );
        $gtin = $this->validate_gtin( $gtin );
        
        $this->set_meta( '_ts_gtin', $gtin );
    }

    /**
     * Check if product has GTIN
     * 
     * @return bool
     */
    public function has_gtin() {
        return ! empty( $this->get_gtin() );
    }

    /**
     * Get MPN (Manufacturer Part Number) value
     * Included for compatibility with Germanized
     * 
     * @param string $context View or edit context
     * @return string
     */
    public function get_mpn( $context = 'view' ) {
        if ( ! $this->product ) {
            return '';
        }

        return $this->get_meta( '_ts_mpn', $context );
    }

    /**
     * Set MPN (Manufacturer Part Number) value
     * 
     * @param string $mpn The MPN value
     */
    public function set_mpn( $mpn ) {
        if ( ! $this->product ) {
            return;
        }

        // Enhanced sanitization and validation
        $mpn = sanitize_text_field( $mpn );
        $mpn = $this->validate_mpn( $mpn );
        
        $this->set_meta( '_ts_mpn', $mpn );
    }

    /**
     * Get meta data from product
     * 
     * @param string $key Meta key
     * @param string $context View or edit context
     * @return mixed
     */
    protected function get_meta( $key, $context = 'view' ) {
        if ( ! $this->product ) {
            return '';
        }

        return $this->product->get_meta( $key, true, $context );
    }

    /**
     * Set meta data on product
     * 
     * @param string $key Meta key
     * @param mixed $value Meta value
     */
    protected function set_meta( $key, $value ) {
        if ( ! $this->product ) {
            return;
        }

        $this->product->update_meta_data( $key, $value );
    }

    /**
     * Save product data
     */
    public function save() {
        if ( $this->product ) {
            $this->product->save();
        }
    }

    /**
     * Check if this is a variation product
     * 
     * @return bool
     */
    public function is_variation() {
        return $this->product && $this->product->is_type( 'variation' );
    }

    /**
     * Check if this is a variable product
     * 
     * @return bool
     */
    public function is_variable() {
        return $this->product && $this->product->is_type( 'variable' );
    }

    /**
     * Get parent product for variations
     * 
     * @return JTL_EAN_Product|null
     */
    public function get_parent() {
        if ( ! $this->is_variation() ) {
            return null;
        }

        $parent_id = $this->product->get_parent_id();
        if ( $parent_id ) {
            return new self( $parent_id );
        }

        return null;
    }

    /**
     * Get variations for variable products
     * 
     * @return JTL_EAN_Product[]
     */
    public function get_variations() {
        if ( ! $this->is_variable() ) {
            return array();
        }

        $variations = array();
        $variation_ids = $this->product->get_children();

        foreach ( $variation_ids as $variation_id ) {
            $variations[] = new self( $variation_id );
        }

        return $variations;
    }

    /**
     * Magic method to proxy calls to the WooCommerce product object
     * 
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed
     */
    public function __call( $method, $args ) {
        if ( $this->product && method_exists( $this->product, $method ) ) {
            return call_user_func_array( array( $this->product, $method ), $args );
        }

        trigger_error( "Call to undefined method JTL_EAN_Product::{$method}()", E_USER_ERROR );
    }

    /**
     * Validate GTIN format
     * 
     * @param string $gtin GTIN to validate
     * @return string Validated GTIN
     */
    private function validate_gtin( $gtin ) {
        if ( empty( $gtin ) ) {
            return '';
        }

        // Remove all non-numeric characters
        $gtin = preg_replace( '/[^0-9]/', '', $gtin );
        
        // GTIN can be 8, 12, 13, or 14 digits
        $valid_lengths = array( 8, 12, 13, 14 );
        
        if ( ! in_array( strlen( $gtin ), $valid_lengths, true ) ) {
            // Log validation error in debug mode
            if ( WP_DEBUG ) {
                error_log( "JTL EAN Plugin: Invalid GTIN length for product {$this->get_id()}: {$gtin}" );
            }
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
        if ( empty( $mpn ) ) {
            return '';
        }

        // Remove potentially dangerous characters, allow alphanumeric and common symbols
        $mpn = preg_replace( '/[^a-zA-Z0-9\-_.]/', '', $mpn );
        
        // Limit length to prevent database abuse
        if ( strlen( $mpn ) > 50 ) {
            $mpn = substr( $mpn, 0, 50 );
            
            // Log truncation in debug mode
            if ( WP_DEBUG ) {
                error_log( "JTL EAN Plugin: MPN truncated for product {$this->get_id()}: {$mpn}" );
            }
        }
        
        return $mpn;
    }
}