<?php


namespace ExtendedWoo\ExtensionAPI\menu;

use ExtendedWoo\ExtensionAPI\import\DiscountsImportController;
use ExtendedWoo\ExtensionAPI\import\IDImportController;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\BrandsImportStrategy;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\IDImportStrategy;
use ExtendedWoo\ExtensionAPI\import\ImportTypeFactory;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\FullProductImportType;
use ExtendedWoo\ExtensionAPI\import\models\BrandsImportController;
use ExtendedWoo\ExtensionAPI\import\PriceImportController;
use ExtendedWoo\ExtensionAPI\import\ProductImporterController;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\ProductImportType;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\ProductPriceImportType;
use ExtendedWoo\ExtensionAPI\import\importing_strategies\ProductSalesImportType;
use ExtendedWoo\ExtensionAPI\import\SecondaryImportController;
use Symfony\Component\HttpFoundation\Request;

final class AdminMenu
{
    private Request $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    public function initMenu():void
    {
        add_action('admin_menu', array( $this, 'showMenu' ), 9);
        add_filter('menu_order', [$this, 'menuOrder']);
    }

    public function showMenu(): void
    {
        global $menu;

        if (current_user_can('edit_others_shop_orders')) {
            $menu[] = array( '', 'read', 'extra-separator', '', 'wp-menu-separator woocommerce' ); // WPCS: override ok.
        }

        add_menu_page(
            __('Импорт товаров', 'extendedwoo'),
            __('Импорт товаров', 'extendedwoo'),
            'manage_options',
            'excel_import',
            [$this, 'productsImportPage'],
            null,
            '55.6'
        );
        add_submenu_page(
            'excel_import',
            __('Вторичный импорт товаров', 'extendedwoo'),
            __('Вторичный импорт товаров', 'extendedwoo'),
            'manage_options',
            'excel_update_products',
            [$this, 'productsUpdatePage']
        );
        add_submenu_page(
            'excel_import',
            __('Импорт цен', 'extendedwoo'),
            __('Импорт цен', 'extendedwoo'),
            'manage_options',
            'excel_import_prices',
            [$this, 'productsPricingImportPage']
        );
        add_submenu_page(
            'excel_import',
            __('Импорт акций', 'extendedwoo'),
            __('Импорт акций', 'extendedwoo'),
            'manage_options',
            'excel_sales_import',
            [$this, 'productsSalesImportPage']
        );
        add_submenu_page(
            'excel_import',
            __('Импорт брендов', 'extendedwoo'),
            __('Импорт брендов', 'extendedwoo'),
            'manage_options',
            'excel_brand_import',
            [$this, 'productsBrandsImportPage']
        );
        add_submenu_page(
            'excel_import',
            __('Импорт ROST ID', 'extendedwoo'),
            __('Импорт ROST ID', 'extendedwoo'),
            'manage_options',
            'excel_id_import',
            [$this, 'productsIDImportPage']
        );
        add_submenu_page(
            'excel_import',
            __('Поиск изображений в медиатеке', 'extendedwoo'),
            __('Поиск изображений в медиатеке', 'extendedwoo'),
            'manage_options',
            'product_image_search',
            [$this, 'productsImagePage']
        );
    }

    public function productsImportPage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new ProductImporterController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(ProductImportType::class))
            ->dispatch();
    }

    public function productsUpdatePage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new SecondaryImportController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(FullProductImportType::class))
            ->dispatch();
    }

    public function productsPricingImportPage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new PriceImportController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(ProductPriceImportType::class))
            ->dispatch();
    }

    public function productsSalesImportPage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new DiscountsImportController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(ProductSalesImportType::class))
            ->dispatch();
    }

    public function productsBrandsImportPage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new BrandsImportController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(BrandsImportStrategy::class))
            ->dispatch();
    }

    public function productsIDImportPage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');
        $controller = new IDImportController($this->request);
        $controller
            ->setImportType(ImportTypeFactory::getImportType(IDImportStrategy::class))
            ->dispatch();
    }

    public function productsImagePage(): void
    {
        wp_localize_script(
            'wc-product-import',
            'wc_product_import_params',
            [
                'import_nonce'    => wp_create_nonce('wc-product-import'),
                'mapping'         => [
                    'from' => '',
                    'to'   => '',
                ]
            ]
        );
        wp_enqueue_script('wc-product-import');

        ?>
        <div class="woocommerce-progress-form-wrapper">
            <form method="post" class="wc-progress-form-content woocommerce-importer" enctype="multipart/form-data">
                <header>
                    <p><?= __('Поиск изображений товаров в медиатеке', 'extendedwoo')?></p>
                </header>
                <div class="wc-actions">
                    <?php

                    if (! empty($_POST['find_images'])) {
                        do_action('check_product_images');
                    }
                    ?>
                    <button class="button button-primary button-search" type="submit"
                            value="<?= __( 'Начать поиск', 'woocommerce' ) ?>" name="find_images"><?= __( 'Начать поиск', 'woocommerce' ) ?></button>
                    <?php wp_nonce_field( 'etx-xls-importer' ); ?>
                </div>
            </form>
        </div>
        <?php
    }

    public function menuOrder(array $menu_order): array
    {
        $current_menu_order = [];
        $separator = array_search('extra-separator', $menu_order, true);
        $wc_product = array_search('edit.php?post_type=product', $menu_order, true);

        foreach ($menu_order as $index => $item) {
            if ('extendedimport' === $item) {
                $current_menu_order[] = 'extra-separator';
                $current_menu_order[] = $item;
                $current_menu_order[] = 'edit.php?post_type=product';

                unset($menu_order[$separator], $menu_order[$wc_product]);
            } elseif ($item !== 'extra-separator') {
                $current_menu_order[] = $item;
            }
        }

        return $current_menu_order;
    }
}
