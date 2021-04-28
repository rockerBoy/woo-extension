<?php


namespace ExtendedWoo\core\db\tables;


class PreImportTable extends AbstractTable
{
    public function __construct(\wpdb $db)
    {
        parent::__construct($db);
        $this->table_name = $this->db->prefix.'woo_pre_import';
    }

    public function createTable(): void
    {
        $preparedQuery = $this->db->prepare("CREATE TABLE IF NOT EXISTS {$this->table_name} (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_title` text NOT NULL,
                `product_excerpt` text NOT NULL,
                `product_content` longtext NOT NULL,
                `sku` varchar(100) NULL default '',
                `min_price` decimal(19,4) NULL default NULL,
                `max_price` decimal(19,4) NULL default NULL,
                `onsale` tinyint(1) NULL default 0,
                `stock_status` varchar(100) NULL default 'instock',
                `tax_class` varchar(100) NULL default '',
                `is_valid` tinyint(1) NULL default 0,
                `is_imported` tinyint(1) NULL default 0,
                `product_uploaded` datetime NOT NULL default '0000-00-00 00:00:00',
                `product_uploaded_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (`id`),
              KEY `stock_status` (`stock_status`),
              KEY `onsale` (`onsale`),
              KEY min_max_price (`min_price`, `max_price`)
            ) $this->collate");

        $this->db->query($preparedQuery);
    }
}