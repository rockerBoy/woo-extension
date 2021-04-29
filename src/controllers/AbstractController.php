<?php


namespace ExtendedWoo\ExtensionAPI\controllers;


use Symfony\Component\HttpFoundation\Request;

abstract class AbstractController
{
    protected Request $request;
    protected string $import_views_path;

    public function __construct(Request $request)
    {
        wp_deregister_script('wc-product-import');

        $this->request           = $request;
        $this->import_views_path = __DIR__ . '/../../views/import/';
    }

    public function render(string $filename, array $params = []): string
    {
        return  include $this->import_views_path . 'base_tpl.php';
    }
}