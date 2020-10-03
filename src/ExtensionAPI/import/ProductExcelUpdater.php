<?php


namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\models\Import;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductExcelUpdater extends Import
{
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

    public function readFile(): void
    {
        $this->columns = (IOFactory::load($this->fileName))
            ->getActiveSheet()
            ->toArray(null, false, true, true);
    }

    public function update(): array
    {
        $prices = $this->prepareRows();
        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];

        foreach ($prices as $product_price) {
            if (! empty($product_price['regular_price'])) {
                $data['updated'][] = $this->process($product_price);
            } else {
                $data['skipped'][] = $this->process($product_price);
            }
        }

        return $data;
    }

    protected function prepareRows(): array
    {
        $prepared_data = [];
        $mapping = $this->params['mapping']['to'];

        foreach ($this->columns as $index => $row) {
            $prepared_row = array_slice(array_values($row), 0, count($mapping));
            $prepared_row = ProductsImportHelper::parseRow($prepared_row, $mapping);

            if (! is_numeric($prepared_row['id'])) {
                continue;
            }

            $prepared_data[] = $prepared_row;
        }

        return $prepared_data;
    }

    private function process(array $data)
    {
        $id = wc_get_product_id_by_sku($data['sku']);
        $columns = [
            'min_price' => $data['sale_price'] ?? 0,
            'max_price' => $data['regular_price'] ?? 0,
        ];
        $product = wc_get_product($id);

        if ($product && $columns['max_price'] && $columns['max_price'] > 0) {
            $product->set_price((float)$columns['max_price']);
            $product->set_regular_price((float)$columns['max_price']);
            $product->save();
        }

        return $product;
    }
}
