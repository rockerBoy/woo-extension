<?php


namespace ExtendedWoo\ExtensionAPI\interfaces\export\import;

use ExtendedWoo\ExtensionAPI\interfaces\export\interfaces\import\ImportType;
use Symfony\Component\HttpFoundation\Request;
use WP_Error;

abstract class BasicController
{
    protected array $errors = [];
    protected Request $request;
    protected string $import_views_path;
    protected string $step;
    protected array $steps;
    protected ImportType $importStrategy;
    protected int $startRow = 1;
    protected string $file;

    public function __construct(Request $request)
    {
        wp_deregister_script('wc-product-import');

        $this->request           = $request;
        $this->import_views_path = __DIR__ . '/../../views/import/';
    }

    public function setImportType(ImportType $import_type): self
    {
        $this->importStrategy = $import_type;
        return $this;
    }

    public function getNextStepLink(string $step = ''): string
    {
        if (! $step) {
            $step = $this->step;
        }

        $keys = array_keys($this->steps);
        if (end($keys) === $step) {
            return admin_url();
        }

        $step_index = array_search($step, $keys, true);

        if ($step_index === false) {
            return '';
        }

        $params = [
            'step'            => $keys[$step_index + 1],
            'file'            => str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $this->file
            ),
            'start_row' => $this->startRow,
            '_wpnonce'        => wp_create_nonce('etx-xls-importer'),
        ];
        return add_query_arg($params);
    }

    /**
     * Import steps dispatcher.
     */
    public function dispatch(): void
    {
        $request = $this->request;
        global $wpdb;

        if (! empty($request->get('remove_step'))) {
            if (is_file($this->file)) {
                $last_file_id = $wpdb->get_var("
                    SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' ORDER BY ID DESC LIMIT 1");
                wp_delete_attachment($last_file_id);
            }
            wp_redirect(esc_url_raw('admin.php?page=excel_import'));
        }

        if (! empty($this->steps[$this->step]['handler'])
            &&
            ! empty($request->get('save_step'))
        ) {
            call_user_func($this->steps[$this->step]['handler'], $this);
        }

        $this
            ->showHeader()
            ->showSteps()
            ->showErrors();
        call_user_func($this->steps[$this->step]['view'], $this);
        $this->showFooter();
    }

    public function uploadImportHandler(): void
    {
        $file = $this->handleUpload();
        if (is_wp_error($file)) {
            $this->addErrors($file->get_error_message());

            return;
        }

        $this->file = $file;
        wp_redirect(esc_url_raw($this->getNextStepLink()));
    }

    public function uploadFormHandler(): void
    {
        $file = $this->handleUpload();
        $request = $this->request;
        if (is_wp_error($file)) {
            $this->addErrors($file->get_error_message());
            return;
        }

        $this->file = $file;

        wp_redirect(esc_url_raw($this->getNextStepLink()));
    }

    public function handleUpload()
    {
        $request  = $this->request;
        $file_url = ( ! empty($request->get('file_url')))
            ? wc_clean(wp_unslash($request->get('file_url'))) : '';
        $this->startRow = (! empty($request->get('starting_row')))
            ? wc_clean(wp_unslash($request->get('starting_row'))) : 1;

        if (empty($file_url) && ! isset($_FILES['import'])) {
            return new WP_Error(
                'extendedwoo_product_xls_importer_upload_file_empty',
                __(
                    'File is empty. Please upload something more substantial.
                 This error could also be caused by uploads being disabled in
                  your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.',
                    'woocommerce'
                )
            );
        }

        if (empty($file_url)) {
            $import = $_FILES['import'];
            $upload = wp_handle_upload($import, [
                'test_form' => false,
                'mimes'     => [
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'xls'  => 'application/vnd.ms-excel',
                ],
            ]);

            if (isset($upload['error'])) {
                return new WP_Error(
                    'woocommerce_product_csv_importer_upload_error',
                    $upload['error']
                );
            }

            $object = [
                'post_title'     => basename($upload['file']),
                'post_content'   => $upload['url'],
                'post_mime_type' => $upload['type'],
                'guid'           => $upload['url'],
                'context'        => 'import',
                'post_status'    => 'private',
            ];

            $id = wp_insert_attachment($object, $upload['file']);
            wp_schedule_single_event(
                time() + DAY_IN_SECONDS,
                'importer_scheduled_cleanup',
                array($id)
            );
            $f_size = (new ProductExcelImporter($upload['file']))
                ->getImportSize();
            if ($f_size <= 1) {
                return new WP_Error(
                    'extendedwoo_product_xls_importer_upload_invalid_file',
                    __(
                        'The file is empty or using a different encoding than UTF-8, please try again with a new file.',
                        'woocommerce'
                    )
                );
            }

            return $upload['file'];
        }

        if (file_exists(ABSPATH . $file_url)) {
            return ABSPATH . $file_url;
        }

        return new WP_Error(
            'extendedwoo_product_xls_importer_upload_invalid_file',
            __('Пожалуйста, загрузите валидный файл .xls', 'extendedwoo')
        );
    }

    /**
     * Add error message.
     *
     * @param string $message Error message.
     * @param array  $actions List of actions with 'url' and label.
     */
    protected function addErrors(string $message, array $actions = []): void
    {
        $this->errors[] = [
            'message' => $message,
            'actions' => $actions,
        ];
    }

    /**
     * Add error message.
     */
    protected function showErrors(): self
    {
        if (empty($this->errors)) {
            return $this;
        }

        foreach ($this->errors as $error) {
            echo '<div class="error inline">';
            echo '<p>' . esc_html($error['message']) . '</p>';

            if (! empty($error['actions'])) {
                echo '<p>';
                foreach ($error['actions'] as $action) {
                    echo '<a class="button button-primary" href="'
                         . esc_url($action['url']) . '">'
                         . esc_html($action['label']) . '</a> ';
                }
                echo '</p>';
            }
            echo '</div>';
        }

        return $this;
    }

    /**
     * Display header view.
     *
     * @return $this
     */
    protected function showHeader(): self
    {
        include $this->import_views_path . 'import-header.php';

        return $this;
    }

    /**
     * Display steps view.
     *
     * @return $this
     */
    protected function showSteps(): self
    {
        include $this->import_views_path . 'import-steps.php';

        return $this;
    }

    protected function showFooter(): self
    {
        include $this->import_views_path . 'import-footer.php';

        return $this;
    }
}
