<?php

if (! defined('ABSPATH')) {
    exit;
}

wp_enqueue_script('wc-product-export');

//$exporter = new WC_Product_CSV_Exporter();
?>
<div class="wrap woocommerce">
    <h1><?php esc_html_e('Export Products', 'woocommerce'); ?></h1>

        <div class="woocommerce-exporter-wrapper">
            <form class="woocommerce-exporter">
                <header>
                    <span class="spinner is-active"></span>
                    <h2><?php esc_html_e('Экспорт товаров в Excel файл', 'extended-woo'); ?></h2>
                    <p><?php esc_html_e('This tool allows you to generate and download a CSV file containing a list of all products.', 'woocommerce'); ?></p>
                </header>
                <section>
                    <table class="form-table woocommerce-exporter-options">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce-exporter-columns"><?php esc_html_e('Which columns should be exported?', 'woocommerce'); ?></label>
                            </th>
                            <td>
                                test
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce-exporter-meta"><?php esc_html_e( 'Export custom meta?', 'woocommerce' ); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="woocommerce-exporter-meta" value="1" />
                                <label for="woocommerce-exporter-meta"><?php esc_html_e( 'Yes, export all custom meta', 'woocommerce' ); ?></label>
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
