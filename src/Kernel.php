<?php


namespace ExtendedWoo;

use ExtendedWoo\Entities\Filters;
use ExtendedWoo\ExtensionAPI\ExtensionInstall;
use ExtendedWoo\ExtensionAPI\interfaces\ExtendedWooInterface;
use ExtendedWoo\ExtensionAPI\Pages;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Kernel
 *
 * @package ExtendedWoo
 */
final class Kernel implements ExtendedWooInterface
{
    /**
     * @return $this|ExtendedWooInterface
     */
    private Request $request;

    public function init(): ExtendedWooInterface
    {
        $this->request = Request::createFromGlobals();
        $pages = new Pages();
        $filters = new Filters();

        add_action('init', array($filters, 'initTaxonomies'));
        add_action('admin_init', array($this, 'install'));
        add_action('admin_menu', [$pages, 'menu'], 1);
        add_action('admin_init', array($pages, 'downloadExportFile'));
        add_action('wp_ajax_ext_do_ajax_product_export', array( $pages, 'doAjaxProductExport' ));
        return $this;
    }

    /**
     * @return $this|ExtendedWooInterface
     */
    public function install(): ExtendedWooInterface
    {
        ExtensionInstall::init();

        return $this;
    }

    /**
     * @return ExtendedWooInterface
     */
    public function uninstall(): ExtendedWooInterface
    {
        ExtensionInstall::uninstallTables();
        return $this;
    }

    public static function pluginUrl(): string
    {
        return untrailingslashit(plugins_url('/', EWOO_PLUGIN_FILE));
    }
}
