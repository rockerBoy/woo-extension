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
        ob_start();
        require __DIR__.'/../../views/taxonomies/product_cat_add_fields.php';
        $view = ob_get_clean();
        echo $view;
    }

    public function editTaxonomyFields($term): void
    {
        $fields = $this->filters->getCategoryFilters($term->term_id);

        ob_start();
        require __DIR__.'/../../views/taxonomies/product_cat_edit_fields.php';
        $view = ob_get_clean();
        echo $view;
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
}
