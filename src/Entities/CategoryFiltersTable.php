<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\interfaces\TableInterface;
use wpdb;

class CategoryFiltersTable implements TableInterface
{

    /**
     * @obj \wpdb $db
     */
    private $db;
    /**
     * @var string $table_name
     */
    private $table_name = 'woo_category_attributes';
    /**
     * @var int $attribute_id
     */
    private $attribute_id = 0;
    /**
     * @var string $attribute_name
     */
    private $attribute_name = '';
    /**
     * @var string $attribute_label
     */
    private $attribute_label = '';
    /**
     * @var string $attribute_type
     */
    private $attribute_type = '';
    /**
     * @var string $attribute_orderby
     */
    private $attribute_orderby = '';
    /**
     * @var int $attribute_category_id
     */
    private $attribute_category_id = 0;
    /**
     * @var bool $attribute_public
     */
    private $attribute_public = false;

    public function __construct(wpdb $wpdb)
    {
        $this->db = $wpdb;
        $this->createTable();
    }

    public function createTable(): void
    {
        $db = $this->db;
        $collate = "";
        if ($db->has_cap('collation')) {
           $collate = $db->get_charset_collate();
        }

        $table = "
            CREATE TABLE IF NOT EXISTS {$db->prefix}{$this->table_name} (
                `attribute_id` BIGINT UNSIGNED NOT NULL auto_increment,
                `attribute_name` varchar(200) NOT NULL,
                `attribute_label` varchar(200) NOT NULL,
                `attribute_type` varchar(20) NOT NULL,
                `attribute_category_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `attribute_order_by` varchar (20) NOT NULL,
                `attribute_public` INT(1) UNSIGNED NOT NULL DEFAULT 1,
                PRIMARY KEY (attribute_id),
                KEY attribute_name (attribute_name(20))
            ) $collate;
        ";
        $db->query($table);
    }

    public function dropTable(): bool
    {
        return (bool) $this->db
            ->query("DROP TABLE IF EXISTS {$this->db->prefix}{$this->table_name}");
    }

    public function checkIfTableExists(): bool
    {
        $query = "SHOW TABLES LIKE '{$this->db->prefix}$this->table_name';";
        return (bool) ($this->db->get_var($query));
    }
}
