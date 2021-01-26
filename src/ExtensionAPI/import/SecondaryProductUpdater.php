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

    public function update(int $index = 0): array
    {
        $product_data = $this->prepareRows();
        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];

        if (empty($product_data[$index])) {
            return [];
        }

        $product = $product_data[$index];

        if (! empty($product['sku'])) {
            $data['updated'][] = $this->process($product);
        } else {
            $data['skipped'][] = $product;
        }

        return $data;
    }

    private function process(array $data)
    {
        $id = (new Product())->getProductIDBySKU($data['sku']);

        if (empty($id)) {
            $id = wc_get_product_id_by_sku($data['sku']);
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

                switch (mb_strtolower($data['catalog_visibility'])) {
                    case 'да':
                        $cat_visibility = 'visible';
                        break;
                    default:
                        $cat_visibility = 'hidden';
                }

                $attributes = $this->makeAttributeObjects($product, $this->prepareAttributes($data));
             
                $product->set_catalog_visibility($cat_visibility);

                $product->set_attributes($attributes);

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

    private function prepareAttributes(array $data): array
    {
        $attributes_taxonomies = wc_get_attribute_taxonomies();
        $attrs = $data_attributes = [];

        foreach ($attributes_taxonomies as $attr) {
            $attrs[] = $attr->attribute_name;
        }

        foreach ($data as $key => $item) {
            if (in_array($key, $attrs, true) && ! empty($item)) {
                $data_attributes[] = [
                    'attribute_names' => [$key],
                    'attribute_values' => [$item],
                ];
            }
        }

        return $data_attributes;
    }

    private function makeAttributeObjects(&$product, array $attributes): array
    {
        $attributes_objects = [];
        $list = wc_get_attribute_taxonomies();
        $i = 0;
        foreach ($attributes as $attribute) {
            $attribute['attribute_position'] = $i++;
            $attr_object  = current($this->prepare_attributes($attribute));
            $id = $attr_object['data']['id'];
            $name = 'pa_'.($list['id:'.$id])->attribute_name;

            $attributes_objects[$name] = $attr_object;
        }

        return $attributes_objects;
    }

    private function prepare_attributes( array $data ): array
    {
        if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
            $attribute_names         = $data['attribute_names'];
            $attribute_values        = $data['attribute_values'];
            $data['attribute_visibility'] = [1];
            $data['attribute_variation'] = [];
            $data['attribute_position'] = [$data['attribute_position']];
            $attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
            $attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
            $attribute_position      = $data['attribute_position'];
            $attribute_names_max_key = max( array_keys( $attribute_names ) );

            for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
                if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
                    continue;
                }

                $attribute_name = wc_clean( esc_html( $attribute_names[ $i ] ) );

                $id = 0;
                $attributes_taxonomies = wc_get_attribute_taxonomies();
                $tax = 'pa_';

                foreach ($attributes_taxonomies as $taxonomy) {
                    if ($taxonomy->attribute_name === $attribute_name) {
                        $attribute_name = wc_clean( esc_html( $taxonomy->attribute_name[ $i ] ) );
                        $id = $taxonomy->attribute_id;
                        $tax .= $taxonomy->attribute_name;
                    }
                }

                $attribute_id = $id;

                $options = [];

                foreach ($attribute_values as $value) {
                    $term = get_term_by('name', $value, $tax);

                    if (! empty($term)) {
                        $options[] = $term->term_id;
                    } else {
                        wp_insert_term($value, $tax);
                        $term = get_term_by('name', $value, $tax);
                        $options[] = $term->term_id;
                    }
                }

                $attribute = new WC_Product_Attribute();
                $attribute->set_id( $attribute_id );
                $attribute->set_name( $tax );
                $attribute->set_options( $options );
                $attribute->set_position( $attribute_position[ $i ] );
                $attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
                $attribute->set_variation( isset( $attribute_variation[ $i ] ) );
                $attributes[] = apply_filters( 'woocommerce_admin_meta_boxes_prepare_attribute', $attribute, $data, $i );
            }
        }

        return $attributes;
    }
}
