<?php
    use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;
?>
<section class="excel-resolver__rows">
    <table>
        <thead>
        <tr>
            <?php $defaultCols = array_flip(ProductsImportHelper::getDefaultFields());  ?>
            <?php foreach ($mapping_to as $key) :?>
                <?php if (!empty($key)) : ?>
                    <th><?= __($defaultCols[$key], 'extendedwoo') ?></th>
                <?php endif;?>
            <?php endforeach; ?>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?= $resulting_table ?>
        </tbody>
    </table>
</section>