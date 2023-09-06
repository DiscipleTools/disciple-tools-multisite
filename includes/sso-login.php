<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

foreach ( wp_get_themes() as $theme ){
    if ( $theme->get( 'TextDomain' ) === 'disciple_tools' ){
        require_once $theme->get_stylesheet_directory() . '/dt-login/login-fields.php';
    }
}


/**
 * Class Disciple_Tools_SSO_Login
 */
class DT_Multisite_Tab_SSO_Login {
    private $token = 'sso-login';
    private $tab_title = 'SSO Login';
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
    } // End __construct()



    public function content() {

        if ( !is_super_admin() ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $link = 'admin.php?page=disciple-tools-multisite&tab='.$this->token.'&sub_tab=';

        if ( isset( $_GET['sub_tab'] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET['sub_tab'] ) );
        } else {
            $tab = 'general';
        }

        $vars = $this->process_postback();
        $tabs = [];
        foreach ( $vars as $val ) {
            $tabs[$val['tab']] = ucwords( str_replace( '_', ' ', $val['tab'] ) );
        }
        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->tab_title ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ( $tabs as $key => $value ) {
                    ?>
                    <a href="<?php echo esc_attr( $link . $key ) ?>"
                       class="nav-tab <?php echo esc_html( ( $tab == $key ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $value ) ?></a>
                    <?php
                }
                ?>
            </h2>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder">
                        <div id="post-body-content">
                            <!-- Box -->
                            <form method="post">
                                <?php wp_nonce_field( $this->token.get_current_user_id(), $this->token . '_nonce' ) ?>
                                <table class="widefat striped">
                                    <tbody>
                                    <?php
                                    if ( ! empty( $vars ) ) {
                                        foreach ( $vars as $key => $value ) {
                                            if ( $tab === $value['tab'] ) {
                                                $this->tab( $value );
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="2">
                                            <button class="button" type="submit">Save</button> <button class="button" type="submit" style="float:right;" name="delete" value="1">Reset</button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </form>
                            <br>
                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
        </div><!-- End wrap -->
        <?php
    }

    public function tab( $args ) {
        $must_have_super_admin_rights = false;
        switch ( $args['type'] ) {
            case 'text':
                ?>
                <tr>
                    <td style="width:10%; white-space:nowrap;">
                        <strong><?php echo esc_html( $args['label'] ) ?></strong>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="<?php echo esc_attr( $args['key'] ) ?>"
                            value="<?php echo esc_attr( $args['value'] ) ?>"
                            <?php echo $must_have_super_admin_rights ? 'disabled' : '' ?>
                        />
                        <?php echo esc_attr( $args['description'] ) ?>
                    </td>
                </tr>
                <?php
                break;
            case 'select':
                ?>
                <tr>
                    <td style="width:10%; white-space:nowrap;">
                        <strong><?php echo esc_html( $args['label'] ) ?></strong>
                    </td>
                    <td>
                        <select
                            name="<?php echo esc_attr( $args['key'] ) ?>"
                            <?php echo $must_have_super_admin_rights ? 'disabled' : '' ?>
                        >
                            <option></option>
                            <?php
                            foreach ( $args['default'] as $item_key => $item_value ) {
                                ?>
                                <option value="<?php echo esc_attr( $item_key ) ?>" <?php echo ( $item_key === $args['value'] ) ? 'selected' : '' ?>><?php echo esc_html( $item_value ) ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <?php echo esc_html( $args['description'] ) ?>
                    </td>
                </tr>
                <?php
                break;
            case 'label':
                ?>
                <tr>
                    <td style="width:10%; white-space:nowrap;">
                        <strong><?php echo esc_html( $args['label'] ) ?></strong>
                    </td>
                    <td>
                        <?php echo esc_html( $args['description'] ) ?>
                        <?php echo ( isset( $args['description_2'] ) && ! empty( $args['description_2'] ) ) ? '<p>' . esc_html( $args['description_2'] ) . '</p>' : '' ?>
                    </td>
                </tr>
                <?php
                break;
            default:
                break;
        }
    }

    public function process_postback(){
        if ( !is_super_admin() ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }
        // process POST
        if ( isset( $_POST[$this->token.'_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST[$this->token.'_nonce'] ) ), $this->token . get_current_user_id() ) ) {

            $params = $_POST;

            if ( isset( $params['delete'] ) ) {
                DT_Login_Fields::delete();
            } else {
                DT_Login_Fields::update( $params );
            }
        }

        $vars = DT_Login_Fields::all();

        return $vars;
    }
}
DT_Multisite_Tab_SSO_Login::instance();
