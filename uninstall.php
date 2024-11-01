<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wpptransients");
$wpdb->query($wpdb->prepare("DELETE p,pm FROM wp_posts `p` JOIN wp_postmeta `pm` on pm.post_id=p.ID where p.post_type='%s'",'wpp_c_block'));
delete_option('yg_wpp_version');
delete_option('yg_wpp_show_bnnrs_edit');
delete_option('yg_wpp_supprss_pu');