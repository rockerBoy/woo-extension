<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import;

use ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import\ProductItemBuilder as ProductBuilder;

class ExcelImportProductItemBuilder implements ProductBuilder
{
    private int $ID;
    private string $SKU;
    private string $name;
    private float $price = 0;
    private float $salePrice = 0;
    private string $categoty = '';
    private string $parentCategory = '';

    public function setID(int $id): ProductBuilder
    {
        $this->ID = $id;
        return $this;
    }

    public function setSKU(string $sku): ProductBuilder
    {
        $this->SKU = $sku;
        return $this;
    }

    public function setName(string $name): ProductBuilder
    {
        $this->name = $name;
        return $this;
    }

    public function setPrice(string $price): ProductBuilder
    {
        $this->price = $price;
        return $this;
    }

    public function setSalePrice(string $price): ProductBuilder
    {
        $this->salePrice = $price;
        return $this;
    }

    public function setCategory(string $category): ProductBuilder
    {
        $this->categoty = $category;
        return $this;
    }

    public function setParentCategory(string $parent_category): ProductBuilder
    {
        $this->parentCategory = $parent_category;

        return $this;
    }

    public function getProduct(): ProductBuilder
    {
        return $this;
    }
}
