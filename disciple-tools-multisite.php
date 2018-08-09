wp<?php
/**
 * Plugin Name: Disciple Tools - Multisite
 * Plugin URI:  https://github.com/DiscipleTools/disciple-tools-multisite
 * Description: Small plugin to be added to modify a multisite "Disciple Tools" environment.
 * Version:     1.0
 */

/**
 * Set the new blog theme to Disciple Tools.
 *
 * @param $blog_id
 * @param $user_id
 * @param $domain
 * @param $path
 * @param $site_id
 * @param $meta
 */
function dt_new_blog_force_dt_theme( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    update_blog_option( $blog_id,'template','disciple-tools-theme' );
    update_blog_option( $blog_id,'stylesheet','disciple-tools-theme' );
    update_blog_option( $blog_id,'current_theme','Disciple Tools' );
}
add_action( 'wpmu_new_blog', 'dt_new_blog_force_dt_theme', 10, 6 );


/**
 * Dev functions for easily logging
 */
if ( ! function_exists( 'dt_write_log' ) ) {
    /**
     * A function to assist development only.
     * This function allows you to post a string, array, or object to the WP_DEBUG log.
     *
     * @param $log
     */
    function dt_write_log( $log )
    {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

function dt_multisite_disable_arvada_header() {
    ?>
    <style>#fusion-slider-3 {display:none;}</style>
    <?php
}
add_action( 'signup_header', 'dt_multisite_disable_arvada_header' );
