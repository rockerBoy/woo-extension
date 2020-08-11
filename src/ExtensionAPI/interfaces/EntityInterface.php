<?php

/**
 * Relationships Table
 *
 * @var int import_id
 * @var int product_id
 * @var int product_category_id
 * @var int product_parent_category_id
 * @var int product_author_id
 * @var int order
 */

namespace ExtendedWoo\ExtensionAPI\interfaces;

interface EntityInterface
{
    public function getFields(): object;
}
