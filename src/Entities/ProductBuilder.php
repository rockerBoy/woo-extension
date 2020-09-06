<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\import\ProductItemBuilder;

class ProductBuilder implements ProductItemBuilder
{
    private \WC_Product $product;

    public function __construct()
    {
        $this->product = new Product();
    }

    public function setID(int $id): ProductItemBuilder
    {
        $this->product->set_id($id);

        return $this;
    }

    public function setSKU(string $sku): ProductItemBuilder
    {
        $this->product->set_sku($sku);

        return $this;
    }

    public function setName(string $name): ProductItemBuilder
    {
        $this->product->set_name($name);

        return $this;
    }

    public function setPrice(string $price): ProductItemBuilder
    {
        $this->product->set_price($price);

        return $this;
    }

    public function setSalePrice(string $price): ProductItemBuilder
    {
        $this->product->set_sale_price($price);

        return $this;
    }

    public function setCategory(int $category): ProductItemBuilder
    {
        $cat = [$category];
        $this->product->set_category_ids($cat);

        return $this;
    }

    public function setParentCategory(int $parent_category): ProductItemBuilder
    {
        $cat = [$parent_category];
        $this->product->set_category_ids($cat);

        return $this;
    }

    public function makeProduct(): \WC_Product
    {
        return $this->product;
    }
}
