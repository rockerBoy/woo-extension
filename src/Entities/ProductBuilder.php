<?php

namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\import\ProductItemBuilder;

class ProductBuilder implements ProductItemBuilder
{
    private \WC_Product $product;

    public function __construct($product = 0)
    {
        if (is_numeric($product)) {
            $product_id = $product;
            if ($product_id && wc_get_product($product_id)) {
                $product = wc_get_product($product_id);
            } else {
                $product = $product_id;
            }
        }

        $this->product = new Product($product);
    }

    public function setID(int $id): ProductItemBuilder
    {
        $this->product->setNewID($id);

        return $this;
    }

    public function setSKU(string $sku): ProductItemBuilder
    {
        if ((string) $this->product->get_sku() !== $sku) {
            $this->product->set_sku($sku);
        }

        return $this;
    }

    public function setName(string $name): ProductItemBuilder
    {
        $this->product->set_name($name);

        return $this;
    }

    public function setShortDescription(string $description): ProductItemBuilder
    {
        $this->product->set_short_description($description);

        return $this;
    }

    public function setDescription(string $description): ProductItemBuilder
    {
        $this->product->set_description($description);

        return $this;
    }

    public function setPrice(string $price): ProductItemBuilder
    {
        $this->product->set_price($price);

        return $this;
    }
    public function setRegularPrice(string $price): ProductItemBuilder
    {
        $this->product->set_regular_price($price);

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
