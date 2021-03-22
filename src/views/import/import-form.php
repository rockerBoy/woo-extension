<form method="post" class="wc-progress-form-content woocommerce-importer" enctype="multipart/form-data">
        <header>
            <h2></h2>
            <p><?= __('Этот инструмент позволяет импортировать (или дописывать) данные товаров из Excel файла к вашему магазину.', 'extendedwoo')?></p>
        </header>
        <section>
            <table class="form-table woocommerce-importer-options">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="upload"><?= __('Выберите Excel файл с вашего компьютера:', 'extendedwoo')?></label>
                    </th>
                    <td>
                        <?php  if (! empty($upload_dir['error'])): ?>
                            <div class="inline error">
                                <p><?php esc_html_e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
                                <p><strong><?= esc_html( $upload_dir['error'] ) ?></strong></p>
                            </div>
                        <?php else: ?>
                            <input type="file" id="upload" name="import" size="2" />
                            <input type="hidden" name="action" value="save" />
                            <input type="hidden" name="max_file_size" value="2043800" />
                            <br>
                            <small>
                                <?php
                                printf(
                                /* translators: %s: maximum upload size */
                                    esc_html__( 'Maximum size: %s', 'woocommerce' ),
                                    esc_html( $size )
                                );
                                ?>
                            </small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="starting_row"><?= __('Экспортировать со строки:', 'extendedwoo')?></label>
                    </th>
                    <td>
                        <input type="number" id="starting_row" name="starting_row" min="1" placeholder="1"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </section>
        <div class="wc-actions">
            <button class="button button-primary button-next" type="submit"
                    value="<?= __( 'Continue', 'woocommerce' ); ?>" name="save_step"><?= __( 'Continue', 'woocommerce' ); ?></button>
            <?php wp_nonce_field( 'etx-xls-importer' ); ?>
        </div>
    </form>
</div>