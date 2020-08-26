<?php


namespace ExtendedWoo\ExtensionAPI\import;

use Symfony\Component\HttpFoundation\Request;
use wpdb;

class ProductImporter
{
    private wpdb $db;
    private Request $request;

    public function __construct(Request $request, wpdb  $db)
    {
        if (! $this->checkUserAccess()) {
            return;
        }

        $this->request = $request;
        $this->db = $db;
    }

    public function ajaxProductImport(): void
    {
        $db = $this->db;
        $request = $this->request;
        check_ajax_referer('extended-product-import', 'security');

        if (! $this->checkUserAccess() || empty($request->get('file'))) {
            wp_send_json_error(array( 'message' => __('Insufficient privileges to import products.', 'woocommerce') ));
        }
    }

    private function checkUserAccess(): bool
    {
        return current_user_can('edit_products') && current_user_can('import');
    }
}
