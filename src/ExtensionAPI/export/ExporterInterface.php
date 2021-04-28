<?php


namespace ExtendedWoo\ExtensionAPI\export;

interface ExporterInterface
{
    public function setColumnsToExport(array $export_columns): ExporterInterface;
    public function setCategoriesToExport(array $categories_to_export): ExporterInterface;
    public function setLimit(int $limit): ExporterInterface;
    public function setPage(int $page): ExporterInterface;
    public function enableMetaExport(bool $enable_meta_export = false): ExporterInterface;
    public function generate(): void;
}
