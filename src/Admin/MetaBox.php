<?php

namespace Bvi\Plugin\MegaMenu\Admin;

use Bvi\Plugin\MegaMenu\Utils\Traits\Security;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

class MetaBox
{
    use Security;

    /** @var Renderer */
    private $renderer;

    /** @var Sanitizer */
    private $sanitizer;

    /** @var array */
    private $config = [];

    public function __construct()
    {
        $this->renderer = new MetaBox\Renderer();
        $this->sanitizer = new MetaBox\Sanitizer();
    }

    /**
     * Configure any requested WordPress meta box configurations.
     *
     * @since 0.1.0
     */
    public function configure($config)
    {
        // if the basics for the config are missing, return
        if (empty($config) || !is_array($config) || empty($config['id'])) {
            return false;
        }

        // pull values from the passed config
        $this->config = [
            'id' => $config['id'],
            'title' => $config['title'] ?? BVI_PLUGIN_MEGAMENU_NAME . ' Custom Meta Box',
            'screen' => $config['screen'] ?? [],
            'context' => $config['context'] ?? 'side',
            'priority' => $config['priority'] ?? 10,
            'fields' => $config['fields'] ?? [],
            'options' => $config['options'] ?? [],
            'class_name' => $config['class_name'],
            'template_id' => $config['template_id'] ?? '',
            'prefix' => $config['prefix'] ?? '_' . BVI_PLUGIN_MEGAMENU_NAMESPACE,
            'before_content' => $config['before_content'] ?? '',
            'after_content' => $config['after_content'] ?? '',
        ];

        // set the nonce after the prefix has been set
        $this->config['nonce'] = $this->config['prefix'] . '_nonce' ?? '';

        // register the default WordPress actions for meta boxes
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
    }

    /**
     * Register the meta box through WordPress core actions.
     *
     * @since 0.1.0
     */
    public function register_meta_box(): void
    {
        add_meta_box(
            $this->config['id'],
            $this->config['title'],
            [$this, 'render_meta_box'],
            $this->config['screen'],
            $this->config['context'],
            $this->config['priority'],
        );
    }

    /**
     * Render the requested meta box.
     *
     * @param WP_Post the post object
     * @return void
     *
     * @since 0.1.0
     */
    public function render_meta_box(\WP_Post $post)
    {
        $this->renderer->render_box($this->config, $post);
    }

    /**
     * Save the data user submitted for the meta box.
     *
     * @param int id of the post
     * @param WP_Post the post object
     * @return bool if the rendering was successful or failed
     *
     * @since 0.1.0
     */
    public function save_meta_box(int $post_id, \WP_Post $post): bool
    {
        // skip gutenberg save requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        // if our nonce field isn't present, this save isn't from our meta box: bail silently
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via validate_nonce() immediately below
        if (empty($_POST[$this->config['nonce']])) {
            return false;
        }

        // validate incoming nonce (validate_nonce() wraps wp_verify_nonce())
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is the nonce verification
        if (!$this->validate_nonce($_POST, $this->config['nonce'], $this->config['nonce'] . '_action')) {
            return false;
        }

        // dont save during an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        // ensure this user can edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        // dont save over existing revisions
        if (wp_is_post_revision($post_id)) {
            return false;
        }

        // get submitted data for our prefix; each field is sanitized by the sanitizer below
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; per-field sanitization runs in the loop
        $submitted_data = isset($_POST[$this->config['prefix']]) ? wp_unslash($_POST[$this->config['prefix']]) : [];

        if (empty($submitted_data)) {
            return false;
        }

        // if theres nothing to submit, return
        if (empty($this->config['fields']) || !is_array($this->config['fields'])) {
            return false;
        }

        // sanitize each field
        $this->sanitizer->set_field_configs($this->config['fields']);

        try {
            // loop through each field and save as a serialized array
            foreach ($this->config['fields'] as $field) {
                $field_id = $field['label_for'] ?? ( $field['id'] ?? '' );
                $meta_key = $this->config['prefix'] . $field_id;
                $value = $submitted_data[$field_id] ?? null;

                if ($value !== null) {
                    $sanitized = $this->sanitizer->sanitize([$field_id => $value]);

                    update_post_meta($post_id, $meta_key, $sanitized[$field_id]);
                } else {
                    $field_type = $field['type'] ?? 'text';

                    // handle unchecked checkboxes, otherwise remove
                    if ($field_type === 'checkbox' || $field_type === 'radio') {
                        update_post_meta($post_id, $meta_key, 0);
                    } else {
                        delete_post_meta($post_id, $meta_key);
                    }
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
