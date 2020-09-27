<?php


namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\models\ProblemResolver;
use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;
use Symfony\Component\HttpFoundation\Request;
use WP_Error;

class ProductImporterController extends BasicController
{
    private const IMPORT_NONCE = 'etx-xls-importer';
    private ImportType $importStrategy;
    private string $step;
    private array $steps;
    private string $import_views_path;
    private string $file;
    private int $startRow = 1;

    public function __construct(Request $request)
    {
        wp_deregister_script('wc-product-import');

        $this->request           = $request;
        $this->import_views_path = __DIR__ . '/../../views/import/';
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
            'validation' => [
                'name'    => __("Обработка товаров", 'extendedwoo'),
                'view'    => [$this, 'showResolverForm'],
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
        $this->steps
                                 = apply_filters(
                                     'woocommerce_product_csv_importer_steps',
                                     $default_steps
                                 );
        $this->step              = ( ! empty($request->get('step'))) ?
            sanitize_key($request->get('step'))
            : current(array_keys($this->steps));
        $this->file              = ( ! empty($request->get('file')))
            ? wc_clean(wp_unslash($request->get('file'))) : '';
    }

    public function setImportType(ImportType $import_type): self
    {
        $this->importStrategy = $import_type;
        return $this;
    }

    public function getNextStepLink(string $step = ''): string
    {
        if (! $step) {
            $step = $this->step;
        }

        $keys = array_keys($this->steps);
        if (end($keys) === $step) {
            return admin_url();
        }

        $step_index = array_search($step, $keys, true);

        if ($step_index === false) {
            return '';
        }

        $params = [
            'step'            => $keys[$step_index + 1],
            'file'            => str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $this->file
            ),
            'start_row' => $this->startRow,
            '_wpnonce'        => wp_create_nonce('etx-xls-importer'),
        ];
        return add_query_arg($params);
    }

    public function uploadFormHandler(): void
    {
        $file = $this->handleUpload();
        $request = $this->request;
        if (is_wp_error($file)) {
            $this->addErrors($file->get_error_message());
            return;
        }

        $this->file = $file;

        wp_redirect(esc_url_raw($this->getNextStepLink()));
    }

    public function uploadImportHandler(): void
    {
        $file = $this->handleUpload();
        if (is_wp_error($file)) {
            $this->addErrors($file->get_error_message());

            return;
        }

        $this->file = $file;
        wp_redirect(esc_url_raw($this->getNextStepLink()));
    }

    public function handleUpload()
    {
        $request  = $this->request;
        $file_url = ( ! empty($request->get('file_url')))
            ? wc_clean(wp_unslash($request->get('file_url'))) : '';
        $this->startRow = (! empty($request->get('starting_row')))
            ? wc_clean(wp_unslash($request->get('starting_row'))) : 1;

        if (empty($file_url) && ! isset($_FILES['import'])) {
            return new WP_Error(
                'extendedwoo_product_xls_importer_upload_file_empty',
                __(
                    'File is empty. Please upload something more substantial.
                 This error could also be caused by uploads being disabled in
                  your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.',
                    'woocommerce'
                )
            );
        }

        if (empty($file_url)) {
            $import = $_FILES['import'];
            $upload = wp_handle_upload($import, [
                'test_form' => false,
                'mimes'     => [
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'xls'  => 'application/vnd.ms-excel',
                ],
            ]);

            if (isset($upload['error'])) {
                return new WP_Error(
                    'woocommerce_product_csv_importer_upload_error',
                    $upload['error']
                );
            }

            $object = [
                'post_title'     => basename($upload['file']),
                'post_content'   => $upload['url'],
                'post_mime_type' => $upload['type'],
                'guid'           => $upload['url'],
                'context'        => 'import',
                'post_status'    => 'private',
            ];

            $id = wp_insert_attachment($object, $upload['file']);
            wp_schedule_single_event(
                time() + DAY_IN_SECONDS,
                'importer_scheduled_cleanup',
                array($id)
            );
            $f_size = (new ProductExcelImporter($upload['file']))
            ->getImportSize();
            if ($f_size <= 1) {
                return new WP_Error(
                    'extendedwoo_product_xls_importer_upload_invalid_file',
                    __(
                        'The file is empty or using a different encoding than UTF-8, please try again with a new file.',
                        'woocommerce'
                    )
                );
            }

            return $upload['file'];
        }

        if (file_exists(ABSPATH . $file_url)) {
            return ABSPATH . $file_url;
        }

        return new WP_Error(
            'extendedwoo_product_xls_importer_upload_invalid_file',
            __('Пожалуйста, загрузите валидный файл .xls', 'extendedwoo')
        );
    }

