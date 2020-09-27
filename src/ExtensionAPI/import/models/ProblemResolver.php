<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

use ExtendedWoo\Entities\ProductBuilder;
use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\interfaces\import\ImportType;
use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;

final class ProblemResolver
{
    private array $dataRows;
    private array $mapping;
    private ImportType $strategy;
    public array $validationResult = [];
    private array $temporaryProductList = [];

    public function __construct(
        ImportType $strategy,
        array $dataRows = [],
        array $mapping_to = []
    ) {
        $this->dataRows = $dataRows;
        $this->mapping = $this->clearMapping($mapping_to);
        $this->strategy = $strategy;
        $this->validateRows($dataRows);
    }


    public function makeViewTable(): ?string
    {
        $table_output = '';
        $temp_products = $this->temporaryProductList;
        if (ProductsImportHelper::validateMapping($this->mapping)) {
            foreach ($temp_products as $index => $product) {
                $validation_flag = $this->validationResult[$index]['status'];
                $rel_id = $product->getRelationsID();

                if (! empty($this->validationResult)) {
                    if ($validation_flag === true) {
                        $table_output .= "<tr id='row-{$rel_id}' class='item-success hidden'>";
                    } else {
                        $table_output .= "<tr id='row-{$rel_id}' class='item-danger'>";
                    }

                    $input_set = [
                        'sku' => '<input type="text" class="edit-product-sku"
                         name="sku['.$rel_id.']" 
                         data-rel="'.$rel_id.'"
                         required />',
                        'category_ids' => '',
                    ];
                    $input_set['category_ids'] = '<select class="select_valid_category" 
                    name="category['.$rel_id.']" data-rel="'.$rel_id.'" required>';
                    $input_set['category_ids']  .= "<option value=''>----------</option>";

                    foreach (ProductsImportHelper::getCategories() as $cat) {
                        $input_set['category_ids'] .= "<option value='$cat->term_id'>$cat->name</option>";
                    }

                    $input_set['category_ids']  .= '</select>';

                    $meta_data = [
                        'id' => $product->getNewID(),
                        'sku' => $product->get_sku(),
                        'name' => ProductsImportHelper::makeExcerpt($product->get_name(), 0, 50),
                        'category_ids' => $product->getCategory(),
                    ];

                    foreach ($this->mapping as $item => $value) {
                        $item = (empty($meta_data[$value]))? $input_set[$value]: $meta_data[$value];
                        $table_output .= $this->makeRow($item);
                    }

                    if (! $validation_flag) {
                        $table_output .= $this->makeRow('<button class="button button-primary remove-item" data-rel="'.$rel_id.'" value="'.$meta_data['id'].'">Удалить продукт</button>');
                    } else {
                        $table_output .= $this->makeRow('');
                    }
                    $table_output .= '</tr>';
                }
            }
            return $table_output;
        }

        return null;
    }

    private function validateRows(array $raw_rows): void
    {
        $validation_statuses = [];
        $required_max = count($this->strategy->getColumns());

        foreach ($raw_rows as $index => $row) {
            $prepared_row = array_slice(array_values($row), 0, $required_max);
            $prepared_row = ProductsImportHelper::parseRow($prepared_row, $this->mapping);

            $validation_result = $this->strategy->validateColumnsData($prepared_row);
            $rules = $this->strategy->getRules();
            $validation_statuses[] = [
                'status' => $rules->getValidationStatus(),
                'data' => $validation_result,
            ];

            $this->setTempProduct($prepared_row, $rules->getValidationStatus());
        }

        $this->validationResult = $validation_statuses;
    }

    private function makeRow(string $content): string
    {
        $format = '<td>%s</td>';
        return sprintf($format, $content);
    }

    private function setTempProduct(array $raw_product_data, bool $is_valid = false): void
    {
        $builder = new ProductBuilder();
        $data = [
            'id' => $raw_product_data['id'] ?? '',
            'name' => $raw_product_data['name'] ?? '',
            'sku' => $raw_product_data['sku'] ?? '',
            'category_ids' => $raw_product_data['category_ids'] ?? '',

        ];
        $data['category_ids'] = ProductCatTaxonomy::parseCategoriesString($data['category_ids']);
        $data['category_ids'] = current($data['category_ids']);

        $temp_product = $builder
            ->setID($data['id'])
            ->setSKU($data['sku'])
            ->setName($data['name'])
            ->setCategory($data['category_ids'])
            ->setValidationFlag($is_valid)
            ->makeProduct();

        $temp_product->saveTemporary();

        $this->temporaryProductList[] = $temp_product;
    }

    private function clearMapping(array $mapping_to): array
    {
        $cleaned_mapping = [];
        foreach ($mapping_to as $item) {
            if (! empty($item)) {
                $cleaned_mapping[] = $item;
            }
        }

        return $cleaned_mapping;
    }
}
