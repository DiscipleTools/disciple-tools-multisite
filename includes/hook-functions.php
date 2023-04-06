<?php
/**
* Cleanup orphaned tables during site deletion
*
* @param $blog_id
* @param $drop
*/
add_action( 'wp_delete_site', function( $old_site ) {
    /**
     * SELECT all tables relating to a specific blog id and add them to wpmu_drop_tables
     */
    global $wpdb;
    $table_list = $wpdb->get_col( $wpdb->prepare( '
        SELECT table_name as table_name FROM information_schema.TABLES WHERE table_name LIKE %s;
        ', $wpdb->esc_like( "{$wpdb->base_prefix}{$old_site->id}_" ) . '%' ) );

    foreach ( $table_list as $tb ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$tb}" ); // phpcs:ignore
    }
}, 10, 1 );
