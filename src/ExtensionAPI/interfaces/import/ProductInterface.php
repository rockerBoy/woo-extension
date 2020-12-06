<?php
/**
 * Product Entity Interface
 * I need to have a possibility to check:
 *  - if product exists
 *  - if this product is correct
 *  - Get product category|categories
 *  - Get product attributes (Brand|Country|Hair type and etc.)
 *  - Filter by the attributes
 */

namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import;

interface ProductInterface
{
    public function checkIfExists(string $product_name): bool;

    /**
     * Get product categories or brands.
     * @return array
     */
    public function getProductTerms(): array;
}
