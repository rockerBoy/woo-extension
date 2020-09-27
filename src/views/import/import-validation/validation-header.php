<?php

use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;

$validated = [
        'correct' => 0,
        'errors' => 0,
        'total' => 0,
];
$row_statuses = $resolver->validationResult;

foreach ($row_statuses as $row) {
    $validated['total']++;

    if ($row['status']) {
        $validated['correct']++;
    } else {
        $validated['errors']++;
    }
}

wp_enqueue_script('ewoo-product-validation');
?>
<header>
    <h2><?= __('Проверка данных', 'extendedwoo')?></h2>
    <p></p>
</header>

<section class="extended-validation-msg">
    <?php
        $results = array();

        $results[] = sprintf(
        /* translators: %d: products count */
            '<div>'.
            _n( 'Всего найдено товаров: %s',
                'Всего найдено товаров: %s',
                $validated['total'], 'extendedwoo' ),
            '<strong>' . number_format_i18n( $validated['total'] ) . '</strong>'
        ).'</div>';
        $results[] = '<div>'.sprintf(
        /* translators: %d: products count */
            _n( 'Без ошибок: %s',
                'Без ошибок: %s',
                $validated['correct'], 'extendedwoo'
            ),
            '<strong>' . number_format_i18n( $validated['correct'] ) . '</strong>'
        ).'</div>';
        $results[] = '<div>'.sprintf(
        /* translators: %d: products count */
            _n( 'С ошибками: %s', 'С ошибками: %s', $validated['errors'], 'extendedwoo' ),
            '<strong>' . number_format_i18n( $validated['errors'] ) . '</strong>'
        ).'</div>';
        echo implode( ' ', $results );
    ?>
    <div>
        <input type="checkbox" id="show_all" name="show_all" value="1" />
        <label for="show_all"><?php esc_html_e( 'Отобразить все товары', 'woocommerce' ); ?></label>
    </div>
</section>