<?php

namespace ExtendedWoo\ExtensionAPI\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class FullProductImportType implements ImportType
{
    private array $basicColumns;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
    }

    public function getColumns(): array
    {
        $columns = [
            'short_description'  => __('Short description', 'woocommerce'),
            'description'        => __('Description', 'woocommerce'),
            'stock_status'       => __('In stock?', 'woocommerce'),
            'category_ids'       => __('Категория', 'woocommerce'),
//            'brands'             => __('Бренд', 'woocommerce'),
//            'manufacturer'       => __('Производитель', 'woocommerce'),
            'images'             => __('Images', 'woocommerce'),
        ];

        return array_merge($this->basicColumns, $columns);
    }

    public function validateColumnsData(array $columns_data): array
    {
        return [];
    }
}
