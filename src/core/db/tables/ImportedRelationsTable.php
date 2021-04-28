<?php


namespace ExtendedWoo\core\db\tables;


class ImportedRelationsTable extends AbstractTable
{

    public function __construct(\wpdb $db)
    {
        parent::__construct($db);
        $this->table_name = $this->db->prefix.'woo_imported_relationships';
    }

    public function createTable(): void
    {
        $this->db->query("
        CREATE TABLE IF NOT EXISTS {$this->table_name} (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` bigint(20) unsigned NOT NULL,
                `post_id` bigint(20) unsigned NOT NULL,
                `sku` varchar(100) NULL default '',
                `product_uploaded` datetime NOT NULL default CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY sku (sku),
                KEY product_id (product_id)
            ) {$this->collate}");
    }
}