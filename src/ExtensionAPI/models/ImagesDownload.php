<?php


namespace ExtendedWoo\ExtensionAPI\models;


use ExtendedWoo\Entities\Product;

final class ImagesDownload
{
    private Product $product;
    private const IMAGES_HOST = 'https://rost.kh.ua/photo/';

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    function downloadImage(): void
    {
        $image_id = $this->product->get_image_id();

        if (empty($image_id)) {
            $url = self::IMAGES_HOST.$this->product->getRealID().'.jpg';
            $image_data = wc_rest_upload_image_from_url($url);

            if (! $image_data instanceof \WP_Error) {
                global $wpdb;

                $image_id = wc_rest_set_uploaded_image_as_attachment($image_data);
                $product_name = $this->product->get_name();
                $wpdb->update($wpdb->posts, [
                                                'post_content' => $product_name,
                                                'post_title' => $product_name,
                    ],
                    ['ID' => $image_id],
                    ['%s', '%s'],
                );
                $this->product->set_image_id($image_id);
                $this->product->save();
            }
        }
    }
}