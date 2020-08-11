<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\Entities\CategoryFiltersTable;
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
    private static $db;
    /**
     * @var array $tables
     */
    private static $tables = [];

    public static function init(): void
    {
        global $wpdb;

        self::$db = $wpdb;
        self::$tables = [
            new PreImportTable(self::$db),
            new CategoryFiltersTable(self::$db)
        ];
        self::registerAssets();
        self::installTables();
    }

    public static function registerAssets(): void
    {
        $assets = new Assets();
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

    private static function registerStyles()
    {
    }
    private static function registerScripts(): void
    {
    }
}
