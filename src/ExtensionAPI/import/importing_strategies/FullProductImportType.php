<?php

namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class FullProductImportType implements ImportType
{
    private $basicColumns;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
    }

    public function getColumns(): array
    {
        $columns = [
//            'published'          => __('Published', 'woocommerce'),
//            'featured'           => __('Is featured?', 'woocommerce'),
//            'catalog_visibility' => __('Visibility in catalog', 'woocommerce'),
            'short_description'  => __('Short description', 'woocommerce'),
            'description'        => __('Description', 'woocommerce'),
//            'price'              => array(
//                'name'    => __('Price', 'woocommerce'),
//                'options' => array(
//                    'regular_price'     => __('Regular price', 'woocommerce'),
//                    'sale_price'        => __('Sale price', 'woocommerce'),
//                    'date_on_sale_from' => __('Date sale price starts', 'woocommerce'),
//                    'date_on_sale_to'   => __('Date sale price ends', 'woocommerce'),
//                ),
//            ),
            'stock_status'       => __('In stock?', 'woocommerce'),
            'stock_quantity'     => _x('Stock', 'Quantity in stock', 'woocommerce'),
//            'backorders'         => __('Backorders allowed?', 'woocommerce'),
//            'low_stock_amount'   => __('Low stock amount', 'woocommerce'),
//            'sold_individually'  => __('Sold individually?', 'woocommerce'),
//            'category_ids'       => __('Categories', 'woocommerce'),
            'tag_ids'            => __('Tags (comma separated)', 'woocommerce'),
            'tag_ids_spaces'     => __('Tags (space separated)', 'woocommerce'),
//            'shipping_class_id'  => __('Shipping class', 'woocommerce'),
//            'images'             => __('Images', 'woocommerce'),
//            'parent_id'          => __('Parent', 'woocommerce'),
//            'upsell_ids'         => __('Upsells', 'woocommerce'),
//            'cross_sell_ids'     => __('Cross-sells', 'woocommerce'),
//            'grouped_products'   => __('Grouped products', 'woocommerce'),
            //            'attributes'         => array(
            //                'name'    => __('Attributes', 'woocommerce'),
            //                'options' => array(
            //                    'attributes:name' . $index     => __('Attribute name', 'woocommerce'),
            //                    'attributes:value' . $index    => __('Attribute value(s)', 'woocommerce'),
            //                    'attributes:taxonomy' . $index => __('Is a global attribute?', 'woocommerce'),
            //                    'attributes:visible' . $index  => __('Attribute visibility', 'woocommerce'),
            //                    'attributes:default' . $index  => __('Default attribute', 'woocommerce'),
            //                ),
            //            ),
//            'reviews_allowed'    => __('Allow customer reviews?', 'woocommerce'),
//            'purchase_note'      => __('Purchase note', 'woocommerce'),
//            'menu_order'         => __('Position', 'woocommerce'),
        ];

        return array_merge($this->basicColumns, $columns);
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}
