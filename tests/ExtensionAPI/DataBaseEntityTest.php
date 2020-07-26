<?php

namespace User;

use ExtendedWoo\ExtensionAPI\DataBaseEntity;
use PHPUnit\Framework\TestCase;

class DataBaseEntityTest extends TestCase
{
    public function __construct(
        ?string $name = null,
        array $data = [],
        $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);
    }

    public function testIsDBEmpty()
    {

    }
}
