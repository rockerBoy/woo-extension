<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\import;

interface ImportType
{
    public function getColumns(): array;
    public function validateColumnsData(array $columns_data): bool;
}