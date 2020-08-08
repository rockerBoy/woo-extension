<?php


namespace ExtendedWoo\ExtensionAPI\interfaces;

interface CategoryInterface
{
    public function getFilters(): array;
    public function getBrands(): array;
    public function getAttributes(): array;
    public function setAvailableAttributes(array $attribute_list): CategoryInterface;
    public function getAvailableAttributes(): array;
}
