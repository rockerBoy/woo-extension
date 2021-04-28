<?php


namespace ExtendedWoo\core\db\tables;


class RelationsTable extends AbstractTable
{
    public function __construct(\wpdb $db)
    {
        parent::__construct($db);

        $this->table_name = $this->db->prefix.'woo_pre_import_relationships';
    }

    public function createTable(): void
    {
        $query = $this->db->prepare(
            "CREATE TABLE IF NOT EXISTS $this->table_name (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `import_id` bigint(20) unsigned NOT NULL default '0',
                `product_id` bigint(20) unsigned NOT NULL,
                `post_id` bigint(20) unsigned NOT NULL,
                `product_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_parent_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_author_id`  bigint(20) unsigned NOT NULL default '0',
                `imported_file_token` varchar(255) NULL default '',
                `is_imported` tinyint(1) NULL default 0,
                `order` int(11) unsigned NULL default 0,
                PRIMARY KEY (id),
                KEY import_id (import_id),
                KEY post_id (post_id),
                KEY product_id (product_id),
                KEY product_category_id (product_category_id),
                KEY is_imported (product_category_id)
            ) $this->collate"
        );
        $this->db->query($query);
    }
}