<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

use ExtendedWoo\Entities\ProductBuilder;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\import\ExcelImportProductItemBuilder;
use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;

final class ProblemResolver
{
    private array $dataRows;
    private array $mapping;
    private bool $showErrorsOnly = true;

    public function __construct(array $dataRows, array $mapping_to, $showErrorsOnly = true)
    {
        $this->dataRows = $dataRows;
        $this->mapping = $mapping_to;
        $this->showErrorsOnly = $showErrorsOnly;
    }

    private function checkUniqueRows(string $sku): bool
    {
        return !in_array($sku, $this->uniqueRows, true);
    }

    public function makeViewTable(): string
    {
        $table_output = '';
        $products = [];
        $categories = ProductsImportHelper::getCategories();
        if (ProductsImportHelper::validateMapping($this->mapping)) {
            $uniqueSKU = ProductsImportHelper::validateRows($this->dataRows, $this->mapping);
            foreach ($this->dataRows as $key => $row) {
                $prepared_row = array_values($row);
                if (! empty($prepared_row)) {
                    $start_from = 1;
                    $parsed_row = ProductsImportHelper::parseRow($row, $this->mapping);
                    $product_builder = new ProductBuilder();
                    if ($uniqueSKU[$key]) {
                        if ($this->showErrorsOnly) {
                            continue;
                        }
                        $table_output .= '<tr>';
                    } else {
                        $table_output .= '<tr class="item-danger">';
                    }

                    foreach ($prepared_row as $index => $item) {
                        $key = $this->mapping[$index] ?? '';
                        $item = ($item) ?? '';
                        $make_excerpt = true;
                        switch ($key) {
                            case 'id':
                                if ($item && $item > 0) {
//                                    $product_builder->setID((int)$item);
                                    $make_excerpt = false;
                                }
                                break;
                            case 'sku':
                                if (!empty($item)) {
//                                    $product_builder->setSKU($item);
                                } else {
                                    $item = '<input type="text" name="sku['.$index.']" required />';
                                }
                                break;
                            case 'name':
//                                $product_builder->setName($item);
                                break;
                            case 'category_ids':
                                $cat = (ProductCatTaxonomy::parseCategoriesString($item)) ?? [];
                                $cat_list = ProductsImportHelper::getCategories();
//                                $product_builder->setName($item);
                                if (empty($cat)) {
                                    $item = '<select name="category['.$index.']" required>';
                                    $item .= "<option value=''>----------</option>";
                                    foreach ($cat_list as $cat) {
                                        $item .= "<option value='$cat->term_id'>$cat->name</option>";
                                    }
                                    $item .= '</select>';
                                    $make_excerpt = false;
                                }
                                break;
                        }

                        $table_output .= '<td>';
                        if ($make_excerpt) {
                            $table_output .= ProductsImportHelper::makeExcerpt($item, 0, 50);
                        } else {
                            $table_output .= $item;
                        }
                        $table_output .= '</td>';
                    }
                    $table_output .= '</tr>';

                }
            }
            return $table_output;
        }

        return false;
    }
}
