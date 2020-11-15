<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\Entities\Products;
use ExtendedWoo\ExtensionAPI\export\Exporter;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\BrandsExcelImporter;
use ExtendedWoo\ExtensionAPI\import\ProductDiscountsUpdater;
use ExtendedWoo\ExtensionAPI\import\ProductExcelImporter;
use ExtendedWoo\ExtensionAPI\import\ProductExcelUpdater;
use ExtendedWoo\ExtensionAPI\import\SecondaryProductUpdater;
use ExtendedWoo\ExtensionAPI\interfaces\PageInterface;
use Symfony\Component\HttpFoundation\Request;
use \ExtendedWoo\ExtensionAPI\export\ExcelExport;

final class Pages implements PageInterface
{
    public const PAGE_ROOT = 'edit.php?post_type=product';

    /**
     * @var Request $request
     */
    private Request $request;
    /**
     * @var array $pages
     */
    private $pages = [];
    /**
     * Current page ID (or false if not registered with this controller).
     *
     * @var string|null $current_page
     */
    private ?string $current_page = null;

    public function __construct()
    {
        if (! defined('ABSPATH')) {
            exit;
        }
        $this->request = Request::createFromGlobals();
    }

    public function menu(): void
    {
        $this->registerPage([
            'id'        => 'product_excel_exporter',
            'parent'    => self::PAGE_ROOT,
            'screen_id' => 'product_page_product_exporter',
            'title'     => __('Export Products', 'woocommerce'),
            'path'     => 'product_excel_exporter',
        ]);
    }

    public function connectPage($options): void
    {
        if (! is_array($options['title'])) {
            $options['title'] = array( $options['title'] );
        }

        /**
         * Filter the options when connecting or registering a page.
         *
         * Use the `js_page` option to determine if registering.
         *
         * @param array $options {
         *   Array describing the page.
         *
         *   @type string       id           Id to reference the page.
         *   @type string|array title        Page title. Used in menus and breadcrumbs.
         *   @type string|null  parent       Parent ID. Null for new top level page.
         *   @type string       screen_id    The screen ID that represents the connected page.
         *                                   (Not required for registering).
         *   @type string       path         Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
         *   @type string       capability   Capability needed to access the page.
         *   @type string       icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
         *   @type int          position     Menu item position.
         *   @type boolean      js_page      If this is a JS-powered page.
         * }
         */
        $options = apply_filters('woocommerce_navigation_connect_page_options', $options);

        // @todo check for null ID, or collision.
        $this->pages[ $options['id'] ] = $options;
    }

    /**
     * @param array $options {
     *   @type string      id           Id to reference the page.
     *   @type string      title        Page title. Used in menus and breadcrumbs.
     *   @type string|null parent       Parent ID. Null for new top level page.
     *   @type string      path         Path for this page, full path in app context; ex /analytics/report
     *   @type string      capability   Capability needed to access the page.
     *   @type string      icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
     *   @type int         position     Menu item position.
     * }
     */
    public function registerPage(array $options): void
    {
        $defaults = array(
            'id'         => null,
            'parent'     => null,
            'title'      => '',
            'capability' => 'view_woocommerce_reports',
            'path'       => '',
            'icon'       => '',
            'position'   => null,
            'js_page'    => true,
        );
        $parsed_options = wp_parse_args($options, $defaults);

//        if (0 !== strpos($parsed_options['path'], self::PAGE_ROOT)) {
//            $options['path'] = self::PAGE_ROOT . '=' . $options['path'];
//        }
        if (is_null($parsed_options['parent'])) {
            add_menu_page(
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                array( __CLASS__, 'page_wrapper' ),
                $parsed_options['icon'],
                $parsed_options['position']
            );
        } else {
            $parent_path = $this->getPathFromId($options['parent']);
            // @todo check for null path.
            add_submenu_page(
                $parent_path,
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                [$this, 'viewPage']
            );
        }

        $this->connectPage($parsed_options);
    }

    public function getCurrentPage(): string
    {
        if (! did_action('current_screen')) {
            _doing_it_wrong(
                __FUNCTION__,
                esc_html__('Current page retrieval should be called on or after the `current_screen` hook.', 'woocommerce'),
                '0.16.0'
            );
        }
        if (is_null($this->current_page)) {
            $this->determineCurrentPage();
        }
        return $this->current_page;
    }

    public function determineCurrentPage(): void
    {
        //$current_screen_id = get_current_screen();
    }

    private function getPathFromId($id)
    {
        if (isset($this->pages[$id], $this->pages[$id]['path'])) {
            return $this->pages[ $id ]['path'];
        }
        return $id;
    }


