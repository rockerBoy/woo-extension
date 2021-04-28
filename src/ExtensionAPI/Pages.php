<?php


namespace ExtendedWoo\ExtensionAPI;

use DateTimeImmutable;
use ExtendedWoo\ExtensionAPI\interfaces\PageInterface;
use ExtendedWoo\ExtensionAPI\models\export\ExcelExport;
use Symfony\Component\HttpFoundation\Request;

final class Pages implements PageInterface
{
    public const PAGE_ROOT = 'edit.php?post_type=product';

    /**
     * @var Request $request
     */
    private Request $request;
    /**
     * @var array $pages
     */
    private $pages = [];
    /**
     * Current page ID (or false if not registered with this controller).
     *
     * @var string|null $current_page
     */
    private ?string $current_page = null;
    private \wpdb $db;

    public function __construct()
    {
        global $wpdb;

        $this->request = Request::createFromGlobals();
        $this->db = $wpdb;
    }

    public function menu(): void
    {
        $this->registerPage([
            'id'        => 'product_excel_exporter',
            'parent'    => self::PAGE_ROOT,
            'screen_id' => 'product_page_product_exporter',
            'title'     => __('Export Products', 'woocommerce'),
            'path'     => 'product_excel_exporter',
        ]);
        $this->registerPage([
            'id'        => 'product_category_filters',
            'parent'    => self::PAGE_ROOT,
            'screen_id' => 'product_category_filters',
            'title'     => __('Настройка фильтров категорий', 'woocommerce'),
            'path'     => 'product_category_filters',
        ]);
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
    public function registerPage(array $options): void
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
        $parsed_options = wp_parse_args($options, $defaults);

        if (is_null($parsed_options['parent'])) {
            add_menu_page(
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                array( __CLASS__, 'page_wrapper' ),
                $parsed_options['icon'],
                $parsed_options['position']
            );
        } else {
            $parent_path = $this->getPathFromId($options['parent']);
            // @todo check for null path.
            add_submenu_page(
                $parent_path,
                $parsed_options['title'],
                $parsed_options['title'],
                $parsed_options['capability'],
                $parsed_options['path'],
                [$this, 'viewPage']
            );
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

        return $this->current_page;
    }

    private function getPathFromId($id)
    {
        if (isset($this->pages[$id], $this->pages[$id]['path'])) {
            return $this->pages[ $id ]['path'];
        }
        return $id;
    }


    public function downloadExportFile(): void
    {
        $request = $this->request;
        $action = $request->get('action');
        $nonce = $request->get('nonce');
        $filename = $request->get('filename');

        $date = (new DateTimeImmutable("now"))->format('d-m-Y');
        if ((! empty($action) && ! empty($nonce)) && wp_verify_nonce(wp_unslash($nonce), 'product-xls') &&
            wp_unslash($action) === 'download_product_xls' && ! empty($filename)) {
            $excelGenerator = new ExcelExport($filename);
            $excelGenerator->sendFileToUser();
        }
    }
    
    public function viewPage(): void
    {
        $prefix = 'product_page_';
        $current = get_current_screen();
        $settings = [];

        foreach ($this->pages as $page) {
            if ($prefix.$page['id'] === $current->base) {
                $settings = $page;
            }
        }

        $viewStr = explode('_', $settings['path']);
        array_walk($viewStr, static function (&$val) {
            $val = ucfirst($val);
        });
        $viewStr = lcfirst(implode('', $viewStr));
        echo $this->$viewStr();
    }

    private function productExcelExporter(): string
    {
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_script('ewoo-product-export');
        wp_localize_script(
            'ewoo-product-export',
            'ewoo_product_export_params',
            array(
                'export_nonce' => wp_create_nonce('ewoo-product-export'),
            )
        );

        if (empty($_GET['files']) && empty($_GET['action']) && empty($_GET['filename'])) {
            $uploads = wp_upload_dir();
            $path = trailingslashit($uploads['basedir']);
            
            foreach (scandir($path) as $file) {
                if (false !== strpos($file, 'Product_Export_')) {
                    @unlink($path.$file);
                }
            }
        }
        ob_start();
        require __DIR__.'/../views/export/admin-page-product-export.php';

        return ob_get_clean();
    }

    protected function getCategoryAttributes(int $category_id): array
    {
        $wpdb = $this->db;
        $result = [];
        $attributes = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woo_category_attributes
                WHERE attribute_category_id = %d", $category_id );

        $queried = $wpdb->get_results($attributes);

        if (false === $queried) {
            return $result;
        }

        foreach ($queried as $attr) {
            $result[] = $attr->attribute_name;
        }

        return $result;
    }

    protected function makeSelectField(string $id, array $options, array $selected_options)
    {
        $cat_atts = (!empty($selected_options[$id])) ? $selected_options[$id] : [];

        ob_start();
        ?>

        <select name="attributes[<?= $id ?>][]"
                class="woocommerce-exporter-category wc-enhanced-select"
                style="width:100%; max-width: 200px !important;" multiple data-placeholder="<?php esc_attr_e( 'Выберите аттрибуты', 'woocommerce' ); ?>">
            <?php
            foreach ( $options as $key => $name ) {
                $active = (! empty($cat_atts) && in_array($name, $cat_atts)) ? 'selected="selected"': '';
                echo '<option value="' . esc_attr( $key ) . '" '.$active.'>' . esc_html( $name ) . '</option>';
            }
            ?>
        </select>
        <?php
        $select = ob_get_contents();
        ob_end_clean();

        return $select;
    }

    private function productCategoryFilters()
    {
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_script('ewoo-product-category-filters');
        wp_localize_script(
            'ewoo-product-category-filters',
            'ewoo_product_category_filters_params',
            array(
                'export_nonce' => wp_create_nonce('ewoo-product-category-filters'),
            )
        );

        $categories = get_categories(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        $parents = [];
        $selected_attribures = [];

        foreach ($categories as $category) {
            $attributes = $this->getCategoryAttributes($category->term_id);

            if (! empty($attributes)) {
                $selected_attribures[$category->term_id] = $attributes;
            }

            if ($category->parent === 0) {
                $parents[] = $category;
            }
        }


        $product_attributes = $this->getProductAttributes();
        $options = $product_attributes;

        ob_start();
        require __DIR__.'/../views/admin-page-category-filters.php';

        if (! empty($_POST['attributes'])) {
            $attributes = $_POST['attributes'];

            foreach ($categories as $category) {
                $term_id = $category->term_id;
                $attr = (empty($attributes[$term_id])) ? []: $attributes[$term_id];

                $this->saveAttributes($term_id, $attr);
            }
        }
        return ob_get_clean();
    }

    private function saveAttributes(int $term_id, array $attributes): void
    {
        $wpdb = $this->db;
        $attributes_list = $this->getProductAttributes();

        if (! empty($attributes)) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}woo_category_attributes
                    WHERE `attribute_category_id` = {$term_id}");

            foreach ($attributes as $key => $attribute) {
                $selected_attribute = $attributes_list[$attribute];
                $is_exists = $wpdb->prepare("SELECT `attribute_id` FROM {$wpdb->prefix}woo_category_attributes
                    WHERE attribute_category_id = %d AND attribute_name = %s", $term_id, $selected_attribute);

                if (false === (bool)$wpdb->query($is_exists)) {
                    $sql = $wpdb->prepare("INSERT INTO {$wpdb->prefix}woo_category_attributes
                        ( attribute_name, attribute_label, attribute_type, attribute_category_id, attribute_order_by )
                        VALUES (%s, %s, %s, %d, %s ); ", $selected_attribute, 'additional_field_'.$key, 'text', $term_id, '');
                    $wpdb->query($sql);
                }
            }
        }
    }

    private function getProductAttributes()
    {
        $product_attributes = [];
        $attributes         = wc_get_attribute_taxonomies();

        foreach ( $attributes as $attribute ) {
            $product_attributes[ 'pa_' . $attribute->attribute_name ] = $attribute->attribute_label;
        }

        return $product_attributes;
    }
}
