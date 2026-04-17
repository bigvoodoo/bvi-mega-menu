<?php if (empty($options)) {
    return;
}

foreach ($options as $key => $option) :
    // support both ['value' => x, 'label' => y] and flat 'value' => 'label' formats
    if (is_array($option)) {
        $option_value = $option['value'] ?? $key;
        $option_label = $option['label'] ?? $option_value;
    } else {
        $option_value = $key;
        $option_label = $option;
    }
?>
<option value="<?php echo esc_attr($option_value); ?>" <?php selected($selected, $option_value); ?>>
    <?php echo esc_html($option_label); ?>
</option>
<?php endforeach; ?>
