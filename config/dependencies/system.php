<?php

return [
    'Kernel' => static function (\Psr\Container\ContainerInterface $container) {
        return new \ExtendedWoo\Kernel($container, $container->get('Request'));
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
    'pluginURL' => untrailingslashit(plugins_url('/', EWOO_PLUGIN_FILE))
];