<?php


namespace ExtendedWoo\ExtensionAPI;

use \wpdb;
use ExtendedWoo\Entities\CategoryFiltersTable;
use ExtendedWoo\Entities\Filters;
use ExtendedWoo\Entities\PreImportTable;
use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;

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
            new CategoryFiltersTable(self::$db)
        ];

        self::installTables();

        $filters = (new Filters())->setCatTable(next(self::$tables));
        $product_cat = new ProductCatTaxonomy($wpdb, $filters);
        $assets = new Assets();

        add_action('admin_enqueue_scripts', [$assets, 'adminStyles']);
        add_action('admin_enqueue_scripts', [$assets, 'adminScripts']);
//        add_action('product_cat_add_form_fields', array( $product_cat, 'addTaxonomyFields' ));
        add_action('product_cat_edit_form_fields', array( $product_cat, 'editTaxonomyFields' ));
        add_action('created_term', array( $product_cat, 'saveTaxonomyFields' ), 10, 3);
        add_action('edit_term', array( $product_cat, 'saveTaxonomyFields' ), 10, 3);
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
