<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces;


interface PageInterface
{
    public function getCurrentPage(): string;
    public function registerPage(array $options): void;
    public function connectPage(array $options): void;
}