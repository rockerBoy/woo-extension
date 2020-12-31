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

        $parsed_categories = [];

        if (!term_exists($categories)) {
            return [];
        }

        $parsed_categories[] = term_exists($categories);

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
