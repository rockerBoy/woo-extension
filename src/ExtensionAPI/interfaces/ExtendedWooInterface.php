<?php


namespace ExtendedWoo\ExtensionAPI\interfaces;

interface ExtendedWooInterface
{
    public function init(): self;
    public function install(): self;
    public function uninstall(): self;
}