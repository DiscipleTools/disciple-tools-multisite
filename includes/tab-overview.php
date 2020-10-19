<?php

/**
 * Class DT_Starter_Tab_Second
 */
class DT_Multisite_Tab_Overview
{
    public function content(){
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

    public function overview_message(){
        ?>
        <style>dt {
                font-weight: bold;
            }</style>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Overview of Plugin</th>
            </tr>
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

    public function network_upgrade(){
        if ( isset( $_POST['network_upgrade_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['network_upgrade_nonce'] ) ), 'network_upgrade' ) ) {
            if ( isset( $_POST['url_trigger'] ) ) {
                dt_write_log( 'url_trigger' );

                global $wpdb;

                $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs;" );
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
                dt_write_log( 'programmatic_trigger' );

                global $wpdb;
                $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs" );
                if ( ! empty( $sites ) ) {
                    foreach ( $sites as $site ) {
                        dt_write_log( $site );
                        if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                            switch_to_blog( $site );


                            require( ABSPATH . '/wp-load.php' ); // loads the wp framework when called
                            require_once( get_template_directory() . '/functions.php' );
                            disciple_tools();

                            restore_current_blog();
                        }
                    }
                }
            }
        }

        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Network Upgrade</th>
            </tr>
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
                        <button type="submit" class="button" name="url_trigger" value="1">Trigger Sites through URL Call</button>
                        <button type="submit" class="button" name="programmatic_trigger" value="1">Trigger Sites Programmatically</button>
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