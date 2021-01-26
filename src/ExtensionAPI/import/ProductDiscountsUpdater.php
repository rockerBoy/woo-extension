<?php


namespace ExtendedWoo\ExtensionAPI\import;

class ProductDiscountsUpdater extends ProductExcelUpdater
{
    public function update(int $index = 0): array
    {
        $discounts = $this->prepareRows();
        $data = [
            'author_id' => get_current_user_id(),
            'failed'   => [],
            'updated'  => [],
        ];

        if (empty($discounts[$index])) {
            return [];
        }

        $product_discount = $discounts[$index];

        if (! empty($product_discount['regular_price']) &&
            ! empty($product_discount['sale_price']) &&
            ! empty($product_discount['date_on_sale_from']) &&
            ! empty($product_discount['date_on_sale_to'])

        ) {
            $data['updated'][] = $this->process($product_discount);
        } else {
            $data['skipped'][] = $this->process($product_discount);
        }

        return $data;
    }

    private function process(array $data)
    {
        $id = wc_get_product_id_by_sku($data['sku']);
        $columns = [
            'min_price' => $data['sale_price'] ?? 0,
            'max_price' => $data['regular_price'] ?? 0,
        ];

        $product = wc_get_product($id);

        if ($product && $columns['max_price'] && $columns['max_price'] > 0) {
            $product->set_price((float)$columns['max_price']);
            $product->set_sale_price((float)$columns['min_price']);
            $product->set_regular_price((float)$columns['max_price']);

            if (! empty($data['date_on_sale_from'])) {
                $date = str_replace('/', '-', $data['date_on_sale_from']);
                $date = strtotime($date);
                $product->set_date_on_sale_from($date);
            }

            if (! empty($data['date_on_sale_to'])) {
                $date = str_replace('/', '-', $data['date_on_sale_to']);
                $date = strtotime($date);
                $product->set_date_on_sale_to($date);
            }

            $product->save();
        }

        return $product;
    }
}