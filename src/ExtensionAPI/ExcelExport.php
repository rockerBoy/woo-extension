<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\ExtensionAPI\interfaces\ExcelExporterInterface;

class ExcelExport implements ExcelExporterInterface
{

    public function setFilters(array $filters = []): ExcelExporterInterface
    {
        return $this;
    }

    public function getProducts(): array
    {
        return [];
    }

    private function generateExport()
    {
    }
}