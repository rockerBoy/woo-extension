<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import\models;

interface RuleSetBuilder
{
    public function checkForNonEmpty(): RuleSetBuilder;
    public function checkForUnique(): RuleSetBuilder;
    public function getResult(): array;
}
