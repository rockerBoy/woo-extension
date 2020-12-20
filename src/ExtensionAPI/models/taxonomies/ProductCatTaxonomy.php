<?php


namespace ExtendedWoo\ExtensionAPI\models\taxonomies;

use ExtendedWoo\Entities\Filters;
use Symfony\Component\HttpFoundation\Request;
use wpdb;

final class ProductCatTaxonomy
{
    private Request $request;
    public const TAXONOMY = 'product_cat';
    private wpdb $db;
    private Filters $filters;

    public function __construct(wpdb $db, Filters $filters)
    {
        $this->db = $db;
        $this->filters = $filters;
        $this->request = Request::createFromGlobals();
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

            foreach ($terms as $index => $term) {
                if (! current_user_can('manage_product_terms')) {
                    break;
                }

                if (!term_exists($term)) {
                    return [];
                }

                $parsed_categories[] = term_exists($term);
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