    public function doAjaxProductExport(): void
    {
        $request = $this->request;
        check_ajax_referer('ewoo-product-export', 'security');
        $date = (new \DateTimeImmutable("now"))->format('d-m-Y');
        $columnsToExport = ($request->get('selected_columns'))?:[];
        $categoriesToExport = ($request->get('export_category'))?:[];
        $export_all = $request->get('export_all');
        $step = ($request->get('step'))?:1;
        $excelGenerator = new ExcelExport('Product_Export_'.$date.'.xls');
        $products = new Products();
        $exporter = new Exporter($excelGenerator, $products);

        $exporter
            ->setColumnsToExport(wp_unslash($columnsToExport))
            ->setCategoriesToExport(wp_unslash($categoriesToExport))
            ->setExportAll($export_all)
            ->setPage($step)
            ->generate();

        $query_args = apply_filters(
            'ewoo_export_get_ajax_query_args',
            array(
                'nonce'    => wp_create_nonce('product-xls'),
                'action'   => 'download_product_xls',
                'filename' => $excelGenerator->getFileName(),
            )
        );

        wp_send_json_success(
            array(
                'step'       => 'done',
                'percentage' => 100,
                'url'        => add_query_arg($query_args, admin_url(Pages::PAGE_ROOT.'&page=product_excel_exporter')),
            )
        );
        wp_die();
    }

    public function doAjaxProductImport(): void
    {
        global $wpdb;

        $request = $this->request;
        check_ajax_referer('ewoo-product-import', 'security');

        if (!empty($request->get('file'))) {
            $params = array(
                'mapping'         => isset($_POST['mapping']) ? (array) wc_clean(wp_unslash($_POST['mapping'])) : array(), // PHPCS: input var ok.
             );
            if (! empty($request->get('import_type'))) {
                $import_type = $request->get('import_type');
                switch ($import_type) {
                    case "update_prices":
                        $updater = new ProductExcelUpdater($request->get('file'), $params);
                        $results = $updater->update();
                        break;
                    case "update_actions":
                        $updater = new ProductDiscountsUpdater($request->get('file'), $params);
                        $results = $updater->update();
                        break;
                    case "secondary_import":
                        $updater = new SecondaryProductUpdater($request->get('file'), $params);
                        $results = $updater->update();
                        break;
                    case "upload_brands":
                        $updater = new BrandsExcelImporter($request->get('file'), $params);
                        $results = $updater->update();
                        break;
                    default:
                        $importer = new ProductExcelImporter($request->get('file'), $params);
                        $results = $importer->import();
                }
            }

            if (! empty($results)) {
                ProductsImportHelper::clearDatabase();
                $imported = empty($results['imported']) ? [] :$results['imported'];
                $updated = empty($results['updated']) ? [] :$results['updated'];
                $failed = empty($results['failed']) ? [] :$results['failed'];
                $skipped = empty($results['skipped']) ? [] :$results['skipped'];
                // Send success.
                wp_send_json_success(
                    array(
                        'position'   => 'done',
                        'percentage' => 100,
                        'url'        => add_query_arg(
                            array( '_wpnonce' => wp_create_nonce('etx-xls-importer') ),
                            admin_url('admin.php?page=excel_import&step=result')
                        ),
                        'imported'   => count($imported),
                        'failed'     => count($failed),
                        'updated'    => count($updated),
                        'skipped'    => count($skipped),
                    )
                );
            }
        }
    }

    /**
     * @return array
     */
    public function doAjaxProductRemove(): void
    {
        $request = $this->request;
        check_ajax_referer('ewoo-product-import', 'security');

        $product_id = $request->get('prod_id');
        $product = new Product();
        $product->deletePreImportedProduct($product_id);

        $data = [
            'total' => $product->getTotal()??0,
            'valid' => $product->getValid()??0,
            'errors' => 0,
        ];
        $data['errors'] = $data['total'] - $data['valid'];

        wp_send_json_success(
            array(
                'total' => $data['total'],
                'valid' => $data['valid'],
                'errors' => $data['errors'],
                'result'   => 'done',
            )
        );
    }

