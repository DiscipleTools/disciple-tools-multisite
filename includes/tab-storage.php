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

            $post = dt_recursive_sanitize_array( $_POST );

            $enabled = isset( $post['m_main_col_connection_manage_enabled'] ) ? (bool) $post['m_main_col_connection_manage_enabled'] : false;
            $id = $post['m_main_col_connection_manage_id'] ?? '';
            $name = $post['m_main_col_connection_manage_name'] ?? '';
            $existing = get_site_option( 'dt_storage_multisite_connection', [] );
            $existing_type = is_array( $existing ) ? ( $existing['type'] ?? 'aws' ) : 'aws';
            $type = $post['m_main_col_connection_manage_type'] ?? $existing_type;

            $details = [
                'access_key' => $post['connection_type_s3_access_key'] ?? '',
                'secret_access_key' => $post['connection_type_s3_secret_access_key'] == '********' ? '' : $post['connection_type_s3_secret_access_key'],
                'region' => $post['connection_type_s3_region'] ?? '',
                'endpoint' => $post['connection_type_s3_endpoint'] ?? '',
                'bucket' => $post['connection_type_s3_bucket'] ?? '',
            ];

            if ( empty( $id ) ) {
                $id = substr( md5( maybe_serialize( $details ) ), 0, 12 );
            }

            // Path-style endpoint support (same behavior as theme storage settings)
            $types = ( class_exists( 'DT_Storage' ) && method_exists( 'DT_Storage', 'list_supported_connection_types' ) ) ? DT_Storage::list_supported_connection_types() : [];
            $default_path_style = isset( $types[$type]['default_path_style'] ) ? (bool) $types[$type]['default_path_style'] : ( $type === 'minio' );
            $path_style = isset( $post['path_style'] ) ? (bool) $post['path_style'] : $default_path_style;

            // Store a single flat connection array, same structure as dt_storage_connection
            $connection = [ 'id' => $id, 'enabled' => $enabled, 'name' => $name, 'type' => ( $type ?: 'aws' ), 'path_style' => $path_style ] + $details;

            update_site_option( 'dt_storage_multisite_connection', $connection );
        }
    }

    public function main_column(){
        $dt_storage_connection = get_site_option( 'dt_storage_multisite_connection', [] );
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
                                $types = ( class_exists( 'DT_Storage' ) && method_exists( 'DT_Storage', 'list_supported_connection_types' ) ) ? DT_Storage::list_supported_connection_types() : [];
                                $selected_type = $dt_storage_connection['type'] ?? 'aws';
                                foreach ( $types as $key => $meta ) {
                                    if ( !empty( $meta['enabled'] ) ) {
                                        $selected = ( $key === $selected_type ) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo esc_attr( $key ) ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $meta['label'] ?? $key ); ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Path-style endpoint</td>
                        <td>
                            <?php $types = ( class_exists( 'DT_Storage' ) && method_exists( 'DT_Storage', 'list_supported_connection_types' ) ) ? DT_Storage::list_supported_connection_types() : []; $selected_type = $dt_storage_connection['type'] ?? 'aws'; $default_path_style = isset( $types[$selected_type]['default_path_style'] ) ? (bool) $types[$selected_type]['default_path_style'] : false; $checked = isset( $dt_storage_connection['path_style'] ) ? (bool) $dt_storage_connection['path_style'] : $default_path_style; ?>
                            <label><input type="checkbox" id="path_style" name="path_style" value="1" <?php echo $checked ? 'checked' : ''; ?> /> Use path-style addressing (required by many MinIO setups)</label>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Access Key</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_access_key" name="connection_type_s3_access_key" value="<?php echo esc_attr( $dt_storage_connection['access_key'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Secret Access Key</td>
                        <td>
                            <input style="min-width: 100%;" type="password" id="connection_type_s3_secret_access_key" name="connection_type_s3_secret_access_key" value="<?php echo esc_attr( $dt_storage_connection['secret_access_key'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Region</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_region" name="connection_type_s3_region" value="<?php echo esc_attr( $dt_storage_connection['region'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Endpoint</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_endpoint" name="connection_type_s3_endpoint" value="<?php echo esc_attr( $dt_storage_connection['endpoint'] ?? '' ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;">Bucket</td>
                        <td>
                            <input style="min-width: 100%;" type="text" id="connection_type_s3_bucket" name="connection_type_s3_bucket" value="<?php echo esc_attr( $dt_storage_connection['bucket'] ?? '' ); ?>"/>
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
