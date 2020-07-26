<?php


namespace ExtendedWoo\ExtensionAPI;

use ExtendedWoo\ExtensionAPI\interfaces\PageInterface;

final class Pages implements PageInterface
{
    public const PAGE_ROOT = 'edit.php?post_type=product';
    /**
     * @var array $pages
     */
    private $pages = [];
    /**
     * Current page ID (or false if not registered with this controller).
     *
     * @var string|null $current_page
     */
    private $current_page = null;

    /**
     * @param array $options
     */
    public function __construct(array $pageList = [])
    {
        add_action('admin_menu', [$this, 'menu'], 1);
    }

    public function menu()
    {
        $pages =  [
            [
                'id'        => 'product_excel_exporter',
                'parent'    => self::PAGE_ROOT,
                'screen_id' => 'product_page_product_exporter',
                'title'     => __('Export Products', 'woocommerce'),
                'path'     => 'product_excel_exporter',
            ],
            [
                'id'        => 'woocommerce-import-products',
                'parent'    => self::PAGE_ROOT,
                'screen_id' => 'product_page_product_importer',
                'title'     => __('Import Products', 'woocommerce'),
                'path'     => 'product_excel_importer',
            ],
        ];

        foreach ($pages as $page) {
            $this->registerPage($page);
        }
    }
    public function connectPage($options): void
    {
        if (! is_array($options['title'])) {
            $options['title'] = array( $options['title'] );
        }

        /**
         * Filter the options when connecting or registering a page.
         *
         * Use the `js_page` option to determine if registering.
         *
         * @param array $options {
         *   Array describing the page.
         *
         *   @type string       id           Id to reference the page.
         *   @type string|array title        Page title. Used in menus and breadcrumbs.
         *   @type string|null  parent       Parent ID. Null for new top level page.
         *   @type string       screen_id    The screen ID that represents the connected page.
         *                                   (Not required for registering).
         *   @type string       path         Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
         *   @type string       capability   Capability needed to access the page.
         *   @type string       icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
         *   @type int          position     Menu item position.
         *   @type boolean      js_page      If this is a JS-powered page.
         * }
         */
        $options = apply_filters('woocommerce_navigation_connect_page_options', $options);

        // @todo check for null ID, or collision.
        $this->pages[ $options['id'] ] = $options;
    }

    /**
     * @param array $options {
     *   @type string      id           Id to reference the page.
     *   @type string      title        Page title. Used in menus and breadcrumbs.
     *   @type string|null parent       Parent ID. Null for new top level page.
     *   @type string      path         Path for this page, full path in app context; ex /analytics/report
     *   @type string      capability   Capability needed to access the page.
     *   @type string      icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
     *   @type int         position     Menu item position.
     * }
     */
    public function registerPage($options): void
    {
        $defaults = array(
            'id'         => null,
            'parent'     => null,
            'title'      => '',
            'capability' => 'view_woocommerce_reports',
            'path'       => '',
            'icon'       => '',
            'position'   => null,
            'js_page'    => true,
        );
        $options = wp_parse_args($options, $defaults);

        if (0 !== strpos($options['path'], self::PAGE_ROOT)) {
//            $options['path'] = self::PAGE_ROOT . '=' . $options['path'];
        }
        if (is_null($options['parent'])) {
            add_menu_page(
                $options['title'],
                $options['title'],
                $options['capability'],
                $options['path'],
                array( __CLASS__, 'page_wrapper' ),
                $options['icon'],
                $options['position']
            );
        } else {
            $parent_path = $this->getPathFromId($options['parent']);
            // @todo check for null path.
            add_submenu_page(
                $parent_path,
                $options['title'],
                $options['title'],
                $options['capability'],
                $options['path'],
                array( __CLASS__, 'viewPage' )
            );
        }

        $this->connectPage($options);
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
        if (is_null($this->current_page)) {
            $this->determineCurrentPage();
        }
        return $this->current_page;
    }

    public function determineCurrentPage(): void
    {
        $current_url = '';
        $current_screen_id = get_current_screen();
    }

    public function getPathFromId($id)
    {
        if (isset($this->pages[ $id ]) && isset($this->pages[ $id ]['path'])) {
            return $this->pages[ $id ]['path'];
        }
        return $id;
    }

    public function productExporter()
    {
    }

    public function viewPage()
    {
        echo '<pre>';
        var_dump(get_current_screen());
        echo '</pre>';
    }
}

//TODO Import page
//TODO Export page
