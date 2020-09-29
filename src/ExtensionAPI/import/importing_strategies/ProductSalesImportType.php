<?php


namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class ProductSalesImportType implements ImportType
{
    private array $basicColumns;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
    }

    public function getColumns(): array
    {
        $columns = [
            'price'              => [
                'name'    => __('Price', 'woocommerce'),
                'options' => [
                    'regular_price'     => __('Regular price', 'woocommerce'),
                    'sale_price'        => __('Sale price', 'woocommerce'),
                    'date_on_sale_from' => __('Date sale price starts', 'woocommerce'),
                    'date_on_sale_to'   => __('Date sale price ends', 'woocommerce'),
                ],
            ],
        ];
        return array_merge($this->basicColumns, $columns);
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}