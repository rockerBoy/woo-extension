<?php

use ExtendedWoo\Entities\Products;

$products = new Products();
?>
<div class="wrap woocommerce">
    <h1><?php esc_html_e('Export Products', 'woocommerce'); ?></h1>

        <div class="woocommerce-exporter-wrapper">
            <form class="woocommerce-exporter">
                <header>
                    <span class="spinner is-active"></span>
                    <h2><?php esc_html_e('Экспорт товаров в Excel файл', 'extended-woo'); ?></h2>
                    <p><?php esc_html_e('Этот инструмент позволяет сгенерировать (и скачать) Excel файл со списком всех товаров.', 'woocommerce'); ?></p>
                </header>
                <section>
                    <table class="form-table woocommerce-exporter-options">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce-exporter-columns"><?php esc_html_e('Which columns should be exported?', 'woocommerce'); ?></label>
                            </th>
                            <td>
                                <select id="woocommerce-exporter-columns" class="woocommerce-exporter-columns wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Export all columns', 'woocommerce' ); ?>">
                                    <?php
                                        foreach ( $products->getDefaultColumnNames() as $column_id => $column_name ) {
                                            echo '<option value="' . esc_attr( $column_id ) . '">' . esc_html( $column_name ) . '</option>';
                                        }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce-exporter-category"><?php esc_html_e( 'Which product category should be exported?', 'woocommerce' ); ?></label>
                            </th>
                            <td>
                                <select id="woocommerce-exporter-category" required class="woocommerce-exporter-category wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Выберите категорию', 'woocommerce' ); ?>">
                                    <?php
                                    $categories = get_categories(
                                        array(
                                            'taxonomy'   => 'product_cat',
                                            'hide_empty' => false,
                                        )
                                    );
                                    foreach ( $categories as $category ) {
                                        echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</option>';
                                    }
                                    ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce-exporter-all-products"><?= __( 'Экспортировать все товары?', 'extendedwoo' ); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="woocommerce-exporter-all-products" value="1" />
                                <label for="woocommerce-exporter-all-products"><?php esc_html_e( 'Да, экспортировать все товары, включая существующие', 'extendedwoo' ); ?></label>
                            </td>
                        </tr>
                        <?php do_action('woocommerce_product_export_row'); ?>
                        </tbody>
                    </table>
                    <progress class="woocommerce-exporter-progress" max="100" value="0"></progress>
                </section>
                <div class="wc-actions">
                    <button type="submit" class="woocommerce-exporter-button button button-primary" value="<?php esc_attr_e('Сгенерировать Эксель', 'extended-woo'); ?>"><?php esc_html_e('Сгенерировать Эксель', 'extended-woo'); ?></button>
                </div>
            </form>
        </div>
</div>
