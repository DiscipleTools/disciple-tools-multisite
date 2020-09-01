<?php

function dt_multisite_token(){
    return 'disciple-tools-multisite';
}

/**********************************************************************************************************************
 * ADMIN MENU
 */
add_action( 'network_admin_menu', 'dt_multisite_network_admin_menu', 10, 2 );
function dt_multisite_network_admin_menu(){
    add_menu_page( 'Disciple Tools', 'Disciple Tools', 'manage_options', dt_multisite_token(), 'dt_multisite_network_admin_content', 'dashicons-admin-tools' );
}
function dt_multisite_network_admin_content(){
    if ( ! current_user_can( 'manage_options' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
        wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( isset( $_GET["tab"] ) ) {
        $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
    } else {
        $tab = 'general';
    }

    $link = 'admin.php?page=' . esc_html( dt_multisite_token() ) . '&tab=';

    $plugins_installed = get_plugins();
    $mu_plugins = get_mu_plugins();

    ?>
    <div class="wrap">
        <h2><?php echo esc_html( 'Disciple Tools Multisite Configuration' ) ?></h2>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab
                <?php echo ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                <?php echo esc_attr( 'Overview' ) ?></a>
            <a href="<?php echo esc_attr( $link ) . 'import' ?>" class="nav-tab
                <?php echo ( $tab == 'import' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                <?php echo esc_attr( 'Import Subsite' ) ?>
            </a>
            <a href="<?php echo esc_attr( $link ) . 'mapbox_keys' ?>" class="nav-tab
                <?php echo ( $tab == 'mapbox_keys' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                <?php echo esc_attr( 'Mapbox Keys' ) ?>
            </a>
            <?php
            if ( isset( $plugins_installed['disciple-tools-network-dashboard/disciple-tools-network-dashboard.php'] ) || isset( $mu_plugins['disciple-tools-network-dashboard/disciple-tools-network-dashboard.php'] ) ) {
                ?>
                <a href="<?php echo esc_attr( $link ) . 'network' ?>" class="nav-tab
                <?php echo ( $tab == 'network' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                    <?php echo esc_attr( 'Network Dashboard Plugin' ) ?>
                </a>
                <?php
                // movement maps is dependent on network dashboard. So hide tab if network dashboard doesn't exist.
                if ( isset( $plugins_installed['movement-maps-stats/movement-maps-stats.php'] ) || isset( $mu_plugins['movement-maps-stats/movement-maps-stats.php'] ) ) {
                    ?>
                    <a href="<?php echo esc_attr( $link ) . 'movement_maps' ?>" class="nav-tab
                    <?php echo ( $tab == 'movement_maps' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                        <?php echo esc_attr( 'Movement Maps & Stats Plugin' ) ?>
                    </a>
                    <?php
                }
            }
            ?>

        </h2>

        <?php
        switch ( $tab ) {
            case "general":
                $object = new DT_Multisite_Tab_Overview();
                $object->content();
                break;
            case "network":
                $object = new DT_Multisite_Tab_Network_Dashboard();
                $object->content();
                break;
            case "import":
                $object = new DT_Multisite_Tab_Import_Subsite();
                $object->content();
                break;
            case "mapbox_keys":
                $object = new DT_Multisite_Tab_Mapbox_Keys();
                $object->content();
                break;
            case "movement_maps":
                $object = new DT_Movement_Maps_Tab_Network_Dashboard();
                $object->content();
                break;

            default:
                break;
        }
        ?>
    </div><!-- End wrap -->
    <?php
}


/**
 * Cleanup orphaned tables during site deletion
 *
 * @param $blog_id
 * @param $drop
 */
add_action( 'wp_delete_site', 'dt_delete_all_subsite_tables', 10, 2 );
function dt_delete_all_subsite_tables( $old_site ) {

    /**
     * SELECT all tables relating to a specific blog id and add them to wpmu_drop_tables
     */
    global $wpdb;
    $table_list = $wpdb->get_col( $wpdb->prepare( "
                SELECT table_name as table_name FROM information_schema.TABLES WHERE table_name LIKE %s;
            ", $wpdb->esc_like( "{$wpdb->base_prefix}{$old_site->id}_" ) . '%' ) );

    foreach ( $table_list as $tb ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$tb}" ); // phpcs:ignore
    }
}

