<?php


namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;

class BrandsExcelImporter extends ProductExcelUpdater
{
    protected function prepareRows(): array
    {
        $prepared_data = [];
        $mapping = $this->params['mapping']['to'];
        foreach ($this->columns as $index => $row) {
            $prepared_row = array_slice(array_values($row), 0, count($mapping));
            $prepared_row = ProductsImportHelper::parseRow($prepared_row, $mapping);

            if (! is_string($prepared_row['brands'])) {
                continue;
            }

            $prepared_data[] = $prepared_row;
        }

        return $prepared_data;
    }

    public function update(): array
    {
        $brands = $this->prepareRows();
        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];

        foreach ($brands as $brand) {
            if ($brand['brands'] != 'Бренд') {
                $data['updated'][] = $this->process($brand);
            }
        }

        return $data;
    }

    private function process( array $data)
    {
        if (! empty($data)) {
            $brand_item = trim($data['brands']);
            $brand = get_term_by('name', $brand_item, 'pa_brands');

            if (empty($brand)) {
                $post = array(
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_author' => get_current_user_id(),
                    'post_name' => sanitize_title($brand_item),
//                    'post_status' => 'private',
                    'post_title' => $brand_item,
                    'post_type' => 'product',
                    'tax_input' => array('pa_brands')
                );

                $new_film_id = wp_insert_post($post);
                wp_set_object_terms($new_film_id, $brand_item, 'pa_brands', true);
            }
        }

        return '';
    }
}