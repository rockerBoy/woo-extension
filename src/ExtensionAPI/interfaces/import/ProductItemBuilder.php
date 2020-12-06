<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\import;

interface ProductItemBuilder
{
    public function setID(int $id): ProductItemBuilder;
    public function setSKU(string $sku): ProductItemBuilder;
    public function setName(string $name): ProductItemBuilder;
    public function setPrice(string $price): ProductItemBuilder;
    public function setSalePrice(string $price): ProductItemBuilder;
    public function setCategory(int $category): ProductItemBuilder;
    public function setParentCategory(int $parent_category): ProductItemBuilder;
}
