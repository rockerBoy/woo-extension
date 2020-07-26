<?php

namespace ExtendedWoo\helpers;

interface MigrationInterface
{
    public function up();
    public function down();
}
