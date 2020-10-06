<?php


namespace ExtendedWoo\ExtensionAPI\taxonomies;

use ExtendedWoo\Entities\Filters;
use ExtendedWoo\ExtensionAPI\interfaces\TaxonomyInterface;
use Symfony\Component\HttpFoundation\Request;

final class ProductCatTaxonomy implements TaxonomyInterface
{
    private Request $request;
    public const TAXONOMY = 'product_cat';
    private ?\wpdb $db = null;
    private Filters $filters;

    public function __construct(\wpdb $db, Filters $filters)
    {
        $this->db = $db;
        $this->filters = $filters;
        $this->request = Request::createFromGlobals();
    }
    public function addTaxonomyFields(): void
    {
        require __DIR__.'/../../views/taxonomies/product_cat_add_fields.php';
    }

    public function editTaxonomyFields($term): void
    {
        $fields = $this->filters->getCategoryFilters($term->term_id);

        require __DIR__.'/../../views/taxonomies/product_cat_edit_fields.php';
    }

    public function saveTaxonomyFields(int $term_id, string $tt_id = '', string $taxonomy = ''): void
    {
        $wpdb = $this->db;

        $request = $this->request;
        if ($taxonomy === self::TAXONOMY && !empty($request->get('additional_field'))) {
            $fields = $request->get('additional_field');
            foreach ($fields as $key => $field) {
                $is_exists = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}woo_category_attributes
                WHERE attribute_category_id = %d AND attribute_label = %s", $field, 'additional_field_'.$key);
                if (! $wpdb->query($is_exists)) {
                    $sql = $wpdb->prepare("INSERT INTO {$wpdb->prefix}woo_category_attributes
                    ( attribute_name, attribute_label, attribute_type, attribute_category_id, attribute_order_by )
                    VALUES (%s, %s, %s, %d, %s ); ", $field, 'additional_field_'.$key, 'text', $term_id, '');
                    $wpdb->query($sql);
                }
            }
        }
    }

    public static function parseCategoriesString(string $categories): array
    {
        if (empty($categories)) {
            return [];
        }
        $row_terms  = self::explodeValues($categories);
        $parsed_categories = [];

        foreach ($row_terms as $row_term) {
            $parent = null;
            $terms = array_map('trim', explode('>', $row_term));
            $total = sizeof($terms);

            foreach ($terms as $index => $term) {
                if (! current_user_can('manage_product_terms')) {
                    break;
                }

                if (!term_exists($term)) {
                    return [];
                } else {
                    $parsed_categories[] = term_exists($term);
                }
            }
        }

        return $parsed_categories;
    }

    private static function explodeValues(string $value, string $separator = ','): array
    {
        $value = str_replace('\\,', '::separator::', $value);
        $values = explode($separator, $value);
        $values = array_map([__CLASS__, 'explodeValueFormatter'], $values);

        return $values;
    }

    private static function explodeValueFormatter(string $value): string
    {
        return trim(str_replace('::separator::', ',', $value));
    }
}
