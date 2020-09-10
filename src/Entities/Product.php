<?php


namespace ExtendedWoo\Entities;

class Product extends \WC_Product_Simple
{
    private int $new_id = 0;
    private \wpdb $db;
    private \DateTimeImmutable $time;
    public bool $is_updated = false;
    public bool $is_saved = false;

    public function __construct($product = 0)
    {
        global $wpdb;

        parent::__construct($product);
        $this->db = $wpdb;
        $this->time = new \DateTimeImmutable('now');
    }

    public function setNewID(int $id)
    {
        $this->new_id = absint($id);
        return $this;
    }

    public function save(): int
    {
        $id = parent::save();
        $this
            ->saveToPreImport()
            ->saveRelationships($this->new_id);

        return $id;
    }

    private function saveToPreImport(): self
    {
        $db = $this->db;
        $table = $db->prefix.'woo_pre_import';
        $dataFormat = ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'];
        $data = [
            'product_title' => $this->get_title(),
            'product_excerpt' => $this->get_short_description(),
            'product_content' => $this->get_description(),
            'sku' => $this->get_sku(),
            'min_price' => $this->get_price(),
            'max_price' => $this->get_regular_price(),
            'product_uploaded' => $this->time->format("Y-m-d H:i:s"),
            'product_uploaded_gmt' => $this->time->format("Y-m-d H:i:s"),
        ];

        $record_id = $db->get_var(
            $db->prepare("SELECT id FROM {$table} WHERE `sku`= %s LIMIT 1", $this->get_sku())
        );

        if (true === (bool)$record_id) {
            $db->update($table, $data, ['id' => $record_id], $dataFormat);
        } else {
            $db->insert($table, $data, $dataFormat);
        }

        return $this;
    }

    private function saveRelationships(int $product_id): void
    {
        $db = $this->db;
        $pre_import_table = $db->prefix.'woo_pre_import';
        $pre_import_id = $db->get_var(
            $db->prepare("SELECT id FROM {$pre_import_table} WHERE `sku`= %s LIMIT 1", $this->get_sku())
        );
        $relations = [
            'post_id' => $this->get_id(),
            'import_id' => $pre_import_id,
            'product_id' => $this->new_id,
            'product_category_id' => $this->get_category_ids(),
            'product_author_id' => get_current_user_id(),
        ];
        $relations_table = $pre_import_table.'_relationships';
        $record_id = $db->get_var(
            $db->prepare("SELECT id FROM {$relations_table} WHERE `post_id`= %d LIMIT 1", $this->get_id())
        );

        if (true === (bool)$record_id) {
            $this->is_updated = true;
            $db->update($relations_table, $relations, ['id' => $record_id]);
        } else {
            $this->is_saved = true;
            $db->insert($relations_table, $relations);
        }
    }
}
