<?php

namespace Bvi\Plugin\MegaMenu\Utils\Traits;

use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Handles all security and verification actions
 */
trait Security
{
    use Strings;

    /**
     * Validates the nonce value for a given post request.
     *
     * This function checks the nonce value from the post data against the expected
     * nonce for the plugin namespace. If the nonce verification fails, a JSON
     * error response is sent indicating a security check failure.
     *
     * @param array $post The post data containing the nonce.
     * @return bool True if the nonce is valid, otherwise a JSON error is sent.
     */
    public function validate_nonce($post, $nonce = '', $nonce_action = ''): bool
    {
        $nonce = $nonce ?? BVI_PLUGIN_MEGAMENU_NAMESPACE . '_nonce';
        $nonce_action = $nonce_action ?? $nonce . '_action';

        // validate nonce value for our own namespace handlers only
        if (empty($post[$nonce]) || !wp_verify_nonce($post[$nonce], $nonce_action)) {
            error_log('bvimegamenu::Security::validate_nonce: security check failed');

            return false;
        }

        return true;
    }
}
