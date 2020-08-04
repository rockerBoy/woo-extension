<?php

/**
 *
 * PreImport database table
 *
 * @var int product_id
 * @var string product_title
 * @var string product_excerpt
 * @var string product_content
 * @var string sku
 * @var float min_price
 * @var float max_price
 * @var boolean on_sale
 * @var string stock_status
 * @var string tax_class
 * @var DateTimeImmutable product_uploaded
 * @var DateTimeImmutable product_uploaded_gmt
 */

namespace ExtendedWoo\Entities;

class Product
{
    /**
     * @var int $limit
     */
    private $limit = 50;
    /**
     * @var int $page
     */
    private $page = 1;
    /**
     * @var int $total_products
     */
    private $total_products = 0;
    /**
     * @var bool $enable_attributes_export
     */
    private $enable_attributes_export = false;
    /**
     * @var array $product_categories_to_export
     */
    private $product_categories_to_export = [];
    /**
     * @var array $product_types_to_export
     */
    private $product_types_to_export = [];

    /**
     * @var array $column_names
     */
    private $column_names = [];
    private $export_type = 'product';
    /**
     * @var array $columns_to_export
     */
    private $columns_to_export = [];

    private array $product_args = [];
    /**
     * @psalm-suppress UndefinedFunction
     * @return array
     */
    public function getDefaultColumnNames(): array
    {
        $columns = [

            'id'                 => __('ID', 'woocommerce'),
            'type'               => __('Type', 'woocommerce'),
            'sku'                => __('SKU', 'woocommerce'),
            'name'               => __('Name', 'woocommerce'),
            'published'          => __('Published', 'woocommerce'),
            'featured'           => __('Is featured?', 'woocommerce'),
            'catalog_visibility' => __('Visibility in catalog', 'woocommerce'),
            'short_description'  => __('Short description', 'woocommerce'),
            'description'        => __('Description', 'woocommerce'),
            'date_on_sale_from'  => __('Date sale price starts', 'woocommerce'),
            'date_on_sale_to'    => __('Date sale price ends', 'woocommerce'),
//            'tax_status'         => __('Tax status', 'woocommerce'),
//            'tax_class'          => __('Tax class', 'woocommerce'),
            'stock_status'       => __('In stock?', 'woocommerce'),
            'stock'              => __('Stock', 'woocommerce'),
            'low_stock_amount'   => __('Low stock amount', 'woocommerce'),
//            'backorders'         => __('Backorders allowed?', 'woocommerce'),
            'sold_individually'  => __('Sold individually?', 'woocommerce'),
            /* translators: %s: weight */
//            'weight'             => sprintf(__('Weight (%s)', 'woocommerce'), get_option('woocommerce_weight_unit')),
            /* translators: %s: length */
//            'length'             => sprintf(__('Length (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
            /* translators: %s: width */
//            'width'              => sprintf(__('Width (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
            /* translators: %s: Height */
//            'height'             => sprintf(__('Height (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
            'reviews_allowed'    => __('Allow customer reviews?', 'woocommerce'),
            'purchase_note'      => __('Purchase note', 'woocommerce'),
            'sale_price'         => __('Sale price', 'woocommerce'),
            'regular_price'      => __('Regular price', 'woocommerce'),
            'category_ids'       => __('Categories', 'woocommerce'),
            'tag_ids'            => __('Tags', 'woocommerce'),
//            'shipping_class_id'  => __('Shipping class', 'woocommerce'),
            'images'             => __('Images', 'woocommerce'),
//            'download_limit'     => __('Download limit', 'woocommerce'),
//            'download_expiry'    => __('Download expiry days', 'woocommerce'),
//            'parent_id'          => __('Parent', 'woocommerce'),
//            'grouped_products'   => __('Grouped products', 'woocommerce'),
//            'upsell_ids'         => __('Upsells', 'woocommerce'),
//            'cross_sell_ids'     => __('Cross-sells', 'woocommerce'),
//            'product_url'        => __('External URL', 'woocommerce'),
//            'button_text'        => __('Button text', 'woocommerce'),
//            'menu_order'         => __('Position', 'woocommerce'),
        ];
        return apply_filters(
            "woocommerce_product_export_{$this->export_type}_default_columns",
            $columns,
        );
    }

    public function setProductArgs(array $args):self
    {
        $this->product_args = $args;

        return $this;
    }
    public function getProducts()
    {
        return wc_get_products(
            apply_filters(
                "woocommerce_product_export_{$this->exportType}_query_args",
                $this->product_args
            )
        );
    }
}
