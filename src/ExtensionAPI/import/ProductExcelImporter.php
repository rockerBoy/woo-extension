<?php

namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;
use PhpOffice\PhpSpreadsheet\IOFactory;
use WC_Data_Exception;
use WC_Product;
use WP_Error;

class ProductExcelImporter
{
    private int $startTime;
    private array $columns = [];
    private string $fileName;
    private array $params;
    private \wpdb $db;
    private array $preImportColumns;
    private array $relationsColumns;
    private $product = null;

    public function __construct(string $fileName, array $args = [])
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->fileName = $fileName;
        $this->params = $args;
        $this->preImportColumns = [
            'product_title' => '',
            'product_excerpt' => '',
            'product_content' => '',
            'sku' => '',
            'min_price' => 0,
            'max_price' => 0,
            'onsale' => false,
            'stock_status' => false,
            'is_valid' => false,
            'is_imported' => false,
            'product_uploaded' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->relationsColumns = [
            'import_id' => 0,
            'product_id' => 0,
            'product_category_id' => 0,
            'product_parent_category_id' => 0,
            'product_author_id' => get_current_user_id(),
        ];
        $this->readFile();
    }

    public function getHeader(int $start_pos = 1): array
    {
        return array_values($this->columns[$start_pos]);
    }

    public function getSample(): array
    {
        $start = (isset($this->params['start_from']))? $this->params['start_from'] : 1;
        $next = $this->columns[++$start];

        return array_values($next);
    }

    public function getImportSize(): int
    {
        return sizeof(array_values($this->columns));
    }

    public function getColumns(): array
    {
        return array_values($this->columns);
    }

    public function import(): array
    {
        $data = [
            'author_id' => get_current_user_id(),
            'imported' => [],
            'failed'   => [],
            'updated'  => [],
            'skipped'  => [],
        ];

        $this->startTime = time();

        foreach ($this->getColumns() as $index => $column) {
            $column = array_values($column);
            $start = (isset($this->params['start_from']))? $this->params['start_from'] : 1;

            if ($column === $this->getHeader($start)) {
                continue;
            }

            $parsed_data = $this->parseRawColumn($column);

            if (! empty($parsed_data['sku']) && $this->validateRawProduct($parsed_data)) {
                $this->process($parsed_data);
            } else {
            }

            $data['imported'][$index] = $parsed_data;
        }

        return $data;
    }


    private function readFile(): void
    {
        $spreadsheet = IOFactory::load($this->fileName);
        $this->columns = $spreadsheet
            ->getActiveSheet()
            ->toArray(null, false, true, true);
    }

    private function parseRawColumn(array $raw_data): array
    {
        $row = [];
        $mapping = $this->params['mapping']['to'];
        foreach ($raw_data as $index => $cell) {
            if (isset($mapping[$index])) {
                $key = $mapping[$index];
                $row[$key] = $cell;
            }
        }

        return $row;
    }

    private function parseCategoriesField(string $categories): array
    {
        return ProductCatTaxonomy::parseCategoriesString($categories);
    }


