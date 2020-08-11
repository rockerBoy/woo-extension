<?php


namespace ExtendedWoo\ExtensionAPI;

interface ExporterInterface
{
    public function getDefaultColumnNames(): array;
    public function setProductCategoryToExport(string $category): self;
    public function setLimit(int $limit): self;
    public function getLimit(): int;
    public function getPage(): int;
    public function enableMetaExport(bool $enable_meta_export): self;
    public function setProductTypesToExport(array $types): self;
}