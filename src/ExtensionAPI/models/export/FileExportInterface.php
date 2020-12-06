<?php


namespace ExtendedWoo\ExtensionAPI\models\export;

interface FileExportInterface
{
    public function setFileName(string $fileName): FileExportInterface;
    public function getFileName();
    public function setRowData(array $data): FileExportInterface;
    public function makeFile(): void;
}
