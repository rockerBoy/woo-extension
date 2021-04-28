<?php

use ExtendedWoo\Entities\Products;

$products = new Products();

function get_children_cat(WP_Term $category, array $filters = []) {
    if (empty(get_term_children($category->term_id, 'product_cat'))) {
        return null;
    }

    return get_term_children($category->term_id, 'product_cat');
}
?>
<div class="wrap woocommerce">
    <h1><?php esc_html_e('Настройка атрибутов категорий', 'woocommerce'); ?></h1>

    <div class="woocommerce-progress-form-wrapper">
        <form class="woocommerce-exporter" method="post" action="/wp-admin/edit.php?post_type=product&page=product_category_filters">
            <header>
                <span class="spinner is-active"></span>
                <h2><?php esc_html_e('Настройка атрибутов категорий', 'extended-woo'); ?></h2>
            </header>
            <div class="wc-actions">
                <table class="category-filters">
                    <?php
                    foreach ($categories as $category) {
                        $required = '';
                        $cat_id = $category->term_id;
                        $filters = $this->makeSelectField($cat_id, $options, $selected_attribures);

                        if ($category->parent === 0) {
                            printf('<tr><td class="parent-cat"><label for="">%s</label></td><td>%s</td></tr>', __($category->name, 'extended-woo'), $filters);
                        }

                        $childrens = get_children_cat($category);
                        if (! empty($childrens)) {
                            foreach ($childrens as $children_id) {
                                $term = get_term($children_id);
                                $filters = $this->makeSelectField($children_id, $options, $selected_attribures);
                                printf('<tr><td><label for="">--- %s</label></td><td>%s</td></tr>', __($term->name, 'extended-woo'), $filters);
                            }
                        }
                    }

                    ?>
                </table>
            </div>
            <div class="wc-actions">
                <button type="submit" class="woocommerce-exporter-button button button-primary" value="<?php esc_attr_e('Сохранить настройки', 'extended-woo'); ?>"><?php esc_html_e('Сохранить настройки', 'extended-woo'); ?></button>
            </div>
        </form>
    </div>
</div>
