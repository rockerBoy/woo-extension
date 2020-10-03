<?php


namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use Symfony\Component\HttpFoundation\Request;

class PriceImportController extends BasicController
{
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $default_steps           = [
            'upload'  => [
                'name'    => __("Загрузить Excel файл", 'extendedwoo'),
                'view'    => [$this, 'showImportForm'],
                'handler' => [$this, 'uploadFormHandler'],
            ],
            'mapping' => [
                'name'    => __("Назначить XLS поля товарам", 'extendedwoo'),
                'view'    => [$this, 'showMappingForm'],
                'handler' => '',
            ],
            'import'  => [
                'name'    => __("Import", 'woocommerce'),
                'view'    => [$this, 'showImport'],
                'handler' => '',
            ],
            'result'  => [
                'name'    => __('Done!', 'woocommerce'),
                'view'    => [$this, 'showDone'],
                'handler' => '',
            ],
        ];
        $this->steps             = apply_filters(
            'woocommerce_product_csv_importer_steps',
            $default_steps
        );
        $this->step              = ( ! empty($request->get('step'))) ?
            sanitize_key($request->get('step'))
            : current(array_keys($this->steps));
        $this->file              = ( ! empty($request->get('file')))
            ? wc_clean(wp_unslash($request->get('file'))) : '';
    }

    protected function showImportForm(): self
    {
        $bytes      = apply_filters(
            'import_upload_size_limit',
            wp_max_upload_size()
        );
        $size       = size_format($bytes);
        $upload_dir = wp_upload_dir();
        include $this->import_views_path . 'import-form.php';

        return $this;
    }

    protected function showMappingForm(): void
    {
        $request = $this->request;
        $this->startRow = $_GET['start_row'];
        $importer = (new ProductExcelImporter($this->file, ['start_from' => $this->startRow]));
        $headers  = $importer->getHeader($this->startRow);
        $labels = array_values($this->importStrategy->getColumns());
        $mapped_items = ProductsImportHelper::autoMapColumns($labels);
        wp_localize_script(
            'ewoo-product-validation',
            'ewoo_product_import_params',
            [
                'import_nonce' => wp_create_nonce('ewoo-product-import'),
                'mapping' => [
                    'required' => array_keys($this->importStrategy->getColumns())
                ],
                'file' => $this->file,
            ]
        );
        $sample = $importer->getSample();
        if (empty($sample)) {
            $this->addErrors(
                __('The file is empty or using a different encoding than UTF-8, please try again with a new file.', 'woocommerce'),
                array(
                    array(
                        'url'   => admin_url('edit.php?post_type=product&page=product_excel_importer'),
                        'label' => __('Upload a new file', 'woocommerce'),
                    ),
                )
            );
            $this->showErrors();
        }

        include_once $this->import_views_path . '/import-column-mapping.php';
    }

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
                'import_type' => 'update_prices',
                'file' => $this->file,
            ]
        );

        wp_enqueue_script('ewoo-product-import');

        include_once $this->import_views_path . '/import-progress.php';
    }

    protected function showDone(): self
    {
        $imported  = isset($_GET['products-imported']) ? absint($_GET['products-imported']) : 0;
        $updated   = isset($_GET['products-updated']) ? absint($_GET['products-updated']) : 0;
        $failed    = isset($_GET['products-failed']) ? absint($_GET['products-failed']) : 0;
        $skipped   = isset($_GET['products-skipped']) ? absint($_GET['products-skipped']) : 0;
        $file_name = isset($_GET['file-name']) ? sanitize_text_field(wp_unslash($_GET['file-name'])) : '';
        $errors    = array_filter((array) get_user_option('product_import_error_log'));

        include $this->import_views_path . 'import-done.php';

        return $this;
    }
}