    public function doAjaxCheckResolverForm(): void
    {
        global $wpdb;

        $request = $this->request;
        check_ajax_referer('ewoo-product-import', 'security');

        $form_data = $request->get('form_data');
        $errors = [
            'name'  => __('Не заполнено поле Имя товара', 'extendedwoo'),
            'sku'   => __('Поле Артикул заполнено неверно', 'extendedwoo'),
            'category' => __('Не заполнено поле Категория товара', 'extendedwoo'),
        ];
        $errors_list = [];
        $product_relations_ids = [];
        $results = [
            'products' => [],
        ];
        $product_data = [];
        $check_item = new Product();

        if (! empty($form_data)) {
            foreach ($form_data as $product) {
                $rel_id = $product['relation_id'];
                $value = trim($product['value']);
                $fields = [];
                if (! in_array($rel_id, $product_relations_ids, true)) {
                    $field = $product['item'];
                    $product_relations_ids[] = $rel_id;
                    $fields[$field] = $value;
                    $product_data[$rel_id] = $fields;
                } else {
                    $fields = $product_data[$rel_id];
                    $field = $product['item'];
                    $fields[$field] = $value;
                    $product_data[$rel_id] = $fields;
                }
            }

            foreach ($form_data as $item) {
                $rel_id = $item['relation_id'];
                $product = new Product();
                $product_id = $wpdb->get_var("
                                SELECT
                                    `product_id`
                                FROM {$wpdb->prefix}woo_pre_import_relationships
                                WHERE `id` = $rel_id LIMIT 1");

                if (! empty($product_id)) {
                    $product->setNewID($product_id);

                    $stored_data = $product->getStoredInfoByProduct($rel_id, $product_id);
                    $product_changes = $product_data[$rel_id];

                    if (empty($stored_data)) {
                        continue;
                    }

                    if (! empty($product_changes['name'])) {
                        $product_name = $product_changes['name'];
                        $product->fixUploadedProductName($stored_data['id'], $product_name);
                        $stored_data['name'] = $product_name;
                    } elseif (!$stored_data['name']) {
                        $errors_list[$rel_id][] = $errors['name'];
                    }

                    if (!empty($product_changes['sku'])) {
                        $sku = $product_changes['sku'];

                        if (true === (bool)$product->fixUploadedProductSKU($stored_data['id'], $sku)) {
                            $stored_data['sku'] = $sku;

                            $results['products'][$rel_id] = [
                                'result'   => 'done',
                                'status'   => 'ok',
                                'sku' => $sku,
                                'row_id' => $rel_id,
                            ];
                        } else {
                            $errors_list[$rel_id][] = $errors['sku'];
                        }
                    }

                    if (!empty($product_changes['category'])) {
                        $category = $product_changes['category'];
                        $product->fixUploadedProductCategory($rel_id, $category);
                        $stored_data['category'] = $category;
                    } elseif (!$stored_data['category']) {
                        $errors_list[$rel_id][] = $errors['category'];
                    }

                    $results['products'][$rel_id] = [
                        'result'   => 'done',
                        'status'   => (empty($errors_list))? 'ok': 'nok',
                        'errors' => $errors_list[$rel_id] ?? []
                    ];
                    if (!empty($stored_data['name']) && !empty($stored_data['category']) && !empty($stored_data['sku'])) {
                        $wpdb
                            ->query($wpdb->prepare(
                                "UPDATE {$wpdb->prefix}woo_pre_import SET `is_valid` = 1  WHERE id = %d",
                                $stored_data['id']
                            ));
                        $results['products'][$rel_id] ['status'] = 'ok';
                    }
                }
            }
        }

        $total = $check_item->getTotal();
        $valid = $check_item->getValid();
        $errors = $check_item->getErrors();
        $results['total'] = $total;
        $results['valid'] = $valid;
        $results['errors'] = $total-$valid;

        if ($errors > 0) {
            $results['status'] = 'nok';
        } else {
            $results['status'] = 'ok';
        }
        //        if ($valid > 0 && $errors === 0) {
//            if ($total - $valid === 0) {
//                $results['errors'] = $total-$valid;
//                $results['status'] = 'ok';
//            }
//        } elseif ($errors > 0) {
//            $results['status'] = 'nok';
//            $results['errors'] = $errors;
//        } else {
//            $results['status'] = 'ok';
//            $results['errors'] = $errors;
//        }

        wp_send_json_success($results);
    }


    public function downloadExportFile(): void
    {
        $request = $this->request;
        $action = $request->get('action');
        $nonce = $request->get('nonce');
        $date = (new \DateTimeImmutable("now"))->format('d-m-Y');
        if ((! empty($action) && ! empty($nonce)) && wp_verify_nonce(wp_unslash($nonce), 'product-xls') && wp_unslash($action) === 'download_product_xls') {
            $excelGenerator = new ExcelExport('Product_Export_'.$date.'.xls');
            $excelGenerator->sendFileToUser();
            wp_die();
        }
    }
    
    public function viewPage(): void
    {
        $prefix = 'product_page_';
        $current = get_current_screen();
        $settings = [];

        foreach ($this->pages as $page) {
            if ($prefix.$page['id'] === $current->base) {
                $settings = $page;
            }
        }

        $viewStr = explode('_', $settings['path']);
        array_walk($viewStr, static function (&$val) {
            $val = ucfirst($val);
        });
        $viewStr = lcfirst(implode('', $viewStr));
        echo $this->$viewStr();
    }

    private function productExcelExporter(): string
    {
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_script('ewoo-product-export');
        wp_localize_script(
            'ewoo-product-export',
            'ewoo_product_export_params',
            array(
                'export_nonce' => wp_create_nonce('ewoo-product-export'),
            )
        );

        ob_start();
        require __DIR__.'/../views/admin-page-product-export.php';

        return ob_get_clean();
    }
}
