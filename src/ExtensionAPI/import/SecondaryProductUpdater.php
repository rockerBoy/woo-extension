<?php

namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use WC_Product_Attribute;


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
            'images'    =>  $data['images'] ?? '',
        ];
        if (! empty($id)) {
            $product = wc_get_product_object('simple', $id);
            $attributes_taxonomies = wc_get_attribute_taxonomies();
            $attrs = $data_attributes = $tax_list = [];

            foreach ($attributes_taxonomies as $attr) {
                $attrs[] = $attr->attribute_label;
            }

            foreach ($data as $key => $item) {
                if (in_array($key, $attrs) && ! empty($item)) {
                    $data_attributes[] = [
                        'name' => $key,
                        'value' => [$item],
                    ];
                }
            }

            if ($product) {
                if ($product->get_short_description() !== $columns['short_description']) {
                    $product->set_short_description($columns['short_description']);
                }
                if (! empty($data['name']) && $data['name'] !== $product->get_name()) {
                    $product->set_name($data['name']);
                }
                if ($product->get_description() !== $columns['description']) {
                    $product->set_description($columns['description']);
                }
                if (! empty($data_attributes)) {
                    foreach ($data_attributes as $attribute) {
                        $attribute_object = new WC_Product_Attribute();
                        $attribute_object->set_name( $attribute['name'] );
                        $attribute_object->set_options( $attribute['value'] );
                        $attribute_object->set_visible( true );
                        $tax_list[] = $attribute_object;
                    }

                    $product->set_attributes($tax_list);
                }

//                if (! empty($columns['brands'])) {
//                    $brand = get_term_by('name', trim($columns['brands']), 'pa_brands');
//
//                    if (!empty($brand)) {
//                        wp_set_post_terms($product->get_id(), [$brand->term_id], 'pa_brands');
//                    }
//                }
//
//                if (! empty($columns['manufacturer'])) {
//                    $manufacturers = get_term_by('name', trim($columns['manufacturer']), 'pa_manufacturers');
//                    if (!empty($manufacturers)) {
//                        wp_set_post_terms($product->get_id(),
//                            [$manufacturers->term_id], 'pa_manufacturers');
//                    }
//                }

                if (! empty($columns['images'])) {
                    $image_id = $product->get_image_id();
                    //https://rost.kh.ua/photo/4233098.jpg
                    if (empty($image_id)) {
                        $url = $columns['images'];
                        $image_data = wc_rest_upload_image_from_url($url);
                        $image_id = wc_rest_set_uploaded_image_as_attachment($image_data);
                        $product->set_image_id($image_id);
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
