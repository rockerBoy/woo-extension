<?php


namespace ExtendedWoo\ExtensionAPI\export;

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ExcelExport implements FileExportInterface
{
    private string $file_name = 'woocommerce_product_export.xlsx';
    private array $row_data = [];
    private array $columnNames = [];

    public function __construct(string $file_name = '')
    {
        if (isset($file_name)) {
            $this->setFileName($file_name);
        } else {
            $this->setFileName($this->file_name);
        }
    }

    public function setColumnNames(array $column_names = []): self
    {
        $this->columnNames = $column_names;

        return $this;
    }

    public function setFileName(string $fileName): FileExportInterface
    {
        $this->file_name = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }

    public function makeFile(): void
    {
        Settings::setLocale('ru');
        $data = $this->row_data;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $file_path = $this->getFilePath();
        $writer = new Xls($spreadsheet);
        $writer->save($file_path);
    }

    public function setRowData(array $data): FileExportInterface
    {
        $this->row_data = $data;
        return $this;
    }

    public function sendFileToUser(): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$this->file_name.'"');
        $content = file_get_contents($this->getFilePath());
        @unlink($this->getFilePath());
        exit($content);
    }


    private function getFilePath(): string
    {
        $uploads = wp_upload_dir();

        return trailingslashit($uploads['basedir']) . $this->getFileName();
    }
}
