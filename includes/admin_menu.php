<?php

Function ucf_devops_rest_main_page(){
	global $wpdb;
	global $wp;
	
	ob_start(); // this allows me to use 1 echo at the end
	
	echo "Welcome to Setup Page";
	
	print("<PRE>wpdb:\n");
	print_r($wpdb);
	echo "</PRE>";
	
	print("<PRE>wp:\n");
	print_r($wp);
	echo "</PRE>";
	
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;

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
	
	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	
	ucf_devops_rest_header();

	//	entry_index		int,
	//	pat_token		varchar(128),	
	//	pat_expire		date,
	//	description		varchar(128),
	//	organization	varchar(128),
	//	project			varchar(128),
	//	wiql			text,
	//	fields_to_query	text,
	//	header_fields	text,
	//	field_style		text,
	//	char_count		text	
	
	//Fist thing is to all new data values to be entered into the system
	if (isset($_POST['addrecord'])){
		//if we are in a post then we can do an sql insert and then pull it down below
		$sql_max="select max(entry_index) + 1 as max_val from " . $wpdb->base_prefix  . "ucf_devops_main";
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
		$ucf_devops_wiql = sanitize_text_field($_POST['wiql']);
		$ucf_devops_fields_to_query = sanitize_text_field($_POST['fields_to_query']);
		$ucf_devops_header_fields = sanitize_text_field($_POST['header_fields']);
		$ucf_devops_field_style = sanitize_text_field($_POST['field_style']);
		$ucf_devops_char_count = sanitize_text_field($_POST['char_count']);
		$sql_insert = sprintf("INSERT INTO " . $wpdb->base_prefix . "ucf_devops_main (entry_index,pat_token," . 
				"description,organization,project,wiql,fields_to_query,header_fields,field_style,char_count" .
				") values (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				$max_val,$ucf_devops_pat_token ,$ucf_devops_description ,$ucf_devops_organization ,
				$ucf_devops_project,$ucf_devops_wiql,$ucf_devops_fields_to_query,$ucf_devops_header_fields ,
				$ucf_devops_field_style ,$ucf_devops_char_count);
				
				
		
		$return = $wpdb->query($sql_insert  );
		if ($return == false) {
				echo "<P>Insert into ucf_devops_main failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
				echo "<P>SQL:<P>";
				echo $sql_insert;
				
		}
		$wpdb->flush();
		
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
	
		echo '<form action="" method="post">S
		<table>
		<tr><td><label for="seachlabel">Description:</label></td><td><input type="text" id="description" name="description" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Token:</label></td><td><input type="text" id="pat_token" name="pat_token" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Exipre:</label></td><td><input type="text" id="pat_expire" name="pat_expire" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Organization:</label></td><td><input type="text" id="organization" name="organization" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Project:</label></td><td><input type="text" id="project" name="project" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Wiql:</label></td><td><textarea type="text" id="wiql" name="wiql" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Fields to Query:</label></td><td><textarea type="text" id="fields_to_query" name="fields_to_query" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Header Fields:</label></td><td><textarea type="text" id="header_fields" name="header_fields" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Field Style:</label></td><td><textarea type="text" id="field_style" name="field_style" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Char Count:</label></td><td><textarea type="text" id="char_count" name="char_count" cols="100" ></textarea></td></tr><p>
		</table>
			<input type="submit" value="addrecord" name="addrecord">
			<br> </form>';
	
	}else 	if (isset($_POST['updrecord'])){
		
		$tablid = sanitize_text_field($_POST['entity']);			
	
		$ucf_devops_pat_token = sanitize_text_field($_POST['pat_token']);
		//$ucf_devops_pat_expire = strtolower(sanitize_text_field($_POST['pat_expire']));
		$ucf_devops_description = sanitize_text_field($_POST['description']);
		$ucf_devops_organization = sanitize_text_field($_POST['organization']);
		$ucf_devops_project = sanitize_text_field($_POST['project']);
		$ucf_devops_wiql = sanitize_text_field($_POST['wiql']);
		$ucf_devops_fields_to_query = sanitize_text_field($_POST['fields_to_query']);
		$ucf_devops_header_fields = sanitize_text_field($_POST['header_fields']);
		$ucf_devops_field_style = sanitize_text_field($_POST['field_style']);
		$ucf_devops_char_count = sanitize_text_field($_POST['char_count']);
		
		$sql = sprintf("update " . $wpdb->base_prefix . "ucf_devops_main " . 
			"set pat_token='%s', description='%s',organization='%s'," .
			"project='%s', wiql='%s', fields_to_query='%s', header_fields='%s', char_count='%s', " .
			"field_style = '%s' " .
			"where entry_index=%d", $ucf_devops_pat_token, $ucf_devops_description, $ucf_devops_organization, 
			$ucf_devops_project, $ucf_devops_wiql, $ucf_devops_fields_to_query, $ucf_devops_header_fields,$ucf_devops_char_count,
			$ucf_devops_field_style,$tablid);

		$return = $wpdb->query($sql );
		$wpdb->show_errors();
		$wpdb->flush();
		
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
		
		echo '<form action="" method="post">
		<table>
		<tr><td><label for="seachlabel">Description:</label></td><td><input type="text" id="description" name="description" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Token:</label></td><td><input type="text" id="pat_token" name="pat_token" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Exipre:</label></td><td><input type="text" id="pat_expire" name="pat_expire" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Organization:</label></td><td><input type="text" id="organization" name="organization" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Project:</label></td><td><input type="text" id="project" name="project" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Wiql:</label></td><td><textarea type="text" id="wiql" name="wiql" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Fields to Query:</label></td><td><textarea type="text" id="fields_to_query" name="fields_to_query" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Header Fields:</label></td><td><textarea type="text" id="header_fields" name="header_fields" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Field Style:</label></td><td><textarea type="text" id="field_style" name="field_style" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Char Count:</label></td><td><textarea type="text" id="char_count" name="char_count" cols="100" ></textarea></td></tr><p>
		</table>
		<input type="submit" value="addrecord" name="addrecord">
		 <br> </form>';
	}else if(isset($_GET['entity'])) {
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Edit Record</h2></div>';
		
		$tablid =  sanitize_text_field($_GET['entity']);
		$sql = "select entry_index,pat_token," . 
				"description,organization,project,wiql,fields_to_query,header_fields,field_style,char_count from " . $wpdb->base_prefix . "ucf_devops_main where entry_index = " . $tablid;
		$eav_tblinfo = $wpdb->get_row($sql);
		echo "<br>allow editing of record<br>";
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

		echo '<tr><td><label for="seachlabel">Wiql:</label></td><td>';
		echo '<textarea type="text" id="wiql" name="wiql" cols="100">' . esc_html($eav_tblinfo->wiql) . '</textarea></td></tr><p>';

		echo '<tr><td><label for="seachlabel">Fields to Query:</label></td><td>';
		echo '<textarea type="text" id="fields_to_query" name="fields_to_query" cols="100" >' . esc_html($eav_tblinfo->fields_to_query) . '</textarea></td></tr><p>';

		echo '<tr><td><label for="seachlabel">Header Fields:</label></td><td>';
		echo '<textarea type="text" id="header_fields" name="header_fields" cols="100" >' . esc_html($eav_tblinfo->header_fields) . '</textarea></td></tr><p>';

		echo '<tr><td><label for="seachlabel">Field Style:</label></td><td>';
		echo '<textarea type="text" id="field_style" name="field_style" cols="100" >' . esc_html($eav_tblinfo->field_style) . '</textarea></td></tr><p>';

		echo '<tr><td><label for="seachlabel">Char Count:</label></td><td>';
		echo '<textarea type="text" id="char_count" name="char_count" cols="100" >' . esc_html($eav_tblinfo->char_count) . '</textarea></td></tr><p>
		</table>';

		
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($tablid) . '">';
		echo '<br>';		
		echo '<input type="submit" value="updrecord" name="updrecord" >';
		echo '<br> </form>';
	}else {	
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
		
		echo '<form action="" method="post">
		<table>
		<tr><td><label for="seachlabel">Description:</label></td><td><input type="text" id="description" name="description" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Token:</label></td><td><input type="text" id="pat_token" name="pat_token" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">PAT Exipre:</label></td><td><input type="text" id="pat_expire" name="pat_expire" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Organization:</label></td><td><input type="text" id="organization" name="organization" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Project:</label></td><td><input type="text" id="project" name="project" size="100" ></td></tr><p>
		<tr><td><label for="seachlabel">Wiql:</label></td><td><textarea type="text" id="wiql" name="wiql" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Fields to Query:</label></td><td><textarea type="text" id="fields_to_query" name="fields_to_query" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Header Fields:</label></td><td><textarea type="text" id="header_fields" name="header_fields" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Field Style:</label></td><td><textarea type="text" id="field_style" name="field_style" cols="100" ></textarea></td></tr><p>
		<tr><td><label for="seachlabel">Char Count:</label></td><td><textarea type="text" id="char_count" name="char_count" cols="100" ></textarea></td></tr><p>
		</table>
		<input type="submit" value="addrecord" name="addrecord">
		 <br> </form>';
	
	}
	
	
	
	// Next up is to show what values we currently have

	$sql = "select a.entry_index, a.description, a.organization , a.project from " . $wpdb->base_prefix . "ucf_devops_main a";
	echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
	echo '<tr >
		<th style="width:5%; border: 1px solid black"; onclick="eav_sortTable(0); cursor: wait">Entry ID</th>
		<th style="width:55%; border: 1px solid black"; onclick="eav_sortTable(1); cursor: progress">Description</th>
		<th style="width:20%; border: 1px solid black"; onclick="eav_sortTable(2); cursor: pointer">Organization</th>
		<th style="width:20%; border: 1px solid black"; onclick="eav_sortTable(3); cursor: pointer">Project</th>
		</tr>
	';
	
	$results = $wpdb->get_results($sql);
	$row_count = 1; 
	foreach($results as $element) {
		echo '<tr style="border: 1px solid black; vertical-align: top; padding: 0px;">';
           echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px; width:100px">';
		//note that the functional name is now in the URL below
		echo '<a href="?page=ucf_devops_rest_manage&entity=' . esc_html($element->entry_index) . '">';
		echo esc_html($element->entry_index) . '</a></td>';
		echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->description) . '</td>';
		echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->organization) . '</td>';
		echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->project) . '</td>';
		echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;"></td>';
		echo '</tr>';
           $row_count = $row_count + 1;
	}
	
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;
}	
?>
