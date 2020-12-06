<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import\ImportType;

class BrandsImportStrategy implements ImportType
{
    public function getColumns(): array
    {
        $columns = [
            'brands'     => __('Бренд', 'woocommerce'),
        ];

        return $columns;
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}