<?php

/**
 * Class DT_Starter_Tab_Second
 */
class DT_Multisite_Tab_Overview
{
    public function content(){
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->overview_message() ?>
                        <?php $this->network_upgrade() ?>

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

    public function overview_message(){
        ?>
        <style>dt {
                font-weight: bold;
            }</style>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Overview of Plugin</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <dl>
                        <dt>This plugin serves the multi-site administrator with maintenance utility and management for a multi-site Disciple Tools installation.</dt>
                    </dl>

                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function network_upgrade(){
        $domains = false;
        if ( isset( $_POST['network_upgrade_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['network_upgrade_nonce'] ) ), 'network_upgrade' ) ) {
            if ( isset( $_POST['url_trigger'] ) ) {
                global $wpdb;
                $domains = $wpdb->get_col("SELECT domain FROM {$wpdb->base_prefix}blogs;");
            }
        }
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Network Upgrade</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <dl>
                        <dt>Because Disciple Tools uses migrations for the system that run only when the site is visited, in a multi-site installation
                            you can have various sites in the multi-site at different migration levels. This utility programmatically runs through all the
                            Disciple Tools sites in the multi-site system and triggers their load and therefore any remaining migrations.</dt>
                    </dl>
                </td>
            </tr>
            <tr>
                <td id="list">
                    <form method="post">
                        <?php wp_nonce_field( 'network_upgrade', 'network_upgrade_nonce', false, true ) ?>
                        <button type="submit" class="button" name="url_trigger" value="1">Trigger Sites through URL Call</button>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
        <?php if ( $domains ) : ?>
            <script>
                jQuery(document).ready(function(){
                    let domains = [<?php echo json_encode( $domains ) ?>][0]
                    console.log(domains)
                    if ( typeof domains !== 'undefined' ){
                        let list = jQuery('#list')
                        jQuery.each(domains, function(i,v){
                            setTimeout(function(){
                                jQuery.ajax({
                                    type: 'GET',
                                    datatype: 'json',
                                    url: 'https://'+v+'/wp-json/'
                                });
                                list.append( v + '<br>')
                                console.log(v)
                            }, 300 * i )
                        })
                    }
                })</script>
        <?php endif; ?>
        <br>
        <!-- End Box -->
        <?php
    }
}
