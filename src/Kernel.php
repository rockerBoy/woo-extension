<?php


namespace ExtendedWoo;

use ExtendedWoo\ExtensionAPI\ExtensionInstall;
use ExtendedWoo\ExtensionAPI\interfaces\ExtendedWooInterface;
use ExtendedWoo\ExtensionAPI\Pages;
use Exteption;

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
    public function init(): ExtendedWooInterface
    {
        $pages = new Pages();
        add_action('admin_init', array($this, 'install'));
        add_action('admin_menu', [$pages, 'menu'], 1);

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
    }
}
