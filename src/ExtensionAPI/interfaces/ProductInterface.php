<?php

namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces;

/**
 * Interface ProductInterface
 * Minimal data structure to Importing
 * - SKU
 * - Title (if product is not exists)
 * - Price
 *
 * @package ExtendedWoo\ExtensionAPI\interfaces
 */
interface ProductInterface
{
    public function setBrand(string $brand): ProductInterface;
    public function getBrand(): string;
    public function setCategories(array $category_list): ProductInterface;
    public function getCategories(): array;
    public function getFilters(): array;
    public function setFilters(array $filter_list): ProductInterface;
    public function getAttributes(): array;
    public function setAttributes(array $attributes): ProductInterface;
}