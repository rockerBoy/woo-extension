<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces;

interface ExportInterface
{
    public function prepareDataToExport(): void;
    public function getColumnNames(): array;
    public function getDefaultColumnNames(): array;
    public function setColumnNames(array $column_names): ExportInterface;
    public function setColumnsToExport(array $columns): ExportInterface;
    public function export(): void;
}
