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
     * @since 5.0.0
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
            return false;
        }

        return true;
    }
}
