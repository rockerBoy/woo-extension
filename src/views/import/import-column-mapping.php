<?php

use ExtendedWoo\ExtensionAPI\interfaces\export\helpers\ProductsImportHelper;

wp_enqueue_script('ewoo-product-validation');
?>
<form action="<?= esc_url($this->getNextStepLink())?>"
      class="extended-mapping wc-progress-form-content woocommerce-importer" method="post">
    <header>
        <h2><?= __('Назначить XLS поля товарам', 'extendedwoo'); ?></h2>
        <p><?= __('Выберите поля в XLS файле для назначения полям товара, или для игнорирования их при импорте.', 'extendedwoo'); ?></p>
    </header>
    <section class="extended-errors hidden">
        <?php
            $required_fields = $this->importStrategy->getColumns();
            foreach ($required_fields as $id => $label) {
                if (! is_array($label)) {
                    echo sprintf('<div id="required_%s" class="hidden">Обязательное поле <span class="extended-error-label">"%s"</span> не задано</div>', $id, $label);
                }
            }
        ?>
    </section>
    <section class="wc-importer-mapping-table-wrapper">
        <table class="widefat wc-importer-mapping-table">
            <thead>
                <tr>
                    <th><?= __('Column name', 'woocommerce'); ?></th>
                    <th><?= __('Map to field', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $default = ProductsImportHelper::getDefaultFields();
            foreach ($headers as $index => $header) : ?>
                <?php
                    $mapped = (! empty($default[$header]))? $default[$header] : '';
                    $options = ($mapped) ? ProductsImportHelper::getMappingOptions($mapped, $labels): [];
                ?>
                <?php  foreach ($labels as $key => $label) : ?>
                    <?php
                    $value = $mapped_items[$key];
                    if (!is_array($label)) : ?>
                        <?php $mapped_value = $mapped_items[$key]; ?>
                    <?php endif; ?>
                <?php endforeach ?>
                <tr class="mapping-field">
                    <td class="wc-importer-mapping-table-name">
                        <?= $header ?>
                        <span class="description"></span>
                    </td>
                    <td>
                        <select name="map_to[]">
                            <option value=""><?= esc_html_e('Do not import', 'woocommerce') ?></option>
                            <option value="">--------------</option>
                            <?php  foreach ($labels as $key => $label) : ?>
                                <?php $value = $mapped_items[$key] ?>
                                <?php
                                if (is_array($label)) : ?>
                                    <optgroup label="<?= esc_attr($label['name']) ?>">
                                        <?php foreach ($label['options'] as $sub_key => $sub_value) : ?>
                                            <option value="<?= esc_attr($sub_key) ?>"
                                                <?php echo selected($sub_key, $default[$header]) ?>
                                            ><?= esc_html($sub_value) ?></option>
                                        <?php endforeach ?>
                                    </optgroup>
                                <?php
                                    else :
                                        $mapped_value = (!empty($mapped_items[$key])? $mapped_items[$key] : '');
                                        $possible_val = (! empty($default[$header]))? $default[$header]: '';
                                ?>
                                <option value="<?= esc_attr($mapped_value) ?>" <?php echo selected($mapped_value, $possible_val) ?> >
                                    <?= esc_html($label)?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </section>
    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next"
                value="<?= __('Run the importer', 'woocommerce') ?>"
                name="save_step"><?php esc_html_e('Начать проверку', 'woocommerce'); ?></button>
        <button type="submit" class="button button-primary button-prev"
                value="<?= __('Вернутся назад', 'woocommerce') ?>"
                name="remove_step"><?php esc_html_e('Вернутся назад', 'woocommerce'); ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file) ?>" />
        <?php wp_nonce_field('etx-xls-importer'); ?>
    </div>
</form>