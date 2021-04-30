<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\export\ExporterInterface;

class ProductExporter implements ExporterInterface
{
    /**
     * @var int $limit
     */
    private int $limit = 100;
    private int $page = 1;
    private array $product_category_to_export = [];
    private array $product_types_to_export = [];
    private array $row_data = [];
    private int $total_rows = 0;
    private bool $enable_meta_export = false;
    private $export_type = 'product';

    public function __construct()
    {
        $this->setProductTypesToExport(array_merge(array_keys(wc_get_product_types()), array( 'variation' )));
    }

    public function getDefaultColumnNames(): array
    {
        return apply_filters(
            "woocommerce_product_export_{$this->export_type}_default_columns",
            array(
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
                'tax_status'         => __('Tax status', 'woocommerce'),
                'tax_class'          => __('Tax class', 'woocommerce'),
                'stock_status'       => __('In stock?', 'woocommerce'),
                'stock'              => __('Stock', 'woocommerce'),
                'low_stock_amount'   => __('Low stock amount', 'woocommerce'),
                'backorders'         => __('Backorders allowed?', 'woocommerce'),
                'sold_individually'  => __('Sold individually?', 'woocommerce'),
                /* translators: %s: weight */
                'weight'             => sprintf(__('Weight (%s)', 'woocommerce'), get_option('woocommerce_weight_unit')),
                /* translators: %s: length */
                'length'             => sprintf(__('Length (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
                /* translators: %s: width */
                'width'              => sprintf(__('Width (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
                /* translators: %s: Height */
                'height'             => sprintf(__('Height (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit')),
                'reviews_allowed'    => __('Allow customer reviews?', 'woocommerce'),
                'purchase_note'      => __('Purchase note', 'woocommerce'),
                'sale_price'         => __('Sale price', 'woocommerce'),
                'regular_price'      => __('Regular price', 'woocommerce'),
                'category_ids'       => __('Категория', 'woocommerce'),
                'tag_ids'            => __('Tags', 'woocommerce'),
                'shipping_class_id'  => __('Shipping class', 'woocommerce'),
                'images'             => __('Images', 'woocommerce'),
                'download_limit'     => __('Download limit', 'woocommerce'),
                'download_expiry'    => __('Download expiry days', 'woocommerce'),
                'parent_id'          => __('Parent', 'woocommerce'),
                'grouped_products'   => __('Grouped products', 'woocommerce'),
                'upsell_ids'         => __('Upsells', 'woocommerce'),
                'cross_sell_ids'     => __('Cross-sells', 'woocommerce'),
                'product_url'        => __('External URL', 'woocommerce'),
                'button_text'        => __('Button text', 'woocommerce'),
                'menu_order'         => __('Position', 'woocommerce'),
            )
        );
    }

    public function setProductCategoryToExport($category): self
    {
        $this->product_category_to_export = $this->product_category_to_export = array_map('sanitize_title_with_dashes', $category);

        return $this;
    }

    public function prepareDataToExport(): void
    {
        $args = array(
            'status'   => array( 'private', 'publish', 'draft', 'future', 'pending' ),
            'type'     => $this->product_types_to_export,
            'limit' => $this->getLimit(),
            'page' => $this->getPage(),
            'orderby'  => array(
                'ID' => 'ASC',
            ),
            'return'   => 'objects',
            'paginate' => true,
        );

        if (! empty($this->product_category_to_export)) {
            $args['category'] = $this->product_category_to_export;
        }
        $products = wc_get_products(apply_filters("woocommerce_product_export_{$this->export_type}_query_args", $args));
        $this->total_rows  = $products->total;
        foreach ($products->products as $product) {
            $this->row_data[] = $this->generateRowData($product);
        }
    }

    public function setLimit(int $limit): ExporterInterface
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function enableMetaExport(bool $enable_meta_export = false): ExporterInterface
    {
        $this->enable_meta_export = $enable_meta_export;
        return $this;
    }

    public function setProductTypesToExport(array $types): ExporterInterface
    {
        $this->product_types_to_export = array_map('wc_clean', $types);
        return $this;
    }

    /**
     * Take a product and generate row data from it for export.
     *
     * @param WC_Product $product WC_Product object.
     *
     * @return array
     */
    protected function generateRowData($product): array
    {
        $columns = $this->getDefaultColumnNames();
        $row     = array();
        foreach ($columns as $column_id => $column_name) {
            $column_id = strstr($column_id, ':') ? current(explode(':', $column_id)) : $column_id;
            $value     = '';

//            // Skip some columns if dynamically handled later or if we're being selective.
//            if ( in_array( $column_id, array( 'downloads', 'attributes', 'meta' ), true ) || ! $this->is_column_exporting( $column_id ) ) {
//                continue;
//            }

            if (has_filter("woocommerce_product_export_{$this->export_type}_column_{$column_id}")) {
                // Filter for 3rd parties.
                $value = apply_filters("woocommerce_product_export_{$this->export_type}_column_{$column_id}", '', $product, $column_id);
            } elseif (is_callable(array( $this, "get_column_value_{$column_id}" ))) {
                // Handle special columns which don't map 1:1 to product data.
                $value = $this->{"get_column_value_{$column_id}"}($product);
            } elseif (is_callable(array( $product, "get_{$column_id}" ))) {
                // Default and custom handling.
                $value = $product->{"get_{$column_id}"}('edit');
            }

            if ('description' === $column_id || 'short_description' === $column_id) {
                $value = $this->filterDescriptionField($value);
            }

            $row[ $column_id ] = $value;
        }

//        $this->prepare_downloads_for_export( $product, $row );
//        $this->prepare_attributes_for_export( $product, $row );
//        $this->prepare_meta_for_export( $product, $row );
        return apply_filters('woocommerce_product_export_row_data', $row, $product);
    }

    protected function filterDescriptionField($description)
    {
        $description = str_replace('\n', "\\\\n", $description);
        $description = str_replace("\n", '\n', $description);

        return $description;
    }
}