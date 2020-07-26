<?php

namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\TableInterface;
use \wpdb;

class PreImportTable implements TableInterface
{
    /**
     * @obj \wpdb $db;
     */
    private $db;
    /**
     * @var string $table_name
     */
    private $table_name = "woo_pre_import";
    /**
     * @var int $product_id
     */
    private $product_id = 0;
    /**
     * @var string $product_name
     */
    private $product_name = '';
    /**
     * @var string $product_article
     */
    private $product_article = '';
    /**
     * @var float $product_price
     */
    private $product_price = 0.00;
    /**
     * @var int $product_category
     */
    private $product_category = 0;
    /**
     * @var int $product_parent_category
     */
    private $product_parent_category= 0;

    /**
     * @return bool
     */
    public function __construct(wpdb $db)
    {
        $this->db = $db;
    }

    public function checkIfTableExists(): bool
    {
        $db = $this->db;
        return (boolean) ($db->get_var("SHOW TABLES LIKE '{$db->prefix}woocommerce_downloadable_product_permissions';")) ?? false;
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
                `import_id` bigint(20) unsigned NOT NULL default '0',
                `product_id` bigint(20) unsigned NOT NULL,
                `product_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_parent_category_id`  bigint(20) unsigned NOT NULL default '0',
                `product_author_id`  bigint(20) unsigned NOT NULL default '0',
                `order` int(11) unsigned NULL default 0,
                PRIMARY KEY (product_id, product_category_id),
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
