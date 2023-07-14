<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'wpmdb_compatibility_plugin_whitelist', function ( $plugins ) {
    $plugins[] = 'disciple-tools-multisite';
    return $plugins;
} );

add_filter('wpmdb_get_alter_queries', function ( $queries, $state_data ){
    if ( empty( $state_data ) ){
        return $queries;
    }

    $blog_id = isset( $state_data['mst_destination_subsite'] ) ? $state_data['mst_destination_subsite'] : $this->selected_subsite( $state_data );

    if ( 1 > $blog_id ){
        return $queries;
    }

    if ( !is_multisite() || 'pull' !== $state_data['intent'] || empty( $state_data['tables'] ) ){
        return $queries;
    }

    $additional_queries = [];

    global $wpdb;
    $target_prefix = $state_data['new_prefix'];

    $temp_prefix                = $state_data['temp_prefix'];
    $source_users_table = $target_prefix . 'wpmdbglobal_users';

    $temp_source_users_table    = $temp_prefix . $source_users_table;

    $sql = "
        SELECT source.id AS source_id, target.id AS target_id FROM `{$temp_source_users_table}` AS source, `{$wpdb->prefix}users` AS target
        WHERE target.user_login = source.user_login
        AND target.user_email = source.user_email
    ";

    $user_ids_to_update = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared


    foreach ( $user_ids_to_update as $user_ids ){
        $additional_queries[]['query'] = "UPDATE `{$wpdb->prefix}users` SET wpmdb_user_id = {$user_ids['source_id']} WHERE id = {$user_ids['target_id']};\n";


        if ( $user_ids['source_id'] !== $user_ids['target_id'] ){
            //update comment mentions
            $additional_queries[]['query'] = "UPDATE `{$target_prefix}comments` 
                        SET comment_content = REPLACE( comment_content, '{$user_ids['source_id']}', '{$user_ids['target_id']}' )
                        WHERE comment_content LIKE '%@[%';\n";
        }
    }

    //update D.T stuff.
    //user ids
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}postmeta` as pm,`{$wpdb->prefix}users` AS u
                    SET pm.meta_value = CONCAT('user-',u.id)
                    WHERE pm.meta_value = CONCAT('user-',u.wpmdb_user_id)
                    ;\n";
    //corresponds to user
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}postmeta` as pm,`{$wpdb->prefix}users` AS u
                    SET pm.meta_value = u.id
                    WHERE pm.meta_key = 'corresponds_to_user'
                    AND pm.meta_value = u.wpmdb_user_id
                    ;\n";
    //corresponds to user
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}options` as o,`{$wpdb->prefix}users` AS u
                    SET o.option_value = u.id
                    WHERE o.option_name = 'base_user'
                    AND o.option_value = u.wpmdb_user_id
                    ;\n";

    //@todo user filters
    //D.T Shares
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_share` as s,`{$wpdb->prefix}users` AS u
                    SET s.user_id = u.id
                    WHERE s.user_id = u.wpmdb_user_id
                    ;\n";
    //D.T Notifications
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_notifications` as n,`{$wpdb->prefix}users` AS u
                    SET n.user_id = u.id
                    WHERE n.user_id = u.wpmdb_user_id
                    ;\n";
    //D.T Comment mentions
    //    $additional_queries[]['query'] = "
    //            UPDATE `{$target_prefix}comments` as c,`{$wpdb->prefix}users` AS u
    //            SET c.comment_content = REPLACE( comment_content, CONCAT( '](', u.wpmdb_user_id ,')' ), CONCAT( '](', u.id ,')' ) )
    //            WHERE comment_content LIKE '%@[%'
    //            ;\n";
    //D.T Activity Log
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_activity_log` as a,`{$wpdb->prefix}users` AS u
                    SET a.user_id = u.id
                    WHERE a.user_id = u.wpmdb_user_id
                    ;\n";
    //D.T Activity Log assigned to
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_activity_log` as a,`{$wpdb->prefix}users` AS u
                    SET a.user_id = CONCAT( 'user-', u.id )
                    WHERE a.user_id = CONCAT( 'user-', u.wpmdb_user_id )
                    ;\n";
    //D.T Activity Log corresponds_to_user
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_activity_log` as a,`{$wpdb->prefix}users` AS u
                    SET a.meta_value = u.id
                    WHERE a.meta_value = u.wpmdb_user_id
                    AND a.meta_key = 'corresponds_to_user'
                    ;\n";
    //D.T Activity users
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_activity_log` as a,`{$wpdb->prefix}users` AS u
                    SET a.object_id = u.id
                    WHERE a.object_id = u.wpmdb_user_id
                    AND a.object_type = 'User'
                    ;\n";
    //D.T Post User Meta
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_post_user_meta` as a,`{$wpdb->prefix}users` AS u
                    SET a.user_id = u.id
                    WHERE a.user_id = u.wpmdb_user_id
                    ;\n";
    //D.T Reports
    $additional_queries[]['query'] = "
                    UPDATE `{$target_prefix}dt_reports` as a,`{$wpdb->prefix}users` AS u
                    SET a.user_id = u.id
                    WHERE a.user_id = u.wpmdb_user_id
                    ;\n";

    $before_drop_index = count( $additional_queries ) - 1;
    foreach ( $queries as $index => $query ){
        if ( $query['query'] === "DROP TABLE `{$source_users_table}`;\n" ){
            $before_drop_index = $index;
        }
    }
    array_splice( $queries, $before_drop_index, 0, $additional_queries );

    return $queries;
}, 20, 2);
