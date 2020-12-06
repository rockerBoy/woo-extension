<?php


namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class BrandsImportStrategy implements ImportType
{
    public function getColumns(): array
    {
        return [
            'brands'     => __('Бренд', 'woocommerce'),
        ];
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}