<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_Storage
 */
class DT_Multisite_Tab_Storage
{
    public function content(){
        $this->process_post();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

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
        if ( isset( $_POST['storage_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['storage_nonce'] ) ), 'storage' ) ) {

            $connection = [
                'id' => sanitize_text_field( wp_unslash( $_POST['m_main_col_connection_manage_id'] ?? '' ) ),
                'enabled' => isset( $_POST['m_main_col_connection_manage_enabled'] ) ? 1 : 0,
                'name' => sanitize_text_field( wp_unslash( $_POST['m_main_col_connection_manage_name'] ?? '' ) ),
                'type' => sanitize_text_field( wp_unslash( $_POST['m_main_col_connection_manage_type'] ?? '' ) )
            ];

            if ( !empty( $connection['type'] ) ) {
                $connection[ $connection['type'] ] = [
                    'access_key' => sanitize_text_field( wp_unslash( $_POST['connection_type_s3_access_key'] ?? '' ) ),
                    'secret_access_key' => sanitize_text_field( wp_unslash( $_POST['connection_type_s3_secret_access_key'] ?? '' ) ),
                    'region' => sanitize_text_field( wp_unslash( $_POST['connection_type_s3_region'] ?? '' ) ),
                    'endpoint' => sanitize_text_field( wp_unslash( $_POST['connection_type_s3_endpoint'] ?? '' ) ),
                    'bucket' => sanitize_text_field( wp_unslash( $_POST['connection_type_s3_bucket'] ?? '' ) )
                ];
            }

            update_site_option( 'dt_storage_multisite_connection_object', $connection );
        }
    }

    public function main_column(){
        $dt_storage_connection = get_site_option( 'dt_storage_multisite_connection_object', [] );
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field( 'storage', 'storage_nonce' ) ?>
            <table class="widefat striped" id="m_main_col_connection_manage">
                <thead>
                <tr>
                    <th>Connection Management</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="vertical-align: middle;">Enabled</td>
                        <td>
                            <input type="checkbox" id="m_main_col_connection_manage_enabled" name="m_main_col_connection_manage_enabled" <?php echo boolval( $dt_storage_connection['enabled'] ?? '' ) ? 'checked' : ''; ?> />
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Name</td>
                        <td>
                            <input type="hidden" id="m_main_col_connection_manage_id" name="m_main_col_connection_manage_id" value="<?php echo esc_attr( empty( $dt_storage_connection['id'] ) ? floor( microtime( true ) * 1000 ) : intval( $dt_storage_connection['id'] ) ); ?>" />
                            <input style="min-width: 100%;" type="text" id="m_main_col_connection_manage_name" name="m_main_col_connection_manage_name" value="<?php echo esc_attr( $dt_storage_connection['name'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Connection Type</td>
                        <td>
                            <select style="min-width: 100%;" id="m_main_col_connection_manage_type" name="m_main_col_connection_manage_type">
                                <option disabled value="">-- select connection type --</option>
                                <?php
                                if ( class_exists( 'Disciple_Tools_Storage_API' ) ) {
                                    foreach ( Disciple_Tools_Storage_API::list_supported_connection_types() ?? [] as $key => $type ) {
                                        if ( $type['enabled'] ) {
                                            $selected = ( $key === ( $dt_storage_connection['type'] ?? '' ) ) ? 'selected' : '';
                                            ?>
                                            <option
                                                value="<?php echo esc_attr( $key ) ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $type['label'] ) ?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Access Key</td>
                        <td>
                            <input style="min-width: 100%;" type="password" id="connection_type_s3_access_key" name="connection_type_s3_access_key" value="<?php echo esc_attr( $dt_storage_connection[ $dt_storage_connection['type'] ?? '' ]['access_key'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Secret Access Key</td>
                        <td>
                            <input style="min-width: 100%;" type="password" id="connection_type_s3_secret_access_key" name="connection_type_s3_secret_access_key" value="<?php echo esc_attr( $dt_storage_connection[ $dt_storage_connection['type'] ?? '' ]['secret_access_key'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Region</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_region" name="connection_type_s3_region" value="<?php echo esc_attr( $dt_storage_connection[ $dt_storage_connection['type'] ?? '' ]['region'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Endpoint</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_endpoint" name="connection_type_s3_endpoint" value="<?php echo esc_attr( $dt_storage_connection[ $dt_storage_connection['type'] ?? '' ]['endpoint'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Bucket</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_bucket" name="connection_type_s3_bucket" value="<?php echo esc_attr( $dt_storage_connection[ $dt_storage_connection['type'] ?? '' ]['bucket'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td>
                    <span style="float:right;">
                        <button type="submit" id="m_main_col_connection_manage_update_but"
                                class="button float-right m-connection-update-but"><?php esc_html_e( 'Update', 'disciple_tools' ) ?></button>
                    </span>
                    </td>
                </tr>
                </tfoot>
            </table>
        </form>
        <!-- End Box -->
        <?php
    }
}
