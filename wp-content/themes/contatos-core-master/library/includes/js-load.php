<?php
/* Load Javascript with proper permissions
 * Should only load if user can edit posts
 *
 * @package RoloPress
 * @subpackage Functions
 */

/**
 * Add JavaScript to the theme on needed pages and only if user has proper permissions
 */
function rolo_add_script() {
    //TODO: Need to include JS only in required pages.
    
//    if (is_page(array('Add Contact','Add Company', 'Edit Company', 'Edit Contact'))) {
        wp_enqueue_script( 'uni-form', ROLOPRESS_JS . '/uni-form-validation.jquery.js', array('jquery'), '', true );
        wp_enqueue_script( 'rolopress-js', ROLOPRESS_JS . '/rolopress.js', array('jquery', 'uni-form'), '', true );
		wp_enqueue_script( 'align-form', ROLOPRESS_JS . '/align-form.js' );
		wp_enqueue_script( 'jquery.contenthover', ROLOPRESS_JS . '/jquery.contenthover.js' );
		wp_enqueue_script( 'custom-contenthover', ROLOPRESS_JS . '/custom-contenthover.js' );
		wp_enqueue_script( 'jquery.tooltipster.min', ROLOPRESS_JS . '/jquery.tooltipster.min.js' );

        // Build in tag auto complete script - Code explanation at http://bit.ly/2vbemR
        wp_enqueue_script( 'suggest' );
//    }
    
    if(is_single()) {
        wp_enqueue_script( 'mask', ROLOPRESS_JS . '/jquery.maskedinput.min.js', array('jquery'), '', true );
        wp_enqueue_script( 'autocomplete', '//code.jquery.com/ui/1.10.3/jquery-ui.js', array('jquery'), '', true );
        wp_enqueue_script( 'jeip', ROLOPRESS_JS . '/jeip.js', array('jquery'), '', true );
        wp_enqueue_script( 'jeip-set', ROLOPRESS_JS . '/jeip-set.js', array('jquery'), '', true );
    }
    
        
        
    if(is_singular( 'post' ) && current_user_can( 'edit_posts' )) {
        wp_enqueue_media();
    }
    
    if(is_single()) {
        wp_localize_script( 'rolopress-js', 'ajax_url', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'postid' => get_the_ID() ) );
    } else {
        wp_localize_script( 'rolopress-js', 'ajax_url', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
    

}
add_action('template_redirect', 'rolo_add_script'); 

// This is a dirty way to get the path in js. TODO: need to have a proper way to fix it.
function rolo_print_script() {
    $wpurl = get_bloginfo('wpurl');
    $ajax_url = admin_url("admin-ajax.php");

echo <<<SCRIPT
<script>
var wpurl = "$wpurl";
var ajax_url = '$ajax_url';
</script>
SCRIPT;

}
add_action('wp_footer', 'rolo_print_script');

/**
 * Add JavaScript to the theme on needed notes (comments) pages
 */
function theme_queue_js() {
    if (!is_admin()) {
        if ( is_singular() AND comments_open() AND (get_option('thread_comments') == 1)) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
}
add_action('get_header', 'theme_queue_js');

?>