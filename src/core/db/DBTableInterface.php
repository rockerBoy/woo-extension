<?php


namespace ExtendedWoo\core\db;


interface DBTableInterface
{
    public function createTable(): void;

    public function removeTable(): bool;
}