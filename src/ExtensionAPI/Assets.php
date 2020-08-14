<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Kernel;

class Assets
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array( $this, 'adminStyles'));
        add_action('admin_enqueue_scripts', array( $this,'adminScripts'));
    }

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
    public function adminScripts()
    {
        global $wp_query, $post;
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $locale  = localeconv();
        $decimal = isset($locale['decimal_point']) ? $locale['decimal_point'] : '.';
        wp_register_script('ewoo-product-export', Kernel::pluginUrl() . '/assets/js/admin/product-export.js', array( 'jquery' ));

        if (in_array($screen_id, wc_get_screen_ids(), true)) {
            $params = array(
                /* translators: %s: decimal */
                'i18n_decimal_error'                => sprintf(__('Please enter with one decimal point (%s) without thousand separators.', 'woocommerce'), $decimal),
                /* translators: %s: price decimal separator */
                'i18n_mon_decimal_error'            => sprintf(__('Please enter with one monetary decimal point (%s) without thousand separators and currency symbols.', 'woocommerce'), wc_get_price_decimal_separator()),
                'i18n_country_iso_error'            => __('Please enter in country code with two capital letters.', 'woocommerce'),
                'i18n_sale_less_than_regular_error' => __('Please enter in a value less than the regular price.', 'woocommerce'),
                'i18n_delete_product_notice'        => __('This product has produced sales and may be linked to existing orders. Are you sure you want to delete it?', 'woocommerce'),
                'i18n_remove_personal_data_notice'  => __('This action cannot be reversed. Are you sure you wish to erase personal data from the selected orders?', 'woocommerce'),
                'decimal_point'                     => $decimal,
                'mon_decimal_point'                 => wc_get_price_decimal_separator(),
                'ajax_url'                          => admin_url('admin-ajax.php'),
                'strings'                           => array(
                    'import_products' => __('Import', 'woocommerce'),
//                    'export_products' => __( 'Export', 'woocommerce' ),
                    'export_products' => 'Test '.__('Export', 'woocommerce'),
                ),
                'nonces'                            => array(
                    'gateway_toggle' => wp_create_nonce('woocommerce-toggle-payment-gateway-enabled'),
                ),
                'urls'                              => array(
                    'import_products' => current_user_can('import') ? esc_url_raw(admin_url('edit.php?post_type=product&page=product_importer')) : null,
                    'export_products' => current_user_can('export') ? esc_url_raw(admin_url('edit.php?post_type=product&page=product_excel_exporter')) : null,
                ),
            );
            wp_localize_script('woocommerce_admin', 'woocommerce_admin', $params);
        }
    }
}
