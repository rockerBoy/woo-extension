<?php


namespace ExtendedWoo\ExtensionAPI\interfaces;


interface PageInterface
{
    public function getCurrentPage(): string;
    public function determineCurrentPage(): void;
    public function registerPage(array $options): void;
    public function connectPage(array $options): void;
}