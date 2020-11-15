<?php


namespace ExtendedWoo\Entities;

final class Product extends \WC_Product_Simple
{
    private int $new_id = 0;
    private string $filename = '';
    private \wpdb $db;
    private \DateTimeImmutable $time;
    private bool $is_valid = false;
    private bool $is_imported = false;
    private string $pre_import_table;
    private string $relation_table;
    private string $uuid;
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
        $this->set_status('private');
    }

    public function setNewID(int $id)
    {
        $this->new_id = absint($id);

        return $this;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Setting up unique transaction ID
     *
     * @param string $uuid
     *
     * @return $this
     */
    public function setUUID(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUUID(): string
    {
        if (empty($this->uuid)) {
            return $this->db->get_var("SELECT `imported_file_token` FROM $this->relation_table ORDER BY id DESC LIMIT 1") ?? '';
        } else {
            return $this->uuid;
        }
    }

    public function getNewID(): int
    {
        return $this->new_id;
    }

    public function getRelationsID(): int
    {
        $uuid = $this->getUUID();

        if (! empty($uuid)) {
            $relations_query = $this->db
                ->prepare(
                    "SELECT
                                id
                            FROM {$this->relation_table}
                            WHERE
                                `product_id` = %d AND 
                                `imported_file_token` = %s
                            order by id ASC LIMIT 1",
                    $this->getRealID(),
                    $uuid
                );
            $relations_query = $this->db->get_var($relations_query);
        } else {
            $relations_query = $this->db
                ->prepare(
                    "SELECT
                                id
                            FROM {$this->relation_table}
                            WHERE
                                `product_id` = %d
                            order by id ASC LIMIT 1",
                    $this->getRealID()
                );
        }

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

        return (!$import_id)? 0: $import_id;
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
        $uuid = $this->getUUID();
        if (!empty($uuid)) {
            $this->uuid = $uuid;
            $total_query = $this->db->get_var("
            SELECT COUNT(rel.id) FROM {$this->relation_table} rel
            WHERE 
                rel.imported_file_token = \"$this->uuid\"
            ");
        } else {
            $total_query = $this->db->get_var("
            SELECT
            COUNT(rel.id) FROM {$this->relation_table} rel
            INNER JOIN {$this->pre_import_table} pi ON rel.import_id = pi.id
            WHERE pi.is_imported <> 1 OR rel.post_id = 0
            ");
        }

        return $total_query ? (int) $total_query: 0;
    }

    public function getValid(): int
    {
        $uuid = $this->getUUID();
        $valid = 0;

        if (! empty($uuid)) {
            $valid = (int) $this->db->get_var("
                                SELECT 
                                    COUNT(`pit`.id) as id
                                 FROM {$this->pre_import_table}  `pit`
                                 LEFT JOIN {$this->relation_table} `rel` ON `pit`.`id` = `rel`.`import_id`
                                 WHERE 
                                    (`pit`.sku != '' AND `pit`.product_title != '' AND `rel`.product_category_id <> 0) AND
                                    `rel`.imported_file_token = \"$uuid\"");
            $duplicates = $this->db->get_results("SELECT
                                                                COUNT(rel.id) as qnu
                                                            FROM {$this->relation_table} rel
                                                            GROUP BY rel.product_id
                                                            HAVING  qnu > 1");
            $duplicates_qnt = sizeof($duplicates);
            if ($duplicates_qnt <= $valid) {
                $valid -= $duplicates_qnt;
            }
        }

        return $valid;
    }

    public function getErrors(): int
    {
        $uuid = $this->getUUID();
        $errors = 0;

        if (! empty($uuid)) {
            $errors = (int) $this->db->get_var("
                            SELECT COUNT(rel.id) 
                            FROM {$this->relation_table} rel
                            INNER JOIN {$this->pre_import_table} pi ON rel.import_id = pi.id
                             WHERE
                                (rel.product_category_id = 0 OR pi.sku = '') AND
                                rel.imported_file_token = \"$uuid\"
                            ");
        }
        return $errors;
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
            ->prepare(
                "UPDATE {$this->relation_table}
                        SET post_id = %d 
                        WHERE import_id = %d",
                $id,
                $import_id
            );
        $this->db->query($query);
        return $id;
    }

    public function getProductIDBySKU(string $sku)
    {
        return $this->db->get_var("SELECT `rel`.post_id
                                        FROM {$this->relation_table} `rel`
                                        INNER JOIN {$this->pre_import_table} `pi` ON `rel`.import_id = `pi`.id                  
                                        WHERE
                                            `pi`.`sku` = '{$sku}'");
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
            $product_id = $this->db->get_var("SELECT `product_id` FROM {$this->relation_table}
                WHERE `id` IN($relation_id) LIMIT 1");
            if (! empty($product_id)) {
                $is_duplicate = $this->db->get_var("SELECT `id` FROM {$this->relation_table}
                    WHERE `product_id` IN($product_id) AND `id` NOT IN($relation_id) ORDER BY id DESC LIMIT 1");
            }

            $import_id = $this->db->get_var("SELECT `import_id` FROM {$this->relation_table}
                WHERE `id` = $relation_id LIMIT 1
            ");
            $this->db
            ->query(
                $this->db->prepare(
                    "DELETE FROM {$this->relation_table} WHERE id = %d",
                    $relation_id
                )
            );
            $this->db
            ->query(
                $this->db->prepare(
                    "DELETE FROM {$this->pre_import_table} WHERE id = %d AND is_valid <> 1 AND is_imported <> 1",
                    $import_id
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
            'imported_file_token' => $this->uuid,
        ];

        $post_id = $this->get_id() ?? 0;

        $record_id = $db->get_var(
            $db->prepare("SELECT id FROM {$this->relation_table} WHERE `post_id`= %d LIMIT 1", $post_id)
        );
        $this->is_saved = true;
        $db->insert($this->relation_table, $relations);
    }

    public function clearDuplicatedRelations(): self
    {
        $existing_product_ids = get_posts(array(
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        $list_of_product_skus = [];

        foreach ($existing_product_ids as $id) {
            $product = wc_get_product($id);
            $list_of_product_skus[] = $product->get_sku();
        }

        if (empty($list_of_product_skus)) {
            $this->db->query("TRUNCATE TABLE {$this->pre_import_table}");
            $this->db->query("TRUNCATE TABLE {$this->relation_table}");
        }

        if (! empty($existing_product_ids)) {
            $limit = sizeof($existing_product_ids);
            $product_ids = implode(', ', $existing_product_ids);
            $unique_ids = [];
            $unique_products = $this->db
                ->prepare("SELECT `id`
                                    FROM {$this->relation_table}
                                    WHERE `post_id` IN($product_ids) GROUP BY `post_id` LIMIT %d  
                                 ", $limit);
            $unique_products_list = $this->db->get_results($unique_products, ARRAY_A);

            foreach ($unique_products_list as $row) {
                $unique_ids[] = $row['id'];
            }
            $unique_ids_list = implode(', ', $unique_ids);
            $product_skus_list = '"'.implode('", "', $list_of_product_skus).'"';

            if (! empty($product_skus_list)) {
                $this->db->query("DELETE FROM {$this->pre_import_table}
                    WHERE `sku` NOT IN($product_skus_list)");
            }

            if (! empty($unique_ids_list)) {
                $this->db->query("DELETE FROM {$this->relation_table}
                    WHERE `id` NOT IN($unique_ids_list)");
                $this->db->query("DELETE FROM {$this->relation_table}
                    WHERE `post_id` = 0 AND `product_category_id` = 0");
            }
        } else {
            $this->db->query("DELETE FROM {$this->relation_table}
                WHERE `post_id` = 0");
        }

        return $this;
    }


    public function getStoredInfoByProduct(int $relation_id, int $product_id)
    {
        $stored_data = $this->db->prepare("
                                            SELECT
                                                pi.id as id,
                                                IF(pi.product_title = '', false, pi.product_title) as name,
                                                IF(pi.sku = '', false, pi.sku) as sku,
                                                rel.product_category_id as category
                                            FROM {$this->relation_table} rel
                                            INNER JOIN {$this->pre_import_table} pi on rel.import_id = pi.id
                                            WHERE
                                                `rel`.id = %d AND
                                                `rel`.product_id = %d
                                            LIMIT 1", $relation_id, $product_id);
        return $this->db->get_row($stored_data, ARRAY_A);
    }

    public function fixUploadedProductName(int $import_id, string $name)
    {
        $prepare_fix = $this->db->prepare(
            "UPDATE {$this->pre_import_table} SET `product_title` = %s WHERE `id` = %d",
            $name,
            $import_id
        );

        return $this->db->query($prepare_fix);
    }

    public function fixUploadedProductCategory(int $relation_id, int $category)
    {
        $term = get_term($category);
        $prepared_query = $this->db
            ->prepare(
                "UPDATE {$this->relation_table}
                    SET product_category_id = %d,
                        product_parent_category_id = %d
                      WHERE id = %d",
                $term->term_id,
                ($term->parent) ?? 0,
                $relation_id
            );

        return $this->db->query($prepared_query);
    }

    public function fixUploadedProductSKU(int $import_id, string $sku)
    {
        $is_post_exists = wc_get_product_id_by_sku($sku);
        $is_preimported  = $this->db->get_var("SELECT `id` FROM {$this->pre_import_table}
                                                        WHERE `sku` = '{$sku}'
                                                        LIMIT 1");

        if (!$is_preimported && !$is_post_exists) {
            $prepare_fix = $this->db->prepare(
                "UPDATE {$this->pre_import_table} SET `sku` = %s WHERE `id` = %d",
                $sku,
                $import_id
            );
            return $this->db->query($prepare_fix);
        }

        return false;
    }
}
