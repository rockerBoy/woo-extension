<?php

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;

wp_enqueue_script('ewoo-product-validation');
?>
<form action="<?= esc_url($this->getNextStepLink())?>" class="extended-mapping wc-progress-form-content woocommerce-importer"
      method="post">
    <header>
        <h2><?= __('Назначить XLS поля товарам', 'extendedwoo'); ?></h2>
        <p><?= __('Выберите поля в XLS файле для назначения полям товара, или для игнорирования их при импорте.', 'extendedwoo'); ?></p>
    </header>
    <section class="wc-importer-mapping-table-wrapper">
        <table class="widefat wc-importer-mapping-table">
            <thead>
                <tr>
                    <th><?= __('Column name', 'woocommerce'); ?></th>
                    <th><?= __('Map to field', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($headers as $index => $header): ?>
            <?php
                $mapped_value = $mapped_items[$index];
                $options = ($mapped_value) ? ProductsImportHelper::getMappingOptions($mapped_value, $labels): [];
            ?>
                <tr class="<?= ($mapped_value)? 'field-'.$mapped_value : ''?>">
                    <td class="wc-importer-mapping-table-name">
                        <?= $header ?>
                        <span class="description"></span>
                    </td>
                    <td>
                        <?php if (! empty($options)): ?>
                        <select name="map_to[<?= esc_html($index) ?>]">
                            <option value=""><?= esc_html_e( 'Do not import', 'woocommerce' ) ?></option>
                            <option value="">--------------</option>
                            <?php  foreach ( $options as $key => $value ) : ?>
                                <?php if ( is_array( $value ) ) : ?>
                                    <optgroup label="<?= esc_attr( $value['name'] ) ?>">
                                        <?php foreach ( $value['options'] as $sub_key => $sub_value ) : ?>
                                            <option value="<?= esc_attr( $sub_key ) ?>" <?php selected( $mapped_value, $sub_key ); ?>><?= esc_html( $sub_value ) ?></option>
                                        <?php endforeach ?>
                                    </optgroup>
                                <?php else : $mapped_value = $mapped_items[$key]; ?>
                                    <option value="<?= esc_attr( $mapped_value ) ?>" <?php selected( $mapped_value, $key ); ?>><?= esc_html( $value )?></option>
                                <?php endif; ?>
                            <?php endforeach ?>
                        </select>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </section>
    <?php foreach ($mapped_items as $key=>$item):?>
        <input type="hidden" name="<?= esc_html($item) ?>" value="" required/>
    <?php endforeach; ?>
    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next"
                value="<?= __('Run the importer', 'woocommerce'); ?>"
                name="save_step"><?php esc_html_e('Начать проверку', 'woocommerce'); ?></button>
        <button type="submit" class="button button-primary button-prev"
                value="<?= __('Вернутся назад', 'woocommerce'); ?>"
                name="remove_step"><?php esc_html_e('Вернутся назад', 'woocommerce'); ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file); ?>" />
        <input type="hidden" name="show_errors_only" value="<?=  $this->showErrorsOnly ?>" />
        <?php wp_nonce_field( 'etx-xls-importer' ); ?>
    </div>
</form>