    /**
     * Import steps dispatcher.
     */
    public function dispatch(): void
    {
        $request = $this->request;
        global $wpdb;
        if (! empty($request->get('remove_step'))) {
            if (is_file($this->file)) {
                $last_file_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' ORDER BY ID DESC LIMIT 1");
                wp_delete_attachment($last_file_id);
            }
            wp_redirect(esc_url_raw('admin.php?page=excel_import'));
        }

        if (! empty($this->steps[$this->step]['handler'])
             &&
             ! empty($request->get('save_step'))
        ) {
            call_user_func($this->steps[$this->step]['handler'], $this);
        }

        $this
            ->showHeader()
            ->showSteps()
            ->showErrors();
        call_user_func($this->steps[$this->step]['view'], $this);
        $this->showFooter();
    }

    /**
     * Display header view.
     *
     * @return $this
     */
    private function showHeader(): self
    {
        include $this->import_views_path . 'import-header.php';

        return $this;
    }


    private function showFooter(): self
    {
        include $this->import_views_path . 'import-footer.php';

        return $this;
    }
    
    /**
     * Display steps view.
     *
     * @return $this
     */
    private function showSteps(): self
    {
        include $this->import_views_path . 'import-steps.php';

        return $this;
    }

    private function showImportForm(): self
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

    private function showMappingForm(): void
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

    private function showResolverForm(): void
    {
        if (! is_file($this->file)) {
            $this->addErrors(__(
                'The file does not exist, please try again.',
                'woocommerce'
            ));
            $this->showErrors();
        }

        $req = $this->request;
        $labels = array_values($this->importStrategy->getColumns());
        $mapping_to = $req->get('map_to');

        if (empty($mapping_to)) {
            wp_redirect(esc_url_raw($this->getNextStepLink('upload')));
        }

        update_user_option(get_current_user_id(), 'woocommerce_product_import_mapping', $mapping_to);
        $importer = new ProductExcelImporter($this->file, []);
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
        wp_localize_script(
            'ewoo-product-import',
            'ewoo_product_import_params',
            [
                'import_nonce' => wp_create_nonce('ewoo-product-import'),
                'mapping' => [
                    'from' => $labels,
                    'to' => $mapping_to,
                ],
                'file' => $this->file,
            ]
        );

        if (! empty($mapping_to) && ! empty($importer->getColumns())) {
            $resolver = new ProblemResolver(
                $this->importStrategy,
                $importer->getColumns(),
                $mapping_to
            );
        } else {
            wp_redirect(esc_url_raw($this->getNextStepLink('upload')));
            return;
        }

        include_once $this->import_views_path . '/import-problem-resolver.php';
    }

    private function showImport(): void
    {
        if (! is_file($this->file)) {
            $this->addErrors(__('The file does not exist, please try again.', 'woocommerce'));
            $this->showErrors();
        }

        $req = $this->request;
        $fixed_sku = $req->get('sku')?? [];
        $fixed_cat_ids = $req->get('category') ?? [];
        if (!empty($req->get('map_from')) && ! empty($req->get('map_to'))) {
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
                'fixes' => [
                  'sku'  => $fixed_sku,
                  'categories'  => $fixed_cat_ids,
                ],
                'file' => $this->file,
            ]
        );

        wp_enqueue_script('ewoo-product-import');
        include_once $this->import_views_path . '/import-progress.php';
    }
    
    private function showDone(): self
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
