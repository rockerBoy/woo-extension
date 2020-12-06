<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces;

interface TableInterface
{
    public function createTable(): void;
    public function dropTable(): ?bool;
    public function checkIfTableExists(): bool;
}