<?php

namespace ExtendedWoo\ExtensionAPI;

use DI\Container;

class Assets
{
    private Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function adminStyles(): void
    {
        wp_register_style('ewoo_admin_style', $this->app->get('pluginURL') . '/assets/css/admin.css');
        wp_enqueue_style('ewoo_admin_style', $this->app->get('pluginURL') . '/assets/css/admin.css');
        wp_enqueue_style('woocommerce_admin_styles');
    }

    public function adminScripts(): void
    {
        wp_register_script(
            'ewoo-bundle',
            $this->app->get('pluginURL') . '/assets/js/bundle.js',
            array( 'jquery' ),
            '0.0.1'
        );
        wp_enqueue_script('ewoo-bundle');
    }

    public function findProductImages(): void
    {
        global $wpdb;

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
        ];
        $product_list = new \WP_Query( $args );

        $wpdb->query("START TRANSACTION");
        $products_total = 0;
        $found = 0;

        while ($product_list->have_posts()) :
            $product_list->the_post();
            global $product;

            $product_id = $product->get_id();

            $this->updateProductRelationsBySKU($product->get_sku());

            $rost_id = $wpdb->prepare("SELECT 
                                            `product_id`
                                        FROM {$wpdb->prefix}woo_imported_relationships
                                        WHERE `post_id` = %d LIMIT 1", $product_id);
            $rost_id = $wpdb->get_var($rost_id);
            $is_image_set = $product->get_image_id();


            if (false === (bool)$is_image_set && ! empty($rost_id)) {
                ++$products_total;

                $attachment_query = $wpdb->prepare("SELECT `id` FROM {$wpdb->posts} 
                    WHERE 
                          `post_type` = %s AND
                          `post_name` LIKE (%s)",'attachment', $rost_id);
                $attachment = $wpdb->get_var($attachment_query);

                if (! empty($attachment)) {
                    $product->set_image_id($attachment);
                    $product->save();
                    ++$found;
                }
            }
        endwhile;

        $wpdb->query("COMMIT");

        echo "<p>Без изображений: {$products_total}</p><p>Найдено: {$found}</p>";
    }

    public function updateProductRelationsBySKU(string $sku)
    {
        global $wpdb;

        $post_id = wc_get_product_id_by_sku($sku);

        if (! empty($post_id)) {
            $wpdb->update(
                $wpdb->prefix.'woo_imported_relationships',
                ['post_id' => $post_id],
                ['sku' => $sku]
            );
        }
    }
}
