<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

use DateTimeImmutable;
use ExtendedWoo\Entities\ProductBuilder;
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
        $row = array_values($this->columns[$this->startImportFrom]);
        $header = [];

        foreach ($row as $cell) {
            if (! empty($cell)) {
                $header[] = $cell;
            }
        }

        return $header;
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

    public function getPreImportedRows(): array
    {
        $product_builder = new ProductBuilder();
        $product_query = $this->db->prepare("
                SELECT 
                rel.product_id as id,
                pi.sku,
                pi.product_title as name,
                rel.product_category_id,
                rel.import_id
                FROM {$this->db->prefix}woo_pre_import_relationships rel
                INNER JOIN {$this->db->prefix}woo_pre_import pi ON rel.import_id = pi.id
                WHERE 
                    `pi`.is_valid = 1 AND 
                    `pi`.is_imported = 0
        ");
        return $this->db->get_results($product_query, ARRAY_A);

    }

    protected function parseCategoriesField(string $categories): array
    {
        return ProductCatTaxonomy::parseCategoriesString($categories);
    }

    abstract public function readFile(): void;
}
