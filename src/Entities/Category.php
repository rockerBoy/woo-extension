<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\CategoryInterface;

class Category implements CategoryInterface
{
    private array $filter_list = [];
    private array $attributes = [];

    public function getFilters(): array
    {
        return [];
    }

    public function getBrands(): array
    {
        $brands = [];

        return $brands;
    }

    public function getAttributes(): array
    {
        // TODO: Implement getAttributes() method.
    }

    public function setAvailableAttributes(array $attribute_list): CategoryInterface
    {
        // TODO: Implement setAvailableAttributes() method.
    }

    public function getAvailableAttributes(): array
    {
        // TODO: Implement getAvailableAttributes() method.
    }
}
