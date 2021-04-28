<?php


namespace ExtendedWoo;

use DI\Container;
use ExtendedWoo\ExtensionAPI\controllers\AjaxController;
use ExtendedWoo\ExtensionAPI\menu\AdminMenu;
use ExtendedWoo\ExtensionAPI\Pages;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Kernel
 *
 * @package ExtendedWoo
 */
final class Kernel
{
    /**
     * @return $this
     */
    protected Request $request;
    protected Container $app;

    public function __construct(Container $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    public function init(): Kernel
    {
        $pages = new Pages();
        $ajaxController = new AjaxController();

        add_action('admin_menu', [(new AdminMenu()), 'initMenu'], 8);
        add_action('admin_init', [$this, 'install']);

        add_action('admin_menu', [$pages, 'menu'], 1);
        add_action('admin_init', [$pages, 'downloadExportFile']);

        add_action('wp_ajax_ext_do_ajax_product_export', [$ajaxController, 'productExport']);
        add_action('wp_ajax_ext_do_ajax_product_import', [$ajaxController, 'productImport']);
        add_action('wp_ajax_ext_do_ajax_upload_file', [$ajaxController, 'fileUploader']);
        add_action('wp_ajax_ext_do_ajax_product_remove', [$ajaxController, 'productRemove']);
        add_action('wp_ajax_ext_do_ajax_check_resolver_form', [$ajaxController, 'checkResolverForm']);

        return $this;
    }

    public function getApp(): Container
    {
        return $this->app;
    }

    /**
     * @return $this|Kernel
     */
    public function install(): Kernel
    {
        $dbExtension = $this->app->get('DBExtension');
        $dbExtension->init();

        return $this;
    }

    /**
     * @return Kernel
     */
    public function uninstall(): Kernel
    {
        $dbExtension = $this->app->get('DBExtension');
        $dbExtension->terminate();

        return $this;
    }
}
