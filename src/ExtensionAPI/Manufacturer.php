<?php

declare(strict_types = 1);

namespace ExtendedWoo\ExtensionAPI;

final class Manufacturer
{
    /**
     * @var array $options
     */
    private array $options = [
        'labels'                     => [],
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
    ];

    public function init(): void
    {
        $labels = [
            'name'                       => __('Производитель', 'extendedwoo'),
            'singular_name'              => __('Страна производитель', 'extendedwoo'),
            'menu_name'                  => __('Производители', 'extendedwoo'),
            'all_items'                  => __('Все производители', 'extendedwoo'),
//            'parent_item'                => __('Родительский бренд', 'extendedwoo'),
//            'parent_item_colon'          => __('Родительский бренд:', 'extendedwoo'),
            'new_item_name'              => __('Название новой страны производителя', 'extendedwoo'),
            'add_new_item'               => __('Добавить нового производителя', 'extendedwoo'),
            'edit_item'                  => __('Редактировать производителя', 'extendedwoo'),
            'update_item'                => __('Обновить производителя', 'extendedwoo'),
            'separate_items_with_commas' => __("Разделить производителей запятыми", "extendedwoo"),
            'search_items'               => __("Поиск производителя", 'extendedwoo'),
            'add_or_remove_items'        => __("Добавить или удалить производителя", "extendedwoo"),
            'choose_from_most_used'      => __("Выберите наиболее используемые производителя", "extendedwoo"),
        ];

        $this->options['labels'] = $labels;
        register_taxonomy('manufacturers', 'product', $this->options);
    }
}
