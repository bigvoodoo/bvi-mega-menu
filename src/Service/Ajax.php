<?php

namespace Bvi\Plugin\MegaMenu\Service;

use Bvi\Plugin\MegaMenu\Service\Shortcode\MegaMenuShortcode;

/**
 * Handles AJAX requests for mega menu dropdown content.
 *
 * Registers a custom rewrite rule so dropdowns can be loaded via
 * /ajax_mega_menu/{theme_location}/{parent_id}.
 *
 * @package bvi-mega-menu
 */
class Ajax
{
    public function __construct()
    {
        add_filter('rewrite_rules_array', [$this, 'add_rewrite_rules']);
        add_action('parse_request', [$this, 'handle_request']);
    }

    /**
     * Add the AJAX rewrite rule.
     *
     * @param array $rules Existing rewrite rules.
     * @return array Modified rewrite rules.
     */
    public function add_rewrite_rules(array $rules): array
    {
        $new_rules = [
            '^ajax_mega_menu/([^/]+)/([0-9]+)' =>
                'index.php?mega_menu_ajax=true&theme_location=$matches[1]&parent=$matches[2]',
        ];

        return array_merge($new_rules, $rules);
    }

    /**
     * Handle an AJAX mega menu request.
     *
     * @param \WP $wp The WordPress environment instance.
     * @return void
     */
    public function handle_request(\WP $wp): void
    {
        parse_str($wp->matched_query ?? '', $query);

        if (!isset($query['mega_menu_ajax']) || $query['mega_menu_ajax'] !== 'true') {
            return;
        }

        if (empty($query['theme_location']) && empty($query['parent'])) {
            status_header(400);
            echo 'Error: bad request.';
            die();
        }

        $charset = get_bloginfo('charset');
        header('Content-Type: text/html; charset=' . $charset);
        header('Expires: ' . gmdate(DATE_RFC1123, strtotime('+1 hour')));
        header('Cache-Control: public, must-revalidate, proxy-revalidate');
        header('Pragma: public');

        $shortcode = new MegaMenuShortcode();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $atts = array_merge($_GET, [
            'theme_location' => $query['theme_location'],
            'ajax' => $query['parent'],
        ]);

        // render() returns the plugin's own menu markup, escaped as it is built.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin-generated markup
        echo $shortcode->render($atts);

        die();
    }
}
