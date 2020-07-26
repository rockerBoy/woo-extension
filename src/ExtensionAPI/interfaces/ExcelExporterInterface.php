<?php

namespace ExtendedWoo\ExtensionAPI\interfaces;

interface ExcelExporterInterface
{
    public function setFilters(array $filters = []): self;

    public function getProducts(): array;
}
