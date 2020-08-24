<?php

namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Kernel;

class Assets
{
    /**
     * Enqueue styles.
     */
    public function adminStyles(): void
    {
        wp_register_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css');
        wp_enqueue_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css');
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
            Kernel::pluginUrl() . '/assets/js/admin/product-import.js',
            array( 'jquery' )
        );
    }
}
