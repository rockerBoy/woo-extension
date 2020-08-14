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

    public function getFilters(int $category_id): array
    {
        $db = $this->db;
        $fields_query = $this->db->prepare("SELECT
                                                ca.attribute_label, 
                                                ca.attribute_name, 
                                                ca.attribute_type 
                                                FROM {$db->prefix}{$this->table_name} `ca`
                                                WHERE ca.attribute_category_id = %d", $category_id);
        return $db->get_results($fields_query);
    }
}
