<?php

namespace ExtendedWoo\ExtensionAPI\import\models;

use ExtendedWoo\ExtensionAPI\models\taxonomies\ProductCatTaxonomy;

class RuleSet implements RuleSetBuilder
{
    private bool $isValid = false;
    private array $rules = [
        'non_empty' => [
            'id' => false,
            'sku' => false,
            'name' => false,
            'category_ids' => false,
        ],
        'unique' => [
            'id' => false,
            'sku' => false
        ],
        'wrong_formatted' => [
            'sku' => false,
        ],
    ];
    private array $row;

    public function setProductRow(array $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    public function checkForNonEmpty(): RuleSetBuilder
    {
        foreach ($this->rules['non_empty'] as $key => $rule) {
            if (! empty($this->row[$key]) && $this->row[$key] !== '') {
                $this->rules['non_empty'][$key] = true;
                $this->isValid = true;
            } else {
                $this->rules['non_empty'][$key] = false;
                $this->isValid = false;
            }
        }

        return $this;
    }

    public function checkForUnique(): RuleSetBuilder
    {
        global $wpdb;

        $product = wc_get_product_id_by_sku($this->row['sku']);
        if (! $product) {
            $this->rules['unique']['sku'] = true;
            $item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}woo_pre_import_relationships
                                WHERE `product_id` = %d", $this->row['id']));
            if (! $item_id) {
                $this->rules['unique']['id'] = true;
            } else {
                $this->rules['unique']['id'] = false;
                $this->isValid = false;
            }
        }

        if (! $this->isValid) {
            return $this;
        }

        $item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}woo_pre_import
                                WHERE `sku` = %s", $this->row['sku']));

        if (! $item_id && !$product) {
            $this->rules['unique']['sku'] = true;
            $item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}woo_pre_import_relationships
                                WHERE `product_id` = %d", $this->row['id']));
            if (! $item_id) {
                $this->rules['unique']['id'] = true;
                $this->isValid = true;
            }
        } else {
            $this->isValid = false;
        }

        return $this;
    }

    public function checkFormatting(string $format): RuleSetBuilder
    {
        if (!$this->isValid) {
            return  $this;
        }

        $matches = [];
        preg_match($format, trim($this->row['sku']), $matches);
        $this->isValid = (!empty($matches));
        $this->rules['wrong_formatted']['sku'] = $this->isValid;

        return $this;
    }

    public function checkCategory(): self
    {
        if (! $this->isValid) {
            return $this;
        }

        $cat = (ProductCatTaxonomy::parseCategoriesString($this->row['category_ids'])) ?? [];
        if (empty($cat)) {
            $this->isValid = $this->rules['non_empty']['category_ids'] = false;
            return $this;
        }

        $this->isValid = true;

        return $this;
    }

    public function getValidationStatus(): bool
    {
        return $this->isValid;
    }

    public function getResult(): array
    {
        return $this->rules;
    }
}
