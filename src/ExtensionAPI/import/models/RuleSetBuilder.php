<?php


namespace ExtendedWoo\ExtensionAPI\import\models;

interface RuleSetBuilder
{
    public function checkForNonEmpty(): RuleSetBuilder;
    public function checkForUnique(): RuleSetBuilder;
    public function getResult(): array;
}
