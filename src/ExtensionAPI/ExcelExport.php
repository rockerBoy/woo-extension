<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\ExtensionAPI\interfaces\ExportInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelExport implements ExportInterface
{
    public function prepareDataToExport(): void
    {
        // TODO: Implement prepareDataToExport() method.
    }

    public function getColumnNames(): array
    {
        // TODO: Implement getColumnNames() method.
    }

    public function getDefaultColumnNames(): array
    {
        // TODO: Implement getDefaultColumnNames() method.
    }

    public function setColumnNames(array $column_names): ExportInterface
    {
        // TODO: Implement setColumnNames() method.
    }

    public function setColumnsToExport(array $columns): ExportInterface
    {
        // TODO: Implement setColumnsToExport() method.
    }

    public function export(): void
    {
        // TODO: Implement export() method.
    }
}