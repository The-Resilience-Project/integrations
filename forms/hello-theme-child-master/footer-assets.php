<?php

/**
 * Footer Assets
 *
 * Progress bar reveal script and GF Address Enhanced plugin asset loading.
 */

// Show progress bar after form render (hidden initially by hide_progress_bar_wrap)
add_action('wp_footer', function () {
    ?>
    <script>
    jQuery(document).bind('gform_post_render', function (event, formId, current_page) {
        jQuery("div.wrap_progress_bar").css({'visibility':'visible', 'display':''});
    });
    </script>
    <?php
});

// GF Address Enhanced plugin assets
add_action('wp_footer', 'enqueue_gf_address_enhanced_assets');
function enqueue_gf_address_enhanced_assets()
{
    // Load the assets from a separate file to keep functions.php clean
    $asset_file = __DIR__ . '/gf-address-enhanced-assets.php';
    if (file_exists($asset_file)) {
        include $asset_file;
    }
}
