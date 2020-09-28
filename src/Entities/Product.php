<?php


namespace ExtendedWoo\Entities;

use ExtendedWoo\ExtensionAPI\taxonomies\ProductCatTaxonomy;

final class Product extends \WC_Product_Simple
{
    private int $new_id = 0;
    private \wpdb $db;
    private \DateTimeImmutable $time;
    private bool $is_valid = false;
    private bool $is_imported = false;
    private string $pre_import_table;
    private string $relation_table;
    public bool $is_updated = false;
    public bool $is_saved = false;


    public function __construct($product = 0)
    {
        global $wpdb;

        parent::__construct($product);
        $this->db               = $wpdb;
        $this->pre_import_table = $wpdb->prefix . 'woo_pre_import';
        $this->relation_table   = $wpdb->prefix
                                  . 'woo_pre_import_relationships';
        $this->time             = new \DateTimeImmutable('now');
    }

    public function setNewID(int $id)
    {
        $this->new_id = absint($id);

        return $this;
    }

    public function getNewID(): int
    {
        return $this->new_id;
    }

    public function getRelationsID(): int
    {
        $relations_query = $this->db
            ->prepare(
                "SELECT id 
                        FROM {$this->relation_table}
                        WHERE 
                            `product_id` = %d
                        order by id DESC LIMIT 1",
                $this->getRealID()
            );
        $relations_query = $this->db->get_var($relations_query);

        return ( ! empty($relations_query)) ? $relations_query : 0;
    }

    public function getPreImportID(): int
    {
        $import_id= $this->db
            ->prepare(
                "SELECT
                            import_id
                        FROM {$this->relation_table}
                         WHERE `product_id` = %d order by id DESC LIMIT 1",
                $this->new_id
            );
        $import_id = $this->db->get_var($import_id);

        return $import_id;
    }

    public function getRealID():int
    {
        if (empty($this->new_id)) {
            $real_id_query = $this->db
                ->prepare("SELECT product_id FROM {$this->relation_table}
                    WHERE
                        post_id = %d
                    ", $this->get_id());
            $real_id = $this->db->get_var($real_id_query);
        } else {
            $real_id = $this->new_id;
        }

        return (! empty($real_id)) ? (int)$real_id : 0;
    }

    public function getTotal(): int
    {
        $total_query = $this->db->get_var("
            SELECT
            COUNT(rel.id) FROM {$this->relation_table} rel
            INNER JOIN {$this->pre_import_table} pi ON rel.import_id = pi.id
            WHERE pi.is_imported <> 1 OR rel.post_id = 0
            ");
        return $total_query ? (int) $total_query: 0;
    }

    public function getValid(): int
    {
        return (int) $this->db->get_var("
                            SELECT COUNT(id)
                             FROM {$this->pre_import_table}  
                             WHERE is_valid <> 0 AND is_imported <> 1");
    }

    public function getErrors(): int
    {
        $duplicates = $this->db->get_results(
            "select
                    id
                    FROM {$this->relation_table} rel
                    WHERE (select count(id) FROM fz_woo_pre_import_relationships WHERE product_id = rel.product_id) > 1
                    GROUP BY product_id
                    ORDER BY id DESC");
        $other_errors = (int) $this->db->get_var("
                        SELECT COUNT(pi.id) 
                        FROM {$this->pre_import_table} pi
                        INNER JOIN {$this->relation_table} rel ON pi.id = rel.import_id
                         WHERE
                            pi.is_imported = 0 AND
                             pi.is_valid = 0");

        return $other_errors+count($duplicates);
    }

    public function setImportedFlag(bool $is_imported): self
    {
        $this->is_imported = $is_imported ?? false;

        return $this;
    }

    public function setValidationFlag(bool $is_valid): self
    {
        $this->is_valid = $is_valid ?? false;

        return $this;
    }

    public function save(): int
    {
        $id = parent::save();
        $this->setImportedFlag(1);

        $import_id = $this->getPreImportID();
        $relation_id = $this->getRelationsID();

        $query = $this->db
            ->prepare("UPDATE {$this->pre_import_table} pi
        SET pi.is_imported = 1 WHERE id = %d", $import_id);
        $this->db->query($query);

        $query = $this->db
            ->prepare("UPDATE {$this->relation_table}
                        SET post_id = %d 
                        WHERE import_id = %d", $id,
                $import_id);
        $this->db->query($query);
        return $id;
    }

    public function getCategory(): string
    {
        $ids      = $this->get_category_ids();
        $cat_list = [];

        foreach ($ids as $cat) {
            if (! empty($cat)) {
                $category   = get_term($cat);
                $cat_list[] = $category->name;
            } else {
                $cat_list[] = '';
            }
        }

        return implode('', $cat_list);
    }

    public function saveTemporary(): void
    {
        $this
            ->saveToPreImport()
            ->saveRelationships($this->new_id);
    }

    public function deletePreImportedProduct(int $relation_id): void
    {
        if (! empty($relation_id)) {
            $this->db
            ->query(
                $this->db->prepare(
                    "DELETE FROM {$this->relation_table} WHERE id = %d",
                    $relation_id
                )
            );
        }
    }

    private function saveToPreImport(): self
    {
        $db = $this->db;
        $table = $this->pre_import_table;
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
            'is_valid' => $this->is_valid,
            'is_imported' => $this->is_imported,
        ];

        $record_id = $db->get_var(
            $db->prepare("SELECT id FROM {$table} WHERE `sku`= %s LIMIT 1", $this->get_sku())
        );

        if (true === (bool)$record_id) {
//            $db->update($table, $data, ['id' => $record_id], $dataFormat);
        } else {
            $db->insert($table, $data, $dataFormat);
        }

        return $this;
    }

    private function saveRelationships(int $product_id): void
    {
        $db = $this->db;
        $sku = $this->get_sku();
        $pre_import_id = $db->get_var(
            $db->prepare("SELECT id FROM {$this->pre_import_table} WHERE `sku`= %s LIMIT 1", $sku)
        );

        $category_id = current($this->get_category_ids());
        $category = get_term($category_id);
        $parent_id = ($category->parent)??0;

        $relations = [
            'post_id' => $this->get_id(),
            'import_id' => $pre_import_id,
            'product_id' => $this->getRealID(),
            'product_category_id' => $category_id,
            'product_parent_category_id' => $parent_id,
            'product_author_id' => get_current_user_id(),
        ];
        $post_id = $this->get_id() ?? 0;
        $record_id = $db->get_var(
            $db->prepare("SELECT id FROM {$this->relation_table} WHERE `post_id`= %d LIMIT 1", $post_id)
        );
//        if ($post_id === 0) {
        $this->is_saved = true;
        $db->insert($this->relation_table, $relations);
//        }
    }
}
