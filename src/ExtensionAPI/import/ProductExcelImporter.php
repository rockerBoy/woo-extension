<?php

namespace ExtendedWoo\ExtensionAPI\import;

use DateTimeImmutable;
use ExtendedWoo\ExtensionAPI\import\models\Import;
use ExtendedWoo\ExtensionAPI\import\models\ProductFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductExcelImporter extends Import
{
    private array $preImportColumns;
    private array $relationsColumns;

    public function __construct(string $fileName, array $args = [])
    {
        global $wpdb;

        if (! is_file($fileName)) {
            wp_redirect(esc_url_raw('admin.php?page=excel_import'));
        }

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
            'product_uploaded' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
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

    public function getSample(): array
    {
        $start = $this->params['start_from'] ?? 1;
        $next = $this->columns[$start];

        return array_values($next);
    }

    public function import(): array
    {
        $uniqueItems = [
            'id' => [],
            'sku' => [],
        ];
        $data = [
            'author_id' => get_current_user_id(),
            'imported' => [],
            'failed'   => [],
            'updated'  => [],
            'skipped'  => [],
        ];

        foreach ($this->getColumns() as $index => $column) {
            $column = array_values($column);
            $this->startImportFrom = (isset($this->params['start_from']))? $this->params['start_from'] : 1;

            if ($column === $this->getHeader()) {
                continue;
            }

            $parsed_data = $this->parseRawColumn($column);

            if (empty($parsed_data['name']) && empty($parsed_data['sku'])) {
                continue;
            }

            if (empty($parsed_data['sku'])) {
                $sku = $this->params['fixes']['sku'][$index];
                if (! empty($sku)) {
                    $parsed_data['sku'] = $sku;
                } else {
                    continue;
                }
            }

            if (! in_array($parsed_data['sku'], $uniqueItems['sku'], true)) {
                $uniqueItems['sku'][] = $parsed_data['sku'];
            } else {
                continue;
            }
            if (! in_array($parsed_data['id'], $uniqueItems['id'], true)) {
                $uniqueItems['id'][] = $parsed_data['id'];
            } else {
                continue;
            }

            if (empty($parsed_data['categories_id'])) {
                $categories_id = $this->params['fixes']['categories'][$index];
                if (! empty($categories_id)) {
                    $cat = get_term($categories_id);
                    $parsed_data['category_ids'] = $cat->name;
                }
            }

            $product = $this->process($parsed_data);

            if (! is_bool($product) && $product->is_updated) {
                $data['updated'][$index] = $parsed_data;
            } elseif (! is_bool($product) && $product->is_saved) {
                $data['imported'][$index] = $parsed_data;
            } else {
                $data['failed'][$index] = $parsed_data;
            }
        }

        return $data;
    }


    public function readFile(): void
    {
        $spreadsheet = IOFactory::load($this->fileName);
        $this->columns = $spreadsheet
            ->getActiveSheet()
            ->toArray(null, false, true, true);
    }

    private function process(array $data)
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
            'product_uploaded' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        if ($columns['sku']) {
            $product = (new ProductFactory($columns))->getProduct();
            try {
                $product->set_catalog_visibility('hidden');
            } catch (\WC_Data_Exception $e) {
            }
            if ($categories) {
                $categories_ids = $this->parseCategoriesField($categories);
                $data['category_ids'] = $categories_ids;
                if (!empty($categories_ids)) {
                    $product->set_category_ids($categories_ids);
                }
            }
            $this->preImportColumns['is_valid'] = $this->validateRawProduct($data);
            $product->save();

            return $product;
        }

        return false;
    }

    private function validateRawProduct(array $product_data): bool
    {
        $id = (isset($product_data['id']))? absint($product_data['id']): 0;
        $sku = $product_data['sku'] ?? '';
        $is_valid = false;

        if (! $sku && !$id) {
            return false;
        }

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
}
