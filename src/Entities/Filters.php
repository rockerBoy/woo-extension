<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\Brands;
use ExtendedWoo\ExtensionAPI\Manufacturer;

final class Filters
{
    private array $taxonomies = [];

    public function __construct()
    {
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


}
