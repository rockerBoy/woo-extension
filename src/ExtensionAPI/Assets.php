<?php

namespace ExtendedWoo\ExtensionAPI;

use Exception;
use ExtendedWoo\Kernel;

class Assets
{
    /**
     * Enqueue styles.
     */
    public function adminStyles(): void
    {
        $ver = rand(1, 10_000);
        wp_register_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css', '', $ver);
        wp_enqueue_style('ewoo_admin_style', Kernel::pluginUrl() . '/assets/css/admin.css', '', $ver);
        wp_enqueue_style('woocommerce_admin_styles');
    }

    /**
     * Enqueue scripts.
     */
    public function adminScripts(): void
    {
        wp_register_script(
            'ewoo-product-export',
            Kernel::pluginUrl() . '/assets/js/admin/product-export.js',
            array( 'jquery' )
        );
        wp_register_script(
            'ewoo-product-import',
            Kernel::pluginUrl() . '/assets/js/admin/product-import.js',
            array( 'jquery' ),
            '103'
        );
        wp_register_script(
            'ewoo-product-validation',
            Kernel::pluginUrl() . '/assets/js/admin/product-validation.js',
            array( 'jquery' )
        );
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
