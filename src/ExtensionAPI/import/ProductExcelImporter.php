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
        $data = [
            'author_id' => get_current_user_id(),
            'imported' => [],
            'failed'   => [],
            'updated'  => [],
            'skipped'  => [],
        ];

        $import_products = $this->getPreImportedRows();

        foreach ($import_products as $product) {
            $data['imported'][] = $this->process($product);
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
        $columns = [
            'product_title' => $data['name'] ?? '',
            'product_excerpt' => $data['short_description'] ?? '',
            'product_content' => $data['description'] ?? '',
            'id' => $data['id'],
            'sku' => $data['sku'],
            'category_ids' => $data['product_category_id'],
            'min_price' => $data['sale_price'] ?? 0,
            'max_price' => $data['regular_price'] ?? 0,
            'stock_status' => $data['stock_status'] ?? (string)false,
            'product_uploaded' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'product_uploaded_gmt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $product = (new ProductFactory($columns))->getProduct();
        $product->set_category_ids([$columns['category_ids']]);
        $product->setFilename($this->fileName);
        try {
            $product->set_catalog_visibility('hidden');
        } catch (\WC_Data_Exception $e) {
            throw new \WC_Data_Exception($e->getCode(), $e->getMessage());
        }

        $product->save();

        return $product;
    }
}
