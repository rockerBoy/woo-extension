<?php


namespace ExtendedWoo\ExtensionAPI\export;

use ExtendedWoo\Entities\Products;

final class Exporter implements ExporterInterface
{
    private array $columnNames = [];
    private array $columnsToExport = [];
    private array $categoriesToExport = [];
    private array $productTypesToExport = [];
    private int $page = 1;
    private int $limit = 50;
    private bool $enableMetaExport = false;
    private FileExportInterface $file_export;
    private string $exportType = 'product';
    private int $totalRows = 0;
    private int $exportedRowCount = 0;
    private array $rowData = [];
    private ?Products $productModel = null;

    public function __construct($file_export, $product)
    {
        try {
            $this->file_export = $file_export;
            $this->productModel = $product;
            $this->columnNames = $this->productModel->getDefaultColumnNames();

            $this->setProductTypesToExport(
                array_merge(
                    array_keys(
                        wc_get_product_types()
                    ),
                    array( 'variation' )
                )
            );
        } catch (Exception $exception) {
        }
    }

    public function setCategoriesToExport(array $categories_to_export): ExporterInterface
    {
        $this->categoriesToExport = array_map('sanitize_title_with_dashes', $categories_to_export);

        return $this;
    }

    public function setColumnsToExport(array $exportColumns): ExporterInterface
    {
        $this->columnsToExport = $exportColumns;

        return $this;
    }

    public function setProductTypesToExport(array $types): self
    {
        $this->productTypesToExport = array_map('wc_clean', $types);
        return $this;
    }

    public function setLimit(int $limit): ExporterInterface
    {
        $this->limit = $limit;

        return $this;
    }

    public function setPage(int $page): ExporterInterface
    {
        $this->page = $page;

        return $this;
    }

    public function enableMetaExport(bool $enable_meta_export = false): ExporterInterface
    {
        $this->enableMetaExport = $enable_meta_export;
        return $this;
    }

    public function isColumnExporting($column_id): bool
    {
        $column_id         = strstr($column_id, ':') ? current(explode(':', $column_id)) : $column_id;
        $columns_to_export = $this->columnsToExport;

        if (empty($columns_to_export)) {
            return true;
        }

        if (in_array($column_id, $columns_to_export, true) || 'meta' === $column_id) {
            return true;
        }

        return false;
    }


    public function prepareDataToExport(): void
    {
        $args = [
            'status'   => [
                'private',
                'publish',
                'draft',
                'future',
                'pending'
            ],
            'type'     => ['simple'],
            'limit' => $this->limit,
            'page' => $this->page,
            'orderby'  => ['ID' => 'ASC'],
            'return'   => 'objects',
            'paginate' => true,
        ];

        if (! empty($this->categoriesToExport)) {
            $args['category'] = $this->categoriesToExport;
        }

        $products = $this->productModel->setProductArgs($args)->getProducts();
        $this->totalRows  = $products->total;
        $headers = $this->getHeaders();
        $this->rowData[] = $headers;

        foreach ($products->products as $product) {
            $this->rowData[] = $this->generateRowData($product);
        }
    }

    public function getTotalExported(): int
    {
        return ( ( $this->page - 1 ) * $this->limit ) + $this->exportedRowCount;
    }

    public function getPercentComplete(): int
    {
        return $this->totalRows ? floor(( $this->getTotalExported() / $this->totalRows ) * 100) : 100;
    }

    /**
     *
     */
    public function generate(): void
    {
        $this->prepareDataToExport();
        $this->file_export->setRowData($this->rowData);
        $this->file_export->makeFile();
    }

    protected function filterDescriptionField($description)
    {
        $description = str_replace('\n', "\\\\n", $description);
        $description = str_replace("\n", '\n', $description);
        return $description;
    }

    /**
     * @return mixed
     */
    private function getColumnNames()
    {
        return apply_filters("woocommerce_{$this->export_type}_export_column_names", $this->columnNames, $this);
    }

    private function getHeaders(): array
    {
        $headers = [];
        foreach ($this->columnNames as $index => $column_name) {
            $column_id = strstr($index, ':') ? current(explode(':', $index)) : $index;
            if (!empty($this->columnsToExport) && !$this->isColumnExporting($column_id)) {
                continue;
            }
            $headers[] = $column_name;
        }

        return $headers;
    }
    /**
     * @param $product
     *
     * @return array
     */
    private function generateRowData($product): array
    {
        $columns = $this->getColumnNames();
        $row     = [];
        $categories = $this->productModel->getCategory($product);
        $brands = $this->productModel->getBrand($product);
        $countries = $this->productModel->getCountry($product);
        $parent_categories = $this->productModel->getCategory($product, true);

        foreach ($columns as $column_id => $column_name) {
            $column_id = strstr($column_id, ':') ? current(explode(':', $column_id)) : $column_id;

            if (!empty($this->columnsToExport) && !$this->isColumnExporting($column_id)) {
                continue;
            }
            $value = '';
            if (has_filter("woocommerce_product_export_{$this->export_type}_column_{$column_id}")) {
                // Filter for 3rd parties.
                $value = apply_filters("woocommerce_product_export_{$this->export_type}_column_{$column_id}", '', $product, $column_id);
            } elseif (is_callable(array( $this, "get_column_value_{$column_id}" ))) {
                // Handle special columns which don't map 1:1 to product data.
                $value = $this->{"get_column_value_{$column_id}"}($product);
            } elseif (is_callable(array( $product, "get_{$column_id}" ))) {
                // Default and custom handling.
                $value = $product->{"get_{$column_id}"}('edit');
            }

            switch ($column_id) {
                case "category_ids":
                    $value = $categories;
                    break;
                case "parent_category_ids":
                    $value = $parent_categories;
                    break;
                case "brands":
                    $value = html_entity_decode($brands);
                    break;
                case "manufacturers":
                    $value = $countries;
                    break;
            }

            if ('description' === $column_id || 'short_description' === $column_id) {
                $value = $this->filterDescriptionField($value);
            }

            $row[$column_id] = $value;
        }
        return apply_filters('woocommerce_product_export_row_data', $row, $product);
    }
}
