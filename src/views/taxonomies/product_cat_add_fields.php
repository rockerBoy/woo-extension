<div class="form-field form-required term-additional-field-wrap">
    <div class="additional_field_wrap">
        <label for="additional_field"><?php esc_html_e( 'Тип фильтра', 'extendedwoo' ); ?></label>
        <input name="additional_field[]" class="additional_field" type="text" value="" size="40">
        <button class="remove_field" type="button">
            <svg viewBox="0 0 24 24">
                <path d="M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M16.207,14.793l-1.414,1.414L12,13.414 l-2.793,2.793l-1.414-1.414L10.586,12L7.793,9.207l1.414-1.414L12,10.586l2.793-2.793l1.414,1.414L13.414,12L16.207,14.793z"></path>
            </svg>
        </button>
    </div>
    <p><?= __('Название определяет, как элемент будет отображаться на вашем сайте.
Ярлык', 'extendedwoo') ?></p>

    <button type="button" class="add-field-option button"><?= __('Добавить ', 'extendedwoo') ?></button>
    </div>
<script>
    (function ($){
        $(document).on('ready', function (e){
           $('.add-field-option').on('click', function (e){
               let copy = $('.additional_field_wrap:first').clone();
               copy.prependTo('.term-additional-field-wrap');
           });
        });
    })(jQuery);
</script>