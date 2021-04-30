<?php


namespace ExtendedWoo\core\db;


final class DBExtension
{
    private array $extension_tables;

    public function __construct(array $extension_tables)
    {
        $this->extension_tables = $extension_tables;
    }

    public function init(): void
    {
        foreach ($this->extension_tables as $tableObj) {
            $tableObj->createTable();
        }
    }

    public function terminate(): void
    {
        foreach ($this->extension_tables as $tableObj) {
            $tableObj->removeTable();
        }
    }
}