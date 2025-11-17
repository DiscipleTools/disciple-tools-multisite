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
        // Verify nonces
        $ai_chat_nonce_verified = isset( $_POST['ai_chat_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_chat_nonce'] ) ), 'ai_chat' );

        $ai_transcript_nonce_verified = isset( $_POST['ai_transcript_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_transcript_nonce'] ) ), 'ai_transcript' );

        $ai_features_nonce_verified = isset( $_POST['ai_features_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_features_nonce'] ) ), 'ai_features' );

        if ( !$ai_chat_nonce_verified && !$ai_transcript_nonce_verified && !$ai_features_nonce_verified ) {
            return [
                'is_update' => false,
                'updated' => false
            ];
        }
        // Get current settings fresh from database
        $current_settings = get_site_option( 'DT_AI_connection_settings', [
            'llm_provider' => '',
            'llm_provider_chat_path' => '',
            'llm_endpoint' => '',
            'llm_api_key' => '',
            'llm_model' => '',
            'transcript_llm_provider' => '',
            'transcript_llm_provider_transcript_path' => '',
            'transcript_llm_endpoint' => '',
            'transcript_llm_api_key' => '',
            'transcript_llm_model' => ''
        ] );

        if ( $ai_chat_nonce_verified ) {


            $updated_settings = $current_settings;
            $updated_settings['llm_provider'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_providers'] ?? '' ) );
            $updated_settings['llm_provider_chat_path'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_provider_chat_paths'] ?? '' ) );
            $updated_settings['llm_endpoint'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_endpoint'] ?? '' ) );
            $updated_settings['llm_api_key'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_api_key'] ?? '' ) );
            $updated_settings['llm_model'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_llm_model'] ?? '' ) );

            update_site_option( 'DT_AI_connection_settings', $updated_settings );

            return [
                'is_update' => true,
                'updated' => true
            ];
        }

        // Process Transcription Model Form
        if ( $ai_transcript_nonce_verified ) {

            $updated_settings = $current_settings;
            $updated_settings['transcript_llm_provider'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_providers'] ?? '' ) );
            $updated_settings['transcript_llm_provider_transcript_path'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_provider_transcript_paths'] ?? '' ) );
            $updated_settings['transcript_llm_endpoint'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_endpoint'] ?? '' ) );
            $updated_settings['transcript_llm_api_key'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_api_key'] ?? '' ) );
            $updated_settings['transcript_llm_model'] = sanitize_text_field( wp_unslash( $_POST['dt_ai_transcript_llm_model'] ?? '' ) );

            update_site_option( 'DT_AI_connection_settings', $updated_settings );

            return [
                'is_update' => true,
                'updated' => true
            ];
        }

        // Process Enabled Features Form
        if ( $ai_features_nonce_verified ) {
            $post_vars = dt_recursive_sanitize_array( $_POST );

            // Check if AI plugin is available
            if ( !class_exists( 'DT_AI_Network_API' ) ) {
                return [
                    'is_update' => false,
                    'updated' => false
                ];
            }

            // Get all modules
            $modules = DT_AI_Network_API::list_modules();

            // Build network module states array
            $network_module_states = [];
            foreach ( $modules as $module ) {
                $network_module_states[ $module['id'] ] = isset( $post_vars[ $module['id'] ] ) ? 1 : 0;
            }

            // Save network-level module states
            update_site_option( 'DT_AI_network_modules', $network_module_states );

            return [
                'is_update' => true,
                'updated' => true
            ];
        }

        return false;
    }

    public function list_keys( $processed ){
        $network_settings = get_site_option( 'DT_AI_connection_settings', [
            'llm_provider' => '',
            'llm_provider_chat_path' => '',
            'llm_endpoint' => '',
            'llm_api_key' => '',
            'llm_model' => '',
            'transcript_llm_provider' => '',
            'transcript_llm_provider_transcript_path' => '',
            'transcript_llm_endpoint' => '',
            'transcript_llm_api_key' => '',
            'transcript_llm_model' => ''
        ] );

        // Use network API to get providers (works even without DT theme)
        $ai_providers = class_exists( 'DT_AI_Network_API' ) ? DT_AI_Network_API::get_ai_providers() : [];

        $selected_ai_provider = $network_settings['llm_provider'] ?? '';
        $selected_ai_provider_chat_path = $network_settings['llm_provider_chat_path'] ?? '';

        $selected_ai_transcript_provider = $network_settings['transcript_llm_provider'] ?? '';
        $selected_ai_transcript_provider_chat_path = $network_settings['transcript_llm_provider_transcript_path'] ?? '';

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
        <!-- Chat Model Form -->
        <form method="post" id="chat-model-form">
            <?php wp_nonce_field( 'ai_chat', 'ai_chat_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Chat Model</span></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width:30%;">
                            Provider
                        </td>
                        <td>
                            <select id="dt_ai_llm_providers" name="dt_ai_llm_providers" style="width:50%; vertical-align: top;" required>
                                <?php if ( empty( $selected_ai_provider ) ) : ?>
                                    <option value="" disabled selected>--- Please Select Option ---</option>
                                <?php endif; ?>
                                <?php
                                foreach ( $ai_providers as $provider_key => $provider ) {

                                    // Exclude providers with no valid paths.
                                    if ( !empty( $provider['paths']['chat'] ) ) {
                                        $selected = ( !empty( $selected_ai_provider ) && $selected_ai_provider == $provider_key ) ? 'selected="selected"' : '';
                                        ?>
                                        <option value="<?php echo esc_attr( $provider_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $provider['label'] ) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">
                            Endpoint
                        </td>
                        <td>
                            <input type="text" class="regular-text" style="width:50%; vertical-align: top;" value="<?php echo esc_attr( $network_settings['llm_endpoint'] ) ?>" id="dt_ai_llm_endpoint" name="dt_ai_llm_endpoint" placeholder="Add Chat LLM Endpoint" />

                            <select id="dt_ai_llm_provider_chat_paths" name="dt_ai_llm_provider_chat_paths" style="width:48%; vertical-align: top;" required>
                                <?php if ( empty( $selected_ai_provider_chat_path ) ) : ?>
                                    <option value="" disabled selected>--- Please Select Option ---</option>
                                <?php endif; ?>
                                <?php
                                if ( !empty( $selected_ai_provider ) && isset( $ai_providers[ $selected_ai_provider ]['paths']['chat'] ) ) {
                                    foreach ( $ai_providers[ $selected_ai_provider ]['paths']['chat'] as $path_key => $path ) {
                                        $selected = ( !empty( $selected_ai_provider_chat_path ) && $selected_ai_provider_chat_path == $path_key ) ? 'selected="selected"' : '';
                                        ?>
                                        <option value="<?php echo esc_attr( $path_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $path ) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">
                            API Key
                        </td>
                        <td>
                            <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_settings['llm_api_key'] ) ?>" name="dt_ai_llm_api_key" placeholder="Add Chat LLM API Key" />
                        </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">
                            Model
                        </td>
                        <td>
                            <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_settings['llm_model'] ) ?>" id="dt_ai_llm_model" name="dt_ai_llm_model" placeholder="Add Chat LLM Model" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="width:10%;">
                        <span style="float:right;">
                            <button class="button btn" type="submit">Save Chat Model</button>
                        </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>

        <!-- Transcription Model Form -->
        <form method="post" id="transcript-model-form">
            <?php wp_nonce_field( 'ai_transcript', 'ai_transcript_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Transcription Model</span></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="width:30%;">
                        Provider
                    </td>
                    <td>
                        <select id="dt_ai_transcript_llm_providers" name="dt_ai_transcript_llm_providers" style="width:50%; vertical-align: top;" required>
                            <?php if ( empty( $selected_ai_transcript_provider ) ) : ?>
                                <option value="" disabled selected>--- Please Select Option ---</option>
                            <?php endif; ?>
                            <?php
                            foreach ( $ai_providers as $provider_key => $provider ) {

                                // Exclude providers with no valid paths.
                                if ( !empty( $provider['paths']['transcript'] ) ) {
                                    $selected = ( !empty( $selected_ai_transcript_provider ) && $selected_ai_transcript_provider == $provider_key ) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo esc_attr( $provider_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $provider['label'] ) ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        Endpoint
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:50%; vertical-align: top;" value="<?php echo esc_attr( $network_settings['transcript_llm_endpoint'] ) ?>" id="dt_ai_transcript_llm_endpoint" name="dt_ai_transcript_llm_endpoint" placeholder="Add Transcript LLM Endpoint" />

                        <select id="dt_ai_transcript_llm_provider_transcript_paths" name="dt_ai_transcript_llm_provider_transcript_paths" style="width:48%; vertical-align: top;" required>
                            <?php if ( empty( $selected_ai_transcript_provider_chat_path ) ) : ?>
                                <option value="" disabled selected>--- Please Select Option ---</option>
                            <?php endif; ?>
                            <?php
                            if ( !empty( $selected_ai_transcript_provider ) && isset( $ai_providers[ $selected_ai_transcript_provider ]['paths']['transcript'] ) ) {
                                foreach ( $ai_providers[ $selected_ai_transcript_provider ]['paths']['transcript'] as $path_key => $path ) {
                                    $selected = ( !empty( $selected_ai_transcript_provider_chat_path ) && $selected_ai_transcript_provider_chat_path == $path_key ) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo esc_attr( $path_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $path ) ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        API Key
                    </td>
                    <td>
                        <input type="password" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_settings['transcript_llm_api_key'] ) ?>" name="dt_ai_transcript_llm_api_key" placeholder="Add Transcript LLM API Key" />
                    </td>
                </tr>
                <tr>
                    <td style="width:30%;">
                        Model
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $network_settings['transcript_llm_model'] ) ?>" id="dt_ai_transcript_llm_model" name="dt_ai_transcript_llm_model" placeholder="Add Transcript LLM Model" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="width:10%;">
                        <span style="float:right;">
                            <button class="button btn" type="submit">Save Transcription Model</button>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>

        <!-- Enabled Features Form -->
        <form method="post" id="features-form">
            <?php wp_nonce_field( 'ai_features', 'ai_features_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Network Default Enabled Features</span></th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Check if AI plugin is available
                if ( !class_exists( 'DT_AI_Network_API' ) ) {
                    ?>
                    <tr>
                        <td colspan="2">
                            <div class="notice notice-warning inline">
                                <p>
                                    <strong>AI Plugin Not Available:</strong> The Disciple.Tools AI plugin must be network activated to configure AI features.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php
                } else {
                    $modules = DT_AI_Network_API::list_modules();

                    // Get network-level module states
                    $network_module_states = get_site_option( 'DT_AI_network_modules', [] );

                    foreach ( $modules as $module ) {
                    if ( isset( $module['visible'] ) && $module['visible'] ) {
                        // Check network state, default to the module's default enabled state if not set
                        $network_enabled = isset( $network_module_states[ $module['id'] ] )
                            ? $network_module_states[ $module['id'] ]
                            : ( isset( $module['enabled'] ) ? $module['enabled'] : 0 );
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_attr( $module['name'] ) ?>
                                <br>
                                <small><?php echo esc_attr( $module['description'] ) ?></small>
                            </td>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr( $module['id'] ) ?>" <?php echo ( $network_enabled ? 'checked' : '' ) ?>>
                            </td>
                        </tr>
                        <?php
                    }
                }
                } // End else (AI plugin available check)
                ?>
                <tr>
                    <td colspan="2">
                        <span style="float:right;">
                            <button class="button btn" type="submit">Save Enabled Features</button>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <script>
            jQuery(document).ready(function() {
                const aiProviders = [<?php echo json_encode( $ai_providers ) ?>][0];

                /**
                 * Chat Model
                 */

                // Function to update the chat paths dropdown based on selected provider
                function updateChatElements(selectedProvider) {
                    const chatEndpoint = jQuery('#dt_ai_llm_endpoint');
                    const chatPathsSelect = jQuery('#dt_ai_llm_provider_chat_paths');
                    const chatModel = jQuery('#dt_ai_llm_model');
                    const aiProvider = aiProviders[selectedProvider];

                    // Ensure we have a valid ai provider
                    if ( aiProvider ) {

                        // Get the paths for the selected provider
                        if ( aiProvider?.paths?.chat ) {
                            const chatPaths = aiProvider.paths.chat;

                            // Clear existing options
                            chatPathsSelect.empty();

                            // Add options for each path
                            jQuery.each(chatPaths, function(pathKey, pathValue) {
                                chatPathsSelect.append(
                                    jQuery('<option></option>')
                                    .attr('value', pathKey)
                                    .text(pathValue)
                                );
                            });
                        }

                        // Set default endpoint for the selected provider
                        if ( aiProvider?.endpoints?.chat && aiProvider.endpoints.chat.length > 0 ) {
                            chatEndpoint.val( aiProvider.endpoints.chat[0] );
                        }

                        // Set default model for the selected provider
                        if ( aiProvider?.models?.chat && aiProvider.models.chat.length > 0 ) {
                            chatModel.val( aiProvider.models.chat[0] );
                        }

                    } else if ( !selectedProvider ) {

                        // Clear existing options
                        chatPathsSelect.empty();

                        // Add placeholder option
                        chatPathsSelect.append(
                            jQuery('<option></option>')
                            .attr('value', '')
                            .attr('disabled', 'disabled')
                            .attr('selected', 'selected')
                            .text('--- Please Select Option ---')
                        );

                        // Clear remaining fields
                        chatEndpoint.val('');
                        chatModel.val('');
                    }
                }

                // Add change event listener to provider select
                jQuery('#dt_ai_llm_providers').on('change', function() {
                    updateChatElements( jQuery(this).val() );
                });

                /**
                 * Transcription Model
                 */

                // Function to update the transcription paths dropdown based on selected provider
                function updateTranscriptElements(selectedProvider) {
                    const transcriptEndpoint = jQuery('#dt_ai_transcript_llm_endpoint');
                    const transcriptPathsSelect = jQuery('#dt_ai_transcript_llm_provider_transcript_paths');
                    const transcriptModel = jQuery('#dt_ai_transcript_llm_model');
                    const aiProvider = aiProviders[selectedProvider];

                    // Ensure we have a valid ai provider
                    if ( aiProvider ) {

                        // Get the paths for the selected provider
                        if ( aiProvider?.paths?.transcript ) {
                            const transcriptPaths = aiProvider.paths.transcript;

                            // Clear existing options
                            transcriptPathsSelect.empty();

                            // Add options for each path
                            jQuery.each(transcriptPaths, function(pathKey, pathValue) {
                                transcriptPathsSelect.append(
                                    jQuery('<option></option>')
                                    .attr('value', pathKey)
                                    .text(pathValue)
                                );
                            });
                        }

                        // Set default endpoint for the selected provider
                        if ( aiProvider?.endpoints?.transcript && aiProvider.endpoints.transcript.length > 0 ) {
                            transcriptEndpoint.val( aiProvider.endpoints.transcript[0] );
                        }

                        // Set default model for the selected provider
                        if ( aiProvider?.models?.transcript && aiProvider.models.transcript.length > 0 ) {
                            transcriptModel.val( aiProvider.models.transcript[0] );
                        }

                    } else if ( !selectedProvider ) {

                        // Clear existing options
                        transcriptPathsSelect.empty();

                        // Add placeholder option
                        transcriptPathsSelect.append(
                            jQuery('<option></option>')
                            .attr('value', '')
                            .attr('disabled', 'disabled')
                            .attr('selected', 'selected')
                            .text('--- Please Select Option ---')
                        );

                        // Clear remaining fields
                        transcriptEndpoint.val('');
                        transcriptModel.val('');
                    }
                }

                // Add change event listener to provider select
                jQuery('#dt_ai_transcript_llm_providers').on('change', function() {
                    updateTranscriptElements( jQuery(this).val() );
                });
            });
        </script>
        <!-- End Box -->
        <?php
    }
}
