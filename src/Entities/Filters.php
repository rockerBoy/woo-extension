<?php


namespace ExtendedWoo\Entities;

final class Filters
{
    private \wpdb $db;
    private CategoryFiltersTable $filtersTable;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function setCatTable(CategoryFiltersTable  $table): self
    {
        $this->filtersTable = $table;
        return $this;
    }
}
