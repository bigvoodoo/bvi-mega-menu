<?php

namespace Bvi\Plugin\MegaMenu\Interfaces;

/**
 * Interface for admin feature classes.
 *
 * All admin features must implement this interface to be
 * discoverable by the Discovery utility during registration.
 *
 * @since 5.0.0
 */
interface Feature
{
    /**
     * Whether this feature is enabled.
     *
     * @since 5.0.0
     *
     * @return bool
     */
    public function get_enabled(): bool;

    /**
     * Returns the feature slug.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public function get_slug(): string;

    /**
     * Returns the settings page id.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public function get_page_id(): string;

    /**
     * Returns the database option id.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public function get_db_id(): string;

    /**
     * Registers the admin settings page for this feature.
     *
     * @since 5.0.0
     *
     * @return object
     */
    public function register_new_admin_settings(): object;
}
