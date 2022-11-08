<?php 
	global $wpdb;
	
	
	$sql = "DROP TABLE " . $wpdb->base_prefix . "ucf_devops_main;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();
	
		
	$sql = "DROP TABLE " . $wpdb->base_prefix . "ucf_devops_setup;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();


	remove_shortcode ('wp_devops_resolved');
	remove_shortcode ('wp_devops_current_sprint');
	remove_shortcode ('wp_devops_tab_start');
	remove_shortcode ('wp_devops_tab_end');
	remove_shortcode ('wp_devops_list_sprint');
	
	
	
	remove_menu_page( 'ucf_devops_rest_main_menu'); 
	remove_menu_page( 'wp_workday_rest_json'); 

?>
