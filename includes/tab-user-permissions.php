<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * DT_Multisite_Tab_User_Permissions
 *
 * Network admin tab for managing user permission settings across the multisite network
 */
class DT_Multisite_Tab_User_Permissions {

    public function content() {
        // Handle form submission
        if ( isset( $_POST['dt_multisite_user_permissions_nonce'] )
             && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_multisite_user_permissions_nonce'] ) ), 'dt_multisite_user_permissions' ) ) {
            $this->process_form();
        }

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">

                        <!-- User Permissions Settings -->
                        <div class="postbox">
                            <h3 class="hndle">
                                <?php esc_html_e( 'Subsite Administrator User Permissions', 'disciple-tools-multisite' ); ?>
                            </h3>
                            <div class="inside">
                                <?php $this->user_permissions_settings(); ?>
                            </div>
                        </div>
                        <!-- End User Permissions Settings -->

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function process_form() {
        if ( ! is_super_admin() ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $allow_subsite_admins_edit_users = isset( $_POST['dt_allow_subsite_admins_edit_users'] ) ? 1 : 0;
        update_site_option( 'dt_allow_subsite_admins_edit_users', $allow_subsite_admins_edit_users );

        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Settings saved successfully.', 'disciple-tools-multisite' ); ?></p>
        </div>
        <?php
    }

    private function user_permissions_settings() {
        $allow_subsite_admins_edit_users = get_site_option( 'dt_allow_subsite_admins_edit_users', false );

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_multisite_user_permissions', 'dt_multisite_user_permissions_nonce' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="dt_allow_subsite_admins_edit_users">
                                <?php esc_html_e( 'Allow Subsite Administrators to Edit Users', 'disciple-tools-multisite' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="dt_allow_subsite_admins_edit_users"
                                   name="dt_allow_subsite_admins_edit_users"
                                   value="1"
                                   <?php checked( $allow_subsite_admins_edit_users, 1 ); ?> />

                            <p class="description">
                                <?php esc_html_e( 'When enabled, administrators on subsites can edit user details (including passwords and email addresses) for users within their own subsite.', 'disciple-tools-multisite' ); ?>
                            </p>

                            <p class="description">
                                <strong><?php esc_html_e( 'Security Restrictions:', 'disciple-tools-multisite' ); ?></strong><br>
                                • <?php esc_html_e( 'Super admin accounts cannot be edited by subsite administrators', 'disciple-tools-multisite' ); ?><br>
                                • <?php esc_html_e( 'Users from other subsites cannot be edited', 'disciple-tools-multisite' ); ?><br>
                                • <?php esc_html_e( 'Only users who are members of the current subsite can be edited', 'disciple-tools-multisite' ); ?>
                            </p>

                            <p class="description">
                                <strong style="color: #d63638;"><?php esc_html_e( 'Security Note:', 'disciple-tools-multisite' ); ?></strong>
                                <?php esc_html_e( 'Enable this only if you trust your subsite administrators, as they will be able to reset passwords and change email addresses for users on their sites.', 'disciple-tools-multisite' ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Save Settings', 'disciple-tools-multisite' ) ); ?>
        </form>
        <?php
    }
}
