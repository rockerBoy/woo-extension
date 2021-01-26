<?php


namespace ExtendedWoo\ExtensionAPI;

use \wpdb;
use ExtendedWoo\Entities\CategoryFiltersTable;
use ExtendedWoo\Entities\Filters;
use ExtendedWoo\Entities\PreImportTable;

/**
 * Class ExtensionInstall
 *
 * @package ExtendedWoo\ExtensionAPI
 * @class ExtensionInstall
 */
final class ExtensionInstall
{
    /**
     * @obj wpdb $db
     */
    private static wpdb $db;
    /**
     * @var array $tables
     */
    private static array $tables;

    public static function init(): void
    {
        global $wpdb;

        self::$db = $wpdb;
        self::$tables = [
            new PreImportTable(self::$db),
            new CategoryFiltersTable(self::$db),
        ];

        self::installTables();
        (new Filters())->setCatTable(next(self::$tables));
        $assets = new Assets();

        add_action('admin_enqueue_scripts', [$assets, 'adminStyles']);
        add_action('admin_enqueue_scripts', [$assets, 'adminScripts']);
        add_action('check_product_images', [$assets, 'findProductImages']);
    }

    /**
     * Creation of the additional database tables
     * such as:
     * - woo_pre_import
     * - woo_woo_pre_import_relationships
     * - woo_category_filters
     */
    public static function installTables(): void
    {
        foreach (self::$tables as $table) {
            $table->createTable();
        }
    }

    /**
     * Removing of an additional tables after plugin uninstalling
     */
    public static function uninstallTables(): void
    {
        foreach (self::$tables as $table) {
            $table->dropTable();
        }
    }
}
