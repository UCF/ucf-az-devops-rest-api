<?php

Function ucf_devops_rest_main_page(){
	global $wpdb;
	global $wp;

	echo "Welcome to Information Page";

}

add_action( 'admin_menu', 'ucf_devops_rest_add_info_menu' );  
Function ucf_devops_rest_add_info_menu(){


    $page_title = 'Credits and Info';
	$menu_title = "DevOps/Wordpress Config";
	$capability = 'manage_options';
	$menu_slug  = 'ucf_devops_rest_main_menu';
	$function   = 'ucf_devops_rest_main_page';
	$icon_url   = 'dashicons-media-code';
	$position   = 4;
	
	add_menu_page( $page_title,$menu_title,	$capability,$menu_slug,	$function,$icon_url,$position );
	
	$submenu1_slug = 'ucf_devops_rest_manage';
    add_submenu_page( $menu_slug, 'Manage Setup', 'Manage Setup'
		, 'manage_options' , $submenu1_slug , $submenu1_slug);

}

Function ucf_devops_rest_manage(){
	global $wpdb;
	global $wp;
	

	$skip_devops_settings_form = 0;
	// get current URL 
	//$current_url =  home_url(add_query_arg(array($_GET), $wp->request));
	$current_url =  "admin.php?page=" . $_GET['page'];
	$default_tab = "DevOpsSettings";
	$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;  
	// handle Posts first
	//print ("<PRE>");
	//print "url = $current_url";
	//print_r($_POST);
	//print ("</PRE>");
	if (isset($_POST['addsettings'])){
		$tab = $_POST['tab'];
		//if we are in a post then we can do an sql insert and then pull it down below
		$sql_max="select max(entry_index) + 1 as max_val from " . $wpdb->base_prefix  . "ucf_devops_setup";
		$ucf_devops_max = $wpdb->get_row($sql_max);
		if (isset($ucf_devops_max->max_val))
			$max_val = $ucf_devops_max->max_val;
		else
			$max_val = 1;
		
		$ucf_devops_pat_token = sanitize_text_field($_POST['pat_token']);
		//$ucf_devops_pat_expire = strtolower(sanitize_text_field($_POST['pat_expire']);
		$ucf_devops_description = sanitize_text_field($_POST['description']);
		$ucf_devops_organization = sanitize_text_field($_POST['organization']);
		$ucf_devops_project = sanitize_text_field($_POST['project']);

		$sql_insert = sprintf("INSERT INTO " . $wpdb->base_prefix . "ucf_devops_setup (entry_index,pat_token," . 
				"description,organization,project" .
				") values (%d, '%s', '%s', '%s', '%s')",
				$max_val,$ucf_devops_pat_token ,$ucf_devops_description ,$ucf_devops_organization ,
				$ucf_devops_project);
		
		$return = $wpdb->query($sql_insert  );
		if ($return == false) {
				echo "<P>Insert into ucf_devops_setup failed: " . ' - wpdb->last_error : ' . esc_html($wpdb->last_error);
				echo "<P>SQL:<P>";
				echo esc_html($sql_insert);
				
		}
		$wpdb->flush();
	} else if (isset($_POST['upddevops'])){ // update  entry
		$tablid =  sanitize_text_field($_GET['entity']);
		
		$ucf_devops_pat_token = sanitize_text_field($_POST['pat_token']);
		//$ucf_devops_pat_expire = strtolower(sanitize_text_field($_POST['pat_expire']));
		$ucf_devops_description = sanitize_text_field($_POST['description']);
		$ucf_devops_organization = sanitize_text_field($_POST['organization']);
		$ucf_devops_project = sanitize_text_field($_POST['project']);
		$ucf_debug_flag = sanitize_text_field($_POST['debug']);
		
		$sql = sprintf("update " . $wpdb->base_prefix . "ucf_devops_setup " . 
			"set pat_token='%s', description='%s',organization='%s'," .
			"project='%s', debugflag='%s' " .
			"where entry_index=%d", $ucf_devops_pat_token, $ucf_devops_description, $ucf_devops_organization, 
			$ucf_devops_project, $ucf_debug_flag, $tablid);

		$return = $wpdb->query($sql );
		$wpdb->show_errors();
		$wpdb->flush();
	} else if (isset($_POST['deldevops'])){ // delete entry
		$tablid =  sanitize_text_field($_GET['entity']);
		$sql_del = "delete from " . $wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $tablid;
		$return = $wpdb->query($sql_del  );
		if ($return == false) {
				echo "<P>Delete into ucf_devops_setup failed: " . ' - wpdb->last_error : ' . esc_html($wpdb->last_error);
				echo "<P>SQL:<P>";
				echo esc_html($sql_del);
				
		}

		$sql_del = "delete from " . $wpdb->base_prefix . "ucf_devops_main where entry_index = " . $tablid;
		$return = $wpdb->query($sql_del  );
		
	} else if (isset($_POST['testdevops'])){ // test entry
		$skip_devops_settings_form = 1;
		print '<h3 class="nav-tab-wrapper"> ';    
		print '<a class="nav-tab nav-tab-active" href="' . $current_url . '&tab=DevOpsSettings">DevOps Settings</a> ';  
		print '<a class="nav-tab" href="' . $current_url . '&tab=WiqlSettings">Wiql Settings</a> ';
		print '<a class="nav-tab" href="' . $current_url . '&tab=QueryIDSettings">Query Settings</a> ';		
		print '</h3>';
		$ucf_devops_pat_token = sanitize_text_field($_POST['pat_token']);
		//$ucf_devops_pat_expire = strtolower(sanitize_text_field($_POST['pat_expire']));
		$ucf_devops_description = sanitize_text_field($_POST['description']);
		$ucf_devops_organization = sanitize_text_field($_POST['organization']);
		$ucf_devops_project = sanitize_text_field($_POST['project']);
	
		$url = "https://dev.azure.com/" . $ucf_devops_organization . "/_apis/projects?api-version=6.0";
	
		$curl = curl_init();
		
	
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($curl, CURLOPT_USERPWD, ':' . $ucf_devops_pat_token );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		$data = curl_exec($curl);
		curl_close($curl);
	
	
		$myjson  = json_decode($data , false );
		
		//print "<PRE>";
		//print_r($myjson);
		//print "</PRE>";

	} else if ( isset($_POST['addwiql'])){
		$tab = $_POST['tab'];
		//if we are in a post then we can do an sql insert and then pull it down below
		$sql_max="select max(wiql_index) + 1 as max_val from " . $wpdb->base_prefix  . "ucf_devops_main";
		$ucf_devops_max = $wpdb->get_row($sql_max);
		if (isset($ucf_devops_max->max_val))
			$max_val = $ucf_devops_max->max_val;
		else
			$max_val = 1;
		
		$ucf_devops_entry_index = sanitize_text_field($_POST['DevOps_ID']);
		$ucf_devops_wiql = sanitize_text_field($_POST['wiql']);
		$ucf_devops_fields_to_query = sanitize_text_field($_POST['fields_to_query']);
		$ucf_devops_header_fields = sanitize_text_field($_POST['header_fields']);
		$ucf_devops_field_style = sanitize_text_field($_POST['field_style']);
		$ucf_devops_char_count = sanitize_text_field($_POST['char_count']);
		
		$sql_insert = sprintf("INSERT INTO " . $wpdb->base_prefix . "ucf_devops_main (wiql_index, entry_index," . 
			"wiql,fields_to_query,header_fields,field_style,char_count" .
			") values (%d, %d, '%s', '%s', '%s', '%s', '%s')",
			$max_val,$ucf_devops_entry_index, 
			$ucf_devops_wiql,$ucf_devops_fields_to_query,$ucf_devops_header_fields ,
			$ucf_devops_field_style ,$ucf_devops_char_count);	
		$return = $wpdb->query($sql_insert  );
		if ($return == false) {
				echo "<P>Insert into ucf_devops_main failed: " . ' - wpdb->last_error : ' . esc_html($wpdb->last_error);
				echo "<P>SQL:<P>";
				echo esc_html($sql_insert);				
		}
		$wpdb->flush();
	} else if (isset($_POST['updqueryfields'])) {
		$ucf_xaxis_field_ID = sanitize_text_field($_POST['xaxis_field_ID']);
		//not used anymore $ucf_yaxis_field_ID  = sanitize_text_field($_POST['yaxis_field_ID']);
		$ucf_queryid  = sanitize_text_field($_POST['queryid']);
		$ucf_wiql_id_index  = sanitize_text_field($_POST['wiql_id_index']);
		
		$sql = sprintf("update " . $wpdb->base_prefix . "ucf_devops_query " . 
			"set xaxis_field='%s'" .
			"where queryid='%s' and wiql_id_index='%s' ", $ucf_xaxis_field_ID, $ucf_queryid, $ucf_wiql_id_index);
		$return = $wpdb->query($sql);
		if ($return == false) {
				echo "<P>Update into ucf_devops_query failed: " . ' - wpdb->last_error : ' . esc_html($wpdb->last_error);
				echo "<P>SQL:<P>";
				echo esc_html($sql);				
		}
		$wpdb->flush();		
		
		
	} else if (isset($_GET['queryid'])) { // update fields on query id
		$queryid =  sanitize_text_field($_GET['queryid']);
		$wiql_id_index =  sanitize_text_field($_GET['wiql_id_index']);
		$entry_index = sanitize_text_field($_GET['entry_index']);
		$skip_devops_settings_form = 1;	


		$sql_setup = "select entry_index,pat_token," . 
			"description,organization,project from " . 
		$wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $entry_index;
		$wp_devops_setup = $wpdb->get_row($sql_setup);
		if ($wp_devops_setup == false) {
			$wpdb->show_errors();
			$wpdb->flush();
		}
		$Organization = str_replace(" ", "%20", $wp_devops_setup->organization);
		$Project = str_replace(" ", "%20", $wp_devops_setup->project); 
		$ucf_devops_pat_token = $wp_devops_setup->pat_token;
		
		#print "<PRE>";
		#print_r($_POST);
		#print "</PRE>";
		
		
		$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/wiql/" .  $queryid  ."?api-version=5.1";
		$curl = curl_init();
		
	
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($curl, CURLOPT_USERPWD, ':' . $ucf_devops_pat_token );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		$data = curl_exec($curl);
		curl_close($curl);
	
	
		$myjson  = json_decode($data , false );
		
		$ucf_columns = $myjson->{'columns'};
		$ucf_count = count($ucf_columns);
		
		#print "<PRE>ucf_columnsf:";
		#print_r($ucf_columns);
		#print "</PRE>";		


		$sql = "select wiql_id_index,entry_index,queryid,xaxis_field,yaxis_field from " . $wpdb->base_prefix . "ucf_devops_query where queryid = '" . $queryid . "' and wiql_id_index = " . $wiql_id_index ;
		$eav_tblinfo = $wpdb->get_row($sql);
		
		#print "<PRE>sql reuturn of:" . $sql ;
		#print_r($eav_tblinfo);
		#print "</PRE>";		
		
		echo '<form action="" method="post">';
		echo '<table>';
		ECHO '<tr><td><label for="seachlabel">X-Axis:</label></td><td>';
		
		print '<select name="xaxis_field ID" id="xaxis_field">';
		for($i=0; $i < $ucf_count ; $i++)  {
			print'<option value="' . esc_html($ucf_columns[$i]->referenceName) . '">' . esc_html($ucf_columns[$i]->referenceName) . '</option>';
		}	
		print '</select>';
		echo '</td></tr><p>';
		
#		ECHO '<tr><td><label for="seachlabel">Y-Axis:</label></td><td>';
#		print '<select name="yaxis_field ID" id="yaxis_field">';
#		for($i=0; $i < $ucf_count ; $i++)  {
#			print'<option value="' . esc_html($ucf_columns[$i]->referenceName) . '">' . esc_html($ucf_columns[$i]->referenceName) . '</option>';
#		}	
#		print '</select>';
#		echo '</td></tr><p>';
	
		print '</table>';

		echo '<input type="hidden" id="queryname" name="queryname" value ="' . esc_html($queryid) . '">';
		echo '<input type="hidden" id="queryid" name="queryid" value ="' . esc_html($queryid) . '">';
		echo '<input type="hidden" id="wiql_id_index" name="wiql_id_index" value ="' . esc_html($wiql_id_index) . '">';
		echo '<br>';		
		echo '<input type="submit" value="Update Query Fields" name="updqueryfields" >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" value="Delete Query Field" name="delqueryfields" >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" value="Test DevOps" name="testqueryfields"  >';
		echo '<br> </form>';
	
	
	} else if (isset($_POST['updwiql'])){ // update entry
		$tablid =  sanitize_text_field($_POST['entity']);
		
		$ucf_devops_wiql = sanitize_text_field($_POST['wiql']);
		$ucf_devops_fields_to_query = sanitize_text_field($_POST['fields_to_query']);
		$ucf_devops_header_fields = sanitize_text_field($_POST['header_fields']);
		$ucf_devops_field_style = sanitize_text_field($_POST['field_style']);
		$ucf_devops_char_count = sanitize_text_field($_POST['char_count']);
		
		
		$sql = sprintf("update " . $wpdb->base_prefix . "ucf_devops_main " . 
			"set wiql='%s', fields_to_query='%s',header_fields='%s'," .
			"field_style='%s', char_count='%s'" .
			"where wiql_index=%d", $ucf_devops_wiql, $ucf_devops_fields_to_query, $ucf_devops_header_fields, 
			$ucf_devops_field_style, $ucf_devops_char_count, $tablid);

		$return = $wpdb->query($sql );
		$wpdb->show_errors();
		$wpdb->flush();
	} else if (isset($_POST['addquery'])){ // addquery entry	
		$queryid = $_POST['queryid'];
		
		//print "<PRE>";
		//print_r($_POST);
		//print "</PRE>";
		
		//if we are in a post then we can do an sql insert and then pull it down below
		$sql_max="select max(wiql_id_index) + 1 as max_val from " . $wpdb->base_prefix  . "ucf_devops_query";
		$ucf_devops_max = $wpdb->get_row($sql_max);
		if (isset($ucf_devops_max->max_val))
			$max_val = $ucf_devops_max->max_val;
		else
			$max_val = 1;
		
				
		$ucf_queryid = sanitize_text_field($_POST['queryid']);
		$ucf_queryname = sanitize_text_field($_POST['queryname']);
		$ucf_devops_entry_index = sanitize_text_field($_POST['entry_index']);

		
		$sql_insert = sprintf("INSERT INTO " . $wpdb->base_prefix . "ucf_devops_query (wiql_id_index, entry_index,queryid,queryname, xaxis_field,yaxis_field) values (%d, %d, '%s', '%s', ' ', ' ')",
			$max_val,$ucf_devops_entry_index, $ucf_queryid,$ucf_queryname );
		
		$return = $wpdb->query($sql_insert  );
		if ($return == false) {
				echo "<P>Insert into ucf_devops_query failed: " . ' - wpdb->last_error : ' . esc_html($wpdb->last_error);
				echo "<P>SQL:<P>";
				echo esc_html($sql_insert);
				
		}
		$wpdb->flush();
		
	} else if (isset($_POST['findquery'])){ // findquery entry	
		$tab = $_POST['tab'];
		$entry_index = sanitize_text_field($_POST['DevOps_ID']);
		
		$sql_setup = "select entry_index,pat_token," . 
			"description,organization,project from " . 
			$wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $entry_index;
		$wp_devops_setup = $wpdb->get_row($sql_setup);
		if ($wp_devops_setup == false) {
			$wpdb->show_errors();
			$wpdb->flush();
		}
		$Organization = str_replace(" ", "%20", $wp_devops_setup->organization);
		$Project = str_replace(" ", "%20", $wp_devops_setup->project); 
		$PAT = $wp_devops_setup->pat_token;
		$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/queries?\$depth=2&api-version=6.0";
	
		$curl = curl_init();
	
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		$data = curl_exec($curl);
		curl_close($curl);
	
		//print "<PRE>";
		$myjson  = json_decode($data , false );
		print '<style>
			table, th, td {
				border:1px solid black;
			}
		</style>';
		echo "<H3>Select query from the list below</H3><P>";
		echo '<form action="#" method="post" id="qsubmit" >';
		print '<table id="querylisttable" >';
		$q_s1 = $myjson->{'count'};
		$q1_array = $myjson->{'value'};
		$q1_array_size = count($q1_array);
		$row_hold = 1;
		for ($q_c1 = 0; $q_c1 < $q1_array_size; $q_c1++) {
			$l1 = $q1_array[$q_c1];
			//print_r($l1);

			$l2 = $l1->{'children'};
			$l2_size = count($l2);
			//print_r($l2);
			for ($q_c2 = 0; $q_c2 < $l2_size; $q_c2++) {
				$l3 = $l2[$q_c2];
				//$l3_size = count($l3);
				//print("\n\ndata is:");
				//print_r($l3);
				$l3_name = $l3->{'name'};
				$l3_id = $l3->{'id'};
				$l3_createddate = $l3->{'createdDate'};
				print '<tr >';
				print "<td>" . $l3_id . "</td><td>" . $l3_name . "</td><td>" . $l3_createddate . "</td>";
				print "</tr>\n";
				$row_hold = $row_hold + 1;
			}
		}
		print("</table><P>\n");	
		echo '<label>Query Id:</label>&nbsp;&nbsp;&nbsp;';
		print '<input type="hidden" id="entry_index" name="entry_index"  value ="' . esc_html($entry_index) . '">';
		print '<input type="text" id="queryid" name="queryid" size="50" value ="">';
		print '<P>';
		echo '<label>Query Name:</label>&nbsp;&nbsp;&nbsp;';
		print '<input type="text" id="queryname" name="queryname" size="60" value ="">';
		echo '<P><input type="submit" value="Add Query" name="addquery" onclick="submitquery()">';
		echo '<br> </form>';
		print '<script type="text/javascript" charset="utf8" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>';

		print '
<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>';

	print "
<script>
function submitquery() {

	document.qsubmit.submit();
}
$('#querylisttable tr').click(function(){
   $(this).addClass('selected').siblings().removeClass('selected');    
   var value=$(this).find('td:first').html(); 
   var value2=$(this).find('td:nth-child(2)').html(); 
   var container = document.getElementById('queryid');
   var container2 = document.getElementById('queryname');
   container.value = value;
   container2.value = value2;

});

$('.ok').on('click', function(e){
    alert($('#querylisttable tr.selected td:first').html());
});
</script>
";
	
	} else if (isset($_POST['delwiql'])){ // update entry

		$tablid =  sanitize_text_field($_POST['entity']);
		$sql_del = "delete from " . $wpdb->base_prefix . "ucf_devops_main where wiql_index = " . $tablid;
		$return = $wpdb->query($sql_del  );

	} else if(isset($_GET['entity'])) {
		$tablid =  sanitize_text_field($_GET['entity']);
		$skip_devops_settings_form = 1;		
		print '<h3 class="nav-tab-wrapper"> ';    
		print '<a class="nav-tab nav-tab-active" href="' . $current_url . '&tab=DevOpsSettings">DevOps Settings</a> ';  
		print '<a class="nav-tab" href="' . $current_url . '&tab=WiqlSettings">Wiql Settings</a> ';
		print '<a class="nav-tab" href="' . $current_url . '&tab=QueryIDSettings">Query Settings</a> ';		
		print '</h3>'; 

		$sql = "select entry_index,pat_token," . 
				"description,organization,project,debugflag from " . $wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $tablid ;
		$eav_tblinfo = $wpdb->get_row($sql);
		
		echo '<form action="" method="post">';
		echo '<table>';
		ECHO '<tr><td><label for="seachlabel">Description:</label></td><td>';
		echo '<input type="text" id="description" name="description" size="100" value="' . esc_html($eav_tblinfo->description) . '"></td></tr><p>';
		
		ECHO '<tr><td><label for="seachlabel">PAT Token:</label></td><td>';
		echo '<input type="text" id="pat_token" name="pat_token" size="100" value="' . esc_html($eav_tblinfo->pat_token) . '"></td></tr><p>';
		
		echo '<tr><td><label for="seachlabel">Organization:</label></td><td>';
		echo '<input type="text" id="organization" name="organization" size="100" value="' . esc_html($eav_tblinfo->organization) . '"></td></tr><p>';
		
		echo '<tr><td><label for="seachlabel">Project:</label></td><td>';
		echo '<input type="text" id="project" name="project" cols="100" value="' . esc_html($eav_tblinfo->project) . '"></td></tr><p>';
		
		if (trim(esc_html($eav_tblinfo->debugflag)) == '') 
			$dbflg = "(blank)";
		else
			$dbflg = esc_html($eav_tblinfo->debugflag);
		
		echo '<tr><td><label for="seachlabel">Debug (Current:' . $dbflg . '):</label></td><td>';
		echo '<select name="debug" id="debug"> ';
			echo '<option value="N">N</option>';
			echo '<option value="Y">Y</option>';
		echo '</select>';
		
		print '</table>';

		
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($tablid) . '">';
		echo '<br>';		
		echo '<input type="submit" value="Update DevOps" name="upddevops" >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" value="Delete DevOps" name="deldevops" >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" value="Test DevOps" name="testdevops"  >';
		echo '<br> </form>';

	} else if (isset($_GET['wiql'])) {
		$tablid =  sanitize_text_field($_GET['wiql']);
		$skip_devops_settings_form = 1;		
		print '<h3 class="nav-tab-wrapper"> ';    
		print '<a class="nav-tab " href="' . $current_url . '&tab=DevOpsSettings">DevOps Settings</a> ';  
		print '<a class="nav-tab nav-tab-active" href="' . $current_url . '&tab=WiqlSettings">Wiql Settings</a> ';
		print '<a class="nav-tab" href="' . $current_url . '&tab=QueryIDSettings">Query Settings</a> ';		
		print '</h3>'; 

		$sql = "select entry_index,wiql_index," . 
				"wiql,fields_to_query,header_fields,field_style,char_count from " . $wpdb->base_prefix . "ucf_devops_main where wiql_index = " . $tablid ;
		$eav_tblinfo = $wpdb->get_row($sql);
		
		echo '<form action="" method="post">';
		echo '<table>';
		print '<tr><td><label for="seachlabel">Wiql:</label></td>';
		print '<td><textarea type="text" id="wiql" cols="100" name="wiql" rows="4" >' . esc_html($eav_tblinfo->wiql) . '</textarea></td></tr><p>';
		
		print '<tr><td><label for="seachlabel">Fields to Query:</label></td>';
		print '<td><textarea type="text" id="fields_to_query" name="fields_to_query" rows="4" cols="100" >' . esc_html($eav_tblinfo->fields_to_query) . '</textarea></td></tr><p>';
		
		print '<tr><td><label for="seachlabel">Header Fields:</label></td>';
		print '<td><textarea type="text" id="header_fields" name="header_fields" rows="4" cols="100" >' . esc_html($eav_tblinfo->header_fields) . '</textarea></td></tr><p>';
		
		print '<tr><td><label for="seachlabel">Field Style:</label></td>';
		print '<td><textarea type="text" id="field_style" name="field_style" rows="4" cols="100" >' . esc_html($eav_tblinfo->field_style) . '</textarea></td></tr><p>';
		
		print '<tr><td><label for="seachlabel">Char Count:</label></td>';
		print '<td><textarea type="text" id="char_count" name="char_count" rows="4" cols="100" >' . esc_html($eav_tblinfo->char_count) . '</textarea></td></tr><p>';

		print '</table>';

		
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($tablid) . '">';
		echo '<br>';		
		echo '<input type="submit" value="Update Wiql" name="updwiql" >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" value="Delete Wiql" name="delwiql" >';
		echo '<br> </form>';
		$skip_devops_settings_form = 1;	
	}
	
	//print "<PRE>TAB:" . $tab . "</PRE>";


	if ($tab == "DevOpsSettings") {
		if ($skip_devops_settings_form  == 0) {
			print '<h3 class="nav-tab-wrapper"> ';    
			print '<a class="nav-tab nav-tab-active" href="' . esc_html($current_url) . '&tab=DevOpsSettings">DevOps Settings</a> ';  
			print '<a class="nav-tab" href="' . esc_html($current_url) . '&tab=WiqlSettings">Wiql Settings</a> ';
			print '<a class="nav-tab" href="' . esc_html($current_url) . '&tab=QueryIDSettings">Query Settings</a> ';		
			print '</h3>'; 
			echo '<form action="" method="post">
			<table>
			<tr><td><label for="seachlabel">Description:</label></td><td><input type="text" id="description" name="description" size="100" ></td></tr><p>
			<tr><td><label for="seachlabel">PAT Token:</label></td><td><input type="text" id="pat_token" name="pat_token" size="100" ></td></tr><p>
			<tr><td><label for="seachlabel">PAT Exipre:</label></td><td><input type="text" id="pat_expire" name="pat_expire" size="100" ></td></tr><p>
			<tr><td><label for="seachlabel">Organization:</label></td><td><input type="text" id="organization" name="organization" size="100" ></td></tr><p>
			<tr><td><label for="seachlabel">Project:</label></td><td><input type="text" id="project" name="project" size="100" ></td></tr>
			</table>
			<P>
			<input type="submit" value="Add Settings" name="addsettings">';
			echo '<input type="hidden" id="tab" name="tab" value ="' . esc_html($tab) . '">';
			print '<br> </form>';
		} else {
			print "<P>&nbsp;<P>";
		}
		
		// Next up is to show what values we currently have

		$sql = "select a.entry_index, a.description, a.organization , a.project from " . $wpdb->base_prefix . "ucf_devops_setup a" ;
		//echo '<table style="width: 50%; border: 1px solid black" id="myTable" >';
		echo '<table style="width: 50%; border-collapse: collapse;" id="myTable" >';
		echo '<tr >
		<th style="width: 10px; border: 1px solid black; ">Entry ID</th>
		<th style="width: 100px; border: 1px solid black; ">Description</th>
		<th style="width: 40px; border: 1px solid black; ">Organization</th>
		<th style="width: 30px; border: 1px solid black; ">Project</th>
		</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 
		foreach($results as $element) {
			echo '<tr>';
			echo '<td style="border: 1px solid black; padding: 0px;">';
			//note that the functional name is now in the URL below
			echo '<a href="?page=ucf_devops_rest_manage&entity=' . esc_html($element->entry_index) . '">';
			echo esc_html($element->entry_index) . '</a></td>';
			echo '<td style="border: 1px solid black; padding: 0px;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&entity=' . esc_html($element->entry_index) . '">';
			echo esc_html($element->description) . '</td>';
			echo '<td style="border: 1px solid black; padding: 0px;">';
			echo '<a href="?page=ucf_devops_rest_manage&entity=' . esc_html($element->entry_index) . '">';
			echo esc_html($element->organization) . '</td>';
			echo '<td style="border: 1px solid black; padding: 0px;">';
			echo '<a href="?page=ucf_devops_rest_manage&entity=' . esc_html($element->entry_index) . '">';
			echo esc_html($element->project) . '</td>';
			echo '</tr>';
			$row_count = $row_count + 1;
		}
	} else if ($tab == "WiqlSettings") {
		if ($skip_devops_settings_form  == 0) {
			print "<style>textarea { resize: both ; }</style>";
			print '<h3 class="nav-tab-wrapper"> ';    
			print '<a class="nav-tab " href="' . esc_html($current_url) . '&tab=DevOpsSettings">DevOps Settings</a> ';  
			print '<a class="nav-tab nav-tab-active" href="' . esc_html($current_url) . '&tab=WiqlSettings">Wiql Settings</a> ';  
			print '<a class="nav-tab" href="' . esc_html($current_url) . '&tab=QueryIDSettings">Query Settings</a> ';	
			print '</h3>'; 
			echo '<form action="" method="post">
			<P>
			<select name="DevOps ID" id="devopsid">';
			$sql_setup = "select a.entry_index, a.description from " . $wpdb->base_prefix . "ucf_devops_setup a" ;
			$results = $wpdb->get_results($sql_setup);
			$row_count = 1; 
			foreach($results as $element) {
				print'<option value="' . esc_html($element->entry_index) . '">' . esc_html($element->description) . '</option>';
			}
			print '</select>
			<table>
			<tr><td><label for="seachlabel">Wiql:</label></td><td><textarea type="text" id="wiql" cols="100" name="wiql" rows="4" cols></textarea></td></tr><p>
			<tr><td><label for="seachlabel">Fields to Query:</label></td><td><textarea type="text" id="fields_to_query" name="fields_to_query" cols="100" ></textarea></td></tr><p>
			<tr><td><label for="seachlabel">Header Fields:</label></td><td><textarea type="text" id="header_fields" name="header_fields" cols="100" ></textarea></td></tr><p>
			<tr><td><label for="seachlabel">Field Style:</label></td><td><textarea type="text" id="field_style" name="field_style" cols="100" ></textarea></td></tr><p>
			<tr><td><label for="seachlabel">Char Count:</label></td><td><textarea type="text" id="char_count" name="char_count" cols="100" ></textarea></td></tr><p>
			</table>
			<P>
			<input type="submit" value="Add Wiql" name="addwiql">';
			echo '<input type="hidden" id="tab" name="tab" value ="' . esc_html($tab) . '">';
			print '<br> </form>';
		} else {
			print "<P>&nbsp;<P>";
		}
		// Next up is to show what values we currently have
		$sql = "select entry_index,wiql_index," . 
				"wiql,fields_to_query,header_fields,field_style,char_count from " . $wpdb->base_prefix . "ucf_devops_main" .
				" where length(fields_to_query) <> 0";
		echo '<table style="width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
		<th style="width: 150px; border: 1px solid black;" >ShortCode</th>
		<th style="width: 10px; border: 1px solid black;" >WiQL ID</th>
		<th style="width: 10px; border: 1px solid black;" >Entry ID</th>		
		<th style="width: 30px; border: 1px solid black;" >Wiql</th>
		<th style="width: 30px; border: 1px solid black;" >Fields to Query</th>
		<th style="width: 30px; border: 1px solid black;" >Header Fields</th>
		</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 
		foreach($results as $element) {
			echo '<tr>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '[wp_devops_wiql record="' . esc_html($element->wiql_index) . '"]</td>';
			
			
			echo '<td style="width: 10px; border: 1px solid black;" >';
			//note that the functional name is now in the URL below
			echo '<a href="?page=ucf_devops_rest_manage&wiql=' . esc_html($element->wiql_index) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->wiql_index) . '</a></td>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&wiql=' . esc_html($element->wiql_index) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->entry_index) . '</a></td>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&wiql=' . esc_html($element->wiql_index) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->wiql) . '</td>';
			
			echo '<td style="width: 40px; border: 1px solid black;">';
			echo '<a href="?page=ucf_devops_rest_manage&wiql=' . esc_html($element->wiql_index) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->fields_to_query) . '</td>';
			
			echo '<td style="width: 30px; border: 1px solid black;">';
			echo '<a href="?page=ucf_devops_rest_manage&wiql=' . esc_html($element->wiql_index) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->header_fields) . '</td>';
			
			echo '</tr>';
			$row_count = $row_count + 1;
		}
	}
	else { //Query Tab 
		print '<h3 class="nav-tab-wrapper"> ';    
		print '<a class="nav-tab " href="' . esc_html($current_url) . '&tab=DevOpsSettings">DevOps Settings</a> ';  
		print '<a class="nav-tab " href="' . esc_html($current_url) . '&tab=WiqlSettings">Wiql Settings</a> ';  
		print '<a class="nav-tab nav-tab-active" href="' . esc_html($current_url) . '&tab=QueryIDSettings">Query Settings</a> ';	
		print '</h3><P>'; 
				
		$sql = "select entry_index,wiql_id_index,queryid,queryname, xaxis_field,yaxis_field from " . $wpdb->base_prefix . "ucf_devops_query";
		echo '<table style="width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
		<th style="width: 60px; border: 1px solid black;" >ShortCode</th>
		<th style="width: 10px; border: 1px solid black;" >WiQL ID</th>
		<th style="width: 10px; border: 1px solid black;" >Entry ID</th>		
		<th style="width: 50px; border: 1px solid black;" >Query ID</th>
		<th style="width: 50px; border: 1px solid black;" >Query Name</th>
		<th style="width: 50px; border: 1px solid black;" >X-Axis Field</th>';
		#<th style="width: 50px; border: 1px solid black;" >Y-Axis Field</th>
		echo '</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 
		foreach($results as $element) {
			echo '<tr>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '[wp_devops_query wiql_id_index="' . esc_html($element->wiql_id_index) . '"]<br>';
			echo '[wp_devops_pretty_query wiql_id_index="' . esc_html($element->wiql_id_index) . '"]';
			echo '</td>';
			
			
			echo '<td style="width: 10px; border: 1px solid black;" >';
			//note that the functional name is now in the URL below
			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->wiql_id_index) . '</a></td>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->entry_index) . '</a></td>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->queryid) . '</td>';
			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->queryname) . '</td>';

			
			echo '<td style="width: 50px; border: 1px solid black;">' ;
			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . esc_html($tab)  . '">';
			echo esc_html($element->xaxis_field) . '</td>';
			
#			echo '<td style="width: 50px; border: 1px solid black;">' ;
#			echo '<a href="?page=ucf_devops_rest_manage&entry_index=' .  esc_html($element->entry_index)   .    '&wiql_id_index=' .  esc_html($element->wiql_id_index)   . '&queryid=' . esc_html($element->queryid) . "&tab=" . $tab  . '">';
#			echo esc_html($element->yaxis_field) . '</td>';

			echo '</tr>';
			$row_count = $row_count + 1;
		}
		print "</table><P>&nbsp;<P>";
		echo '<form action="" method="post">';
		print '<select name="DevOps ID" id="devopsid">';
		$sql_setup = "select a.entry_index, a.description from " . $wpdb->base_prefix . "ucf_devops_setup a" ;
		$results = $wpdb->get_results($sql_setup);
		$row_count = 1; 
		foreach($results as $element) {
			print'<option value="' . esc_html($element->entry_index) . '">' . esc_html($element->description) . '</option>';
		}	
		print '</select>';
		print '&nbsp;&nbsp<input type="submit" value="Find Query" name="findquery">';
		echo '<input type="hidden" id="tab" name="tab" value ="' . esc_html($tab) . '">';
		print '<br> </form>';
			
	}

}  

?>
