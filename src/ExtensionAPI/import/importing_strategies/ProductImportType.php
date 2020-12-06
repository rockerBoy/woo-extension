<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import\importing_strategies;

use ExtendedWoo\ExtensionAPI\interfaces\export\import\models\RuleSet;
use ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import\ImportType;

class ProductImportType implements ImportType
{
    private array $basicColumns;
    private object $rules;

    public function __construct(array $basicColumns)
    {
        $this->basicColumns = $basicColumns;
        $this->rules = new RuleSet();
    }

    public function getColumns(): array
    {
        return array_merge($this->basicColumns, [
            'category_ids'       => __('Categories', 'woocommerce'),
        ]);
    }

    public function getRules():object
    {
        return $this->rules;
    }

    public function validateColumnsData(array $columns_data): array
    {
        return $this->rules
            ->setProductRow($columns_data)
            ->checkForNonEmpty()
            ->checkCategory()
            ->checkFormatting('/\d+\.\d+\.\d+/')
            ->checkForUnique()
            ->getResult();
    }
}
