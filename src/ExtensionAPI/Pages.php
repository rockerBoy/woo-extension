<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\Entities\Products;
use ExtendedWoo\ExtensionAPI\export\Exporter;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\ProductExcelImporter;
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
        $useMeta = $request->get('export_meta');
        $step = ($request->get('step'))?:1;
        $excelGenerator = new ExcelExport('Product_Export_'.$date.'.xls');
        $products = new Products();
        $exporter = new Exporter($excelGenerator, $products);
        $exporter
            ->setColumnsToExport(wp_unslash($columnsToExport))
            ->setCategoriesToExport(wp_unslash($categoriesToExport))
            ->enableMetaExport($useMeta)
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
            $importer = new ProductExcelImporter($request->get('file'), $params);
            $results = $importer->import();

            if (! empty($results)) {
                ProductsImportHelper::clearDatabase();

                // Send success.
                wp_send_json_success(
                    array(
                        'position'   => 'done',
                        'percentage' => 100,
                        'url'        => add_query_arg(
                            array( '_wpnonce' => wp_create_nonce('etx-xls-importer') ),
                            admin_url('admin.php?page=excel_import&step=result')
                        ),
                        'imported'   => count($results['imported']),
                        'failed'     => count($results['failed']),
                        'updated'    => count($results['updated']),
                        'skipped'    => count($results['skipped']),
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

        (new Product())
            ->deletePreImportedProduct($product_id);
        wp_send_json_success(
            array(
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
        $results = [
            'products' => [],
        ];

        $check_item = new Product();
        if (! empty($form_data)) {
            foreach ($form_data as $item) {
                $item_type = $item['item'];
                $item_value = $item['value'];
                $rel_id = $item['relation_id'];
                $product_id = $item['product_id'];

                $product = new Product();
                $product->setNewID($product_id);

                switch ($item_type) {
                    case "sku":
                        $is_exists = $wpdb->prepare("SELECT id 
                                                FROM {$wpdb->prefix}woo_pre_import
                                                WHERE
                                                 sku = %s", $item_value);
                        $is_exists = $wpdb->get_var($is_exists);

                        if (false === (bool)$is_exists) {
                            $import_id = $product
                                ->getPreImportID();
                            $wpdb->query($wpdb->prepare(
                                "UPDATE {$wpdb->prefix}woo_pre_import
                                    SET sku = %s, is_valid = 1 
                                    WHERE id = %d",
                                $item_value,
                                $import_id
                            ));
                            $results['products'][$rel_id] = [
                                'result'   => 'done',
                                'status'   => 'ok',
                                'sku' => $item_value,
                                'row_id' => $rel_id,
                            ];
                        } else {
                            $results['products'][$rel_id] = [
                                'result'   => 'done',
                                'status'   => 'error',
                                'msg' => __('Артикль уже существует', 'extendedwoo'),
                                'row_id' => $rel_id,
                                'sku' => $item_value,
                            ];
                            wp_send_json_success($results);
                        }
                        break;
                    case "category":
                        $import_id = $product
                            ->getPreImportID();
                        $term = get_term($item_value);
                        $wpdb->query($wpdb->prepare(
                            "UPDATE {$wpdb->prefix}woo_pre_import_relationships
                        SET product_category_id = %d, product_parent_category_id = %d
                        WHERE product_id = %d",
                            $term->term_id,
                            ($term->parent) ?? 0,
                            $product_id
                        ));

                        $wpdb->query($wpdb->prepare(
                            "UPDATE {$wpdb->prefix}woo_pre_import
                        SET is_valid = 1
                        WHERE id = %d",
                            $import_id
                        ));
                        $results['products'][$rel_id] = [
                            'result'   => 'done',
                            'status'   => 'ok',
                            'category' => $term->name,
                            'row_id' => $rel_id,
                        ];
                        break;
                }

                $total = $product->getTotal();
                $valid = $product->getValid();
                $results['total'] = $total;
                $results['success'] = $valid;
                $results['errors'] = $total-$valid;
            }
        }

        $total = $check_item->getTotal();
        $valid = $check_item->getValid();

        if ($total - $valid === 0) {
            $results['errors'] = $total-$valid;
            $results['status'] = 'ok';
        }
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
