<?php

// prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// render field description if available
if (!empty($description)) {
    echo '<p class="description">' . esc_html($description) . '</p>';
}
