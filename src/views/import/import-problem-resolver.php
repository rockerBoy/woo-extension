<?php
    use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
?>

<form action="<?= esc_url($this->getNextStepLink())?>" class="wc-progress-form-content woocommerce-importer excel-resolver"
      method="post">
    <header>
        <h2><?= __('Проверка данных', 'extendedwoo')?></h2>
        <p></p>
    </header>
    <section class="excel-resolver__rows">
        <table>
            <thead>
            <tr>
                <?php
                    $defaultCols = array_flip(ProductsImportHelper::getDefaultFields());
                ?>
                <?php foreach ($mapping_to as $key) :?>
                <th><?= __($defaultCols[$key], 'extendedwoo') ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
               <?= $view_data ?>
            </tbody>
        </table>
    </section>
    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next"
                value="<?= __('Continue', 'woocommerce') ?>"
                name="save_step"><?= __('Run the importer', 'woocommerce') ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file); ?>" />
        <input type="hidden" name="update_existing" value="<?= (int) $this->update_existing; ?>" />
        <?php  foreach ($labels as $index => $map_from) : ?>
            <input type="hidden" name="map_from[<?= esc_html($index) ?>]" value="<?= $map_from ?>"/>
        <?php endforeach; ?>
        <?php  foreach ($mapping_to as $index => $map_to) : ?>
            <input type="hidden" name="map_to[<?= esc_html($index) ?>]" value="<?= $map_to ?>"/>
        <?php endforeach; ?>
        <?php wp_nonce_field('etx-xls-importer'); ?>
    </div>
</form>