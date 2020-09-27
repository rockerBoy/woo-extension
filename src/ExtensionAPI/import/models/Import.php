<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

use DateTimeImmutable;
use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;
use wpdb;

abstract class Import
{
    protected wpdb $db;
    protected array $columns;
    protected string $fileName;
    protected int $startImportFrom = 1;
    protected DateTimeImmutable $importStartTime;
    protected array $importedRows = [
        'author_id' => 0,
        'imported' => [],
        'failed'   => [],
        'updated'  => [],
        'skipped'  => [],
    ];
    protected array $params;

    public function getHeader(): array
    {
        return array_values($this->columns[$this->startImportFrom]);
    }

    public function getImportSize(): int
    {
        return sizeof(array_values($this->columns));
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function parseRawColumn(array $raw_data): array
    {
        $row = [];
        $mapping = $this->params['mapping']['to'];
        foreach ($raw_data as $index => $cell) {
            if (isset($mapping[$index])) {
                $key = $mapping[$index];
                $row[$key] = $cell;
            }
        }

        return $row;
    }

    protected function parseCategoriesField(string $categories): array
    {
        return ProductCatTaxonomy::parseCategoriesString($categories);
    }

    abstract public function readFile(): void;
}
