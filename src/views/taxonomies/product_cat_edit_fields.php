<style>
    .additional_field_wrap {
        display: flex;
        align-items: center;
        justify-content: space-around;
        max-width: 25rem;
        padding-bottom: .5rem;
        font-size: 14px;
    }
    .additional_field_wrap label {
        text-align: left;
        line-height: 1.3;
        font-weight: 600;
    }
    .additional_field {
        max-width: 15rem;
    }
</style>


<div class="form-field form-required term-additional-field-wrap">
    <div class="additional_field_container">
        <?php if (! empty($fields)): ?>
            <?php foreach ($fields as $index => $field):?>
            <div class="additional_field_wrap">
                <label for="additional_field"><?php esc_html_e( 'Тип фильтра', 'extendedwoo' ); ?></label>
                <input name="additional_field[]" class="additional_field" type="text" value="<?= $field->attribute_name ?>"
                       size="40" aria-required="true">
                <button class="remove_field" type="button">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M16.207,14.793l-1.414,1.414L12,13.414 l-2.793,2.793l-1.414-1.414L10.586,12L7.793,9.207l1.414-1.414L12,10.586l2.793-2.793l1.414,1.414L13.414,12L16.207,14.793z"></path>
                    </svg>
                </button>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="additional_field_wrap">
                <label for="additional_field"><?php esc_html_e( 'Тип фильтра', 'extendedwoo' ); ?></label>
                <input name="additional_field[]" class="additional_field" type="text" value=""
                       size="40" aria-required="true">
                <button class="remove_field" type="button">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M16.207,14.793l-1.414,1.414L12,13.414 l-2.793,2.793l-1.414-1.414L10.586,12L7.793,9.207l1.414-1.414L12,10.586l2.793-2.793l1.414,1.414L13.414,12L16.207,14.793z"></path>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="additional-field-description">
        <p><?= __('Название определяет, как элемент будет отображаться на вашем сайте.
Ярлык', 'extendedwoo') ?></p>
    </div>
</div>

<button type="button" class="add-field-option button"><?= __('Добавить', 'extendedwoo') ?></button>
<script>
    (function ($){
        $(document).on('ready', function (e){
           $('.add-field-option').on('click', function (e){
               // let fields = document.querySelectorAll('');
               let copy = $('.additional_field_wrap:first').clone();
               copy.find('input').val('');
               copy.appendTo('.additional_field_container');
           });
           $('body').on('click','.remove_field', function (e){
               let items = document.querySelectorAll('.additional_field_wrap');
               if (items.length > 1) {
                    $(this).parents('.additional_field_wrap').remove();
               }
           });
        });
    })(jQuery);
</script>