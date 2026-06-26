<?php
/**
 * Redirect users who log in to the main site to their correct subsite.
 *
 * If the user has no membership on the main site but belongs to exactly one
 * subsite, they are forwarded there automatically.  If they belong to several
 * subsites a site-picker is displayed using the standard WordPress login-page
 * chrome so they can choose where to go.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DT_Multisite_Login_Site_Redirect {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ( ! is_multisite() ) {
            return;
        }

        // wp_login fires after a fresh credential check (form POST or re-auth).
        // Priority 5 so we exit before other handlers add their own redirects.
        add_action( 'wp_login', [ $this, 'redirect_on_login' ], 5, 2 );

        // login_redirect fires for BOTH fresh logins AND already-logged-in users
        // who land on wp-login.php.  It is the only hook that fires in the
        // already-logged-in path (wp_login does not).
        add_filter( 'login_redirect', [ $this, 'maybe_redirect_to_subsite' ], 20, 3 );

        // Allow wp_safe_redirect() to send users to subsite domains (needed for
        // subdomain multisite where subsites live on different hostnames).
        add_filter( 'allowed_redirect_hosts', [ $this, 'allow_subsite_redirect_hosts' ] );

        add_action( 'login_init', [ $this, 'handle_site_selector' ] );
    }

    // -------------------------------------------------------------------------
    // Fresh-login hook (form POST / re-auth)
    // -------------------------------------------------------------------------

    /**
     * Fires right after auth cookies are set on a successful login.
     * Calling wp_redirect() + exit here prevents WordPress's subsequent
     * wp_safe_redirect() from running, so cross-domain subsite URLs work
     * even without the allowed_redirect_hosts filter.
     *
     * @param string  $user_login
     * @param WP_User $user
     */
    public function redirect_on_login( $user_login, $user ) {
        // Skip REST API (Firebase SSO) and other non-web contexts.
        if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }

        // Only change redirect to subsite if the user is logging in to the main site.
        if ( ! is_main_site() ) {
            return;
        }

        // If the user is a member of the main site, let WordPress handle the redirect.
        $main_site_id = get_main_site_id();
        if ( is_user_member_of_blog( $user->ID, $main_site_id ) ) {
            return;
        }

        $subsites = $this->get_user_subsites( $user->ID, $main_site_id );

        if ( empty( $subsites ) ) {
            return;
        }

        if ( count( $subsites ) === 1 ) {
            // wp_redirect (not safe) bypasses the cross-domain block in
            // wp_validate_redirect() for subdomain multisite URLs.
            wp_redirect( get_blog_option( $subsites[0]->userblog_id, 'siteurl' ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            exit;
        }

        // Site selector lives on wp-login.php.  Use site_url() directly —
        // wp_login_url() is filtered by DT to return the custom /login page,
        // which doesn't fire login_init and ignores unknown actions.
        wp_safe_redirect( add_query_arg( 'action', 'dt-select-site', site_url( 'wp-login.php', 'login' ) ) );
        exit;
    }

    // -------------------------------------------------------------------------
    // Already-logged-in hook (and fallback for fresh logins)
    // -------------------------------------------------------------------------

    /**
     * Fires for every path through wp-login.php, including the already-logged-in
     * shortcut where wp_login never fires.
     *
     * @param string           $redirect_to
     * @param string           $requested_redirect_to
     * @param WP_User|WP_Error $user
     * @return string
     */
    public function maybe_redirect_to_subsite( $redirect_to, $requested_redirect_to, $user ) {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
            return $redirect_to;
        }

        if ( ! is_main_site() ) {
            return $redirect_to;
        }

        $main_site_id = get_main_site_id();

        if ( is_user_member_of_blog( $user->ID, $main_site_id ) ) {
            return $redirect_to;
        }

        $subsites = $this->get_user_subsites( $user->ID, $main_site_id );

        if ( empty( $subsites ) ) {
            return $redirect_to;
        }

        if ( count( $subsites ) === 1 ) {
            return get_blog_option( $subsites[0]->userblog_id, 'siteurl' );
        }

        // Multiple subsites: show a picker page.
        return add_query_arg( 'action', 'dt-select-site', site_url( 'wp-login.php', 'login' ) );
    }

    // -------------------------------------------------------------------------
    // Site-picker page
    // -------------------------------------------------------------------------

    /**
     * Intercept the dt-select-site login action and display the site picker.
     * Hooks into login_init so it runs inside wp-login.php where
     * login_header() and login_footer() are already defined.
     */
    public function handle_site_selector() {
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $action !== 'dt-select-site' ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }

        $main_site_id = get_main_site_id();
        $subsites     = $this->get_user_subsites( get_current_user_id(), $main_site_id );

        // Edge case: user somehow ended up here with only one site.
        if ( count( $subsites ) === 1 ) {
            wp_redirect( get_blog_option( $subsites[0]->userblog_id, 'siteurl' ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            exit;
        }

        $this->render_site_selector( $subsites );
        exit;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the sites a user belongs to, excluding the given site ID.
     *
     * @param int $user_id
     * @param int $exclude_site_id
     * @return array
     */
    private function get_user_subsites( $user_id, $exclude_site_id ) {
        $all_sites = get_blogs_of_user( $user_id );
        return array_values( array_filter( $all_sites, function ( $site ) use ( $exclude_site_id ) {
            return (int) $site->userblog_id !== (int) $exclude_site_id;
        } ) );
    }

    /**
     * Allow wp_safe_redirect() to send users to subsite domains.
     * Required for subdomain multisite where each subsite is on its own hostname.
     *
     * @param array $allowed_hosts
     * @return array
     */
    public function allow_subsite_redirect_hosts( $allowed_hosts ) {
        foreach ( get_sites() as $site ) {
            $allowed_hosts[] = $site->domain;
        }
        return array_unique( $allowed_hosts );
    }

    /**
     * Return the logo URL for a given blog: custom if set, otherwise the DT default.
     *
     * @param int $blog_id
     * @return string
     */
    private function get_site_logo_url( $blog_id ) {
        $custom = get_blog_option( $blog_id, 'custom_logo_url' );
        if ( ! empty( $custom ) ) {
            return $custom;
        }
        return apply_filters( 'dt_default_logo', get_template_directory_uri() . '/dt-assets/images/disciple-tools-logo-white.png' );
    }

    /**
     * Render a site-picker page using the standard WordPress login chrome.
     * login_header() and login_footer() are defined inside wp-login.php,
     * which is executing when login_init fires.
     *
     * @param array $sites
     */
    private function render_site_selector( array $sites ) {
        login_header( __( 'Select Your Site', 'disciple_tools' ) );
        ?>
        <style>
            :root { --primary-color: #3F729B; }
            .login .message { border-left-color: var(--primary-color); }
            #dt-site-list { margin-top: 1em; }
            .dt-site-card {
                display: flex;
                align-items: stretch;
                gap: 14px;
                margin-bottom: 10px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: inherit;
                box-sizing: border-box;
                width: 100%;
                transition: border-color 0.15s, box-shadow 0.15s;
                overflow: hidden;
            }
            .dt-site-card:hover {
                border-color: var(--primary-color);
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            }
            .dt-site-card::after {
                content: '';
                display: block;
                width: 8px;
                height: 8px;
                border-top: 2px solid var(--primary-color);
                border-inline-end: 2px solid var(--primary-color);
                transform: rotate(45deg);
                align-self: center;
                margin-inline-start: auto;
                margin-inline-end: 16px;
                flex-shrink: 0;
                opacity: 0;
                transition: opacity 0.15s;
            }
            .dt-site-card:hover::after { opacity: 1; }
            .dt-site-logo-container {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0.5rem;
                background: var(--primary-color);
                flex-shrink: 0;
            }
            .dt-site-logo {
                height: 40px;
                width: 40px;
                object-fit: contain;
                flex-shrink: 0;
            }
            .dt-site-info { 
                padding: 14px 16px;
                min-width: 0; 
            }
            .dt-site-name {
                font-weight: 600;
                font-size: 1em;
                color: #1d2327;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .dt-site-url,
            .dt-site-description {
                font-size: 0.8em;
                color: #72777c;
                margin-top: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .dt-site-description { font-style: italic; }
        </style>
        <p class="message" style="text-align:center; margin-bottom:1.5em;">
            <?php esc_html_e( 'You have access to multiple sites. Please choose where you want to go:', 'disciple_tools' ); ?>
        </p>
        <div id="dt-site-list">
            <?php foreach ( $sites as $site ) :
                $site_url    = get_blog_option( $site->userblog_id, 'siteurl' );
                $description = get_blog_option( $site->userblog_id, 'blogdescription' );
                $logo_url    = $this->get_site_logo_url( $site->userblog_id );
                $display_url = preg_replace( '#^https?://#', '', rtrim( $site_url, '/' ) );
                ?>
                <a href="<?php echo esc_url( $site_url ); ?>" class="dt-site-card">
                    <div class="dt-site-logo-container">
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="dt-site-logo" />
                    </div>    
                    <div class="dt-site-info">
                        <div class="dt-site-name"><?php echo esc_html( $site->blogname ); ?></div>
                        <div class="dt-site-url"><?php echo esc_html( $display_url ); ?></div>
                        <?php if ( ! empty( $description ) ) : ?>
                            <div class="dt-site-description"><?php echo esc_html( $description ); ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        login_footer();
    }
}

DT_Multisite_Login_Site_Redirect::instance();
