<?php


namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class ProductImportType implements ImportType
{
    private array $basicColumns;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
    }

    public function getColumns(): array
    {
        return array_merge($this->basicColumns, [
            'category_ids'       => __('Categories', 'woocommerce'),
        ]);
    }

    public function validateColumnsData(array $columns_data): bool
    {
        return false;
    }
}