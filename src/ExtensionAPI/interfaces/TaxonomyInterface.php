<?php


namespace ExtendedWoo\ExtensionAPI\interfaces;

interface TaxonomyInterface
{
    public function addTaxonomyFields(): void;
    public function editTaxonomyFields($term): void;
    public function saveTaxonomyFields(int $term_id, string $tt_id = '', string $taxonomy = ''): void;
}
