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
        $next = $this->columns[$start];

        return array_values($next);
    }

    public function getImportSize(): int
    {
        return sizeof(array_values($this->columns));
    }

    public function getColumns(): array
    {
        $columns = array_values($this->columns);
        array_shift($columns);
        return $columns;
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
            if (empty($parsed_data['name']) && empty($parsed_data['sku'])) {
                continue;
            }

            $this->process($parsed_data);
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

    public function parseRawColumn(array $raw_data): array
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
        $id = $data['id'] ? absint($data['id']): 0;
        $categories = $data['category_ids'] ?? '';

        $columns = [
            'product_title' => $data['name'] ?? '',
            'product_excerpt' => $data['short_description'] ?? '',
            'product_content' => $data['description'] ?? '',
            'id' => $id,
            'sku' => $sku,
            'min_price' => $data['sale_price'] ?? 0,
            'max_price' => $data['regular_price'] ?? 0,
            'stock_status' => $data['stock_status'] ?? (string)false,
            'product_uploaded' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($columns['sku']) {
            $product = $this->makeProduct($columns);
            $product->set_catalog_visibility('hidden');
            if ($categories) {
                $categories_ids = $this->parseCategoriesField($categories);
                $data['category_ids'] = $categories_ids;
                if (!empty($categories_ids)) {
                    $product->set_category_ids($categories_ids);
                }
            }

            $this->preImportColumns['is_valid'] = $this->validateRawProduct($data);
            $product->save();
        }
//        $this->saveProduct($product);

        return false;
    }


    private function makeProduct(array $productData): WC_Product
    {
        $settings = $productData;
        $settings['type'] = 'simple';

        $new_product = $this->getProductObject($settings);
        $new_product->set_name($settings['product_title']);
        if (!empty($productData['sku'])) {
            $new_product->set_sku($settings['sku']);
        }
        $new_product->set_short_description($settings['product_excerpt']);
        $new_product->set_description($settings['product_content']);

        if ($productData['max_price'] > 0) {
            $new_product->set_regular_price($settings['max_price']);
        }
        if ($productData['min_price'] > 0) {
            $new_product->set_price($settings['min_price']);
            $new_product->set_sale_price($settings['min_price']);
        }

        return $new_product;
    }


    private function saveProduct(WC_Product $product): void
    {
        $db = $this->db;
        $db->insert($db->prefix.'woo_pre_import', $this->preImportColumns);
        if ($product->get_id()) {
            $this->relationsColumns['import_id'] = $db->insert_id;
            $this->relationsColumns['product_id'] = ($product->get_id() !== 0) ?$product->get_id(): $db->insert_id;
            $this->relationsColumns['product_category_id'] = current($product->get_category_ids());
//            $db->insert($db->prefix.'woo_pre_import_relationships', $this->relationsColumns);
        }
    }


    /**
     * @param array $data
     *
     * @return WC_Product|WP_Error
     */
    private function getProductObject(array $data)
    {
        $id = isset($data['id']) ? absint($data['id']) : 0;
        $sku = $data['sku'];

        if ($id && wc_get_product($id)) {
            return wc_get_product($id);
        }

        if ($sku) {
            $id = wc_get_product_id_by_sku($sku);
            if (wc_get_product($id)) {
                return wc_get_product($id);
            }
        }

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
        
        if (empty($product_data['category_ids'])) {
            return false;
        }

        if ($sku) {
            $id_from_sku = wc_get_product_id_by_sku($sku);
            $product     = $id_from_sku ? wc_get_product($id_from_sku) : false;

            if (false !== $product) {
                $this->product = $product;
            }

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

    public function clearDatabase(): void
    {
        $wpdb = $this->db;

        $wpdb->delete($wpdb->postmeta, array( 'meta_key' => '_original_id' ));
        $wpdb->delete($wpdb->posts, array(
            'post_type'   => 'product',
            'post_status' => 'importing',
        ));
        $wpdb->delete($wpdb->posts, array(
            'post_type'   => 'product_variation',
            'post_status' => 'importing',
        ));
        $wpdb->query(
            "
                    DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
                    WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = 'product_variation'
			    "
        );
        $wpdb->query(
            "
                    DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
                    WHERE wp.ID IS NULL
                "
        );
        // @codingStandardsIgnoreStart.
        $wpdb->query( "
                    DELETE tr.* FROM {$wpdb->term_relationships} tr
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
                    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE wp.ID IS NULL
                    AND tt.taxonomy IN ( '" . implode( "','", array_map( 'esc_sql', get_object_taxonomies( 'product' ) ) ) . "' )
                " );
    }
}
