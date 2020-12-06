<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\interfaces;

interface ExtendedWooInterface
{
    public function init(): self;
    public function install(): self;
    public function uninstall(): self;
}