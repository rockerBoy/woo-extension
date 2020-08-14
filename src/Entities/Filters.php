<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\Brands;
use ExtendedWoo\ExtensionAPI\helpers\ProductCategoryMetaboxes;
use ExtendedWoo\ExtensionAPI\Manufacturer;

final class Filters
{
    private array $taxonomies = [];
    private \wpdb $db;
    private CategoryFiltersTable $filtersTable;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->taxonomies = [
            'brands' => new Brands(),
            'manufacturers' => new Manufacturer()
        ];
    }

    public function initTaxonomies(): self
    {
        foreach ($this->taxonomies as $taxonomy) {
            $taxonomy->init();
        }
        return $this;
    }

    public function setCatTable(CategoryFiltersTable  $table): self
    {
        $this->filtersTable = $table;
        return $this;
    }

    public function getCategoryFilters(int $category_id): array
    {
        return $this->filtersTable->getFilters($category_id);
    }
}
