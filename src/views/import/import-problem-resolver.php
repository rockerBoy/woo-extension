<?php
    function parseCol(array $col, array $mapping) {
        $row = [];
        foreach ($col as $index => $cell) {
            if (isset($mapping[$index])) {
                $key = $mapping[$index];
                $row[$key] = $cell;
            }
        }
        return $row;
    }
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
                <?php foreach ($labels as $label):?>
                <th><?= __($label, 'extendedwoo') ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($importer->getColumns() as $index => $column):
                        $column = array_values($column);
                        $start = (isset($this->params['start_from']))? $this->params['start_from'] : 1;

                        if ($column === $importer->getHeader($start)) {
                            continue;
                        }

                        $parsed_data = parseCol($column, $mapping_to);
                        if (empty($parsed_data['name']) && empty($parsed_data['sku'])) {
                            continue;
                        }
                        ?>
                        <tr>
                            <?php foreach ($column as $k => $item): ?>
                            <?php
                                $key = $mapping_to[$k] ?? '';
                                $item = $item ?? '';
                            ?>
                                <td>
                                    <?php if ($key !== 'sku' || $item ): ?>
                                    <?= $importer->makeExcerpt($item, 0, 100) ?>
                                    <?php else: ?>
                                        <input type="text" name="sku[<?= $k ?>]"
                                               required placeholder="Заполните артикль" style="border: 2px solid #e11616; max-width: 100px;"/>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php

                    endforeach;
                ?>
            </tbody>
        </table>
    </section>
    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next"
                value="<?= __('Continue', 'woocommerce') ?>"
                name="save_step"><?= __('Run the importer', 'woocommerce') ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file); ?>" />
        <input type="hidden" name="update_existing" value="<?= (int) $this->update_existing; ?>" />
        <?php  foreach ($labels as $index=> $map_from): ?>
            <input type="hidden" name="map_from[<?= esc_html($index) ?>]" value="<?= $map_from ?>"/>
        <?php endforeach; ?>
        <?php  foreach ($mapping_to as $index=> $map_to): ?>
            <input type="hidden" name="map_to[<?= esc_html($index) ?>]" value="<?= $map_to ?>"/>
        <?php endforeach; ?>
        <?php wp_nonce_field( 'etx-xls-importer' ); ?>
    </div>
</form>