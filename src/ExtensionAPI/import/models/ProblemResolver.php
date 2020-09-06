<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

use ExtendedWoo\Entities\ProductBuilder;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\ExcelImportProductItemBuilder;

final class ProblemResolver
{
    private array $dataRows;
    private array $mapping;
    public function __construct(array $dataRows, array $mapping_to)
    {
        $this->dataRows = $dataRows;
        $this->mapping = $mapping_to;
    }

    private function checkUniqueRows(string $sku): bool
    {
        return !in_array($sku, $this->uniqueRows, true);
    }

    public function makeViewTable(): string
    {
        $table_output = '';
        $products = [];

        if (ProductsImportHelper::validateMapping($this->mapping)) {
            $uniqueSKU = ProductsImportHelper::validateRows($this->dataRows, $this->mapping);

            foreach ($this->dataRows as $key => $row) {
                $prepared_row = ProductsImportHelper::validateRow($row);
                if (! empty($prepared_row)) {
                    $start_from = 1;
                    $parsed_row = ProductsImportHelper::parseRow($row, $this->mapping);
                    $product_builder = new ProductBuilder();
                    if ($uniqueSKU[$key]) {
                        $table_output .= '<tr>';
                    } else {
                        $table_output .= '<tr class="item-danger">';
                    }

                    foreach ($prepared_row as $index => $item) {
                        $key = $this->mapping[$index] ?? '';
                        $item = ($item) ?? '';
                        switch ($key) {
                            case 'id':
                                if ($item && $item > 0) {
                                    $product_builder->setID((int)$item);
                                }
                                break;
                            case 'sku':
                                dump(($item && $item !== ''));
                                if ($item && $item !== '') {
                                    $product_builder->setSKU($item);
                                } else {
                                    $item = '<input type="text"/>';
                                }
                                break;
                            case 'name':
                                $product_builder->setName($item);
                                break;
                        }
                        $table_output .= '<td>';
                        $table_output .= ProductsImportHelper::makeExcerpt($item, 0, 50);
                        $table_output .= '</td>';
                    }
                    $table_output .= '</tr>';

                    $product = $product_builder->makeProduct();
                    $products[] = $product;
                }
            }
            return $table_output;
        }

        return false;
    }
}
