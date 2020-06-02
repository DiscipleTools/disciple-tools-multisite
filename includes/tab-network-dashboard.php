<?php

/**
 *
 */
class DT_Multisite_Tab_Network_Dashboard
{
    public function content(){
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

    public function main_column(){
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Overview of Network Dashboard Plugin</th>
            </tr>
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
            <?php wp_nonce_field( 'dashboards_approved_' . get_current_user_id(), 'dashboards_approved_nonce' ) ?>
            <strong>Sites with Network Dashboard Activated</strong>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Site Name</th>
                    <th style="width:75px;text-align:center;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $active_sites as $value ) {
                    echo '<tr>';
                    echo '<td style="width:30px;">' . esc_html( $value->blog_id ) . '</td>';

                    if ( isset( $enabled_sites[$value->blog_id]['all'] ) && $enabled_sites[$value->blog_id]['all'] === true ) {
                        echo '<td style="width:30px;">&#9989;</td>';
                        echo '<td>' . esc_html( $value->blogname ) . '</td>';
                        echo '<td style="width:75px;text-align:center;">
                                    <button type="submit" class="button" style="background-color:red;border-color:red;color:white;" name="disable-all" value="' . esc_html( $value->blog_id ) . '">Disable</button>
                                </td>';
                    } else {
                        echo '<td style="width:30px;"><span style="font-size:1.5em;">&#10007;</span></td>';
                        echo '<td>' . esc_html( $value->blogname ) . '</td>';
                        echo '<td style="width:75px;text-align:center;"><button type="submit" class="button" name="enable-all" value="'.esc_html( $value->blog_id ).'">Enable</button></td>';
                    }

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
                if ( $plugin === 'disciple-tools-network-dashboard/disciple-tools-network-dashboard.php' ) {
                    $active_sites[] = get_blog_details( $site );
                }
            }
        }
        return $active_sites;

    }

} // end DT_Multisite_Tab_Network_Dashboard class
