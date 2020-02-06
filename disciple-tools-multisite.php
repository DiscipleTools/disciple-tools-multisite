<?php
/**
 * Plugin Name: Disciple Tools - Multisite
 * Plugin URI:  https://github.com/DiscipleTools/disciple-tools-multisite
 * Description: Small plugin to be added to modify a multisite "Disciple Tools" environment.
 * Version:     1.1
 */

/** Multisite wrapper */
if ( is_multisite() ) : // check if system is multi-site, if not do not run.

    function dt_multisite_token(){
        return 'disciple-tools-multisite';
    }

    /**********************************************************************************************************************
     * MAKE DISCIPLE TOOLS DEFAULT THEME
     */
    define( 'WP_DEFAULT_THEME', 'disciple-tools-theme' );

    function dt_new_blog_force_dt_theme( $blog_id, $user_id, $domain, $path, $site_id, $meta ){
        update_blog_option( $blog_id, 'template', 'disciple-tools-theme' );
        update_blog_option( $blog_id, 'stylesheet', 'disciple-tools-theme' );
        update_blog_option( $blog_id, 'current_theme', 'Disciple Tools' );
    }
    add_action( 'wpmu_new_blog', 'dt_new_blog_force_dt_theme', 10, 6 );
    /** End Make Disciple.Tools Default */

    require_once( "multisite-migration.php" );

    /**********************************************************************************************************************
     * ADMIN MENU
     */
    function dt_multisite_network_admin_menu(){
        add_menu_page( 'Disciple Tools', 'Disciple Tools', 'manage_options', dt_multisite_token(), 'dt_multisite_network_admin_content', 'dashicons-admin-tools' );
    }
    add_action( 'network_admin_menu', 'dt_multisite_network_admin_menu', 10, 2 );

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

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( 'Disciple Tools Multisite Configuration' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab
                <?php echo ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                    <?php echo esc_attr( 'Overview' ) ?></a>

                <a href="<?php echo esc_attr( $link ) . 'network' ?>" class="nav-tab
                <?php echo ( $tab == 'network' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                    <?php echo esc_attr( 'Network Dashboard Settings' ) ?>
                </a>
                <a href="<?php echo esc_attr( $link ) . 'import' ?>" class="nav-tab
                <?php echo ( $tab == 'import' ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                    <?php echo esc_attr( 'Import Subsite' ) ?>
                </a>

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

                default:
                    break;
            }
            ?>
        </div><!-- End wrap -->
        <?php
    }
    /** End Network Dashboard Features */

    /**
     * Class DT_Starter_Tab_Second
     */
    class DT_Multisite_Tab_Overview
    {
        public function content()
        {
            ?>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->overview_message() ?>
                            <?php $this->network_upgrade() ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
            <?php
        }

        public function overview_message()
        {
            ?>
            <style>dt {
                    font-weight: bold;
                }</style>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Overview of Plugin</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <dl>
                            <dt>This plugin serves the multi-site administrator with maintenance utility and management for a multi-site Disciple Tools installation.</dt>
                        </dl>

                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
            <?php
        }

        public function network_upgrade()
        {
            if ( isset( $_POST['network_upgrade_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['network_upgrade_nonce'] ) ), 'network_upgrade' ) ) {
                if ( isset( $_POST['url_trigger'] ) ) {
                    dt_write_log('url_trigger');

                    global $wpdb;
                    $table = $wpdb->base_prefix . 'blogs';
                    $sites = $wpdb->get_col("SELECT blog_id FROM $table" );
                    if ( ! empty( $sites ) ) {
                        foreach ( $sites as $site ) {
                            if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                                $url = get_blog_option( $site, 'siteurl' );
                                $response = wp_remote_head( $url );
                                dt_write_log( $response );
                            }
                        }
                    }
                }

                if ( isset( $_POST['programmatic_trigger'] ) ) {
                    dt_write_log('programmatic_trigger');

                    global $wpdb;
                    $table = $wpdb->base_prefix . 'blogs';
                    $sites = $wpdb->get_col("SELECT blog_id FROM $table" );
                    if ( ! empty( $sites ) ) {
                        foreach ( $sites as $site ) {
                            dt_write_log($site);
                            if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                                switch_to_blog( $site );

                                require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' ); // loads the wp framework when called
                                require_once ( get_template_directory() . '/functions.php' );
                                disciple_tools();

                                restore_current_blog();
                                dt_write_log( $response );
                            }
                        }
                    }
                }
            }

            ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Network Upgrade</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <dl>
                            <dt>Because Disciple Tools uses migrations for the system that run only when the site is visited, in a multi-site installation
                            you can have various sites in the multi-site at different migration levels. This utility programmatically runs through all the
                            Disciple Tools sites in the multi-site system and triggers their load and therefore any remaining migrations.</dt>
                        </dl>
                    </td>
                </tr>
                <tr>
                    <td>
                        <form method="post">
                            <?php wp_nonce_field( 'network_upgrade', 'network_upgrade_nonce', false, true ) ?>
                            <button type="submit" name="url_trigger" value="1">Trigger Sites through URL Call</button>
                            <button type="submit" name="programmatic_trigger" value="1">Trigger Sites Programmatically</button>
                        </form>

                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
            <?php
        }
    }

    /**
     *
     */
    class DT_Multisite_Tab_Network_Dashboard
    {
        public function content()
        {
            // Checks that the Network Dashboard plugin is installed.
            $plugins_installed = get_plugins();
            if ( ! isset( $plugins_installed['disciple-tools-network-dashboard/disciple-tools-network-dashboard.php'] ) ) {
                $mu_plugins = get_mu_plugins();
                if ( ! isset( $mu_plugins['disciple-tools-network-dashboard/disciple-tools-network-dashboard.php'] ) ) {
                    echo 'Network Dashboard plugin for Disciple Tools is not installed.<br>';
                    echo '<a href="https://github.com/DiscipleTools/disciple-tools-network-dashboard" target="_blank">Download the Network Dashboard Plugin</a>';
                    return;
                }
            }

            ?>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->sites_with_network_dashboard() ?>
                            <?php $this->main_column() ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->

                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
            <?php
        }

        public function main_column()
        {
            ?>
            <table class="widefat striped">
                <thead>
                <th>Overview of Network Dashboard Plugin</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <strong>Features</strong>
                        <ol>

                            <li>The Network Dashboard Plugin can be independently installed in individual sites within the
                            multisite network. It can setup remote sites independently, which requires agreement from the
                            corresponding site to make a site-to-site connection and provide reporting data. This kind of
                            reporting down not require permission at a network level.</li>
                            <li>The Network Dashboard Plugin can also be given permission to gather reports from the
                            multisite network. This permission is controled on this panel by super admins of the network.
                            If permission is enabled, the network dashboard will have a new tab appear in the admin panel
                            and will be able to collect reports from all sites it has been given permission to gather
                            reports from.</li>
                            <li>The need this is addressing is the fluid nature of org structures. Where one group might
                            need to get reporting from a subset of all the Disciple Tools systems on the multisite, and
                            another might need reporting on all the systems in the Disciple Tools multisite.</li>
                            <li>Enabling permission to a Network Dashboard of a certain site, allows it to collect reports
                            even if the "Network Dashboard" setting has not been enabled or a site-to-site connection
                            has been made. This becomes an advantage for a network administrator and reduces the
                            required setup for a site-to-site connection.</li>
                        </ol>

                        <br>
                        <a href="https://github.com/DiscipleTools/disciple-tools-network-dashboard" target="_blank">Download the Network Dashboard Plugin from Github</a>

                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <?php
        }

        public function sites_with_network_dashboard() {

            // process post
            $enabled_sites = get_site_option( 'dt_dashboard_approved_sites' );
            if ( isset( $_POST['dashboards_approved_nonce'] )
                && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dashboards_approved_nonce'] ) ), 'dashboards_approved_' . get_current_user_id() ) ) {

                if ( isset( $_POST['enable-all'] ) && ! empty( $_POST['enable-all'] ) ) {
                    $site_id = sanitize_key( wp_unslash( $_POST['enable-all'] ) );
                    $enabled_sites[$site_id] = [
                          'all' => true,
                          'include_only' => []
                    ];
                    update_site_option( 'dt_dashboard_approved_sites', $enabled_sites );
                    $enabled_sites = get_site_option( 'dt_dashboard_approved_sites' );
                }

                if ( isset( $_POST['disable-all'] ) && ! empty( $_POST['disable-all'] ) ) {
                    $site_id = sanitize_key( wp_unslash( $_POST['disable-all'] ) );
                    unset( $enabled_sites[$site_id] );
                    update_site_option( 'dt_dashboard_approved_sites', $enabled_sites );
                    $enabled_sites = get_site_option( 'dt_dashboard_approved_sites' );
                }

            }

            // get enabled sites
            $active_sites = $this->get_dashboard_activated_sites();
            if ( empty( $active_sites ) ) {
                echo 'No sites found to have activated network dashboard plugin';
                return;
            }

            // print table
            ?>
            <form method="post">
                <?php wp_nonce_field('dashboards_approved_' . get_current_user_id(), 'dashboards_approved_nonce') ?>
                <strong>Sites with Network Dashboard Activated</strong>
                <table class="widefat striped">
                    <thead>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Site Name</th>
                    <th style="width:75px;text-align:center;">Action</th>
                    </thead>
                    <tbody>
                        <?php
                            foreach ( $active_sites as $value ) {
                                if ( isset( $enabled_sites[$value->blog_id]['all'] ) && $enabled_sites[$value->blog_id]['all'] === true ) {
                                    $action = '<button type="submit" class="button" style="background-color:red;border-color:red;color:white;" name="disable-all" value="'.$value->blog_id.'">Disable</button>';
                                    $status = '&#9989;';
                                } else {
                                    $action = '<button type="submit" class="button" name="enable-all" value="'.$value->blog_id.'">Enable</button>';
                                    $status = '<span style="font-size:1.5em;">&#10007;</span>';
                                }

                                echo '<tr>';
                                echo '<td style="width:30px;">' . $value->blog_id . '</td>';
                                echo '<td style="width:30px;">'.$status.' </td>';
                                echo '<td>' . $value->blogname . '</td>';
                                echo '<td style="width:75px;text-align:center;">'.$action.'</td>';
                                echo '</tr>';
                            }
                        ?>
                    </tbody>
                </table>
                <br>
            </form>
            <?php
        }

        public function get_dashboard_activated_sites() {
            global $wpdb;
            $active_sites = [];

            // Get list of blogs with active network dashboards activated
            $table = $wpdb->base_prefix . 'blogs';
            $sites = $wpdb->get_col("SELECT blog_id FROM $table" );
            if ( empty( $sites ) ) {
                return $active_sites;
            }

            foreach ( $sites as $site ) {
                $active_plugins = get_blog_option( $site, 'active_plugins' );

                if ( empty( $active_plugins ) ) {
                    continue;
                }
                foreach ( $active_plugins as $plugin ) {
                    if ( $plugin === 'disciple-tools-network-dashboard/disciple-tools-network-dashboard.php' ) {
                        $active_sites[] = get_blog_details( $site );
                    }
                }
            }
            return $active_sites;

        }

    } // end DT_Multisite_Tab_Network_Dashboard class

    /**
     * Cleanup orphaned tables during site deletion
     *
     * @param $blog_id
     * @param $drop
     */
    add_action( 'delete_blog', 'dt_delete_all_subsite_tables', 10, 2 );
    function dt_delete_all_subsite_tables( $blog_id, $drop ) {

        if ( true == $drop ) {

            /**
             * SELECT all tables relating to a specific blog id and add them to wpmu_drop_tables
             */
            global $wpdb;
            $table_list = $wpdb->get_results( $wpdb->prepare( "
                SELECT table_name as table_name FROM information_schema.TABLES WHERE table_name LIKE %s;
            ", $wpdb->esc_like( "{$wpdb->base_prefix}{$blog_id}_" ) . '%' ),
            ARRAY_A );

            add_filter( 'wpmu_drop_tables', function ( $filter_list ) use ( $table_list ) {
                foreach ( $table_list as $index => $data ) {
                    $filter_list[] = $data['table_name'];
                }
                return array_unique( $filter_list );
            } );
        }
    }


endif; // end multisite check wrapper