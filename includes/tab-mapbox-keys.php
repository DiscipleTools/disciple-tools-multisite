<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_Mapbox_Keys
 */
class DT_Multisite_Tab_Mapbox_Keys
{
    public function content(){
        $this->process_post();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->list_keys() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->bulk_key_add() ?>

                        <?php $this->default_key() ?>

                        <?php $this->upgrade_locations() ?>


                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function process_post() {
        // update
        if ( isset( $_POST['mapbox_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mapbox_nonce'] ) ), 'mapbox' ) ) {

            if ( isset( $_POST['site_id'] ) && ! empty( $_POST['site_id'] ) ) {
                $site_id = sanitize_text_field( wp_unslash( $_POST['site_id'] ) );

                if ( isset( $_POST['mapbox_key'] ) && empty( $_POST['mapbox_key'] ) ) {
                    update_blog_option( $site_id, 'dt_mapbox_api_key', '' );
                } else {
                    update_blog_option( $site_id, 'dt_mapbox_api_key', sanitize_text_field( wp_unslash( $_POST['mapbox_key'] ) ) );
                }
            }
        }
        // bulk
        if ( isset( $_POST['bulk_mapbox_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bulk_mapbox_nonce'] ) ), 'bulk_mapbox' )
            && isset( $_POST['add_key'] ) && ! empty( $_POST['add_key'] )
        ) {
            $key = sanitize_text_field( wp_unslash( $_POST['add_key'] ) );

            global $wpdb;
            $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs;" );
            if ( ! empty( $sites ) ) {
                foreach ( $sites as $site ) {
                    if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                        $current_key = get_blog_option( $site, 'dt_mapbox_api_key' );
                        if ( empty( $current_key ) ) {
                            update_blog_option( $site, 'dt_mapbox_api_key', $key );
                        }
                    }
                }
            }
        }
        // default
        if ( isset( $_POST['default_mapbox_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['default_mapbox_nonce'] ) ), 'default_mapbox' )
            && isset( $_POST['default_key'] )
        ) {
            $key = sanitize_text_field( wp_unslash( $_POST['default_key'] ) );
            if ( empty( $_POST['default_key'] ) ) {
                update_network_option( 1, 'dt_mapbox_api_key', '' );
            } else {
                update_network_option( 1, 'dt_mapbox_api_key', $key );
            }
        }
    }

    public function list_keys(){
        global $wpdb;

        $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs;" );
        $list = [];
        $list['count'] = 0;
        if ( ! empty( $sites ) ) {
            foreach ( $sites as $site ) {
                if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                    $dt_setup_options = get_blog_option( $site, "dt_setup_options", [] );
                    $list[$site] = [];
                    $list[$site]['url'] = get_blog_option( $site, 'siteurl' );
                    $list[$site]['mapbox_key'] = get_blog_option( $site, 'dt_mapbox_api_key' );
                    $list[$site]['locations_upgraded'] = isset( $dt_setup_options["mapbox_upgrade"] );
                    $list['count']++;
                }
            }
        }

        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Site Name (<?php echo esc_attr( $list['count'] ) ?>)</th>
                <th>Keys</th>
                <th></th>
                <th>Locations Upgraded</th>
            </tr>
            </thead>
            <tbody>

            <?php if ( ! empty( $list ) ) : unset( $list['count'] ); foreach ( $list as $id => $site ) : ?>
                <form method="post">
                    <tr>
                        <td style="width:30%;">
                            <?php echo esc_url( $site['url'] ) ?>
                        </td>
                        <td>
                            <?php wp_nonce_field( 'mapbox', 'mapbox_nonce' ) ?>
                            <input type="hidden" name="site_id" value="<?php echo esc_attr( $id ) ?>" />
                            <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $site['mapbox_key'] ) ?>" name="mapbox_key" placeholder="add mapbox key" />
                        </td>
                        <td style="width:10%;">
                            <button class="button btn" type="submit">Update</button>
                        </td>
                        <td>
                            <?php if ( $site['mapbox_key'] ) { echo esc_html( $site["locations_upgraded"] ? "True" : "False" ); } ?>
                        </td>
                    </tr>
                </form>
            <?php endforeach;
            endif; ?>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function bulk_key_add(){
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field( 'bulk_mapbox', 'bulk_mapbox_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Add Mapbox Key to All Empty Sites</th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="text" name="add_key" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <button class="button btn" type="submit">Add Key to All Empty Fields</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function default_key(){
        $key = get_network_option( 1, 'dt_mapbox_api_key' );
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field( 'default_mapbox', 'default_mapbox_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Set Default Mapbox Key</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <input type="text" name="default_key" value="<?php echo esc_attr( $key ) ?>" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="button btn" type="submit">Set Default Key</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }


    public function upgrade_locations(){

        ?>
        <!-- Box -->
        <form method="GET" action="">
            <?php wp_nonce_field( 'upgrade_database', 'upgrade_database_nonce' ) ?>
            <input type="hidden" name="page" value="disciple-tools-multisite" />
            <input type="hidden" name="loop" value="1" />
            <input type="hidden" name="tab" value="mapbox_keys" />
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Upgrade Locations</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <button class="button btn" type="submit" name="upgrade_database">Upgrade Locations</button>
                    </td>
                </tr>

                <?php
                $continue = false;
                $site_to_update = null;
                global $wpdb;
                if ( isset( $_GET['upgrade_database_nonce'] )
                    && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['upgrade_database_nonce'] ) ), 'upgrade_database' )
                     ) {
                    $continue = true;
                }

                if ( $continue ){

                    //find the site we need to update
                    $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs;" );

                    if ( ! empty( $sites ) ) {
                        foreach ( $sites as $site ) {
                            if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                                $dt_setup_options = get_blog_option( $site, "dt_setup_options", [] );
                                $api_key = get_blog_option( $site, 'dt_mapbox_api_key' );
                                if ( $api_key && !isset( $dt_setup_options["mapbox_upgrade"] ) ){
                                    $site_to_update = $site;
                                    break;
                                }
                            }
                        }
                    }
                }

                if ( $site_to_update ){
                    switch_to_blog( $site_to_update );
                    $wpdb->dt_location_grid_meta = $wpdb->prefix . 'dt_location_grid_meta';
                    $wpdb->dt_location_grid = $wpdb->prefix . 'dt_location_grid';
                    require_once( get_template_directory() . '/dt-mapping/mapping-admin.php' );
                    if ( !method_exists( DT_Mapping_Module_Admin::instance(), "get_record_count_with_no_location_meta" ) ){
                        ?><tr><td><strong>This feature requires D.T v1.0.2 or later</strong><br><?php
                        return;
                    }
                    $location_wo_meta = DT_Mapping_Module_Admin::instance()->get_record_count_with_no_location_meta();
                    $limit = 100;

                    $count = $location_wo_meta;

                    $user_location_wo_meta = DT_Mapping_Module_Admin::instance()->get_user_count_with_no_location_meta();
                    $user_count = $user_location_wo_meta;

                    if ( empty( $count ) && empty( $user_count ) ){
                        $dt_setup_options = get_option( "dt_setup_options", [] );
                        $dt_setup_options["mapbox_upgrade"] = true;
                        update_option( "dt_setup_options", $dt_setup_options );
                    }
                    $greater_than_limit = true;


                    ?>

                    <?php if ( !empty( $count ) || !empty( $user_count ) ) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( get_site_url() ); ?></strong><br>
                            <strong>Processing ( <?php echo esc_attr( $count + $user_count ) ?> ) </strong><br>
                            <span><img src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" width="22px" alt="spinner "/></span><br>
                            <?php

                            require_once( get_template_directory() . '/dt-mapping/geocode-api/location-grid-geocoder.php' );
                            require_once( get_template_directory() . '/dt-mapping/location-grid-meta.php' );
                            require_once( get_template_directory() . '/dt-mapping/mapping-queries.php' );
                            global $wpdb;
                            if ( !empty( $count )){
                                // Insert processing with offset
                                $geocoder = new Location_Grid_Geocoder();
                                $query = $wpdb->get_results( $wpdb->prepare( "
                                    SELECT *
                                    FROM $wpdb->postmeta
                                    WHERE meta_key = 'location_grid'
                                    AND meta_id NOT IN (
                                        SELECT DISTINCT( postmeta_id_location_grid )
                                        FROM $wpdb->dt_location_grid_meta
                                    )
                                    AND meta_value >= 100000000
                                    LIMIT %d",
                                    $limit
                                ), ARRAY_A );

                                if ( !empty( $query ) ){
                                    foreach ( $query as $row ){
                                        $grid = $geocoder->query_by_grid_id( $row["meta_value"] );
                                        if ( $grid ){
                                            $location_meta_grid = [];

                                            Location_Grid_Meta::validate_location_grid_meta( $location_meta_grid );
                                            $location_meta_grid['post_id'] = $row['post_id'];
                                            $location_meta_grid['post_type'] = get_post_type( $row['post_id'] );
                                            $location_meta_grid['grid_id'] = $row['meta_value'];
                                            $location_meta_grid['lng'] = $grid["longitude"];
                                            $location_meta_grid['lat'] = $grid["latitude"];
                                            $location_meta_grid['level'] = $grid["level_name"];
                                            $location_meta_grid['label'] = $geocoder->_format_full_name( $grid );

                                            $potential_error = Location_Grid_Meta::add_location_grid_meta( $row['post_id'], $location_meta_grid, $row['meta_id'] );

                                            echo esc_html( $location_meta_grid['label'] ) . '<br>';
                                        }
                                    }
                                }
                            } elseif ( !empty( $user_count ) ){
                                $geocoder = new Location_Grid_Geocoder();
                                $query = $wpdb->get_results( $wpdb->prepare( "
                                    SELECT *
                                    FROM $wpdb->usermeta
                                    WHERE meta_key = %s
                                    AND umeta_id NOT IN (
                                        SELECT DISTINCT( postmeta_id_location_grid )
                                        FROM $wpdb->dt_location_grid_meta)
                                    LIMIT %d",
                                    $wpdb->prefix . 'location_grid',
                                    $limit
                                ), ARRAY_A);
                                if ( ! empty( $query ) ) {
                                    foreach ( $query as $row ) {
                                        $grid = $geocoder->query_by_grid_id( $row["meta_value"] );
                                        if ( $grid ) {
                                            $location_meta_grid = [];

                                            Location_Grid_Meta::validate_location_grid_meta( $location_meta_grid );
                                            $location_meta_grid['post_id'] = $row['user_id'];
                                            $location_meta_grid['post_type'] = 'users';
                                            $location_meta_grid['grid_id'] = $row['meta_value'];
                                            $location_meta_grid['lng'] = $grid["longitude"];
                                            $location_meta_grid['lat'] = $grid["latitude"];
                                            $location_meta_grid['level'] = $grid["level_name"];
                                            $location_meta_grid['label'] = $geocoder->_format_full_name( $grid );

                                            $potential_error = Location_Grid_Meta::add_user_location_grid_meta( $row['user_id'], $location_meta_grid, $row['umeta_id'] );

                                            echo esc_html( $location_meta_grid['label'] ) . '<br>';
                                        }
                                    }
                                }
                            }

                            ?>
                        <td>
                    <tr>

                    <?php else : ?>
                        <tr>
                            <td>
                                <strong>Loading next site</strong><br>
                                <span><img src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" width="22px" alt="spinner "/></span><br>
                            </td>
                        </tr>
                    <?php endif; // loop_again
                    restore_current_blog();
                    ?><script type="text/javascript">
                        function nextpage() {
                            location.href = "<?php echo esc_url( network_admin_url() ) ?>admin.php?page=disciple-tools-multisite&tab=mapbox_keys&upgrade_database_nonce=<?php echo esc_attr( wp_create_nonce( 'upgrade_database' ) ) ?>&loop=<?php echo esc_attr( $greater_than_limit ) ?>";
                        }
                        setTimeout( "nextpage()", 1500 );
                    </script>
                    <?php
                } else if ($continue) {
                    ?>
                        <script type="text/javascript">
                        function nextpage() {
                            location.href = "<?php echo esc_url( network_admin_url() ) ?>admin.php?page=disciple-tools-multisite&tab=mapbox_keys";
                        }
                        setTimeout( "nextpage()", 100 );
                        </script>
                    <?php
                }
                ?>

                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }
}
