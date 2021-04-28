<?php

namespace ExtendedWoo\ExtensionAPI\import\models;

use ExtendedWoo\Entities\ProductBuilder;
use WC_Product;

class ProductFactory
{
    private WC_Product $product;

    public function __construct(array $settings = [])
    {
        $productBuilder = new ProductBuilder();
        $settings['type'] = 'simple';

        if (! empty($settings['id'])) {
            $productBuilder->setID($settings['id']);
        }

        if (! empty($settings['product_title'])) {
            $productBuilder->setName($settings['product_title']);
        }

        if (! empty($settings['sku'])) {
            $productBuilder->setSKU($settings['sku']);
        }

        if (! empty($settings['product_excerpt'])) {
            $productBuilder->setShortDescription($settings['product_excerpt']);
        }

        if (!empty($settings['product_content'])) {
            $productBuilder->setDescription($settings['product_content']);
        }

        if ($settings['max_price'] > 0) {
            $productBuilder->setRegularPrice($settings['max_price']);
        }

        if ($settings['min_price'] > 0) {
            $productBuilder
                ->setPrice($settings['min_price'])
                ->setSalePrice($settings['min_price']);
        }

        $this->product = $productBuilder->makeProduct();
    }

    public function getProduct(): WC_Product
    {
        return $this->product;
    }
}
