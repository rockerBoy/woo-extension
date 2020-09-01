<?php


namespace ExtendedWoo\ExtensionAPI\import;

final class ProblemResolver
{
    private \wpdb $db;
    private array $invalidProducts;
    private array $errorsList = [
        'empty_sku',
        'non_existing_category'
    ];
    public function __construct(\wpdb $db, array $failed_products)
    {
        $this->db = $db;
        $this->invalidProducts = $failed_products;
    }

    public function resolve(): void
    {
        foreach ($this->invalidProducts as $product) {
//            $product->set_sku();
            //TODO product validation
        }
    }
}