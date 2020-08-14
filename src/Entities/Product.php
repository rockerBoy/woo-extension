<?php


namespace ExtendedWoo\Entities;


use ExtendedWoo\ExtensionAPI\interfaces\ProductInterface;

class Product implements ProductInterface
{

    public function setBrand(string $brand): ProductInterface
    {
        // TODO: Implement setBrand() method.
    }

    public function getBrand(): string
    {
        // TODO: Implement getBrand() method.
    }

    public function setCategories(array $category_list): ProductInterface
    {
        // TODO: Implement setCategories() method.
    }

    public function getCategories(): array
    {
        // TODO: Implement getCategories() method.
    }

    public function getFilters(): array
    {
        // TODO: Implement getFilters() method.
    }

    public function setFilters(array $filter_list): ProductInterface
    {
        // TODO: Implement setFilters() method.
    }

    public function getAttributes(): array
    {
        // TODO: Implement getAttributes() method.
    }

    public function setAttributes(array $attributes): ProductInterface
    {
        // TODO: Implement setAttributes() method.
    }
}