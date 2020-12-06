<?php

namespace ExtendedWoo\ExtensionAPI\interfaces\export\import;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\ExtensionAPI\interfaces\export\helpers\ProductsImportHelper;

class SecondaryProductUpdater extends ProductExcelUpdater
{
    protected function prepareRows(): array
    {
        $prepared_data = [];
        $mapping = $this->params['mapping']['to'];

        foreach ($this->columns as $index => $row) {
            $row = array_values($row);
            $prepared_row = ProductsImportHelper::parseRow($row, $mapping);

            if (! is_numeric($prepared_row['id'])) {
                continue;
            }

            $prepared_data[] = $prepared_row;
        }

        return $prepared_data;
    }

    public function update(): array
    {
        $product_data = $this->prepareRows();

        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];
        foreach ($product_data as $product) {
            if (! empty($product['sku']) &&
                ! empty($product['short_description']) &&
                ! empty($product['description'])
            ) {
                $data['updated'][] = $this->process($product);
            } else {
                $data['skipped'][] = $product;
            }
        }
        return $data;
    }

    private function process(array $data)
    {
        if (empty($data['id'])) {
            $id = (new Product())->getProductIDBySKU($data['sku']);
        } else {
            $id = (int)$data['id'];
        }
        $columns = [
            'short_description' => $data['short_description'] ?? '',
            'description' => $data['description'] ?? '',
            'stock_status' => $data['stock_status'] ?? '',
            'status' => 'publish',
            'brands' => $data['brands'] ?? '',
            'manufacturer' => $data['manufacturer'] ?? '',
        ];
        if (! empty($id)) {
            $product = wc_get_product_object('simple', $id);
            if ($product) {
                if ($product->get_short_description() !== $columns['short_description']) {
                    $product->set_short_description($columns['short_description']);
                }
                if ($product->get_description() !== $columns['description']) {
                    $product->set_description($columns['description']);
                }
                if (! empty($columns['brands'])) {
                    $brand = get_term_by('name', trim($columns['brands']), 'brands');
                    if ($brand) {
                        wp_set_post_terms($product->get_id(), [$brand->term_id], 'brands');
                    }
                }
                if (! empty($columns['manufacturer'])) {
                    $manufacturers = get_term_by('name', trim($columns['manufacturer']), 'manufacturers');
                    if ($brand) {
                        wp_set_post_terms($product->get_id(),
                            [$manufacturers->term_id], 'manufacturers');
                    }
                }
                if ($product->get_stock_status() !== $columns['stock_status']) {
                    $product->set_stock_status($columns['stock_status']);
                }
                if ($product->get_status() !== $columns['status']) {
                    $product->set_status($columns['status']);
                }

                $product->save();
                return $product;
            }
        }
    }
}
