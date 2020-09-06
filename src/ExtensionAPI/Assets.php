<?php

namespace ExtendedWoo\ExtensionAPI;

use Exception;
use ExtendedWoo\Kernel;

class Assets
{
    /**
     * Enqueue styles.
     */
    public function adminStyles(): void
    {
        $ver = rand(1, 10000);
        wp_register_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css', '', $ver);
        wp_enqueue_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css', '', $ver);
        wp_enqueue_style('woocommerce_admin_styles');
    }

    /**
     * Enqueue scripts.
     */
    public function adminScripts(): void
    {
        wp_register_script(
            'ewoo-product-export',
            Kernel::pluginUrl() . '/assets/js/admin/product-export.js',
            array( 'jquery' )
        );
        wp_register_script(
            'ewoo-product-import',
            Kernel::pluginUrl() . '/assets/js/admin/product-import.js?ver=100.1',
            array( 'jquery' ),
            '15'
        );
    }
}
