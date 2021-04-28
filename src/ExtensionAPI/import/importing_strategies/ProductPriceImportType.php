<?php


namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class ProductPriceImportType implements ImportType
{
    private array $basicColumns;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
    }

    public function getColumns(): array
    {
        unset($this->basicColumns['id']);

        $columns = [
            'regular_price'     => __('Regular price', 'woocommerce'),
        ];

        return array_merge($this->basicColumns, $columns);
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}