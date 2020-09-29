<?php


namespace ExtendedWoo\ExtensionAPI\import;

use Symfony\Component\HttpFoundation\Request;

abstract class BasicController
{
    protected array $errors = [];
    protected Request $request;
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

    abstract public function dispatch(): void;
}
