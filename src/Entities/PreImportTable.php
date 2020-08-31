<?php

namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\TableInterface;
use wpdb;

class PreImportTable implements TableInterface
{
    /**
     * @obj \wpdb $db;
     */
    private wpdb $db;
    /**
     * @var string $table_name
     */
    private string $table_name = "woo_pre_import";

    public function __construct(wpdb $db)
    {
        $this->db = $db;
    }

    public function checkIfTableExists(): bool
    {
        $db = $this->db;
        return ($db->get_var("SHOW TABLES LIKE '{$db->prefix}{$this->table_name}';")) ?? false;
    }

    public function createTable(): void
    {
        $db = $this->db;
        $collate = '';
        if ($db->has_cap('collation')) {
            $collate = $db->get_charset_collate();
        }
        $table = "CREATE TABLE IF NOT EXISTS {$db->prefix}{$this->table_name} (
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
            ) $collate;
        ";
        $db->query($table);
        $db->query("
        CREATE TABLE IF NOT EXISTS {$db->prefix}{$this->table_name}_relationships (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `import_id` bigint(20) unsigned NOT NULL default '0',
                `product_id` bigint(20) unsigned NOT NULL,
                `product_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_parent_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_author_id`  bigint(20) unsigned NOT NULL default '0',
                `order` int(11) unsigned NULL default 0,
                PRIMARY KEY (id),
                UNIQUE KEY product_id_category (product_id, product_category_id),
                 
                KEY import_id (import_id),
                KEY product_id (product_id)
            ) $collate;");
    }

    /**
     * @return bool|null
     */
    public function dropTable(): ?bool
    {
        if ($this->checkIfTableExists()) {
            $this->db->query("DROP TABLE IF EXISTS {$this->db->prefix}{$this->table_name}_relationships");
            return $this->db->query("DROP TABLE IF EXISTS {$this->db->prefix}{$this->table_name}");
        }
        return null;
    }
}
