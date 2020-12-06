<?php

declare(strict_types=1);

namespace ExtendedWoo\ExtensionAPI\interfaces\export\export;


use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExcelExportFactory
{
    private const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @psalm-param callable(\Box\Spout\Writer\XLSX\Writer): void $dataWriter
     *
     * @param string   $filename
     * @param callable $dataWriter
     *
     * @return Response
     */
    public function createResponse(string $filename, callable $dataWriter): Response
    {
        $response = new StreamedResponse(static function () use ($dataWriter): void {
            $tempFile = tempnam(sys_get_temp_dir(),'excel_export');
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($tempFile);

            $dataWriter($writer);

            $writer->close();
        });

        $disposition = HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', self::MIME_TYPE);

        return $response;
    }

}