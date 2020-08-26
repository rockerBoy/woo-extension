<?php

namespace ExtendedWoo\ExtensionAPI\import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductExcelImporter
{
    private int $startTime;
    private array $columns = [];
    private string $fileName;
    private array $params;

    public function __construct(string $fileName, array $args = [])
    {
        $this->fileName = $fileName;
        $this->params = $args;
        $this->readFile();
    }

    public function getHeader(): array
    {
        return array_values(current($this->columns));
    }

    public function getSample(): array
    {
        return array_values(next($this->columns));
    }

    public function getImportSize(): int
    {
        return sizeof(array_values($this->columns));
    }

    public function getColumns(): array
    {
        return array_values($this->columns);
    }

    public function import(): array
    {
        $data = [
            'author_id' => get_current_user_id(),
            'imported' => [],
            'failed'   => [],
            'updated'  => [],
            'skipped'  => [],
        ];

        $this->startTime = time();

        foreach ($this->getColumns() as $index => $column) {
            $column = array_values($column);
            if ($column === $this->getHeader()) {
                continue;
            }
            $parsed_data = $this->parseRawColumn($column);
            $id = isset($parsed_data['id']) ? absint($parsed_data['id']): 0;
            $sku = $parsed_data['sku'] ?? '';
            $categories = $parsed_data['category_ids'] ?? '';
            $id_exists = false;
            $sku_exists = false;

            if ($id) {
                $product = wc_get_product($id);
                $id_exists = $product && 'importing' !== $product->get_status();
            }

            if ($sku) {
                $id_from_sku = wc_get_product_id_by_sku($sku);
                $product     = $id_from_sku ? wc_get_product($id_from_sku) : false;
                $sku_exists  = $product && 'importing' !== $product->get_status();
            }

            if (!$sku) {
                $data['failed'][$index] = $parsed_data;
                continue;
            }

            if ($categories) {
                $categories_ids = $this->parseCategoriesField($categories);

                if (empty($categories_ids)) {
                    $data['failed'][$index] = $parsed_data;
                    continue;
                }
            }
            $data['imported'][$index] = $parsed_data;
        }
        return $data;
    }


    private function readFile(): void
    {
        $spreadsheet = IOFactory::load($this->fileName);
        $this->columns = $spreadsheet
            ->getActiveSheet()
            ->toArray(null, false, true, true);
    }

    private function parseRawColumn(array $raw_data): array
    {
        $row = [];
        $mapping = $this->params['mapping']['to'];
        foreach ($raw_data as $index => $cell) {
            $key = $mapping[$index];
            $row[$key] = $cell;
        }

        return $row;
    }

    private function parseCategoriesField(string $categories): array
    {
        if (empty($categories)) {
            return [];
        }
        $row_terms  = $this->explodeValues($categories);
        $parsed_categories = [];

        foreach ($row_terms as $row_term) {
            $parent = null;
            $terms = array_map('trim', explode('>', $row_term));
            $total = sizeof($terms);

            foreach ($terms as $index => $term) {
                if (! current_user_can('manage_product_terms')) {
                    break;
                }

                if (!term_exists($term)) {
                    return [];
                } else {
                    $parsed_categories[] = term_exists($term);
                }
            }
        }

        return $parsed_categories;
    }

    private function explodeValues(string $value, string $separator = ','): array
    {
        $value = str_replace('\\,', '::separator::', $value);
        $values = explode($separator, $value);
        $values = array_map([$this, 'explodeValueFormatter'], $values);

        return $values;
    }

    private function explodeValueFormatter(string $value): string
    {
        return trim(str_replace('::separator::', ',', $value));
    }
    //TODO: Set start position (it will be an array of headers)
}
