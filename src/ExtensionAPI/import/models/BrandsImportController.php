<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import\models;

use ExtendedWoo\ExtensionAPI\interfaces\export\import\PriceImportController;

class BrandsImportController extends PriceImportController
{
    protected function showImport(): void
    {
        if (! is_file($this->file)) {
            $this->addErrors(__('The file does not exist, please try again.', 'woocommerce'));
            $this->showErrors();
        }

        $req = $this->request;

        $labels = array_values($this->importStrategy->getColumns());
        $mapping_to = $req->get('map_to');

        if (!empty($labels) && ! empty($req->get('map_to'))) {
            $mapping_from = $req->get('map_from');
            $mapping_to = $req->get('map_to');
            // Save mapping preferences for future imports.
            update_user_option(get_current_user_id(), 'woocommerce_product_import_mapping', $mapping_to);
        } else {
            wp_redirect(esc_url_raw($this->getNextStepLink('upload')));
            exit;
        }

        wp_localize_script(
            'ewoo-product-import',
            'ewoo_product_import_params',
            [
                'import_nonce' => wp_create_nonce('ewoo-product-import'),
                'mapping' => [
                    'from' => $mapping_from,
                    'to' => $mapping_to,
                ],
                'import_type' => 'upload_brands',
                'file' => $this->file,
            ]
        );

        wp_enqueue_script('ewoo-product-import');

        include_once $this->import_views_path . '/import-progress.php';
    }
}