<?php


namespace ExtendedWoo\ExtensionAPI\controllers;


use DateTimeImmutable;
use ExtendedWoo\Entities\Product;
use ExtendedWoo\Entities\Products;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\BrandsExcelImporter;
use ExtendedWoo\ExtensionAPI\import\ProductDiscountsUpdater;
use ExtendedWoo\ExtensionAPI\import\ProductExcelImporter;
use ExtendedWoo\ExtensionAPI\import\ProductExcelUpdater;
use ExtendedWoo\ExtensionAPI\import\SecondaryProductUpdater;
use ExtendedWoo\ExtensionAPI\models\export\ExcelExport;
use ExtendedWoo\ExtensionAPI\models\export\Exporter;
use ExtendedWoo\ExtensionAPI\Pages;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController
{
    /**
     * @var Request $request
     */
    private Request $request;
    private array $referrers = [
        'export' => 'ewoo-product-export',
        'import' => 'ewoo-product-import',
    ];


    public function __construct()
    {
        if (! defined('ABSPATH')) {
            exit;
        }

        $this->request = Request::createFromGlobals();
    }

    public function productExport(): void
    {
        $request = $this->request;
        $ref = $this->referrers['export'];
        check_ajax_referer($ref, 'security');
        $date = (new DateTimeImmutable("now"))->format('d-m-Y');
        $columnsToExport = ($request->get('selected_columns'))?:[];
        $categoriesToExport = ($request->get('export_category'))?:[];
        $export_all = $request->get('export_all');
        $export_without_images = $request->get('export_without_images');

        $exportedFiles = [];

        foreach ( $categoriesToExport as $category) {
            $excelGenerator = new ExcelExport("Product_Export_{$category}_{$date}.xls");
            $products = new Products();
            $exporter = new Exporter($excelGenerator, $products);
            $exporter
                ->setColumnsToExport(wp_unslash($columnsToExport))
                ->setCategoriesToExport(wp_unslash($category))
                ->setExportAll($export_all)
                ->setExportWithoutImages($export_without_images)
                ->generate();
            $exportedFiles[] = $excelGenerator->getFileName();
        }

        $query_args = apply_filters(
            'ewoo_export_get_ajax_query_args',
            array(
                'nonce'    => wp_create_nonce('product-xls'),
                'action'   => 'download_product_xls',
                'files' => $exportedFiles,
            )
        );

        wp_send_json_success(
            array(
                'step'       => 'done',
                'percentage' => 100,
                'url'        => add_query_arg($query_args, admin_url(Pages::PAGE_ROOT.'&page=product_excel_exporter')),
            )
        );
    }

    public function productImport(): void
    {
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
                        $imported = (empty($_POST['imported']))? 0 : (int) $_POST['imported'];
                        $total = (empty($_POST['total']))? $importer->getImportProgress() : (int) $_POST['total'];

                        $results = $importer->import();
                        $progress_left = $importer->getImportProgress();
                        $percentile = round(($imported/$total)*100, PHP_ROUND_HALF_UP);

                        if ($progress_left > 0) {
                            wp_send_json_success(
                                array(
                                    'position'   => 'in_progress',
                                    'percentage' => $percentile,
                                    'imported' => $imported,
                                    'total' => $total,
                                )
                            );
                        }
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

    public function productRemove(): void
    {
        $request = $this->request;
        check_ajax_referer('ewoo-product-import', 'security');

        $product_id = $request->get('prod_id');

        $product = new Product();
        $product->deletePreImportedProduct($product_id);

        $data = [
            'total' => $product->getTotal() ?? 0,
            'valid' => $product->getValid() ?? 0,
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

    public function checkResolverForm(): void
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

        wp_send_json_success($results);
    }
}