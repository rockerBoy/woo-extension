<?php


namespace ExtendedWoo\ExtensionAPI\import;


use ExtendedWoo\ExtensionAPI\helpers\ProductsImportHelper;

class IDsExcelImporter extends ProductExcelUpdater
{
    protected function prepareRows(): array
    {
        $prepared_data = [];
        $mapping = $this->params['mapping']['to'];

        foreach ($this->columns as $index => $row) {
            if ($index === 1) {
                continue;
            }

            $prepared_row = array_slice(array_values($row), 0, count($mapping));
            $prepared_row = ProductsImportHelper::parseRow($prepared_row, $mapping);
            $prepared_data[] = $prepared_row;
        }

        return $prepared_data;
    }

    public function update(int $index = 0): array
    {
        $ids = $this->prepareRows();
        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];

        $this->db->query("START TRANSACTION");

        foreach ($ids as $id) {
            $data['updated'][] = $this->process($id);
        }

        $this->db->query("COMMIT");

        $this->synchronizeImportedProducts();

        return $data;
    }

    public function process(array $data)
    {
        if (! empty($data)) {
            $db = $this->db;
            $rost_id = $data['id'] ?? '';
            $sku = $data['sku'] ?? '';
            $imported_id = $db->get_var("SELECT id FROM {$db->prefix}woo_imported_relationships WHERE `product_id` = $rost_id");

            if (! empty($rost_id) && !empty($sku)) {
                $product_id = wc_get_product_id_by_sku($sku);

                if (! empty($product_id) && empty($imported_id)) {
                    $data = [
                        'product_id' => $rost_id,
                        'post_id' => $product_id,
                        'sku' => $sku
                    ];
                    $format = ['%d', '%d', '%s',];
                    $db->insert("{$db->prefix}woo_imported_relationships", $data, $format);
                }
            }
        }

        return '';
    }

    private function synchronizeImportedProducts(): void
    {
        $db = $this->db;
        $table = "{$db->prefix}woo_pre_import";

        $db->query("START TRANSACTION");
        $query = $db->prepare("SELECT
                                        `rel`.`product_id` as product_id,
                                        `rel`.`post_id` as post_id,
                                        `pi`.sku as sku
                                    FROM {$table}_relationships `rel`
                                    INNER JOIN {$table} `pi` ON `rel`.import_id = `pi`.id
                                    WHERE
                                        `rel`.`product_id` NOT IN( SELECT `product_id` FROM {$db->prefix}woo_imported_relationships)
                                    ");
        $nonSyncData = $db->get_results($query);

        foreach ($nonSyncData as $product) {
            $id = $product->product_id;
            $imported_id = $db->get_var("SELECT id FROM {$db->prefix}woo_imported_relationships WHERE `product_id` = $id");

            if (empty($imported_id) && ! empty($id)) {
                $data = [
                    'product_id' => $id,
                    'post_id' => $product->post_id,
                    'sku' => $product->sku
                ];
                $format = ['%d', '%d', '%s',];
                $db->insert("{$db->prefix}woo_imported_relationships", $data, $format);
            }
        }

        $db->query("COMMIT");
    }
}