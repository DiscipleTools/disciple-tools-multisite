<?php
/**
 * Plugin Name: Disciple Tools Multisite
 * Plugin URI:  https://github.com/DiscipleTools/disciple-tools-multisite
 * Description: Small plugin to be added to modify a multisite Disciple Tools environment.
 * Version:     1.0
 */


/**
 * Example of wpmu_new_blog usage
 *
 * @param int    $blog_id Blog ID.
 * @param int    $user_id User ID.
 * @param string $domain  Site domain.
 * @param string $path    Site path.
 * @param int    $site_id Site ID. Only relevant on multi-network installs.
 * @param array  $meta    Meta data. Used to set initial site options.
 */
function dt_new_blog_force_dt_theme( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    dt_write_log($meta);
}
add_action( 'wpmu_new_blog', 'wporg_wpmu_new_blog_example', 10, 6 );



