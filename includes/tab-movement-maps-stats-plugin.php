<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Movement_Maps_Tab_Network_Dashboard
 */
class DT_Movement_Maps_Tab_Network_Dashboard
{
    public function content(){
        // Checks that the Movement Maps & Stats plugin is installed.
        $plugins_installed = get_plugins();
        if ( ! isset( $plugins_installed['movement-maps-stats/movement-maps-stats.php'] ) ) {
            $mu_plugins = get_mu_plugins();
            if ( ! isset( $mu_plugins['movement-maps-stats/movement-maps-stats.php'] ) ) {
                echo 'Movement Maps & Stats plugin is not installed. This plugin is for publishing Movement Maps & Stats Movement data to a website.<br>';
                echo '<a href="https://github.com/ZumeProject/movement-maps-stats" target="_blank">Download the Movement Maps & Stats</a>';
                return;
            }
        }

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->sites_with_movement_maps() ?>
                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column(){
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Overview of Movement Maps & Stats Plugin</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <strong>Features</strong>
                    <ol>

                        <li>The Movement Maps & Stats Plugin can be independently installed in individual sites within the
                            multisite network.</li>
                    </ol>

                    <br>
                    <a href="https://github.com/ZumeProject/movement-maps-stats" target="_blank">Download the Movement Maps & Stats Plugin from Github</a>

                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <?php
    }

    public function sites_with_movement_maps() {
        global $wpdb;
        $dash_tab = new DT_Multisite_Tab_Network_Dashboard();
        $active_dashboard_sites = $dash_tab->get_dashboard_activated_sites();

        // process post
        $enabled_sites = get_site_option( 'movement_map_approved_sites' );
        if ( isset( $_POST['movement_map_approved_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['movement_map_approved_nonce'] ) ), 'movement_map_approved_' . get_current_user_id() ) ) {

            if ( isset( $_POST['enable-all'] ) && ! empty( $_POST['enable-all'] ) && isset( $_POST['db_table'] ) && ! empty( $_POST['db_table'] ) ) {
                $site_id = sanitize_key( wp_unslash( $_POST['enable-all'] ) );

                $data_table = sanitize_text_field( wp_unslash( $_POST['db_table'] ) );
                if ( $data_table ) {
                    $enabled_sites[$site_id] = [
                        'enabled' => true,
                        'table' => $data_table
                    ];
                    update_site_option( 'movement_map_approved_sites', $enabled_sites );
                    $enabled_sites = get_site_option( 'movement_map_approved_sites' );
                }
            }

            if ( isset( $_POST['disable-all'] ) && ! empty( $_POST['disable-all'] ) ) {
                $site_id = sanitize_key( wp_unslash( $_POST['disable-all'] ) );
                unset( $enabled_sites[$site_id] );
                update_site_option( 'movement_map_approved_sites', $enabled_sites );
                $enabled_sites = get_site_option( 'movement_map_approved_sites' );
            }
        }

        // get enabled sites
        $active_sites = $this->get_movement_maps_sites();
        if ( empty( $active_sites ) ) {
            echo 'No sites found to have activated movement maps plugin';
            return;
        }
        dt_write_log( $active_sites );
        // get enabled sites
        if ( empty( $active_dashboard_sites ) ) {
            echo 'No sites found to have network dashboard plugin';
            return;
        }

        // print table
        ?>
        <form method="post">
            <?php wp_nonce_field( 'movement_map_approved_' . get_current_user_id(), 'movement_map_approved_nonce' ) ?>
            <strong>Sites with Movement Maps & Stats Activated</strong>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Site Name</th>
                    <th>Network Dashboard</th>
                    <th style="width:75px;text-align:center;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $active_sites as $value ) {
                    ?>
                    <tr>
                        <td style="width:30px;"><?php echo esc_html( $value->blog_id ) ?></td>
                        <?php
                        if ( isset( $enabled_sites[$value->blog_id]['enabled'] ) && $enabled_sites[$value->blog_id]['enabled'] === true ) {
                            ?>
                            <td style="width:30px;">&#9989;</td>
                            <td><?php echo esc_html( $value->blogname ) ?></td>
                            <td><select name="db_table">
                                <?php
                                foreach ( $active_dashboard_sites as $dashboard_site ) {
                                    $table = $wpdb->base_prefix . $dashboard_site->blog_id .'_dt_movement_log';
                                    echo '<option value="'. esc_attr( $table ) .'" ';
                                    if ( isset( $enabled_sites[$value->blog_id]['table'] ) && $enabled_sites[$value->blog_id]['table'] === $table ) {
                                        echo 'selected';
                                    }
                                    echo '>'. esc_html( $dashboard_site->blogname ).'</option>';
                                }
                                ?>
                                </select></td>
                            <td style="width:75px;text-align:center;">
                                <button type="submit" class="button" style="background-color:red;border-color:red;color:white;" name="disable-all" value="<?php echo esc_html( $value->blog_id ) ?>">Disable</button>
                            </td>
                            <?php
                        } else {
                            ?>
                            <td style="width:30px;"><span style="font-size:1.5em;">&#10007;</span></td>
                            <td><?php echo esc_html( $value->blogname ) ?></td>
                            <td><select name="db_table">
                                    <?php
                                    foreach ( $active_dashboard_sites as $dashboard_site ) {
                                        $table = $wpdb->base_prefix . $dashboard_site->blog_id .'_dt_movement_log';
                                        echo '<option value="'. esc_attr( $table ) .'" ';
                                        if ( isset( $enabled_sites[$value->blog_id]['table'] ) && $enabled_sites[$value->blog_id]['table'] === $table ) {
                                            echo 'selected';
                                        }
                                        echo '>'. esc_html( $dashboard_site->blogname ).'</option>';
                                    }
                                    ?>
                                </select></td>
                            <td style="width:75px;text-align:center;"><button type="submit" class="button" name="enable-all" value="<?php echo esc_html( $value->blog_id ) ?>">Enable</button></td>
                            <?php
                        }
                        ?>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <br>
        </form>
        <?php
    }

    public function get_movement_maps_sites() {
        global $wpdb;
        $active_sites = [];

        // Get list of blogs with active network dashboards activated
        $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs" );
        if ( empty( $sites ) ) {
            return $active_sites;
        }

        foreach ( $sites as $site ) {
            $active_plugins = get_blog_option( $site, 'active_plugins' );

            if ( empty( $active_plugins ) ) {
                continue;
            }
            foreach ( $active_plugins as $plugin ) {
                if ( $plugin === 'movement-maps-stats/movement-maps-stats.php' ) {
                    $active_sites[] = get_blog_details( $site );
                }
            }
        }
        return $active_sites;

    }

} // end DT_Multisite_Tab_Network_Dashboard class
