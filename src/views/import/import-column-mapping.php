<form action="<?= esc_url($this->getNextStepLink())?>" class="wc-progress-form-content woocommerce-importer"
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
                <tr>
                    <td class="wc-importer-mapping-table-name">
                        <span class="description"></span>
                    </td>
                    <td>
                        <?php foreach ( $headers as $index => $name ) :
                            $mapped_value = $mapped_items[$index];
                        ?>
                        <tr>
                            <td class="wc-importer-mapping-table-name">
                                <?= esc_html($name) ?>
                                <?php if ( ! empty($sample[$index])) : ?>
                                    <span class="description"><?php esc_html_e( 'Sample:', 'woocommerce' ); ?> <code><?= esc_html( $sample[ $index ] ) ?></code></span>
                                <?php endif; ?>
                            </td>
                            <td class="wc-importer-mapping-table-field">
                                <input type="hidden" name="map_from[<?= esc_html($index) ?>]" value="<?= esc_html($name) ?>" />
                                <select name="map_to[<?= esc_html($index) ?>]">
                                    <option value=""><?= esc_html_e( 'Do not import', 'woocommerce' ) ?></option>
                                    <option value="">--------------</option>
                                    <?php  foreach ( $this->getMappingOptions($mapped_value) as $key => $value ) : ?>
                                        <?php if ( is_array( $value ) ) : ?>
                                            <optgroup label="<?= esc_attr( $value['name'] ) ?>">
                                                <?php foreach ( $value['options'] as $sub_key => $sub_value ) : ?>
                                                    <option value="<?= esc_attr( $sub_key ) ?>" <?php selected( $mapped_value, $sub_key ); ?>><?= esc_html( $sub_value ) ?></option>
                                                <?php endforeach ?>
                                            </optgroup>
                                        <?php else : ?>
                                            <option value="<?= esc_attr( $key ) ?>" <?php selected( $mapped_value, $key ); ?>><?= esc_html( $value )?></option>
                                        <?php endif; ?>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
    <div class="wc-actions">
        <button type="submit" class="button button-primary button-next"
                value="<?= __('Run the importer', 'woocommerce'); ?>"
                name="save_step"><?php esc_html_e('Начать проверку', 'woocommerce'); ?></button>
        <input type="hidden" name="file" value="<?= esc_attr($this->file); ?>" />
        <input type="hidden" name="update_existing" value="<?= (int) $this->update_existing; ?>" />
        <?php wp_nonce_field( 'etx-xls-importer' ); ?>
    </div>
</form>