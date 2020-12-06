<?php


namespace ExtendedWoo\ExtensionAPI\models\export;

use ExtendedWoo\Entities\Products;

final class Exporter
{
    private array $columnNames;
    private array $columnsToExport = [];
    private string $categoriesToExport;
    private array $productTypesToExport;
    private int $page = 1;
    private int $limit = 50;
    private bool $enableExportAll = false;
    private bool $exportWithoutImages = false;
    private FileExportInterface $file_export;
    private int $totalRows = 0;
    private int $exportedRowCount = 0;
    private array $rowData = [];
    private ?Products $productModel = null;

    public function __construct($file_export, $product)
    {
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
    }

    public function setCategoriesToExport(string $categories_to_export): self
    {
        $this->categoriesToExport = $categories_to_export;

        return $this;
    }

    public function setColumnsToExport(array $exportColumns): self
    {
        $this->columnsToExport = $exportColumns;

        return $this;
    }

    public function setProductTypesToExport(array $types): self
    {
        $this->productTypesToExport = array_map('wc_clean', $types);
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function setExportAll(bool $export_all = false): self
    {
        $this->enableExportAll = $export_all;

        return $this;
    }

    public function setExportWithoutImages(bool $without_images = false): self
    {
        $this->exportWithoutImages = $without_images;

        return $this;
    }

    public function isColumnExporting($column_id): bool
    {
        $column_id         = strpos($column_id, ':') !== false ? current(explode(':', $column_id)) : $column_id;
        $columns_to_export = $this->columnsToExport;

        if (empty($columns_to_export)) {
            return true;
        }

        if ('meta' === $column_id || in_array($column_id, $columns_to_export, true)) {
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
            'category' => $this->categoriesToExport,
            'type'     => ['simple'],
            'limit' => $this->limit,
            'page' => $this->page,
            'orderby'  => ['ID' => 'ASC'],
            'return'   => 'objects',
            'paginate' => true,
        ];

        $products = $this->productModel->setProductArgs($args)->getProducts($this->enableExportAll);

        if (! empty($this->exportWithoutImages) && true === $this->exportWithoutImages) {
            foreach ($products->products as $key => $product) {
                if (true === (bool) $product->get_image_id()) {
                    unset($products->products[$key]);
                    $products->total -= 1;
                }
            }
        }

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
        $description = str_replace(array('\n', "\n"), array("\\\\n", '\n'),
            $description);
        return $description;
    }

    private function getHeaders(): array
    {
        $headers = [];
        foreach ($this->columnNames as $index => $column_name) {
            $column_id = strpos($index, ':') !== false ? current(explode(':', $index)) : $index;
            if (!empty($this->columnsToExport) && !$this->isColumnExporting($column_id)) {
                continue;
            }
            $headers[] = $column_name;
        }

        $cat_id = get_term_by('slug', $this->categoriesToExport, 'product_cat');
        $attributes = $this->productModel->getCategoryAttributes($cat_id->term_id);

        if (! empty($attributes)) {
            $headers = array_merge($headers, $attributes);
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
        $columns = $this->columnNames;
        $row = [];
        $categories = $this->productModel->getCategory($product);
        $brands = $this->productModel->getBrand($product);
        $cat_id = current($product->category_ids);
        $attributes = $this->productModel->getCategoryAttributes($cat_id);
        $countries = $this->productModel->getCountry($product);
        $parent_categories = $this->productModel->getCategory($product, true);

        foreach ($columns as $column_id => $column_name) {
            $column_id = strpos($column_id, ':') !== false ? current(explode(':', $column_id)) : $column_id;

            if (!empty($this->columnsToExport) && !$this->isColumnExporting($column_id)) {
                continue;
            }
            $value = '';

            if (is_callable(array( $this, "get_column_value_{$column_id}" ))) {
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
                case "images":
                    $id = $product->get_image_id() ?? 0;
                    $value = wp_get_attachment_image_url($id, 'full');
                    break;
            }

            if ('description' === $column_id || 'short_description' === $column_id) {
                $value = $this->filterDescriptionField($value);
            }

            $row[$column_id] = $value;
        }

        $extra = [];
        if (! empty($attributes)) {
            $atts = $product->get_attributes();
            foreach ($attributes as $index => $name) {
                foreach ($atts as $item) {
                    if ($name === $item['data']['name']) {
                        $extra[$index] = implode(', ', $item['data']['options']);
                    } else if(empty($extra[$index])) {
                        $extra[$index] = '';
                    }
                }
            }
            $row = array_merge($row, $extra);
        }

        return $row;
    }
}
