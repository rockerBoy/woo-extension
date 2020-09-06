<?php

namespace ExtendedWoo\ExtensionAPI\import;

use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;

class ImportTypeFactory
{
    public static function getImportType(string $import_type): ImportType
    {
        return new $import_type([
            'id'                 => __('ID', 'woocommerce'),
            'sku'                => __('SKU', 'woocommerce'),
            'name'               => __('Name', 'woocommerce'),
        ]);
    }
}
