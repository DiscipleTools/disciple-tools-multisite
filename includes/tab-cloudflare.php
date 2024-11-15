<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_Google Map_Keys
 */
class DT_Multisite_Tab_Cloudflare
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

<!--                        --><?php //$this->bulk_key_add() ?>

<!--                        --><?php //$this->default_key() ?>


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
        if ( isset( $_POST['cloudflare_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cloudflare_nonce'] ) ), 'cloudflare' ) ) {

            update_site_option( 'dt_cloudflare_site_key', sanitize_text_field( wp_unslash( $_POST['dt_cloudflare_site_key'] ?? '' ) ) );
            update_site_option( 'dt_cloudflare_secret_key', sanitize_text_field( wp_unslash( $_POST['dt_cloudflare_secret_key'] ?? '' ) ) );
        }
    }

    public function list_keys(){
        global $wpdb;

        $network_cloudflare_site_key = get_site_option( 'dt_cloudflare_site_key' );
        $network_cloudflare_secret_key = get_site_option( 'dt_cloudflare_secret_key' );

        ?>
        <!-- Box -->
        <form method="post">
        <?php wp_nonce_field( 'cloudflare', 'cloudflare_nonce' ) ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Option</th>
                <th>Keys</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width:30%;">
                        Cloudflare Turnstile Site Key
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_cloudflare_site_key ) ?>" name="dt_cloudflare_site_key" placeholder="add site key" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        Cloudflare Turnstile Secret Key
                    </td>
                    <td>
                        <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_cloudflare_secret_key ) ?>" name="dt_cloudflare_secret_key" placeholder="add secret key" />
                    </td>
                </tr>
                <tr>
                    <td style="width:10%;">
                        <button class="button btn" type="submit">Update</button>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }
}
