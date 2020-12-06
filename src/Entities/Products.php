<?php

namespace ExtendedWoo\Entities;

final class Products
{
    public const POST_TYPE = 'product';

    private array $product_args = [];
    /**
     * @psalm-suppress UndefinedFunction
     * @return array
     */
    public function getDefaultColumnNames(): array
    {
        return [
            'id'                        => __('ID', 'woocommerce'),
            'sku'                       => __('SKU', 'woocommerce'),
            'name'                      => __('Name', 'woocommerce'),
            'published'                 => __('Published', 'woocommerce'),
            'featured'                  => __('Is featured?', 'woocommerce'),
            'catalog_visibility'        => __('Visibility in catalog', 'woocommerce'),
            'short_description'         => __('Short description', 'woocommerce'),
            'description'               => __('Description', 'woocommerce'),
            'date_on_sale_from'         => __('Date sale price starts', 'woocommerce'),
            'date_on_sale_to'           => __('Date sale price ends', 'woocommerce'),
            'stock_status'              => __('In stock?', 'woocommerce'),
            'stock'                     => __('Stock', 'woocommerce'),
            'low_stock_amount'          => __('Low stock amount', 'woocommerce'),
            'sold_individually'         => __('Sold individually?', 'woocommerce'),
            'reviews_allowed'           => __('Allow customer reviews?', 'woocommerce'),
            'purchase_note'             => __('Purchase note', 'woocommerce'),
            'sale_price'                => __('Sale price', 'woocommerce'),
            'regular_price'             => __('Regular price', 'woocommerce'),
            'parent_category_ids'       => __('Родительские категории', 'woocommerce'),
            'category_ids'              => __('Дочерние категории', 'woocommerce'),
            'brands'                    => __('Бренд', 'woocommerce'),
            'manufacturers'             => __('Страна производитель', 'woocommerce'),
            'tag_ids'                   => __('Tags', 'woocommerce'),
            'images'                   => __('Images', 'woocommerce'),
        ];
    }

    public function setProductArgs(array $args):self
    {
        $this->product_args = $args;

        return $this;
    }

    public function getProducts(bool $export_all = false)
    {
        $export_all = true;
//        dd($this->product_args);
        if (false === $export_all) {
            $this->product_args['status'] = ['private'];
        }

        return wc_get_products(
            apply_filters(
                "woocommerce_product_export_product_query_args",
                $this->product_args
            )
        );
    }

    public function getCategory($product, bool $is_parent = false):string
    {
        $term_ids = $product->get_category_ids('edit');
        $term_ids = wp_parse_id_list($term_ids);
        $taxonomy = get_term(current($term_ids), 'product_cat');

        if (! $is_parent) {
            return $taxonomy->name;
        } else {
            $parent_id = $taxonomy->parent;
            return $parent_id > 0 ? get_term($parent_id, 'product_cat')->name : '';
        }
    }

    public function getBrand($product): string
    {
        $taxonomy = get_the_terms($product->id, 'brands');
        $brand = '';
        if (false !== $taxonomy) {
            $brand = current($taxonomy)->name;
        }

        return $brand;
    }

    public function getCountry($product): string
    {
        $taxonomies = get_the_terms($product->id, 'manufacturers');
        $countries = [];

        if (false !== $taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                $countries[] = $taxonomy->name;
            }
        }

        return implode(' ', $countries);
    }

    public function getCategoryAttributes(int $category): array
    {
        global $wpdb;

        $result_attributes = [];
        $attributes = $wpdb->prepare(
            "SELECT
                        `attribute_name`
                    FROM {$wpdb->prefix}woo_category_attributes
                    WHERE
                        `attribute_category_id` = %d AND 
                        `attribute_public` <> 0",
            $category
        );
        $attributes_list = $wpdb->get_results($attributes, ARRAY_A);

        foreach ($attributes_list as $attribute) {
            $result_attributes[] = $attribute['attribute_name'];
        }

        return $result_attributes;
    }
}
