<?php

$labels = array_values($this->importStrategy->getColumns());
$mapping_to = $req->get('map_to');
$resulting_table = $resolver->makeViewTable();
?>
<form action="<?= esc_url($this->getNextStepLink())?>" class="wc-progress-form-content woocommerce-importer excel-resolver" method="post">

    <?php include __DIR__.'/import-validation/validation-header.php' ?>
    <?php include __DIR__.'/import-validation/validation-body.php' ?>

    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next hidden"
                value="<?= __('Continue', 'woocommerce') ?>"
                name="save_step"><?= __('Run the importer', 'woocommerce') ?></button>
        <button type="submit" class="button button-primary button-next btn-check-form"
                value="<?= __('Начать проверку', 'woocommerce') ?>"
                name="save_step"><?= __('Начать проверку', 'woocommerce') ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file); ?>" />

        <?php  foreach ($labels as $index => $map_from) : ?>
            <input type="hidden" name="map_from[<?= esc_html($index) ?>]" value="<?= $map_from ?>"/>
        <?php endforeach; ?>
        <?php  foreach ($mapping_to as $index => $map_to) : ?>
            <input type="hidden" name="map_to[<?= esc_html($index) ?>]" value="<?= $map_to ?>"/>
        <?php endforeach; ?>
        <?php wp_nonce_field('etx-xls-importer'); ?>
    </div>
</form>