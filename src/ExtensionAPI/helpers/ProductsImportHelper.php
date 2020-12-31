<?php


namespace ExtendedWoo\ExtensionAPI\helpers;

use ExtendedWoo\ExtensionAPI\models\taxonomies\ProductCatTaxonomy;

final class ProductsImportHelper
{
    public static $uniqueIDs = [];
    public static $uniqueSKUs = [];
    public static $validSKUs = [];

    public static function getDefaultFields(): array
    {
        return [
                __('ID', 'woocommerce')             => 'id',
                __('Type', 'woocommerce')           => 'type',
                __('SKU', 'woocommerce')            => 'sku',
                __('Name', 'woocommerce')           => 'name',
                __('Published', 'woocommerce')      => 'published',
                __('Is featured?', 'woocommerce')   => 'featured',
                __('Visibility in catalog', 'woocommerce') => 'catalog_visibility',
                __('Short description', 'woocommerce') => 'short_description',
                __('Description', 'woocommerce')    => 'description',
                __('Date sale price starts', 'woocommerce') => 'date_on_sale_from',
                __('Date sale price ends', 'woocommerce') => 'date_on_sale_to',
                __('In stock?', 'woocommerce')      => 'stock_status',
                __('Stock', 'woocommerce')          => 'stock_quantity',
                __('Backorders allowed?', 'woocommerce') => 'backorders',
                __('Low stock amount', 'woocommerce') => 'low_stock_amount',
                __('Purchase note', 'woocommerce')  => 'purchase_note',
                __('Sale price', 'woocommerce')     => 'sale_price',
                __('Regular price', 'woocommerce')  => 'regular_price',
                __('Категория', 'woocommerce')     => 'category_ids',
                __('Tags', 'woocommerce')           => 'tag_ids',
                __('Parent', 'woocommerce')         => 'parent_id',
                __('Position', 'woocommerce')       => 'menu_order',
                __('Images', 'woocommerce')         => 'images',
            ];
    }
    public static function normalizeRowNames(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $key => $column) {
            $normalized[strtolower($key)] = $column;
        }

        return $normalized;
    }

    public static function autoMapColumns(
        array $headers,
        bool $num_indexes = true
    ): array {
        $default_columns = self::normalizeRowNames(
            self::getDefaultFields(),
            apply_filters(
                'woocommerce_csv_product_import_mapping_default_columns',
                $headers
            )
        );

        $parsed_headers = [];
        foreach ($headers as $index => $header) {
            $normalized_field  = strtolower($header);
            $key = $num_indexes ? $index : $header;
            $parsed_headers[$key] = $default_columns[$normalized_field] ??
                                    $normalized_field;
        }

        return $parsed_headers;
    }

    public static function parseRow(array $col, array $mapping): array
    {
        $row = [];
        foreach ($col as $index => $cell) {
            if (isset($mapping[$index])) {
                $key = $mapping[$index];
                $row[$key] = $cell;
            }
        }
        return $row;
    }

    public static function validateMapping(array $mapping): bool
    {
        $is_mapping_valid = false;

        foreach ($mapping as $item) {
            if (empty($item)) {
                continue;
            }

            $is_mapping_valid = true;
        }

        return $is_mapping_valid;
    }

    public static function validateRows(array $rows, array $mapping): array
    {
        $rows = self::prepareRows($rows);
        foreach ($rows as $index => $row) {
            foreach ($row as $i => $cell) {
                $key = $mapping[$i] ?? '';
                $item = ($cell) ?? false;
                switch ($key) {
                    case 'id':
                        if (! empty($item) && !in_array($item, self::$uniqueIDs, true)) {
                            self::$uniqueIDs[$index] = $item;
                        } else {
                            self::$uniqueIDs[$index] = false;
                        }
                        break;
                    case 'sku':
                        if (! empty($item) && !in_array($item, self::$uniqueSKUs, true)) {
                            self::$uniqueSKUs[$index] = $item;
                        } else {
                            self::$uniqueSKUs[$index] = false;
                        }
                        break;
                    case 'category_ids':
                        $cat = (ProductCatTaxonomy::parseCategoriesString($item)) ?? [];
                        if (empty($cat)) {
                            self::$uniqueSKUs[$index] = false;
                        }
                        break;
                }
            }
        }

        return self::$uniqueSKUs;
    }

    public static function prepareRows(array $rows): array
    {
        $prepared_rows = [];

        foreach ($rows as $row) {
            $vals = array_values($row);
            $vals = self::validateRow($vals);

            if ($vals) {
                $prepared_rows[] = $vals;
            }
        }

        return $prepared_rows;
    }

    public static function makeExcerpt(string $str, int $startPos = 0, int $maxLength = 100): string
    {
        if (strlen($str) > $maxLength) {
            $excerpt   = substr($str, $startPos, $maxLength-3);
            $lastSpace = strrpos($excerpt, ' ');
            $excerpt   = substr($excerpt, 0, $lastSpace);
            $excerpt  .= '...';
        } else {
            $excerpt = $str;
        }

        return $excerpt;
    }

    public static function validateRow(array $row): ?array
    {

        foreach ($row as $index => $cell) {
            if (empty($cell)) {
                continue;
            }

            return $row;
        }

        return null;
    }

    public static function getCategories(): array
    {
        $term_list = get_terms( ['taxonomy' => 'product_cat', 'hide_empty' => false] );

        return $term_list;
    }

    public static function getProductBySKU(string $sku)
    {
        $id = wc_get_product_id_by_sku($sku);

        return wc_get_product($id);
    }

    public static function clearDatabase(): void
    {
        global $wpdb;

        $wpdb->delete($wpdb->postmeta, array( 'meta_key' => '_original_id' ));
        $wpdb->delete($wpdb->posts, array(
            'post_type'   => 'product',
            'post_status' => 'importing',
        ));
        $wpdb->delete($wpdb->posts, array(
            'post_type'   => 'product_variation',
            'post_status' => 'importing',
        ));
        $wpdb->query(
            "
                    DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
                    WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = 'product_variation'
			    "
        );
        $wpdb->query(
            "
                    DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
                    WHERE wp.ID IS NULL
                "
        );
        // @codingStandardsIgnoreStart.
        $wpdb->query( "
                    DELETE tr.* FROM {$wpdb->term_relationships} tr
                    LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
                    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE wp.ID IS NULL
                    AND tt.taxonomy IN ( '" . implode( "','", array_map( 'esc_sql', get_object_taxonomies( 'product' ) ) ) . "' )
                " );
    }

    public static function getMappingOptions(string $item = '', array $options): array
    {
        $index = $item;

        if (preg_match('/\d+/', $item, $matches)) {
            $index = $matches[0];
        }

        return apply_filters('woocommerce_csv_product_import_mapping_options', $options, $item);

    }
}
