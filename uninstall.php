<?php
/*
 * Wordpress will run the code in this file when the user deletes the plugin
 * 
 */

if ( !defined('WP_UNINSTALL_PLUGIN')) 
	exit;

delete_option('blogimpressions_version');
delete_option('blogimpressions_registered');
delete_option('blogimpressions_tracking_id');
delete_option('blogimpressions_token_id');
?>