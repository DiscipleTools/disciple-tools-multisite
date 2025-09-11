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
        $processed = $this->process_post();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->list_keys( $processed ) ?>

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
            update_site_option( 'DT_AI_transcript_llm_endpoint', sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_endpoint'] ?? '' ) ) );
            update_site_option( 'DT_AI_transcript_llm_api_key', sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_api_key'] ?? '' ) ) );
            update_site_option( 'DT_AI_transcript_llm_model', sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_model'] ?? '' ) ) );

            return [
                'is_update' => true,
                'updated' => true
            ];
        }

        return [
            'is_update' => false,
            'updated' => false
        ];
    }

    public function list_keys( $processed ){
        $network_ai_llm_endpoint = get_site_option( 'DT_AI_llm_endpoint', '' );
        $network_ai_llm_api_key = get_site_option( 'DT_AI_llm_api_key', '' );
        $network_ai_llm_model = get_site_option( 'DT_AI_llm_model', '' );

        $network_ai_transcript_llm_endpoint = get_site_option( 'DT_AI_transcript_llm_endpoint', '' );
        $network_ai_transcript_llm_api_key = get_site_option( 'DT_AI_transcript_llm_api_key', '' );
        $network_ai_transcript_llm_model = get_site_option( 'DT_AI_transcript_llm_model', '' );

        if ( isset( $processed['is_update'], $processed['updated'] ) && $processed['is_update'] ) {
            ?>
            <div class="notice <?php echo esc_html( $processed['updated'] ? 'notice-success' : 'notice-error' ) ?>">
                <p>
                    <?php echo esc_html( $processed['updated'] ? 'Successfully updated all AI Plugin settings.' : 'No AI Plugin update changes made across any settings.' ) ?>
                </p>
            </div>
            <?php
        }
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field( 'ai', 'ai_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Chat Model</span></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width:30%;">
                            Endpoint
                        </td>
                        <td>
                            <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_endpoint ) ?>" name="dt_ai_llm_endpoint" placeholder="Add Chat LLM Endpoint" />
                        </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">
                            API Key
                        </td>
                        <td>
                            <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_api_key ) ?>" name="dt_ai_llm_api_key" placeholder="Add Chat LLM API Key" />
                        </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">
                            Model
                        </td>
                        <td>
                            <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_llm_model ) ?>" name="dt_ai_llm_model" placeholder="Add Chat LLM Model" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="width:10%;">
                        <span style="float:right;">
                            <button class="button btn" type="submit">Update</button>
                        </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Transcription Model</span></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="width:30%;">
                        Endpoint
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_transcript_llm_endpoint ) ?>" name="dt_ai_transcript_llm_endpoint" placeholder="Add Transcript LLM Endpoint" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        API Key
                    </td>
                    <td>
                        <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_transcript_llm_api_key ) ?>" name="dt_ai_transcript_llm_api_key" placeholder="Add Transcript LLM API Key" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        Model
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_ai_transcript_llm_model ) ?>" name="dt_ai_transcript_llm_model" placeholder="Add Transcript LLM Model" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="width:10%;">
                        <span style="float:right;">
                            <button class="button btn" type="submit">Update</button>
                        </span>
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
