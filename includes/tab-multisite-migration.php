<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Multisite_Tab_Import_Subsite
 */
class DT_Multisite_Tab_Import_Subsite {
    public function content() {

        global $wpdb;
        $finished_migration = false;

        if ( isset( $_POST['migration-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['migration-nonce'] ), 'run-migration' ) && isset( $_POST['clear-cache'] ) ) {
            $temp_tables = $wpdb->get_results($wpdb->prepare( '
                    SELECT table_name as table_name
                    FROM information_schema.tables
                    WHERE table_schema = %s
                    AND table_name LIKE %s
                ', DB_NAME, 'dt_tmp_migration_%' ), ARRAY_A );
            foreach ( $temp_tables as $table ){
                $table_name = $table['table_name'];
                $wpdb->query( "DROP TABLE $table_name" ); // phpcs:ignore
            }
            delete_option( 'dt_import_migration' );
        }

        if ( isset( $_POST['migration-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['migration-nonce'] ), 'run-migration' ) && isset( $_POST['migrate'] ) ) {



            $old_site_id = isset( $_POST['old_site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['old_site_id'] ) ) : '';
            $old_db_prefix = 'dt_tmp_migration_';
            $new_site_name = !empty( $_POST['new_site'] ) ? sanitize_text_field( wp_unslash( $_POST['new_site'] ) ) : false;


            $cached = get_option( 'dt_import_migration', [
                'users' => [],
                'progress' => 0
            ] );

            if ( !isset( $cached['old_site_id'] ) || $cached['old_site_id'] !== $old_site_id ) {
                $cached = [
                    'users' => [],
                    'progress' => 0,
                    'old_site_id' => $old_site_id,
                    'new_site_name' => $new_site_name,
                    'old_db_prefix' => $old_db_prefix
                ];
            }
            $new_site_name = $cached['new_site_name'];
            $old_db_prefix = $cached['old_db_prefix'];
            $old_site_id = $cached['old_site_id'];
            $old_site_prefix = $old_site_id ? $old_site_id . '_' : '';
            $old_site_key = $old_db_prefix . $old_site_prefix;
            $temp_table_prefix = 'dt_tmp_migration_' . ( $old_site_id ? $old_site_id . '_' : '' );



            /**
             * Add users
             */
            $users = $wpdb->get_results( '
                SELECT * FROM dt_tmp_migration_users
            ', ARRAY_A );
            if ( !$users ){
                ?>
                <h1>Expected to find table named "dt_tmp_migration_users" but table not found or something went wrong</h1>
                <?php
                return false;
            }

            foreach ( $users as $user ){
                $new_user_id = null;
                if ( !isset( $cached['users'][$user['user_email']] ) ) {
                    $new_user_id = email_exists( $user['user_email'] );
                    $user_name_exists = username_exists( $user['user_login'] );

                    if ( !$new_user_id ){
                        // copy user to users table
                        $copy_user = $wpdb->query( $wpdb->prepare( "
                            INSERT INTO {$wpdb->users}
                            ( user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name )
                            SELECT user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name
                            FROM dt_tmp_migration_users
                            WHERE ID = %s
                        ", $user['ID'] ) );
                        // get new id
                        $new_user = get_user_by( 'email', $user['user_email'] );
                        $new_user_id = $new_user->ID;
                        // if $user_name_exits, replace with email.
                        if ( $user_name_exists ) {
                            $wpdb->query( $wpdb->prepare( "
                                UPDATE $wpdb->users
                                SET user_login = %s
                                WHERE ID = %s
                            ", strtolower( $user['user_email'] ), $new_user_id ) );
                        }
                    }
                    $cached['users'][$user['user_email']] = [
                        'old' => $user['ID'],
                        'new' => $new_user_id
                    ];
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
            }

            /**
             * Add new site
             */
            if ( !isset( $cached['site_id'] ) ) {
                $table_name = $temp_table_prefix . 'options';
                // phpcs:disable
                $admin_email = $wpdb->get_var("
                    SELECT option_value
                    FROM $table_name
                    WHERE option_name = 'admin_email'
                ");
                if ( !$new_site_name ){
                    $new_site_name = $wpdb->get_var("
                        SELECT option_value
                        FROM $table_name
                        WHERE option_name = 'blogname'
                    ");
                }
                // phpcs:enable
                $admin = get_user_by( 'email', $admin_email );


                $site_url = $cached['new_site_name'] . '.'. preg_replace( '|^www\.|', '', get_network()->domain );
                $new_site_id = wpmu_create_blog(
                    $site_url,
                    '/',
                    $cached['new_site_name'],
                    $admin->ID
                );
                if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) {
                    $site_url = 'https://' . $site_url;
                } else {
                    $site_url = 'http://' . $site_url;
                }
                $wpdb->dt_options_table_name = $table_name;
                $wpdb->query( $wpdb->prepare( "
                    UPDATE $wpdb->dt_options_table_name SET
                    option_value = %s
                    WHERE option_name = 'siteurl'
                ", $site_url ));
                $wpdb->query( $wpdb->prepare( "
                    UPDATE $wpdb->dt_options_table_name SET
                    option_value = %s
                    WHERE option_name = 'home'
                ", $site_url ));
                if ( !is_wp_error( $new_site_id ) ){
                    $cached['site_id'] = $new_site_id;
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                } else {
                    return 'Something went wrong: ' . $new_site_id->get_error_message();
                }
            }

            $usermeta_site_key = $wpdb->base_prefix . $cached['site_id'] . '_';

            //replace subsite specif usermeta ids
            if ( !isset( $cached['usermeta_keys'] ) ){
                if ( !$cached['old_site_id'] ){
                    $a = $wpdb->query( $wpdb->prepare( '
                        UPDATE dt_tmp_migration_usermeta SET
                        meta_key = CONCAT( %s, meta_key )
                    ', $usermeta_site_key ));
                    $a = $wpdb->query("
                        UPDATE dt_tmp_migration_usermeta SET
                        meta_key = REPLACE( meta_key, 'dt_tmp_migration_', '' )
                    " );
                } else {
                    $a = $wpdb->query( $wpdb->prepare('
                        UPDATE dt_tmp_migration_usermeta SET
                        meta_key = REPLACE(meta_key, %s, %s)
                    ', $old_site_key, $usermeta_site_key) );
                }
                if ( !is_wp_error( $a ) ){
                    $cached['usermeta_keys'] = true;
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
            }
            if ( !isset( $cached['options_keys'] ) ){
                $table_name = $temp_table_prefix . 'options';

                // phpcs:disable
                $a = $wpdb->query($wpdb->prepare( "
                    UPDATE $table_name SET
                    option_name = REPLACE(option_name, %s, %s)
                ", $temp_table_prefix, $usermeta_site_key) );
                // phpcs:enable

                if ( !is_wp_error( $a ) ){
                    $cached['options_keys'] = true;
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
            }


            /**
             * Update user ids
             */
            foreach ( $cached['users'] as $user_email => $values ){
                if ( !isset( $values['updated'] ) ){
                    $values['updated'] = [];
                }
                //update usermeta user_id
                if ( !isset( $values['updated']['user_meta'] ) ){
                    $a = $wpdb->query( $wpdb->prepare('
                        UPDATE dt_tmp_migration_usermeta SET
                        user_id = %s
                        WHERE user_id = %s
                    ', $values['new'], $values['old'] ) );
                    if ( !is_wp_error( $a ) ){
                        $cached['users'][$user_email]['updated']['user_meta'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }

                //update posts user_id
                if ( !isset( $values['updated']['posts'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}posts SET
                        post_author = %s
                        WHERE post_author = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['posts'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['corresponds_to_user'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}postmeta SET
                        meta_value = %s
                        WHERE meta_key = 'corresponds_to_user'
                        AND meta_value = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['corresponds_to_user'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['assigned_to'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}postmeta SET
                        meta_value = %s
                        WHERE meta_key = 'assigned_to'
                        AND meta_value = %s
                    ", 'user-' . $values["new"], 'user-' . $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['assigned_to'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['base_user'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}options SET
                        option_value = %s
                        WHERE option_name = 'base_user'
                        AND option_value = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['base_user'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['saved_filters'] ) ){
                    $filters = $wpdb->get_var( $wpdb->prepare( '
                        SELECT meta_value
                        FROM dt_tmp_migration_usermeta
                        WHERE meta_key = %s
                        AND user_id = %s
                    ', $usermeta_site_key . 'saved_filters', $values['new'] ) );
                    if ( $filters ){
                        $filters = maybe_unserialize( $filters );
                        foreach ( $filters as $post_type => $list ) {
                            foreach ( $filters[$post_type] as $filter_index => $filter ) {
                                if ( isset( $filter['query']['assigned_to'] ) ) {
                                    foreach ( $filter['query']['assigned_to'] as $index => $u_id ) {
                                        foreach ( $cached['users'] as $email => $u ){
                                            if ( strval( $u_id ) == strval( $u['old'] ) ) {
                                                $filters[$post_type][$filter_index]['query']['assigned_to'][$index] = $u['new'];
                                                break;
                                            }
                                        }
                                    }
                                    foreach ( $filter['labels'] as $index => $label ) {
                                        foreach ( $cached['users'] as $email => $u ){
                                            if ( $label['field'] === 'assigned_to' && strval( $label['id'] ) == strval( $u['old'] ) ) {
                                                $filters[$post_type][$filter_index]['labels'][$index]['id'] = $u['new'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $a = $wpdb->query( $wpdb->prepare('
                            UPDATE dt_tmp_migration_usermeta SET
                            meta_value = %s
                            WHERE meta_key = %s
                            AND user_id = %s
                        ', serialize( $filters ), $usermeta_site_key . 'saved_filters', $values['new'] ) );
                        if ( !is_wp_error( $a ) && $a ) {
                            $cached['users'][$user_email]['updated']['saved_filters'] = true;
                        }
                    }
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
                if ( !isset( $values['updated']['shares'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_share SET
                        user_id = %s
                        WHERE user_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['shares'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['notifications'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_notifications SET
                        user_id = %s
                        WHERE user_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['notifications'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                //@todo comment author?
                if ( !isset( $values['updated']['comments'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}comments SET
                        user_id = %s
                        WHERE user_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['comments'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                //comment @mentions
                if ( !isset( $values['updated']['comment_mentions'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}comments SET
                        comment_content = REPLACE( comment_content, %s, %s )
                        WHERE comment_content LIKE '%@[%'
                    ", ']('.$values["old"].')', ']('.$values["new"].')' ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['comments_mentions'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['activity_log'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_activity_log SET
                        user_id = %s
                        WHERE user_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['activity_log'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['activity_log_assigned_to'] ) ) {
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare( "
                        UPDATE {$temp_table_prefix}dt_activity_log SET
                        meta_value = %s
                        WHERE meta_value = %s
                    ", 'user-' . $values["new"], 'user-' . $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['activity_log_assigned_to'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['activity_log_assigned_to'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_activity_log SET
                        meta_value = %s
                        WHERE meta_value = %s
                        AND meta_key = 'corresponds_to_user'
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['activity_log_assigned_to'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['activity_log_users'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_activity_log SET
                        object_id = %s
                        WHERE object_type = 'User'
                        AND object_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['activity_log_users'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }
                if ( !isset( $values['updated']['post_user_meta'] ) ){
                    // phpcs:disable
                    $a = $wpdb->query( $wpdb->prepare("
                        UPDATE {$temp_table_prefix}dt_post_user_meta SET
                        user_id = %s
                        WHERE user_id = %s
                    ", $values["new"], $values["old"] ) );
                    // phpcs:enable
                    if ( !is_wp_error( $a ) ) {
                        $cached['users'][$user_email]['updated']['post_user_meta'] = true;
                        $cached['progress']++;
                        update_option( 'dt_import_migration', $cached );
                    }
                }

                if ( !isset( $values['updated']['usermeta_default'] ) ){
                    $locale_set = $wpdb->get_var( $wpdb->prepare("
                        SELECT meta_value
                        FROM dt_tmp_migration_usermeta
                        WHERE meta_key = 'locale'
                        AND user_id = %s
                    ", $values['new'] ) );
                    if ( empty( $locale_set ) ){
                        $a = $wpdb->query( $wpdb->prepare("
                            UPDATE dt_tmp_migration_usermeta
                            SET meta_key = REPLACE( meta_key, %s, '')
                            WHERE user_id = %s
                            AND ( meta_key = %s OR meta_key LIKE %s )
                        ", $usermeta_site_key, $values['new'], $usermeta_site_key . 'locale', $usermeta_site_key . 'dt_user_%' ) );
                        if ( !is_wp_error( $a ) ) {
                            $cached['users'][$user_email]['updated']['usermeta_default'] = true;
                            $cached['progress']++;
                            update_option( 'dt_import_migration', $cached );
                        }
                    }
                }
            }



            //copy usermeta table
            if ( !isset( $cached['migrated']['usermeta'] ) ){
                // phpcs:disable
                $copy_user_meta = $wpdb->query( "
                    INSERT INTO {$wpdb->usermeta} ( user_id, meta_key, meta_value )
                    SELECT user_id, meta_key, meta_value FROM dt_tmp_migration_usermeta
                ");
                // phpcs:enable
                if ( $copy_user_meta ){
                    $cached['migrated']['usermeta'] = true;
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
            }


            if ( !isset( $cached['migrations_run'] ) ) {
                switch_to_blog( $cached['site_id'] );
                new Disciple_Tools();
                Disciple_Tools_Migration_Engine::migrate( Disciple_Tools_Migration_Engine::$migration_number );
                restore_current_blog();
                $cached['migrations_run'] = true;
                $cached['progress']++;
                update_option( 'dt_import_migration', $cached );
            }

            /**
             * Copy, migrate and delete temporary tables tables
             */
            $existing_tables = $wpdb->get_results($wpdb->prepare( '
                SELECT table_name as table_name
                FROM information_schema.tables
                WHERE table_schema = %s
                AND table_name LIKE %s
            ', DB_NAME, $wpdb->base_prefix . $cached['site_id'] . '%'), ARRAY_A );
            $existing_table_names =[];
            foreach ( $existing_tables as $table ){
                $existing_table_names[] = $table['table_name'];
            }
            $temp_tables = $wpdb->get_results($wpdb->prepare( '
                SELECT table_name as table_name
                FROM information_schema.tables
                WHERE table_schema = %s
                AND table_name LIKE %s
            ', DB_NAME, 'dt_tmp_migration_%' ), ARRAY_A );
            foreach ( $temp_tables as $table ){
                $table_name = $table['table_name'];
                if ( $table_name === 'dt_tmp_migration_users' || $table_name === 'dt_tmp_migration_usermeta' ) {
                    $drop = $wpdb->query( "DROP TABLE $table_name" ); // phpcs:ignore
                    continue;
                }
                $table_name = str_replace( 'dt_tmp_migration_', $wpdb->base_prefix . ( $cached['old_site_id'] ? '' : $cached['site_id'] . '_' ), $table_name );
                $table_name = str_replace( $old_db_prefix, $wpdb->base_prefix, $table_name );
                $table_name = str_replace( '_' . $old_site_id . '_', '_' . $cached['site_id'] . '_', $table_name );

                // phpcs:disable
                if ( in_array( $table_name, $existing_table_names ) ) {
                    $drop = $wpdb->query( "DROP TABLE $table_name" ); // phpcs:ignore
                }
                $create = $wpdb->query("
                    CREATE TABLE $table_name LIKE {$table["table_name"]}
                ");
                $copy_table = $wpdb->query( "
                    INSERT INTO $table_name SELECT * FROM {$table["table_name"]}
                ");
                // phpcs:enable

                $wpdb->query( "DROP TABLE {$table["table_name"]}" ); // phpcs:ignore
                if ( !is_wp_error( $copy_table ) ){
                    $cached['migrated'][$table_name] = true;
                    $cached['progress']++;
                    update_option( 'dt_import_migration', $cached );
                }
            }
            delete_option( 'dt_import_migration' );
            $finished_migration = $cached['new_site_name'];

        }

        ?>
        <div class="wrap">
            <h2>Multisite Migration</h2>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->main_column( $finished_migration ); ?>

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
        </div><!-- End wrap -->

        <?php
    }
    public function main_column( $finished_migration ) {
        $cached = get_option( 'dt_import_migration', [] );
        ?>
        <?php if ( $finished_migration ) : ?>
            <h1>Finished migration for <?php echo esc_html( $finished_migration ) ?></h1>
        <?php else : ?>
            <?php if ( !isset( $cached['progress'] ) ) : ?>
                <h2>Here are the instructions on how to migrate a D.T instance from a multisite or a single site into a multisite</h2>

                <p>Because importing users into a new multisite changes the user ids we need a custom strategy to update ids while importing the data</p>
                <p>This strategy has you import the data into temporary tables so can replace the ids before copying to their final destination. </p>
                <p>Stage 1 is exporting and importing the database. Stage 2 is running the migration</p>
                <ol>
                    <li>
                        <ol style="list-style-type: lower-latin">
                            <li>
                                <h2>To export from a single site:</h2>
                                <p>
                                    <code>mysqldump [DB_NAME] > dump.sql</code><br>
                                    Replace [DB_NAME] with the name of your database. Ex: local<br>
                                </p>
                            </li>
                            <li>
                                <h2>To export from a multiste:</h2>
                                <p>Let's import a subsite with id <strong>15</strong> and database prefix <strong>wp_</strong> from a multisite.</p>
                                <p>Export your sql file from you old site:</p>
                                <p>We'll only extract the users and usermeta that belong to the subsite.</p>
                                <p>
                                    Replace 15 with the id of your existing subsite<br>
                                    Replace "wp_" if you instances uses another prefix
                                </p>
                                <p>
                                    Export the subsite specific tables<br>
                                    <code>mysqldump [DB_NAME] $(mysql -D [DB_NAME] -Bse "show tables like 'wp\_15\_%'") > dump.sql</code>
                                </p>
                                <p>
                                    Extract the users<br>
                                    <code>mysqldump [DB_NAME] wp_users --lock-all-tables --where "ID in (SELECT user_id from wp_usermeta um where um.meta_key = 'wp_15_capabilities' )" >> dump.sql</code>
                                </p>
                                <p>
                                    Extract the usermeta<br>
                                    <code>mysqldump [DB_NAME] wp_usermeta --lock-all-tables --where "user_id in (SELECT user_id from wp_usermeta um where um.meta_key = 'wp_15_capabilities' ) AND meta_key LIKE 'wp_15_%'" >> dump.sql</code>
                                </p>
                            </li>
                        </ol>
                    </li>
                    <li>
                        <strong>Replace domain names</strong><br>
                        <p>Ex domain: disciple.tools -> dtorg.local</p>
                        <code>sed -i -e 's|https://disciple\.tools|http://dtorg.local|g' -e 's|disciple\.tools|dtorg.local|g' dump.sql</code>
                    </li>
                    <li>
                        <strong>Prepare the sql</strong><br>
                        <p>We are going to import this sql file into the database creating new temporary tables starting with `dt_tmp_migration`</p>
                        <p>So we want to replace `wp_` (or your prefix) with `dt_tmp_migration`</p>
                        <code>sed -i -e 's|wp_|dt_tmp_migration_|g' dump.sql</code>
                    </li>
                    <li>
                        <strong>Import the sql</strong><br>
                        <p>Backup your database</p>
                        <p>Now on the server you want to import dump.sql into the database:</p>
                        <code>mysql [DB_NAME] < dump.sql</code>
                        Delete the dump.sql file so it is not laying around.
                    </li>
                </ol>
            <?php endif; ?>


            <h1>Now you are ready to run the D.T migration</h1>
            <form method="post" action="" >
                <?php wp_nonce_field( 'run-migration', 'migration-nonce' ); ?>
                <?php if ( isset( $cached['progress'] ) ) : ?>
                    <p>Progress <?php echo isset( $cached['progress'] ) ? esc_attr( $cached['progress'] ) : 0 ?></p>
                    <p>
                        <label>
                            Migration in progress.
                            <button class="button" type="submit" name="migrate">Continue Migration</button>
                        </label>
                    </p>
                <?php else : ?>
                    <p>
                        <label>
                            New SubSite Name. Note this must be a <strong>new</strong> site. You cannot use an existing name or subdomain.<br>
                            Also. Please disable any caching plugin (cache, redis, etc). They keep users from being created in some cases.
                            <input type="text" name="new_site" placeholder="site1">
                        </label>
                    </p>
                    <p>
                        <label>
                            Give the id of the subsite if you are migrating from a multisite. Leave blank if you are migrating from a single site.<br>
                            <input type="number" name="old_site_id" placeholder="subsite id">
                        </label>
                    </p>
                    <p>
                        <button class="button" type="submit" name="migrate">Run Migration</button>
                    </p>
                    <p>
                        <strong>Note: If the request times out refresh this page and click the continue button to resume the migration</strong>
                    </p>
                <?php endif; ?>
                <p>
                    Start over and delete migration cached and temporary tables.<br>
                    <button class="button" type="submit" name="clear-cache">Clear Progress/ Reset</button>
                </p>

            </form>


        <?php endif;
    }

}
