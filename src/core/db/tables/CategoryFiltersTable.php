<?php


namespace ExtendedWoo\core\db\tables;


class CategoryFiltersTable extends AbstractTable
{
    public function __construct(\wpdb $db)
    {
        parent::__construct($db);
        $this->table_name = $db->prefix.'woo_category_attributes';
    }

    public function createTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$this->table_name} (
                `attribute_id` BIGINT UNSIGNED NOT NULL auto_increment,
                `attribute_name` varchar(200) NOT NULL,
                `attribute_label` varchar(200) NOT NULL,
                `attribute_type` varchar(20) NOT NULL,
                `attribute_category_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `attribute_order_by` varchar (20) NOT NULL,
                `attribute_public` INT(1) UNSIGNED NOT NULL DEFAULT 1,
                PRIMARY KEY (attribute_id),
                KEY attribute_name (attribute_name(20))
            ) {$this->collate};
        ");
    }
}