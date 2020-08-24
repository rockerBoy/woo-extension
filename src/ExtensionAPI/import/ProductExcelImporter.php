<?php

namespace ExtendedWoo\ExtensionAPI\import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductExcelImporter
{
    private int $startRow = 0;
    private int $endRow = 0;
    private array $columns = [];
    private string $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $this->readFile();
    }

    private function readFile(): void
    {
        $spreadsheet = IOFactory::load($this->fileName);
        $this->columns = $spreadsheet
            ->getActiveSheet()
            ->toArray(null, false, true, true);
    }

    public function getHeader(): array
    {
        return array_values(current($this->columns));
    }

    public function getSample(): array
    {
        return array_values(next($this->columns));
    }

    //TODO: Set start position (it will be an array of headers)
}
