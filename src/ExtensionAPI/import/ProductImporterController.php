<?php


namespace ExtendedWoo\ExtensionAPI\import;

use Symfony\Component\HttpFoundation\Request;
use WP_Error;

class ProductImporterController
{
    private const IMPORT_NONCE = 'etx-xls-importer';
    private Request $request;
    private string $step;
    private array $steps;
    private array $errors;
    private bool $update_existing = true;
    private string $import_views_path;
    private string $file;
    private int $startRow = 1;

    public function __construct(Request $request)
    {
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
            'update_existing' => $this->update_existing,
            '_wpnonce'        => wp_create_nonce('etx-xls-importer'),
        ];
        return add_query_arg($params);
    }

    public function uploadFormHandler(): void
    {
        check_admin_referer(self::IMPORT_NONCE);
        $file = $this->handleUpload();

        if (is_wp_error($file)) {
            $this->addErrors($file->get_error_message());
            return;
        } else {
            $this->file = $file;
        }

        wp_redirect(esc_url_raw($this->getNextStepLink()));
    }

    public function uploadImportHandler(): void
    {
        check_admin_referer(self::IMPORT_NONCE);
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
                    __('The file is empty or using a different encoding than UTF-8, please try again with a new file.', 'woocommerce')
                );
            }

            return $upload['file'];
        } elseif (file_exists(ABSPATH . $file_url)) {
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
     * Add error message.
     *
     * @param string $message Error message.
     * @param array  $actions List of actions with 'url' and label.
     */
    private function addErrors(string $message, array $actions = []): void
    {
        $this->errors[] = [
            'message' => $message,
            'actions' => $actions,
        ];
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
        check_admin_referer(self::IMPORT_NONCE);
        $this->startRow = $_GET['start_row'];
        $importer = (new ProductExcelImporter($this->file, ['start_from' => $this->startRow]));
        $headers  = $importer->getHeader($this->startRow);
        $mapped_items = $this->autoMapColumns($headers);
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

    private function showImport(): void
    {
        check_admin_referer(self::IMPORT_NONCE);

        if (! is_file($this->file)) {
            $this->addErrors(__('The file does not exist, please try again.', 'woocommerce'));
            $this->showErrors();
        }

        $req = $this->request;

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
                'file' => $this->file,
                'update_existing' => $this->update_existing
            ]
        );

        wp_deregister_script('wc-product-import');
        wp_enqueue_script('ewoo-product-import');
        include_once $this->import_views_path . '/import-progress.php';
    }
    private function showFooter(): self
    {
        include $this->import_views_path . 'import-footer.php';

        return $this;
    }

    private function showDone(): self
    {
        check_admin_referer(self::IMPORT_NONCE);
        $imported  = isset( $_GET['products-imported'] ) ? absint( $_GET['products-imported'] ) : 0;
        $updated   = isset( $_GET['products-updated'] ) ? absint( $_GET['products-updated'] ) : 0;
        $failed    = isset( $_GET['products-failed'] ) ? absint( $_GET['products-failed'] ) : 0;
        $skipped   = isset( $_GET['products-skipped'] ) ? absint( $_GET['products-skipped'] ) : 0;
        $file_name = isset( $_GET['file-name'] ) ? sanitize_text_field( wp_unslash( $_GET['file-name'] ) ) : '';
        $errors    = array_filter( (array) get_user_option( 'product_import_error_log' ) );

        include $this->import_views_path . 'import-done.php';

        return $this;
    }

    /**
     * Add error message.
     */
    private function showErrors(): self
    {
        if (empty($this->errors)) {
            return $this;
        }

        foreach ($this->errors as $error) {
            echo '<div class="error inline">';
            echo '<p>' . esc_html($error['message']) . '</p>';

            if (! empty($error['actions'])) {
                echo '<p>';
                foreach ($error['actions'] as $action) {
                    echo '<a class="button button-primary" href="'
                         . esc_url($action['url']) . '">'
                         . esc_html($action['label']) . '</a> ';
                }
                echo '</p>';
            }
            echo '</div>';
        }

        return $this;
    }

    private function normalizeColumnsNames(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $key => $column) {
            $normalized[strtolower($key)] = $column;
        }

        return $normalized;
    }

    private function autoMapColumns(
        array $headers,
        bool $num_indexes = true
    ): array {
        $default_columns = $this->normalizeColumnsNames(
            apply_filters(
                'woocommerce_csv_product_import_mapping_default_columns',
                array(
                    __('ID', 'woocommerce')             => 'id',
                    __('Type', 'woocommerce')           => 'type',
                    __('SKU', 'woocommerce')            => 'sku',
                    __('Name', 'woocommerce')           => 'name',
                    __('Published', 'woocommerce')      => 'published',
                    __('Is featured?', 'woocommerce')   => 'featured',
                    __('Visibility in catalog', 'woocommerce') => 'catalog_visibility',
                    __('Short description', 'woocommerce') => 'short_description',
                    __('Description', 'woocommerce')    => 'description',
                    __('Date sale price starts', 'woocommerce') => 'date_on_sale_from',
                    __('Date sale price ends', 'woocommerce') => 'date_on_sale_to',
                    __('Tax status', 'woocommerce')     => 'tax_status',
                    __('Tax class', 'woocommerce')      => 'tax_class',
                    __('In stock?', 'woocommerce')      => 'stock_status',
                    __('Stock', 'woocommerce')          => 'stock_quantity',
                    __('Backorders allowed?', 'woocommerce') => 'backorders',
                    __('Low stock amount', 'woocommerce') => 'low_stock_amount',
                    __('Sold individually?', 'woocommerce') => 'sold_individually',
                    __('Allow customer reviews?', 'woocommerce') => 'reviews_allowed',
                    __('Purchase note', 'woocommerce')  => 'purchase_note',
                    __('Sale price', 'woocommerce')     => 'sale_price',
                    __('Regular price', 'woocommerce')  => 'regular_price',
                    __('Categories', 'woocommerce')     => 'category_ids',
                    __('Tags', 'woocommerce')           => 'tag_ids',
                    __('Shipping class', 'woocommerce') => 'shipping_class_id',
                    __('Images', 'woocommerce')         => 'images',
                    __('Download limit', 'woocommerce') => 'download_limit',
                    __('Download expiry days', 'woocommerce') => 'download_expiry',
                    __('Parent', 'woocommerce')         => 'parent_id',
                    __('Upsells', 'woocommerce')        => 'upsell_ids',
                    __('Cross-sells', 'woocommerce')    => 'cross_sell_ids',
                    __('Grouped products', 'woocommerce') => 'grouped_products',
                    __('External URL', 'woocommerce')   => 'product_url',
                    __('Button text', 'woocommerce')    => 'button_text',
                    __('Position', 'woocommerce')       => 'menu_order',
                ),
                $headers
            )
        );

        $parsed_headers = [];
        foreach ($headers as $index => $header) {
            $normalized_field  = strtolower($header);
            $key = $num_indexes ? $index : $header;
            $parsed_headers[$key] = $default_columns[$normalized_field] ??
                                    $normalized_field;
        }

        return $parsed_headers;
    }

    private function getMappingOptions(string $item = ''): array
    {
        $index = $item;
        if (preg_match('/\d+/', $item, $matches)) {
            $index = $matches[0];
        }

        $options        = array(
            'id'                 => __('ID', 'woocommerce'),
            'type'               => __('Type', 'woocommerce'),
            'sku'                => __('SKU', 'woocommerce'),
            'name'               => __('Name', 'woocommerce'),
            'published'          => __('Published', 'woocommerce'),
            'featured'           => __('Is featured?', 'woocommerce'),
            'catalog_visibility' => __('Visibility in catalog', 'woocommerce'),
            'short_description'  => __('Short description', 'woocommerce'),
            'description'        => __('Description', 'woocommerce'),
            'price'              => array(
                'name'    => __('Price', 'woocommerce'),
                'options' => array(
                    'regular_price'     => __('Regular price', 'woocommerce'),
                    'sale_price'        => __('Sale price', 'woocommerce'),
                    'date_on_sale_from' => __('Date sale price starts', 'woocommerce'),
                    'date_on_sale_to'   => __('Date sale price ends', 'woocommerce'),
                ),
            ),
            'stock_status'       => __('In stock?', 'woocommerce'),
            'stock_quantity'     => _x('Stock', 'Quantity in stock', 'woocommerce'),
            'backorders'         => __('Backorders allowed?', 'woocommerce'),
            'low_stock_amount'   => __('Low stock amount', 'woocommerce'),
            'sold_individually'  => __('Sold individually?', 'woocommerce'),
            /* translators: %s: weight unit */
            'category_ids'       => __('Categories', 'woocommerce'),
            'tag_ids'            => __('Tags (comma separated)', 'woocommerce'),
            'tag_ids_spaces'     => __('Tags (space separated)', 'woocommerce'),
            'shipping_class_id'  => __('Shipping class', 'woocommerce'),
            'images'             => __('Images', 'woocommerce'),
            'parent_id'          => __('Parent', 'woocommerce'),
            'upsell_ids'         => __('Upsells', 'woocommerce'),
            'cross_sell_ids'     => __('Cross-sells', 'woocommerce'),
            'grouped_products'   => __('Grouped products', 'woocommerce'),
            'attributes'         => array(
                'name'    => __('Attributes', 'woocommerce'),
                'options' => array(
                    'attributes:name' . $index     => __('Attribute name', 'woocommerce'),
                    'attributes:value' . $index    => __('Attribute value(s)', 'woocommerce'),
                    'attributes:taxonomy' . $index => __('Is a global attribute?', 'woocommerce'),
                    'attributes:visible' . $index  => __('Attribute visibility', 'woocommerce'),
                    'attributes:default' . $index  => __('Default attribute', 'woocommerce'),
                ),
            ),
            'reviews_allowed'    => __('Allow customer reviews?', 'woocommerce'),
            'purchase_note'      => __('Purchase note', 'woocommerce'),
            'menu_order'         => __('Position', 'woocommerce'),
        );

        return apply_filters('woocommerce_csv_product_import_mapping_options', $options, $item);
    }
}
