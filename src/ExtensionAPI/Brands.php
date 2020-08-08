<?php


namespace ExtendedWoo\ExtensionAPI;

final class Brands
{
    /**
     * @var array $options
     */
    private array $options = [
        'label'                      => '',
        'labels'                     => [],
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'query_var'                  => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
    ];

    public function init(): void
    {
        $this->setLabels();
        register_taxonomy(
            'brands',
            apply_filters('woocommerce_taxonomy_objects_product_tag', array( 'product' )),
            $this->options
        );
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function setLabels(array $labels = []): self
    {
        $this->options['label'] = __('Бренды', 'woocommerce');
        $this->options['labels'] = [
            'name'                       => __('Бренды', 'extendedwoo'),
            'singular_name'              => __('Бренд', 'extendedwoo'),
            'menu_name'                  => __('Бренды', 'extendedwoo'),
            'all_items'                  => __('Все бренды', 'extendedwoo'),
            'parent_item'                => __('Родительский бренд', 'extendedwoo'),
            'parent_item_colon'          => __('Родительский бренд:', 'extendedwoo'),
            'new_item_name'              => __('Название нового бренда', 'extendedwoo'),
            'add_new_item'               => __('Добавить новый бренд', 'extendedwoo'),
            'edit_item'                  => __('Редактировать бренд', 'extendedwoo'),
            'update_item'                => __('Обновить бренд', 'extendedwoo'),
            'separate_items_with_commas' => __("Разделить бренды запятыми", "extendedwoo"),
            'search_items'               => __("Поиск бренда", 'extendedwoo'),
            'add_or_remove_items'        => __("Добавить или удалить бренды", "extendedwoo"),
            'choose_from_most_used'      => __("Выберите наиболее используемые бренды", "extendedwoo"),
        ];

        return $this;
    }
}
