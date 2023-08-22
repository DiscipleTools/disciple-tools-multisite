<?php

/**
 * To add a columns to the sites columns
 *
 * @param array
 *
 * @return array
 */
function disciple_tools_multisite_blogs_columns( $sites_columns ) {
    $sites_columns['blogid'] = 'Site ID';
    return $sites_columns;
}
add_filter( 'wpmu_blogs_columns', 'disciple_tools_multisite_blogs_columns' );

/**
 * Show blog id
 *
 * @param string
 * @param integer
 *
 * @return void
 */
function disciple_tools_multisite_sites_custom_column( $column_name, $blog_id ) {
    if ( $column_name == 'blogid' ) {
        echo esc_attr( $blog_id );
    }
}
add_action( 'manage_sites_custom_column', 'disciple_tools_multisite_sites_custom_column', 10, 2 );


function disciple_tools_multisite_sites_table_style() {
    $current_screen = get_current_screen();
    if ( $current_screen->parent_file === 'sites.php' ) {
        ?>
        <style>
            .sites.fixed .column-blogid {
                width: 80px;
            }
        </style>
        <?php
    }
}
add_action( 'network_admin_notices', 'disciple_tools_multisite_sites_table_style' );
