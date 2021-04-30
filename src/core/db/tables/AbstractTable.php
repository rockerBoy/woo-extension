<?php


namespace ExtendedWoo\core\db\tables;


use ExtendedWoo\core\db\DBTableInterface;

abstract class AbstractTable implements DBTableInterface
{
    protected \wpdb $db;
    protected string $table_name;
    protected string $collate;

    public function __construct(\wpdb $db)
    {
        $this->db = $db;

        $this->setCollate();
    }

    private function setCollate(): void
    {
        $this->collate = $this->db->get_charset_collate();
    }

    abstract public function createTable(): void;

    public function removeTable(): bool
    {
        return $this->db->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
}