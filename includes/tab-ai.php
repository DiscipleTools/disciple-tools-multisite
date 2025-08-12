<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_AI
 */
class DT_Multisite_Tab_AI
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
        if ( isset( $_POST['ai_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_nonce'] ) ), 'ai' ) ) {

            update_site_option( 'DT_AI_llm_endpoint', sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_endpoint'] ?? '' ) ) );
            update_site_option( 'DT_AI_llm_api_key', sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_api_key'] ?? '' ) ) );
            update_site_option( 'DT_AI_llm_model', sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_model'] ?? '' ) ) );
        }
    }

    public function list_keys(){
        $network_ai_llm_endpoint = get_site_option( 'DT_AI_llm_endpoint', '' );
        $network_ai_llm_api_key = get_site_option( 'DT_AI_llm_api_key', '' );
        $network_ai_llm_model = get_site_option( 'DT_AI_llm_model', '' );

        ?>
        <!-- Box -->
        <form method="post">
        <?php wp_nonce_field( 'ai', 'ai_nonce' ) ?>
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
                        LLM Endpoint
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_endpoint ) ?>" name="dt_ai_llm_endpoint" placeholder="Add LLM Endpoint" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        LLM API Key
                    </td>
                    <td>
                        <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_api_key ) ?>" name="dt_ai_llm_api_key" placeholder="Add LLM API Key" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        LLM Model
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_model ) ?>" name="dt_ai_llm_model" placeholder="Add LLM Model" />
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
