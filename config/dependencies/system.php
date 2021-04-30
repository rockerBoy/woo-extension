<?php
return [
    'Kernel' => static function (\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\Kernel($container, $container->get('Request'));
    },
    'Assets' => static function (\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\ExtensionAPI\Assets($container);
    },
    'Request' => DI\factory(function () {
        return new Symfony\Component\HttpFoundation\Request();
    }),
    'Response' => DI\factory(function () {
        return new Symfony\Component\HttpFoundation\Response();
    }),
    'ReaderEntityFactory' => DI\factory(function () {
        return Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createXLSXReader();
    }),
    'WPDB' => DI\factory(function (){
        global $wpdb;

        return $wpdb;
    }),
    'PreImportTable' => static function(\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\core\db\tables\PreImportTable($container->get('WPDB'));
    },
    'RelationsTable' => static function(\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\core\db\tables\RelationsTable($container->get('WPDB'));
    },
    'ImportedRelationsTable' => static function(\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\core\db\tables\ImportedRelationsTable($container->get('WPDB'));
    },
    'CategoryFiltersTable' => static function(\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\core\db\tables\CategoryFiltersTable($container->get('WPDB'));
    },
    'DBExtension' => static function(\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\core\db\DBExtension([
                                                        $container->get('PreImportTable'),
                                                        $container->get('RelationsTable'),
                                                        $container->get('ImportedRelationsTable'),
                                                        $container->get('CategoryFiltersTable'),
                                                    ]);
    },
    'pluginURL' => untrailingslashit(plugins_url('/', EWOO_PLUGIN_FILE)),
    'localizeImport' => static function() {
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
    },
    'adminPages' => [
        [
            'id'        => 'product_excel_exporter',
            'parent'    => 'edit.php?post_type=product',
            'screen_id' => 'product_page_product_exporter',
            'title'     => __('Export Products', 'woocommerce'),
            'path'     => 'product_excel_exporter',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ], [
            'id'        => 'product_category_filters',
            'parent'    => 'edit.php?post_type=product',
            'screen_id' => 'product_category_filters',
            'title'     => __('Настройка фильтров категорий', 'woocommerce'),
            'path'     => 'product_category_filters',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Импорт товаров', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_import',
            'icon' => null,
            'position' => '55.6',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Вторичный импорт товаров', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_update_products',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Импорт цен', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_import_prices',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Импорт акций', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_sales_import',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Импорт брендов', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_sales_import',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Импорт ROST ID', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'excel_id_import',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
        [
            'title'     => __('Поиск изображений в медиатеке', 'extendedwoo'),
            'capability' => 'manage_woocommerce',
            'path' => 'product_image_search',
            'icon' => null,
            'parent' => 'excel_import',
            'controller' => 'ExtendedWoo\controllers\import\PrimaryImportController',
        ],
    ]
];