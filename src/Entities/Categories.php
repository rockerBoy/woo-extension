<?php

declare(strict_types=1);

namespace ExtendedWoo\Entities;

/**
 * Class Categories
 *  Global attributes:
 *  - Brand
 *  -
 * @package ExtendedWoo\Entities
 */
class Categories
{
    public function init():self
    {
        $this->setCategoryAttributes();
        return $this;
    }

    private function setCategoryAttributes(): self
    {
        return $this;
    }
}
