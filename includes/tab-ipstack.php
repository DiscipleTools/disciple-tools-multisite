<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_IpStack_Keys
 */
class DT_Multisite_Tab_Ipstack_Keys
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
        if ( isset( $_POST['ipstack_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipstack_nonce'] ) ), 'ipstack' ) ) {

            if ( isset( $_POST['site_id'] ) && ! empty( $_POST['site_id'] ) ) {
                $site_id = sanitize_text_field( wp_unslash( $_POST['site_id'] ) );

                if ( isset( $_POST['ipstack_key'] ) && empty( $_POST['ipstack_key'] ) ) {
                    update_blog_option( $site_id, 'dt_ipstack_api_key', '' );
                } else {
                    update_blog_option( $site_id, 'dt_ipstack_api_key', sanitize_text_field( wp_unslash( $_POST['ipstack_key'] ) ) );
                }
            }
        }
        // bulk
        if ( isset( $_POST['bulk_ipstack_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bulk_ipstack_nonce'] ) ), 'bulk_ipstack' )
            && isset( $_POST['add_key'] ) && ! empty( $_POST['add_key'] )
        ) {
            $key = sanitize_text_field( wp_unslash( $_POST['add_key'] ) );

            global $wpdb;
            $sites = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs;" );
            if ( ! empty( $sites ) ) {
                foreach ( $sites as $site ) {
                    if ( get_blog_option( $site, 'stylesheet' ) === 'disciple-tools-theme' ) {
                        $current_key = get_blog_option( $site, 'dt_ipstack_api_key' );
                        if ( empty( $current_key ) ) {
                            update_blog_option( $site, 'dt_ipstack_api_key', $key );
                        }
                    }
                }
            }
        }
        // default
        if ( isset( $_POST['default_ipstack_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['default_ipstack_nonce'] ) ), 'default_ipstack' )
            && isset( $_POST['default_key'] )
        ) {
            $key = sanitize_text_field( wp_unslash( $_POST['default_key'] ) );
            if ( empty( $_POST['default_key'] ) ) {
                update_network_option( 1, 'dt_ipstack_api_key', '' );
            } else {
                update_network_option( 1, 'dt_ipstack_api_key', $key );
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
                    $dt_setup_options = get_blog_option( $site, 'dt_setup_options', [] );
                    $list[$site] = [];
                    $list[$site]['url'] = get_blog_option( $site, 'siteurl' );
                    $list[$site]['ipstack_key'] = get_blog_option( $site, 'dt_ipstack_api_key' );
                    $list[$site]['locations_upgraded'] = isset( $dt_setup_options['ipstack_upgrade'] );
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
                            <?php wp_nonce_field( 'ipstack', 'ipstack_nonce' ) ?>
                            <input type="hidden" name="site_id" value="<?php echo esc_attr( $id ) ?>" />
                            <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $site['ipstack_key'] ) ?>" name="ipstack_key" placeholder="add ipstack key" />
                        </td>
                        <td style="width:10%;">
                            <button class="button btn" type="submit">Update</button>
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
            <?php wp_nonce_field( 'bulk_ipstack', 'bulk_ipstack_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Add IpStack Key to All Empty Sites</th>
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
        $key = get_network_option( 1, 'dt_ipstack_api_key' );
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field( 'default_ipstack', 'default_ipstack_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Set Default IpStack Key</th>
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

}