    private function process(array $data): bool
    {
        $sku = $data['sku'] ?? '';
        $categories = $data['category_ids'] ?? '';

        $this->preImportColumns = [
            'product_title' => $data['name'] ?? '',
            'product_excerpt' => $data['short_description'] ?? '',
            'product_content' => $data['description'] ?? '',
            'sku' => $sku,
            'min_price' => $data['sale_price'] ?? 0,
            'max_price' => $data['regular_price'] ?? 0,
            'stock_status' => $data['stock_status'] ?? (string)false,
            'product_uploaded' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->product = $this->makeProduct($this->preImportColumns);

        if ($categories) {
            $categories_ids = $this->parseCategoriesField($categories);

            if (!empty($categories_ids)) {
                $this->product->set_category_ids($categories_ids);
            }

//            if (empty($categories_ids)) {
//                $data['failed'][$index] = $parsed_data;
//                continue;
//            }
        }

        $this->product->set_catalog_visibility('hidden');
        $this->saveProduct($this->product);

//        else {
//            $this->product = $this->productUpdate($this->preImportColumns);
//        }



//        if ($product) {
//            $this->relationsColumns['product_id'] = $product->get_id();
//            $this->relationsColumns['product_category_id'] = current($product->get_category_ids());
////            $this->productUpdate($product);
//        } else {
////            $this->productInsert($this->preImportColumns);
//        }

        return false;
    }


    private function makeProduct(array $productData): ?WC_Product
    {
        $settings = $this->preImportColumns;
        $settings['type'] = 'simple';

        $new_product = $this->getProductObject($settings);

        if ($new_product !== null) {
            $new_product->set_name($productData['product_title']);
            $new_product->set_sku($productData['sku']);
            $new_product->set_short_description($productData['product_excerpt']);
            $new_product->set_description($productData['product_content']);

            if ($this->preImportColumns['max_price'] > 0) {
                $new_product->set_regular_price($productData['max_price']);
            }
            if ($this->preImportColumns['min_price'] > 0) {
                $new_product->set_sale_price($productData['min_price']);
            }
        }

        return $new_product;
    }


    private function saveProduct(WC_Product $product): void
    {
        $db = $this->db;

        $db->insert($db->prefix.'woo_pre_import', $this->preImportColumns);
        $this->relationsColumns['import_id'] = $db->insert_id;
        $db->insert($db->prefix.'woo_pre_import_relationships', $this->relationsColumns);

        $product->save();
    }

//    private function productUpdate($productData): void
//    {
//        $wpdb->insert($wpdb->prefix.'woo_pre_import_relationships', $this->relationsColumns);
//        $new_product = $this->product;
//    }

    /**
     * @param array $data
     *
     * @return WC_Product|WP_Error
     */
    private function getProductObject(array $data)
    {
        if ($this->product) {
            return $this->product;
        }

        $id = isset($data['id']) ? absint($data['id']) : 0;
        // Type is the most important part here because we need to be using the correct class and methods.
        if (isset($data['type'])) {
            $types   = array_keys(wc_get_product_types());
            $types[] = 'variation';

            if (! in_array($data['type'], $types, true)) {
                return new WP_Error('woocommerce_product_importer_invalid_type', __('Invalid product type.', 'woocommerce'), array( 'status' => 401 ));
            }

            try {
                // Prevent getting "variation_invalid_id" error message from Variation Data Store.
                if ('variation' === $data['type']) {
                    $id = wp_update_post(
                        array(
                            'ID'        => $id,
                            'post_type' => 'product_variation',
                        )
                    );
                }

                $product = wc_get_product_object($data['type'], $id);
            } catch (WC_Data_Exception $e) {
                return new WP_Error('woocommerce_product_csv_importer_' . $e->getErrorCode(), $e->getMessage(), array( 'status' => 401 ));
            }
        } elseif (! empty($data['id'])) {
            $product = wc_get_product($id);

            if (! $product) {
                return new WP_Error(
                    'woocommerce_product_csv_importer_invalid_id',
                    /* translators: %d: product ID */
                    sprintf(__('Invalid product ID %d.', 'woocommerce'), $id),
                    array(
                        'id'     => $id,
                        'status' => 401,
                    )
                );
            }
        } else {
            $product = wc_get_product_object('simple', $id);
        }

        return apply_filters('woocommerce_product_import_get_product_object', $product, $data);
    }


    private function validateRawProduct(array $product_data): bool
    {
        $id = (isset($product_data['id']))? absint($product_data['id']): 0;
        $sku = (isset($product_data['sku']))? $product_data['sku']: '';
        $is_valid = false;

        if (! $sku && !$id) {
            return false;
        }

        $id_exists = $sku_exists = false;

        if ($id) {
            $product = wc_get_product($id);
            $id_exists = $product && 'importing' !== $product->get_status();

            if ($id_exists) {
                $this->product = $product;
                if ($product->get_sku()) {
                    return true;
                }
            }
        }

        if ($sku) {
            $id_from_sku = wc_get_product_id_by_sku($sku);
            $product     = $id_from_sku ? wc_get_product($id_from_sku) : false;
            $this->product = $product;
            $sku_exists  = $product && 'importing' !== $product->get_status();
            $is_valid = true;

            if ($sku_exists) {
                $this->product = $product;
                if ($product->get_sku()) {
                    return true;
                }
            }
        }

        return $is_valid;
    }
}
