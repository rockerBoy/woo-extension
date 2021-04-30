<?php


namespace ExtendedWoo\core\services;


use Psr\Container\ContainerInterface;

final class AdminPages
{
    private array $pages;
    private ?string $currentPage = null;
    private ContainerInterface $app;

    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    public function menu(): void
    {
        $this->pages = $this->app->get('adminPages');

        foreach ($this->pages as $page) {
            $this->registerPage($page);
        }
    }

    public function registerPage(array $page): void
    {
        $defaults = [
            'id'         => null,
            'parent'     => null,
            'title'      => '',
            'capability' => 'view_woocommerce_reports',
            'path'       => '',
            'icon'       => '',
            'position'   => null,
            'js_page'    => true,
        ];

        $parsed_options = wp_parse_args($page, $defaults);
        $controller = $this->app->get($parsed_options['controller']);

        if (is_null($parsed_options['parent'])) {
            add_menu_page(
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                [$controller, 'index'],
                $parsed_options['icon'],
                $parsed_options['position']
            );
        } else {
            $parent_path = $this->getPathFromId($page['parent']);
            $settings = [
                $parent_path,
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                [$controller, 'index']
            ];

            add_submenu_page(...$settings);
        }

        $this->connectPage($parsed_options);
    }

    public function getCurrentPage(): string
    {
        if (! did_action('current_screen')) {
            _doing_it_wrong(
                __FUNCTION__,
                esc_html__('Current page retrieval should be called on or after the `current_screen` hook.', 'woocommerce'),
                '0.16.0'
            );
        }

        return $this->currentPage;
    }

    public function connectPage(array $settings): void
    {
        if (! is_array($settings['title'])) {
            $settings['title'] = [$settings['title']];
        }

        $options = apply_filters('woocommerce_navigation_connect_page_options', $settings);

        $this->pages[$settings['id']] = $options;
    }

    private function getPathFromID(string $id): string
    {
        return $this->pages[$id]['path'] ?? $id;
    }
}