<?php


namespace ExtendedWoo\ExtensionAPI\models\export;


use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\XLSX\Writer;
use ExtendedWoo\ExtensionAPI\interfaces\export\export\ExcelExportFactory;
use Symfony\Component\HttpFoundation\Response;

final class ExcelExportAction
{
    public function __invoke( $export, ExcelExportFactory $factory): Response
    {

        return $factory->createResponse('export.xlsx', static function (Writer $writer) use ($export): void {
            $writer->getCurrentSheet()->setName('Ferzo export');

            foreach ($export as $values) {
                $writer->addRow(WriterEntityFactory::createRowFromArray($values));
            }
        });
    }
}