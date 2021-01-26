<?php


namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;


class IDImportStrategy
    implements \ExtendedWoo\ExtensionAPI\interfaces\import\ImportType
{

    public function getColumns(): array
    {
        return [
            'id'     => __('ID', 'woocommerce'),
            'sku'     => __('SKU', 'woocommerce'),
        ];
    }

    public function validateColumnsData(array $columns_data): array
    {
        // TODO: Implement validateColumnsData() method.
    }
}