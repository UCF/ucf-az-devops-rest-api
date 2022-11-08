<?php
/**
* Plugin Name: Brad's Azure Devops REST API 4 UCF
* Plugin URI: https://www.yourwebsiteurl.com/
* Description: Brad's Azure Devops REST API 4 UCF
* Version: 2.92
* Author: Bradley Smith
* Author URI: http://yourwebsiteurl.com/
**/

// Load all the nav menu interface functions.
require_once ABSPATH . 'wp-admin/includes/nav-menu.php';



 
register_activation_hook( __FILE__, 'ucf_devops_rest_api' );
function ucf_devops_rest_api(){
	global $wpdb;
	global $wp;
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = "CREATE TABLE " . $wpdb->base_prefix . "ucf_devops_main (
		wiql_index		int,
		entry_index		int,
		wiql			text,
		fields_to_query	text,
		header_fields	text,
		field_style		text,
		char_count		text,
	PRIMARY KEY(wiql_index, entry_index )		
	)";
	dbDelta( $sql );
	//$wpdb->show_errors();
	$wpdb->flush();
	
	$sql = "CREATE TABLE " . $wpdb->base_prefix . "ucf_devops_setup (
		entry_index		int,
		pat_token		varchar(128),	
		pat_expire		date,
		description		varchar(128),
		organization	varchar(128),
		project			varchar(128),
	PRIMARY KEY(entry_index)		
	)";
	dbDelta( $sql );
	//$wpdb->show_errors();
	$wpdb->flush();

}


function myplugin_update(){
	global $wpdb;
	global $wp;
		
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	$drop = "DROP TABLE " . $wpdb->base_prefix . "ucf_devops_main";
	print "<script>alert('" . $drop . "');</script>";
//	dbDelta( $drop );
//	$wpdb->flush();
	
	$drop = "DROP TABLE " . $wpdb->base_prefix . "ucf_devops_setup";
	print "<script>alert('" . $drop . "');</script>";
//	dbDelta( $drop );
//	$wpdb->flush();
	


}
add_action('upgrader_process_complete', 'myplugin_update');

require_once( plugin_dir_path( __FILE__ ) . 'includes/admin_menu.php');

require_once( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php');

function ucf_devops_rest_header() {
echo '
<style>
th  { cursor: pointer; }
</style>
<script>
function eav_sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("myTable");
  switching = true;
  //Set the sorting direction to ascending:
  dir = "asc"; 
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
    //start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /*Loop through all table rows (except the
    first, which contains table headers):*/
    for (i = 1; i < (rows.length - 1); i++) {
      //start by saying there should be no switching:
      shouldSwitch = false;
      /*Get the two elements you want to compare,
      one from current row and one from the next:*/
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /*check if the two rows should switch place,
      based on the direction, asc or desc:*/
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch= true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /*If a switch has been marked, make the switch
      and mark that a switch has been done:*/
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      //Each time a switch is done, increase this count by 1:
      switchcount ++;      
    } else {
      /*If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again.*/
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
</script>
';
}
add_action( 'wp_enqueue_script', function() {
			wp_enqueue_script(
				'ucf-charts-data-tables', // Handle
				'https://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.10.2min.js', // JS URL of the data tables plugin
				array( 'jquery' ),  // Dependencies
				'1.10.2', // Version
				true      // Load in footer
			);
			
			wp_enqueue_script(
				'ucf-charts-data-tables', // Handle
				'https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', // JS URL of the data tables plugin
				array( 'jquery' ),  // Dependencies
				'1.10.2', // Version
				true      // Load in footer
			);
			
			wp_enqueue_script(
				'ucf-charts-data-tables', // Handle
				'https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js', // JS URL of the data tables plugin
				array( 'jquery' ),  // Dependencies
				'1.10.2', // Version
				true      // Load in footer
			);

			
			wp_enqueue_style( 'popup-css', get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/popup.css', 
			array( 'jquery' ), '2.0', true);	
			
			wp_enqueue_style( 'timelinegraph-css', get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/timelinegraph.css' , 
			array( 'jquery' ), '2.0', true);
				
			wp_enqueue_script( 'popup-js', 	get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/js/popup.js' , false, '2.0', true);

} );

?>
