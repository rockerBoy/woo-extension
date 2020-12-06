<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import\models;

use ExtendedWoo\Entities\Product;
use ExtendedWoo\Entities\ProductBuilder;
use ExtendedWoo\ExtensionAPI\interfaces\export\helpers\ProductsImportHelper;
use ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import\ImportType;
use ExtendedWoo\ExtensionAPI\interfaces\export\taxonomies\ProductCatTaxonomy;

final class ProblemResolver
{
    private array $dataRows;
    private array $mapping;
    private ImportType $strategy;
    public array $validationResult = [];
    private array $temporaryProductList = [];
    private string $filename;
    private string $uuid;

    public function __construct(
        ImportType $strategy,
        array $dataRows = [],
        array $mapping_to = [],
        string $filename = ''
    ) {
        $this->dataRows = $dataRows;
        $this->mapping = $this->clearMapping($mapping_to);
        $this->strategy = $strategy;
        $this->filename = $filename;
        $this->validateRows($dataRows);
    }


    public function makeViewTable(): ?string
    {
        $table_output = '';
        $temp_products = $this->temporaryProductList;

        if (ProductsImportHelper::validateMapping($this->mapping)) {
            $unique_rel_ids = [];
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
                        'name' => '<input type="text" class="edit-product-name"
                         name="name['.$rel_id.']" 
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
                    $id = ($product->getNewID() > 0) ? $product->getNewID(): $product->getRealID();
                    $is_unique = $this->validationResult[$index]['data']['unique'];

                    if (!$is_unique['id'] || !$is_unique['sku']) {
                        foreach ($input_set as $key => $item) {
                            $input_set[$key] = '';
                        }
                    }

                    $meta_data = [
                        'id' => $id,
                        'sku' => $product->get_sku(),
                        'name' => ProductsImportHelper::makeExcerpt($product->get_name(), 0, 50),
                        'category_ids' => $product->getCategory(),
                    ];

                    foreach ($this->mapping as $item => $value) {
                        if (isset($meta_data[$value])) {
                            $item = (empty($meta_data[$value]))? $input_set[$value]: $meta_data[$value];
                        } elseif (isset($input_set[$value])) {
                            $item = (empty($meta_data[$value]))? $input_set[$value]: $meta_data[$value];
                        }

                        $table_output .= $this->makeRow((! empty($item))? $item:'');
                    }

                    $error_msg = '';
                    if (! $validation_flag) {
                        $table_output .= $this->makeRow('<button class="button button-primary remove-item" data-rel="'.$rel_id.'" value="'.$meta_data['id'].'">Удалить продукт</button>');
//                        $is_unique = $this->validationResult[$index]['data']['unique'];
                        $is_not_empty = $this->validationResult[$index]['data']['non_empty'];
                        $is_format_correct = $this->validationResult[$index]['data']['wrong_formatted'];

                        if (! $is_unique['sku'] || ! $is_unique['id']) {
                            $error_msg = __('Такой товар уже существует', 'extendedwoo');
                        }

                        $field_names = [
                            'id'                => 'ID',
                            'name'              => 'Имя',
                            'category_ids'      => 'Категория',
                            'sku'               => 'Артикул',
                        ];


                        if (empty($error_msg)) {
                            foreach ($is_not_empty as $field => $value) {
                                if (false === $value) {
                                    $error_msg = sprintf('Поле %s не задано', $field_names[$field]);
                                    break;
                                }
                            }
                        }
                    }

                    $table_output .= $this->makeRow($error_msg);
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

        $this->uuid = uniqid('import_'.date('Y-m-d').'_', true);
        $products = new Product();

        $products->clearDuplicatedRelations();

        foreach ($raw_rows as $index => $row) {
            $prepared_row = array_slice(array_values($row), 0, $required_max);
            $prepared_row = ProductsImportHelper::parseRow($prepared_row, $this->mapping);

            if (! is_numeric($prepared_row['id'])) {
                continue;
            }

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
        $isExists = false;

        if (!empty($data['sku'])) {
            $isExists = wc_get_product_id_by_sku($data['sku']);
        }

        if (! $isExists) {
            $temp_product = $builder
            ->setID($data['id'])
            ->setName($data['name'])
            ->setSKU($data['sku'])
            ->setCategory($data['category_ids'])
            ->setValidationFlag($is_valid)
            ->makeProduct();
        } else {
            $temp_product = new Product($isExists);
        }

        $temp_product
            ->setFilename($this->filename)
            ->setUUID($this->uuid)
            ->saveTemporary();

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
