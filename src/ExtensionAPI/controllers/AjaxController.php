<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\controllers;


use DateTimeImmutable;
use ExtendedWoo\Entities\Products;
use ExtendedWoo\ExtensionAPI\interfaces\export\models\export\ExcelExport;
use ExtendedWoo\ExtensionAPI\interfaces\export\models\export\Exporter;
use ExtendedWoo\ExtensionAPI\interfaces\export\Pages;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController
{
    /**
     * @var Request $request
     */
    private Request $request;
    private array $referrers = [
        'export' => 'ewoo-product-export',
        'import' => 'ewoo-product-import',
    ];


    public function __construct()
    {
        if (! defined('ABSPATH')) {
            exit;
        }

        $this->request = Request::createFromGlobals();
    }

    public function productExport(): void
    {
        $request = $this->request;
        $ref = $this->referrers['export'];
        check_ajax_referer($ref, 'security');
        $date = (new DateTimeImmutable("now"))->format('d-m-Y');
        $columnsToExport = ($request->get('selected_columns'))?:[];
        $categoriesToExport = ($request->get('export_category'))?:[];
        $export_all = $request->get('export_all');
        $export_without_images = $request->get('export_without_images');

        $exportedFiles = [];
        foreach ( $categoriesToExport as $category) {
            $excelGenerator = new ExcelExport("Product_Export_{$category}_{$date}.xls");
            $products = new Products();
            $exporter = new Exporter($excelGenerator, $products);
            $exporter
                ->setColumnsToExport(wp_unslash($columnsToExport))
                ->setCategoriesToExport(wp_unslash($category))
                ->setExportAll($export_all)
                ->setExportWithoutImages($export_without_images)
                ->generate();
            $exportedFiles[] = $excelGenerator->getFileName();
        }

        $query_args = apply_filters(
            'ewoo_export_get_ajax_query_args',
            array(
                'nonce'    => wp_create_nonce('product-xls'),
                'action'   => 'download_product_xls',
                'files' => $exportedFiles,
            )
        );

        wp_send_json_success(
            array(
                'step'       => 'done',
                'percentage' => 100,
                'url'        => add_query_arg($query_args, admin_url(Pages::PAGE_ROOT.'&page=product_excel_exporter')),
            )
        );
    }

}