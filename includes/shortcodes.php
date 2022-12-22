<?php

#
# okay this function basically does Wiql from devops based off of the settings
# and then uses jquery datatable to show the rows.
# output is strickly done via the wiql statement.
#
# one thing we need to do is to add some error handleing on the API calls which I don't have
#

# sorting function
$devops_sort_field = "";
$custom_sort_array = array();
$turn_on_debug = 1;


function devops_cmp($a, $b) {
	global $devops_sort_field;
	global $turn_on_debug;

	if ($turn_on_debug == 1) 
		print "<!-- Debug: devops_sort_field: " . $devops_sort_field . " -->\n";
	$flda = trim($a->{'fields'}->{$devops_sort_field});
	$fldb = trim($b->{'fields'}->{$devops_sort_field});
	if ($turn_on_debug == 1) 
		print "<!-- Debug: a: " . $flda . " -->\n";
    return strcmp($flda , $fldb);

}
function devops_custom_cmp($a, $b) {
	global $devops_sort_field;
	global $custom_sort_array;
	global $turn_on_debug;
	
	if ($turn_on_debug == 1) 
		print "<!-- Debug: devops_sort_field: " . $devops_sort_field . " -->\n";
	// need to make sure that this field has a value
	if(isset($a->{'fields'}->{$devops_sort_field})) {
		$flda = trim($a->{'fields'}->{$devops_sort_field});
		$va = array_search($flda,$custom_sort_array);
		if ($va === FALSE)
			$va = count($custom_sort_array) + 1;
	} else {
		$flda = "";
		$va = count($custom_sort_array) + 1;
	}
	if(isset($b->{'fields'}->{$devops_sort_field})) {
		$fldb = trim($b->{'fields'}->{$devops_sort_field});
		$vb = array_search($fldb,$custom_sort_array);
		if ($vb === FALSE)
				$vb = count($custom_sort_array) +1;
	} else {
		$fldb = "";
		$vb =  count($custom_sort_array) +1;
	}
	if ($turn_on_debug == 1) {
		print "<!-- Debug: a: " . $flda . " -- " . $va . " -->\n";
		print "<!-- Debug: b: " . $fldb . " -- " . $vb . " -->\n";
	}
	
	if ($va == $vb)  // sort on value - just incase there are multiple undefined
		return strcmp($flda, $fldb);
	else
		return ($va - $vb);

}

function wp_devops_wiql($atts = [], $content = null) {
	global $wpdb;
	global $devops_sort_field;
	global $custom_sort_array;
	global $turn_on_debug;
	 
	 
	ob_start(); // this allows me to use echo instead of using concat all strings


	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ';
	
	print("\n");

	$css_file = ABSPATH . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/popup.css';
	$css_open = fopen($css_file, "r");
	$css_data = fread($css_open, filesize($css_file));
	fclose($css_open);
	print( "<style>\n" . $css_data . "\n</style>\n");
	
	// Epic array to make sure we only print an Epic once
	$EpicArray = array();
	
	
	# first up is to get all the items to connect and the Wiql and fields
	$tablid = sanitize_text_field($atts['record']);
	$sql = "select entry_index, wiql,fields_to_query," .
		"header_fields,field_style,char_count from " . 
		$wpdb->base_prefix . "ucf_devops_main where wiql_index = " . $tablid;
	$wp_devops_return = $wpdb->get_row($sql);
	if ($wp_devops_return == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
		
	$entry_index = $wp_devops_return->entry_index;

	$sql_setup = "select entry_index,pat_token," . 
		"description,organization,project from " . 
		$wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $entry_index;
	$wp_devops_setup = $wpdb->get_row($sql_setup);
	if ($wp_devops_setup == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
	
	if(isset($atts['include']))
		$include = sanitize_text_field($atts['include']);
	else
		$include = "";
	
	if(isset($atts['sort']))
		$sort = sanitize_text_field($atts['sort']);
	else
		$sort = "";
	
	if ($turn_on_debug == 1) 
		print "<!-- debug: sort value is: " . $sort . "-->\n";

	//according to Jim Barnes remove for now - but I am still keeping it b/c it seems to work.
	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ' . "\n";
	
	
	$tableid = "table_" . rand();  //this allows my code be on the page more than once
	//$tableid = "table_1";
	
	$Organization = str_replace(" ", "%20", $wp_devops_setup->organization); //"UCF-Operations";  // this is the organization name from Azure DevOps - needs to be html escaped
	$Project = str_replace(" ", "%20", $wp_devops_setup->project); //"Workday%20Operations"; // this is the project name from Azure DevOps - needs to be html escaped
	$PAT= $wp_devops_setup->pat_token; //"gx6jvhpecqdaneevel7owmtr7yd5ja5p675t3hwnbamvmgkuyq7q"; // This is currently my Personal Access Token. This needs to be changed
	$Wiql = $wp_devops_return->wiql; 
	//'"Select [System.Id] from WorkItems where [Custom.DisplayonWebsite] = True and [System.State] = \"Done\" "';
	$FieldsToQuery = explode(",",$wp_devops_return->fields_to_query);
	$HeaderFields = explode(",",$wp_devops_return->header_fields);
	$FieldStyle = explode(",",$wp_devops_return->field_style);
	$CharCount = explode(",",$wp_devops_return->char_count);

//	$FieldsToQuery = array("Id", "System.Title", "Microsoft.VSTS.Common.Resolution","System.WorkItemType",  "Custom.UCFCategory", "Custom.ImpactedAudience", "Microsoft.VSTS.Common.Priority", "System.CreatedDate" ,"Microsoft.VSTS.Common.ClosedDate");
//	$HeaderFields = array("ID", "Title", "Resolution", "Type", "Category", "Impacted Audience", "Priority",  "Created Date", "Date Closed");
//	$FieldStyle = array("width:5%", "width:15%", "width:20%", "width:5%", "width:10%", "width:10%", "width:5%", "width:10%", "width:10%", "width:10%");
	// 0 = means no substrin
//	$CharCount = array(0,0,0,0,0,0, 0, 10, 10);


	$FieldArraySize = count($FieldsToQuery);

	
	// need to make sure that all our arrays don't have extra spaces
	for($x = 0; $x < $FieldArraySize; $x++){
		$FieldsToQuery[$x] = trim($FieldsToQuery[$x]);
		$HeaderFields[$x] = trim($HeaderFields[$x]);
		$FieldStyle[$x] = trim($FieldStyle[$x]);
		$CharCount[$x] = trim($CharCount[$x]);
		
	}	
	
	// This is the REST url for the Wiql API
	$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/wiql?api-version=6.0";
	
	$curl = curl_init(); // we use curl to get the results - easy
	
	$json_string = '{ "query": '. $Wiql . ' }';
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, TRUE);  // this is try because the curl call is a post.
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	
	if ($turn_on_debug == 1) {
		print "<!-- debug: \n";
		print "json_string is: " . $json_string . "\n";
		print "curl_exec returned: ";
		print_r($data);
		print "\n";
		print "-->\n";
	}
	
	$myjson  = json_decode($data , false );
		
	$workitems = (isset( $myjson->{'workItems'} ) ? $myjson->{'workItems'} : '') ;
	if ($workitems == '') {
		print "<PRE>ERROR\n";
		print "Wiql string is:" . $Wiql . "\n";
		print "Json return is:" ;
		print_r($myjson);
		print "</PRE>";
		return;
	}
	$sizeof = count($workitems);


	//print "<PRE>sizeof workitems: " . $sizeof . "</PRE>\n";

	$count_pipe = 0;
	//echo "sizeof:" . $sizeof;
	print "\n";
	print '<table id="' . $tableid . '" class="display" style="border-collapse: collapse; width: 100%; font-size: 12px;">' . "\n";
	print "    <thead>\n";
	print '        <tr style="background-color:#FFC409; border-bottom: 1px solid black;">' . "\n";
	print '<th>&nbsp;</th>';
	for($x = 0; $x < $FieldArraySize; $x++) {
		if ($HeaderFields[$x][0] != "|" ) {
			// if the first field is a vertical bar we will skip it for now b/c it will be handled later
			print "            <th style=\"". $FieldStyle[$x] . "\" >" . $HeaderFields[$x] . "</th>\n";
		} else {
			$col_span = 1;
			$count_pipe = $count_pipe + 1;
		}
	}
	print "        </tr>\n";
	print "    </thead>\n";
	print "    <tbody>\n";
	// so myjson has all the id's from the query.  We will loop through all the return id's
	
	$workitems_array = array();
	
	// okay we will collect all the items first, that way if we have a custom sort we are ready to handle that
	//
	for($x = 0; $x < $sizeof; $x++){
		$workitem_id = $workitems[$x]->{'id'}; // not used maybe delete?
		$workitem_url = $workitems[$x]->{'url'}; // this url didn't return all the info I was looking for
		$workitem_url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $workitem_id . '&$expand=all&api-version=6.0';
		
		// This gets all the data elements of the devops id that the query returned
		$item_url = $workitem_url;
		$curl_workitem = curl_init();
		curl_setopt($curl_workitem, CURLOPT_URL, $item_url);
		curl_setopt($curl_workitem, CURLOPT_POST, 0);  // this is a GET
		curl_setopt($curl_workitem, CURLOPT_USERPWD, ':' . $PAT);
		curl_setopt($curl_workitem, CURLOPT_RETURNTRANSFER, true );
		$item_data = curl_exec($curl_workitem);
	
		curl_close($curl_workitem);
		
		$item_decode  = json_decode($item_data , false );
		$item_json = $item_decode->{'value'}[0]; // trasverse json structure
		
		// we will do now is to see if this issue is tied to an Epic 
		// the array we will check is EpicArray
		$ParentID = (isset($item_json->{'fields'}->{'System.Parent'}) ? $item_json->{'fields'}->{'System.Parent'} : false) ;
		// so if ParentId is not false we need to grab it instead

		$saveid = $workitems[$x]->{'id'};
		if ($ParentID != false) {
			// we have a parent id, so we need to check our EpicArry and see if it is there
			// if it there, that means we have already shown it, and we can skip this one

			$srch = array_search($ParentID, $EpicArray);

			if ( $srch === FALSE ) {
				// not found, need to get it
				array_push($EpicArray, $ParentID);
				$epic_url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $ParentID . '&$expand=all&api-version=6.0';
				$curl_workitem = curl_init();
				curl_setopt($curl_workitem, CURLOPT_URL, $epic_url);
				curl_setopt($curl_workitem, CURLOPT_POST, 0);  // this is a GET
				curl_setopt($curl_workitem, CURLOPT_USERPWD, ':' . $PAT);
				curl_setopt($curl_workitem, CURLOPT_RETURNTRANSFER, true );
				$item_data = curl_exec($curl_workitem);
			
				curl_close($curl_workitem);
			
				$item_decode  = json_decode($item_data , false );
				$item_json = $item_decode->{'value'}[0]; // trasverse json structure
				array_push($workitems_array,  $item_json ); // replace item with Parent
			}
		} else {		
			array_push($workitems_array,  $item_json );
		}
	}
	
	// At this point we want to sort the object if the sort option is present
	if (strlen($sort) > 0) {
			if ($turn_on_debug == 1) 
				print("<!-- Debug sort: " . $sort . " -->\n");
			$has_question_mark = strpos($sort, "?");
			if ($has_question_mark != FALSE) {
				// means we have a custom sort
				$condit_array = explode("?",$sort);
				$devops_sort_field = $condit_array[0];  // assigns what field will be sorting on
				$custom_sort_array = explode(",", $condit_array[1]);
				$ca = count($custom_sort_array);
				for($xz=0; $xz < $ca; $xz++) 
					$custom_sort_array[$xz] = trim($custom_sort_array[$xz]);
				usort($workitems_array, "devops_custom_cmp");
			} else {
				$devops_sort_field = $sort; // just sort of the field, no custom order
				usort($workitems_array, "devops_cmp");
			}				
	}

	$sizeof = count($workitems_array); // need to reset the count just incase a Parent has more than 1 child, the number of items will be reduced
	for($x = 0; $x < $sizeof; $x++){

		$workitem_url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $workitem_id . '&$expand=all&api-version=6.0';
		
		$item_json = $workitems_array[$x];
		$workitem_id = $workitems_array[$x]->{'id'}; // not used maybe delete?
		$workitem_url = $workitems_array[$x]->{'url'}; // this url didn't return all the info I was looking for

		//
		// okay so the Wiql query will remove any issues that shouldn't be shown on the website (flag) and those that
		// aren't attached to a sprint.  If it is tied to a sprint it isn't consided in-queue/backlog.
		$skip_for_epic = 0;
		//
		// this  is to make sure that this item is not tied to a sprint
		if(strtolower($include) == "all" )
				$skip_for_epic = 0;
		else {
			$IterationPath = (isset($item_json->{'fields'}->{'System.IterationPath'}) ? $item_json->{'fields'}->{'System.IterationPath'} : "not set") ;
			if ($turn_on_debug == 1) 
				print "<!-- debugging IterationPath " .  $IterationPath . "-->\n";
			if ($IterationPath == $wp_devops_setup->project ) {
				$skip_for_epic = 0;
				if ($turn_on_debug == 1) 
					print "<!-- debugging setting skip_for_epic to zero -->\n";
			} else {
				$skip_for_epic = 1;
				if ($turn_on_debug == 1) 
					print "<!-- debugging setting skip_for_epic to 1 -->\n";
			}
		}

	
		if($skip_for_epic == 0) {
			// show this
			if ($turn_on_debug == 1) {
				print "<!-- debug workitems: \n";
				print_r($item_json);
				print("-->\n");
			}
			$workitem_title = $item_json->{'fields'}->{'System.Title'};
			if (isset($item_json->{'fields'}->{'System.AssignedTo'})) {
				// have an assignee
				$stdClass_object = $item_json->{'fields'}->{'System.AssignedTo'};
				$workitem_assignee = $stdClass_object ->{'displayName'};
			}else {
				$workitem_assignee = "[Unassigned]";
			}
			$workitem_descr = isset($item_json->{'fields'}->{'System.Description'}  ) ? $item_json->{'fields'}->{'System.Description'} : '';
			$workitem_Area = isset( $item_json->{'fields'}->{'Custom.WebsiteAreas'} ) ? $item_json->{'fields'}->{'Custom.WebsiteAreas'} : '';
			$workitem_Title = isset( $item_json->{'fields'}->{'System.Title'} ) ? $item_json->{'fields'}->{'System.Title'} : '';
			$workitem_IterationPath = isset($item_json->{'fields'}->{'System.IterationPath'}) ? $item_json->{'fields'}->{'System.IterationPath'} : '';

			print ("<script>\n") ;
			$detail_show_workitem = show_workitem($workitem_id, $workitem_title, $workitem_assignee, '', $workitem_descr, $workitem_Area, $workitem_IterationPath );
			$sprint_detail = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $detail_show_workitem)));
			print "var Detail_" . $x . "_0 = \"" . $sprint_detail . '";' . "\n";
			if(strlen($workitem_Title) > 50)
				print 'var DetailTitle_' . $x . '_0 = "' . substr($workitem_Title, 0, 40) . '...";' . "\n";
			else
				print 'var DetailTitle_' . $x . '_0 = "' . $workitem_Title . '";' . "\n";
			print ("</script>\n") ;
					
			// This does the start of each row, we want a line at the top execpt for the last row we need 2 lines, top and bottom
			if ( $x == ($sizeof -1))  {// last row need top and bottom line
				print '<tr style="background-color:#FFC409; border-top: 1px solid black; border-bottom: 1px solid black;">' . "\n";
				print('<td style="width: 1px; background-color:White; vertical-align: top; visibility: hidden;">' . $x . ".0</td>");
			} else {
				print '<tr style="background-color:#FFC409; border-top: 1px solid black;">' . "\n";	
				print('<td style="width: 1px; background-color:White; vertical-align: top; visibility: hidden; ">' . $x . ".0</td>");
			}
	
			
			// okay so  for the returned item
			for($y = 0; $y < $FieldArraySize; $y++) {
				$do_col_span = 0;
				if (strtolower($FieldsToQuery[$y]) == "id") {
					$CellValue = $item_json->{'id'}; // special case for id of the issue
				} else {
					if ($FieldsToQuery[$y][0] == "|") { // This handles the ability to have 1 field do a col_span across the whole row
						$do_col_span = $do_col_span + 1;
						print "</tr>\n<tr >";
						print('<td style="width: 1px; background-color:White; vertical-align: top; visibility: hidden;">' . $x . "." . $do_col_span . "</td>");
						print '<td colspan="' . ( $FieldArraySize - $count_pipe) . '" style="background-color:White; vertical-align: top;" ><B>' . 
							substr($HeaderFields[$y], 1) . ':&nbsp</B>';
					}
					// okay at this point we will check to see if we have a question mark
					// if we do we will take the first field and if there is something there
					// we will use it, if not we will print the second field regardless of what
					// is there
					$has_question_mark = strpos($FieldsToQuery[$y], "?");
					if ($has_question_mark == FALSE) {
						# no question mark 
						$CellValue = (isset($item_json->{'fields'}->{$FieldsToQuery[$y]}) ? 
							$item_json->{'fields'}->{$FieldsToQuery[$y]} : false);
						// need to check for priority Microsoft.VSTS.Common.Priority
						if ($FieldsToQuery[$y]== "Microsoft.VSTS.Common.Priority") {
							switch($CellValue) {
								case "1":
									$CellValue = "Critical";
									break;
								case "2":
									$CellValue = "High";
									break;
								case "3":
									$CellValue = "Med";
									break;
								case "4":
									$CellValue = "Low";
									break;
							}
						}
						if ($CharCount[$y] > 0) 
							$CellValue = substr($CellValue, 0, 10);
					} else {
						$condit_array = explode("?",$FieldsToQuery[$y]);
						$condit_array[0] = trim($condit_array[0]);
						$condit_array[1] = trim($condit_array[1]);
						$CellValue = (isset($item_json->{'fields'}->{$condit_array[0]}) ? 
							$item_json->{'fields'}->{$condit_array[0]} : false);
						if (strlen(trim($CellValue)) == 0) {
							$CellValue = (isset($item_json->{'fields'}->{$condit_array[1]}) ? 
							$item_json->{'fields'}->{$condit_array[1]} : false);
						}
						if ($CharCount[$y] > 0) 
							$CellValue = substr($CellValue, 0, 10);
					}	
				}
				if ($do_col_span > 0) {
					print $CellValue . "</td>";
					for ($yy = 1; $yy < ($FieldArraySize - $count_pipe) ; $yy++) {
							print '<td style="display: none"></td>';
					}
				} else {		
					print '<td style="' . $FieldStyle[$y] . '; background-color:White; vertical-align: top;">' ;
					$div_value = '<div style="cursor: pointer; " onclick="detail.open(' . $x . ', 0)"> '; 
					print $div_value;
					print $CellValue ;		
					print ("</div>\n");					
					print "</td>";
				}
			}
			print "</tr>\n";
		}
	}
	print "    </tbody>\n";
	print "</table>\n";

	
//print '<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.8.2.min.js"></script>';
print '<script type="text/javascript" charset="utf8" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>';

print'
<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>';

// this is what is needed for the jquery datatables to work.
print'
	<script>

(function() {
	$("#' . $tableid . '").dataTable({});
	}
)(jQuery);
</script>
';
	
	$js_file = str_replace('\\', '/', ABSPATH) . 'wp-content/plugins/ucf-az-devops-rest-api/includes/js/popup.js';
	$js_open = fopen($js_file, "r");
	$js_data = fread($js_open, filesize($js_file));
	fclose($js_open);
	print "<script>" . $js_data . "</script>";
	
	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}


// this shortcode/function will show a clickable ghant chart of the sprints
// it only shows sprints that are current, ones in the past and to far in the future
// are not shown.
function wp_devops_current_sprint($atts = [], $content = null) {
	global $turn_on_debug;
	global $wpdb;
	global $wp;
	
	$months = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	$bg_color = array("#588BAE","#0080FF","#4682B4","#57A0D3","#0E4D92","#4F97A3","#73C2FB","#0080FF", "#588BAE","#0080FF","#4682B4","#57A0D3","#0E4D92","#4F97A3","#73C2FB","#0080FF");


	 
	$tablid = sanitize_text_field($atts['record']); 
	ob_start(); // this allows me to use echo instead of using concat all strings
	
	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ';
	
	$css_file = ABSPATH . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/popup.css';
	$css_open = fopen($css_file, "r");
	$css_data = fread($css_open, filesize($css_file));
	fclose($css_open);
	print "<style>" . $css_data . "</style>";
		
	
	$sql_setup = "select entry_index,pat_token," . 
		"description,organization,project from " . 
		$wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $tablid;
	$wp_devops_setup = $wpdb->get_row($sql_setup);
	if ($wp_devops_setup == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
	


	$tableid = "table_" . rand();  //this allows my code be on the page more than once
	
	$Organization = str_replace(" ", "%20", $wp_devops_setup->organization); 
	$Project = str_replace(" ", "%20", $wp_devops_setup->project); 
	$PAT= $wp_devops_setup->pat_token;  

	#
	# In order to get all sprints/iterations we first need to get the Project ID
	$url = "https://dev.azure.com/" . $Organization . "/_apis/projects?api-version=6.0";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$ListOfProjects = $myjson->value;

	
	$ProjectID = $ListOfProjects[0]->{'id'};
	$ProjectName = $ListOfProjects[0]->{'name'};
	#
	# Next up is to get the Team
	$url = "https://dev.azure.com/" . $Organization . "/_apis/projects/" . $ProjectID . "/properties?api-version=5.1-preview.1";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$List = $myjson->value;
	$sizeof = count($List);
	$team_id = "";
	for($x = 0; $x < $sizeof; $x++){
		$t_name = $List[$x]->{'name'};
		$t_value = $List[$x]->{'value'};
		if ( $t_name == "System.Microsoft.TeamFoundation.Team.Default" ) {
			$team_id = $t_value;
			break;
		}
	}
	
	//print("Team Name:" . $t_name . " -> Team id:" . $team_id . "\n");
	
	#
	# Next up is to get all iterations and loop through them
	$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/" . $team_id . "/_apis/work/teamsettings/iterations?api-version=6.0";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$List = $myjson->value;
	$sizeof = count($List);
	

	// This is our setup on how much we show
	$done=1;
	$colwidth = 130;
	//$total_width = 1400; // This is the total width of the graph
	$columns_to_show = 9; // this is number of columns to show
	$days_per_column = 7; // days per column
	$total_width = $colwidth * $columns_to_show;
	$column_size = $total_width / $columns_to_show ;
	$column_offset = 20;

	// we do the style here b/c we have some calc'd fields also some wordpress sites (here at ucf)
	// don't allow an include of .css on plugins, only themes

//* {
//    margin 0 0 0 0; /* margin: 0; */
//    padding 0 0 0 0; /* padding: 0; */
//    box-sizing: border-box;    
//  }
//
	print "<style>

.container {
      max-width: " . $total_width . "px;
      min-width: 400px;
      margin: 0 0 0 0 ; /* 0 auto; */
      padding 0 0 0 0; /* padding: 50px; */
  }
.chart {
	width: ". (($columns_to_show * $column_size )+ $column_offset ) . "px;
    display: grid;
	border: 2px solid #000;
	margin 0 0 0 0;  /* added by brad */
    position:  relative;
    overflow: hidden;   /* */
  }
.chart-row {
    display: grid;   
    grid-template-columns: " . $column_offset . "px 1fr;
    background-color: #DCDCDC;
  }
.chart-period {
    color:  #fff;
    background-color:  #708090 !important;
    border-bottom: 2px solid #000; 
    grid-template-columns: " . $column_offset . "px repeat(" . $columns_to_show . ", " . $column_size . "px); /* was 1fr */
  }
.chart-lines {
    position: absolute;
    height: 100%; 
    width: 100%; /* -- removed to help keep blocks same size? */
    background-color: transparent;
    grid-template-columns: " . $column_offset . "px repeat(" . $columns_to_show . "," . $column_size . "px); /* was 1fr */
  }
.chart-lines > span {  
	display: block;  border-right: 1px solid rgba(0, 0, 0, 0.3);
	resize: none;
}
.chart-row-item {
    background-color:#808080;
    border: 1px solid  #000;
    border-top: 0;
    border-left: 0;      
    padding: 20px 0;
    font-size: 15px;
    font-weight: bold;
    text-align: center;
  } 

.chart-row-bars {
    list-style: none;
    display: grid;
    padding: 15px 0;
    margin: 0;
    grid-template-columns: repeat(" . $columns_to_show . ", 1fr);
    grid-gap: 10px 0;
    border-bottom: 1px solid  #000;
  }
li.extra {
    font-weight: 450;
    text-align: left;
    font-size: 15px;
    min-height: 15px;
    background-color: #708090;
    padding: 5px 15px;
    color: #fff;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    border-radius: 15px;
  }
\n";

	if ($turn_on_debug == 1) 
		print "/* -- Debugging:date('w'): " . date('w')  . "*/\n";
	$datew = date('w') - 1;
	if ( $datew < 0 )
		$datew = 6;
	$monday = $datew . " days";
	$cur_day_str = date_create( date("Y-m-d"));
	print "/* -- Debugging:cur_day_str(initially): " . date_format($cur_day_str,"Y/m/d H:i:s") . " --- monday value: " . $monday . " */\n";
	date_sub($cur_day_str, date_interval_create_from_date_string($monday)); // this finds the first day of the ghant
	
	$week_add = (($columns_to_show - 1)* $days_per_column). " days";  // columns * 2 (because each column represents 2 weeks 
	print "/* -- Debugging: weeks to add: " . $week_add . " */\n";
	$weeks = date_interval_create_from_date_string( $week_add) ;
	$end_day = date_create( date("Y-m-d"));
	date_sub($end_day, date_interval_create_from_date_string($monday)); // this finds the first day of the ghant
	date_add($end_day, $weeks);
	
	print "/* -- Debugging:after date_add cur_day_str: " . date_format($cur_day_str,"Y/m/d H:i:s") . " */\n";
	print "/* -- Debugging:after date_add end_day: " . date_format($end_day,"Y/m/d H:i:s") . " */\n";

	
	
	// currently we only handle 10 sprints
	$count_word = array("one","two","three","four","five","six","seven", "eight", "nine", "ten");
	for($x = 0; $x < $sizeof; $x++){
		$sprint_name = $List[$x]->{'name'};
		$sprint_id = $List[$x]->{'id'};
		$sprint_path = $List[$x]->{'path'};
		
		$sprint_attrib = $List[$x]->{'attributes'};
		// format: 2022-10-24T00:00:00Z
		$sprint_startDate = $sprint_attrib->{'startDate'}; 
		$sprint_finishDate = $sprint_attrib->{'finishDate'};
		$sprint_timeFrame = $sprint_attrib->{'timeFrame'};
		
		$sprint_str = date_create(substr($sprint_startDate, 0, 10));
		$sprint_end = date_create(substr($sprint_finishDate, 0, 10));

		$cur_day_end = clone $cur_day_str;
		date_add($cur_day_end,date_interval_create_from_date_string("13 days"));
		
		$to_old_to_show = 0;

		if ($turn_on_debug == 1)  {
			print "/* -- Debugging:sprint_name: " . $sprint_name . " */\n";
			print "/* -- Debugging:cur_day_str: " . date_format($cur_day_str,"Y/m/d H:i:s") . " */\n";
			print "/* -- Debugging:cur_day_end: " . date_format($cur_day_end,"Y/m/d H:i:s") . " */\n";
			print "/* -- Debugging:end_day: " . date_format($end_day,"Y/m/d H:i:s") . " */\n";

			print "/* -- Debugging: sprint_str: " . date_format($sprint_str,"Y/m/d H:i:s") . " */ \n";
			print "/* -- Debugging: sprint_end: " . date_format($sprint_end,"Y/m/d H:i:s") . " */ \n";
		}
		$diff_str = date_diff($cur_day_str, $sprint_str);
		$diff_strday = $diff_str->format("%R%a");
		if ($turn_on_debug == 1) 
			print "/* -- Debugging:diff_strday: " . $diff_strday . " */ \n";
		$graph_start = intdiv($diff_strday ,$days_per_column );
		if ($turn_on_debug == 1) 
			print "/* -- Debugging:graph_start: " . $graph_start . " */ \n";
		if ($graph_start <= 0) {
			$graph_start = 1;
			if ($turn_on_debug == 1) 
				print "/* -- Debugging:graph_start: setting to 1 */ \n";
		} else {
			if ($turn_on_debug == 1) 
				print "/* -- Debugging:graph_start: adding 1 to it */ \n";
			$graph_start = $graph_start +1 ;
		}
		
		$diff_end = date_diff($cur_day_str, $sprint_end);
		$diff_endday = $diff_end->format("%R%a");
		if ($turn_on_debug == 1) 
			print "/* -- Debugging:diff_endday: " . $diff_endday . " */ \n";
		$graph_end = intdiv($diff_endday ,$days_per_column );
		if ($turn_on_debug == 1) 
			print "/* -- Debugging:graph_end: " . $graph_end . " */ \n";
		if ($graph_end == 0)
				$graph_end = $graph_start;
		else if ($graph_end > 0) {
			$graph_end = $graph_end + 2; // past?
			if ($turn_on_debug == 1) 
				print "/* -- Debugging:graph_end: +2 " . $graph_end . " */ \n";
		}
		
		print "/* -- Debugging:final graph_start: " . $graph_start . " */ \n";
		print "/* -- Debugging:final graph_end: " . $graph_end . " */ \n";
		//The grid-column property specifies a grid item's size and location in a grid layout, and is a shorthand property for the following properties:
		//grid-column-start
		// grid-column-end
	
		// graph_start = start column
		// graph_end = how man columns to span
		//	'left: ' . (($graph_start * $column_size )+ $column_offset ) . 'px;' . "\n" .
		//	'position: absolute;' . "\n" .
		print 'ul .chart-li-' . $count_word[$x] . ' { ' . "\n" .
			'grid-column: '. $graph_start . '/' . ($graph_end) . ';' . "\n" .

			'background-color:#588BAE;  }';
		print "\n";
	}	
	print "</style>\n";
	// Setup Start of Graph
	print '<div class="container "> ';
	print '<div class="chart"> ';
	print '<div class="chart-row chart-period">';
	print '<div class="chart-row-item"></div>	';
	$done = 1;
	$ghant_start_date =  clone $cur_day_str;
	while($done <= $columns_to_show) {
		$ghant_end_date = clone $ghant_start_date;
		date_add($ghant_end_date,date_interval_create_from_date_string( ($days_per_column-1) . " days"));
		//old date range show //. "<br>" . date_format($ghant_end_date,"Y/m/d") 
		print("<span><center>Week of:<br>" . date_format($ghant_start_date,"m/d/Y") . "</center></span>" );
		date_add ( $ghant_start_date , date_interval_create_from_date_string( $days_per_column . " days"));	
		$done = $done + 1;
	}
	print "</div>\n"; // ending class="chart-row chart-period"
	
	print '<div class="chart-row chart-lines">';
	$done=1;
	while ($done <= $columns_to_show) {
		print ("<span></span>");
		$done = $done + 1;
	}
	print "</div>\n"; //ending for class="chart-row chart-lines"

	$display_row = 1;
	for($x = 0; $x < $sizeof; $x++){
		/* collect all the needed information */
		$sprint_name = $List[$x]->{'name'};
		$sprint_id = $List[$x]->{'id'};
		$sprint_path = $List[$x]->{'path'};
		$sprint_attrib = $List[$x]->{'attributes'};
		// format: 2022-10-24T00:00:00Z
		$sprint_startDate = $sprint_attrib->{'startDate'}; 
		$sprint_finishDate = $sprint_attrib->{'finishDate'};
		$sprint_timeFrame = $sprint_attrib->{'timeFrame'};
		
		$sprint_str = date_create(substr($sprint_startDate, 0, 10));
		$sprint_end = date_create(substr($sprint_finishDate, 0, 10));
		
		print("<script>\n");
		if ($turn_on_debug == 1) {
			print "/* -- Debugging2:sprint name: " . $sprint_name . " */\n";
			print "/* -- Debugging2:cur_day_str: " . date_format($cur_day_str,"Y/m/d H:i:s") . " */\n";
			print "/* -- Debugging2:cur_day_end: " . date_format($cur_day_end,"Y/m/d H:i:s") . " */\n";
			print "/* -- Debugging2:end_day: " . date_format($end_day,"Y/m/d H:i:s") . " */\n";
			print "/* -- Debugging2: sprint_str: " . date_format($sprint_str,"Y/m/d H:i:s") . " */ \n";
			print "/* -- Debugging2: sprint_end: " . date_format($sprint_end,"Y/m/d H:i:s") . " */ \n";
		}
		print("</script>\n");
		if ( ($sprint_end >= $cur_day_str) and ($sprint_str <= $end_day)) {
			$sprint_url = $List[$x]->{'url'};
			# For each sprint/iterations, we will get all the items
			$url2 = "https://dev.azure.com/" . $Organization . "/" . $Project . "/" . $team_id . "/_apis/work/teamsettings/iterations/" . $sprint_id . "/workitems?api-version=6.0-preview.1";
			
			$curl2 = curl_init();
			curl_setopt($curl2, CURLOPT_URL, $url2);
			curl_setopt($curl2, CURLOPT_POST, FALSE);
			curl_setopt($curl2, CURLOPT_USERPWD, ':' . $PAT );
			curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true );
			$data2 = curl_exec($curl2);
			curl_close($curl2);
			$myjson2  = json_decode($data2 , false );
			
			//print("should show all iteractions\n");
			//print_r($myjson2);
			
			$worklistitems = $myjson2->workItemRelations;
			$sizeof2 = count($worklistitems);
			
			print("<script>\n");
			$sprint_goal = $sprint_name;
			$sprint_text = "";
			
			for ( $w_z = 0; $w_z < $sizeof2 ; $w_z++) {
					$w_target = $worklistitems[$w_z]->{'target'};
					$w_id = $w_target->id;
					$w_url = $w_target->url;	
					
					$sprint_detail = "";
					
					# next up go and get the information for each workitem
					$url3 = $w_url;
					$curl3 = curl_init();
					curl_setopt($curl3, CURLOPT_URL, $url3);
					curl_setopt($curl3, CURLOPT_POST, FALSE);
					curl_setopt($curl3, CURLOPT_USERPWD, ':' . $PAT );
					curl_setopt($curl3, CURLOPT_RETURNTRANSFER, true );
					$data3 = curl_exec($curl3);
					curl_close($curl3);
					$myjson3  = json_decode($data3 , false );
					
					$detail_fields = $myjson3->fields;
					$detail_id = $myjson3->{'id'};
					$detail_title = $detail_fields->{'System.Title'};
					if (isset($detail_fields ->{'System.AssignedTo'})) {
						// have an assignee
						$stdClass_object = $detail_fields ->{'System.AssignedTo'};
						$detail_assignee = $stdClass_object ->{'displayName'};
					}else {
						$detail_assignee = "[Unassigned]";
					}
					
					$detail_descr = isset($detail_fields->{'System.Description'}  ) ? $detail_fields->{'System.Description'} : '';
					
					// if this gives an error, maybe the field is not defined, if you set it to '1' it will always show the workitem
					$detail_ShowOnWebsite = isset($detail_fields->{'Custom.DisplayonWebsite'}) ? $detail_fields->{'Custom.DisplayonWebsite'} : '0';
					$detail_IterationPath = isset($detail_fields->{'System.IterationPath'}) ? $detail_fields->{'System.IterationPath'} : '';
					$detail_createdDate = 	isset( $detail_fields->{'System.CreatedDate'} ) ? $detail_fields->{'System.CreatedDate'} : '';
					$detail_UCFCategory = 	isset( $detail_fields->{'Custom.Category'} ) ? $detail_fields->{'Custom.Category'} : '';
					$detail_Area = 			isset( $detail_fields->{'Custom.WebsiteAreas'} ) ?  $detail_fields->{'Custom.WebsiteAreas'} : '';
					$detail_Priority = 		isset( $detail_fields->{'Microsoft.VSTS.Common.Priority'} ) ? $detail_fields->{'Microsoft.VSTS.Common.Priority'} : '' ;
					$detail_State = 		isset( $detail_fields->{'System.State'} ) ? $detail_fields->{'System.State'} :  '';
					$detail_WebsiteType = 	isset( $detail_fields->{'Custom.WebsiteType'} ) ? $detail_fields->{'Custom.WebsiteType'} : '' ;
					$detail_ImpactedAudience = isset( $detail_fields->{'Custom.ImpactedAudience'}) ? $detail_fields->{'Custom.ImpactedAudience'} : '' ;
					$detail_WebsiteAreas = isset($detail_fields->{'Custom.WebsiteAreas'}) ? $detail_fields->{'Custom.WebsiteAreas'} : '' ;
					$detail_LevelofEffort = isset($detail_fields->{'Custom.LevelofEffort'}) ? $detail_fields->{'Custom.LevelofEffort'} : '' ;
					$detail_EstimatedCompletion = isset($detail_fields->{'Custom.EstimatedCompletion'}) ? $detail_fields->{'Custom.EstimatedCompletion'} : '' ;
					$detail_ClosedDate = isset($detail_fields->{'Microsoft.VSTS.Common.ClosedDate'}) ? $detail_fields->{'Microsoft.VSTS.Common.ClosedDate'} : '' ;
	
					if ($turn_on_debug == 1)  {
						print "/* wp_devops_current_sprint Debugging Detail\n";
						print "detail_id: " . $detail_id . "\n";
						print "detail_createdDate: " . $detail_createdDate . "\n";
						print "detail_UCFCategory: " . $detail_UCFCategory . "\n";
						print "detail_Area: " . $detail_Area . "\n";
						print "detail_Priority: " . $detail_Priority . "\n";
						print "detail_ShowOnWebsite: " . $detail_ShowOnWebsite . "\n";
						print "*/\n";	
					}
	
					// this does the summary
					if( $detail_ShowOnWebsite == '1') {
						$sprint_text = $sprint_text . "<div style=\\\"cursor: pointer; \\\" onclick=\\\"detail.open(" . $x . "," . $w_z . ")\\\"> ";
						//$sprint_text = $sprint_text . "<i>" . $w_z . "-" . $detail_id . "</i> - ";
						$sprint_text = $sprint_text . "<i>" . $detail_id . "</i> - ";
						$sprint_text = $sprint_text . $detail_title ;
						
						$sprint_text = $sprint_text . "<br></div>";
		
						$detail_show_workitem = show_workitem($detail_id, $detail_title, $detail_assignee, '', $detail_descr, $detail_Area, $detail_IterationPath );
						$sprint_detail = $sprint_detail . str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $detail_show_workitem)));
						print "var Detail_" . $x . "_" . $w_z . " = \"" . $sprint_detail . '";' . "\n";
						if(strlen($detail_title) > 50)
							print "var DetailTitle_" . $x . "_" . $w_z . " = \"" . substr($detail_title, 0, 40) . '...";' . "\n";
						else
							print "var DetailTitle_" . $x . "_" . $w_z . " = \"" . $detail_title . '";' . "\n";
					}
			}
			print "var Text_" . $x . " = \"" . $sprint_text;
			print '";';
			print "\nvar Goal_" . $x . ' = "' . $sprint_goal . '"; ';
			print("</script>\n");
			

			// Add Sprint to graph		
			// format: 2022-10-24T00:00:00Z
			//$sprint_startDate
			$sprint_str = date_create(substr($sprint_startDate, 0, 10));
			$sprint_end = date_create(substr($sprint_finishDate, 0, 10));
			
			$monday = ((date('d') % 7 ) + 7) . " days";
			$cur_day_str = date_create( date("Y-m-d"));
			date_sub($cur_day_str, date_interval_create_from_date_string($monday)); // this finds the first day of the ghant
		
			$week_add = ($columns_to_show * 2). " weeks";  // columns * 2 (because each column represents 2 weeks 
			$weeks = date_interval_create_from_date_string( $week_add) ;
			$cur_day_end = date_create( date("Y-m-d"));
			date_sub($cur_day_end, date_interval_create_from_date_string($monday)); // this finds the first day of the ghant
			date_add($cur_day_end, $weeks);
	
			if ($turn_on_debug == 1) {
				print "<!-- -- Debugging2:sprint_name: " . $sprint_name . " */\n";
				print "<!-- -- Debugging2:cur_day_str: " . date_format($cur_day_str,"Y/m/d H:i:s") . " -->\n";
				print "<!-- -- Debugging2:cur_day_end: " . date_format($cur_day_end,"Y/m/d H:i:s") . " -->\n";
				print "<!-- -- Debugging2:end_day: " . date_format($end_day,"Y/m/d H:i:s") . " */\n";
				print "<!-- -- Debugging2: sprint_str: " . date_format($sprint_str,"Y/m/d H:i:s") . " -->\n";
				print "<!-- -- Debugging2: sprint_end: " . date_format($sprint_end,"Y/m/d H:i:s") . " -->\n";
			}
			if ( $sprint_end >= $cur_day_str) {	// if the sprint ends before we start - don't show
				if ( $sprint_str > $cur_day_end) {
					print "<!-- -- Debugging2: skipping -->\n";
				} else {
					print "<!-- -- Debugging2: Not skipping -->\n";
					print '<div class="chart-row">' . "\n"; // need 1 div at end
					print '<div class="chart-row-item" >' . ($display_row) . '</div>' . "\n";
					print '<ul class="chart-row-bars"  onclick="pop.open(\'title\' , ' . $x . ')">' ;
					print '  <li class="extra chart-li-' . $count_word[$x] . ' " >' ;
					print "<font size=\"2\"> " . $sprint_name . "<br><font size=\"1\"> " . date_format($sprint_str, "m/d/Y") . "</font>";
					print '</li>';
					// now we add the popup stuff
					print '</ul>' ;
					print "</div>"; //end for class=chart-row
					$display_row = $display_row + 1;
				}
			} else {
				print "<!-- -- Debugging2: skipping -->\n";
			}
		}
		
	}
	print "</div>"; //class="chart"
	print "</div>"; //class="container "
	
	$js_file = str_replace('\\', '/', ABSPATH) . 'wp-content/plugins/ucf-az-devops-rest-api/includes/js/popup.js';
	$js_open = fopen($js_file, "r");
	$js_data = fread($js_open, filesize($js_file));
	fclose($js_open);
	print "<script>" . $js_data . "</script>";

# used to have this: but even on my local machine caching would get in the way
#print'
#<script type="text/javascript" charset="utf8" src="' . get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/js/popup.js"></script>';


	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}
//
// this function is designed to show a work item data elements 
function show_workitem($id, $title, $assignee, $comment, $description, $area, $iteration )
{
	global $turn_on_debug;
	//$id = "16007";
	//$title = "INT013 PeopleFirst Payroll Deductions BNO002 - Duplicate Inputs";
	//$description = "Quick Brown Fox";
	//$assignee = "brad";
	//$comment = "";
	//$area = "area";
	//$iteration = "iteration";
	
	$id = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $id)));
	$title = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $title)));
	//$assignee = str_replace("'", "", $assignee);
	//$assignee = str_replace('"', '', $assignee);
	//$assignee = str_replace("\r", "", str_replace("\n", "", $assignee));
	$comment = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $comment)));
	$description = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $description)));
	$description = str_replace('\\', '', $description);
	//$area = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $area)));
	//$iteration = str_replace('"', '\"', str_replace("\r", "", str_replace("\n", "", $iteration)));
	
	
	
$return_content =  '
<table border="0" cellpadding="1" cellspacing="1" >
	<tbody>
		<tr>
			<td rowspan="4" style="background-color:#339933; width:31px">&nbsp;</td>
			<td style="vertical-align:top; "><strong>Issue ' .  $id . '</strong></td>
			<td style="text-align:right; white-space:nowrap; ">
			<table border="0" cellpadding="1" cellspacing="1" >
				<tbody>
					<tr>
						<td ><B>Area:</B></td>
						<td >' . $area . '</td>
					</tr>
					<tr>
						<td ><B>Iteration:</B></td>
						<td >' . $iteration . '</td>
					</tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr>
			<td colspan="3" rowspan="1" style="width:753px"><strong>' . $title . '</strong></td>
		</tr>
		<tr>
			<td colspan="2"><B>Assignee</B>:&nbsp;' .  $assignee . '</td>
		</tr>
		<tr>
			<td colspan="2" rowspan="1" >
			<table border="0" cellpadding="1" cellspacing="1" >
				<tbody>
					<tr>
						<td style="text-align: left"><B>Description</B></td>
					</tr>
					<tr>
						<td >' .  $description . '</td>
					</tr>
				</tbody>
			</table>

			<p>&nbsp;</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

';

return($return_content);
}

//
// this shortcode is used to start tabs
//
function wp_devops_tab_start()
{
	print '
	<style>
body {font-family: Arial;}

/* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 6px; /* padding: 6px 12px; /* */
  border: 1px solid #ccc; /* */
  border-top: none;
}
</style>
';
}
//
// this is the end of the tabs - you should put code inbetween 
function wp_devops_tab_end()
{
	print '
	<script>
function openDevOpsTab(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  /* not needed? evt.currentTarget.className += " active"; */
}
</script>
';

}

function wp_devops_query($atts = [], $content = null) {
	global $turn_on_debug;
	global $wpdb;
	global $wp;
	
	
	ob_start(); // this allows me to use echo instead of using concat all strings
 
	$wiql_id_index =  sanitize_text_field($atts['wiql_id_index']);
	$wiql_fieldname = sanitize_text_field($atts['field']);
	if (isset($atts['charttype']))
		$charttype = sanitize_text_field($atts['charttype']);
	else
		$charttype = "bar";
	
	$skip_devops_settings_form = 1;	
	
	$barColors = array("#3366CC", "#DC3912", "#FF9900", "#109618", "#990099", "#3B3EAC", "#0099C6", "#DD4477", "#66AA00", "#B82E2E", "#316395", "#994499", "#22AA99", "#AAAA11", "#6633CC", "#E67300", "#8B0707", "#329262", "#5574A6", "#651067");


	$sql_setup = "select a.entry_index, a.pat_token," . 
		"a.description, a.organization,a.project, b.queryname, b.queryid from " . 
	$wpdb->base_prefix . "ucf_devops_setup a, " . $wpdb->base_prefix . "ucf_devops_query b  where a.entry_index = b.entry_index and b.wiql_id_index = " . $wiql_id_index;
	$wp_devops_setup = $wpdb->get_row($sql_setup);
	if ($wp_devops_setup == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
	$Organization = str_replace(" ", "%20", $wp_devops_setup->organization);
	$Project = str_replace(" ", "%20", $wp_devops_setup->project); 
	$ucf_devops_pat_token = $wp_devops_setup->pat_token;
	

	$queryid = $wp_devops_setup->queryid;    //"392fff89-3500-4880-a925-620650238fd5";
	$queryname = $wp_devops_setup->queryname;
	
	$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/wiql/" .  $queryid  ."?api-version=5.1";
	$curl = curl_init();
	

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $ucf_devops_pat_token );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	//curl_close($curl);


	$myjson  = json_decode($data , false );
	
	$ucf_workItems = $myjson->{'workItems'};
	
	//print "<PRE>";
	//print_r($ucf_workItems);
	//print "</PRE>";
	
	$ucf_count = count($ucf_workItems);
	
	$value_array = array(); // holds the count 
	$index_array = array(); // holds the value/
	
	$ucf_count = 10;
	//so now we have a list of devops id, we need to get/find the value so we can count/group them
	for ($i=0; $i < $ucf_count; $i++) {
		$ucf_workitem_id = $ucf_workItems[$i]->{'id'};
		$ucf_workitem_url = $ucf_workItems[$i]->{'url'};
		
		$item_url =  "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $ucf_workitem_id . '&$expand=all&api-version=6.0';

		curl_setopt($curl, CURLOPT_URL, $item_url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($curl, CURLOPT_USERPWD, ':' . $ucf_devops_pat_token );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		$ucf_item_data = curl_exec($curl);
		
		//curl_close($curl);
		
		$myjson  = json_decode($ucf_item_data , false );
	
		$item_json = $myjson->{'value'}[0]; // trasverse json structure
		$detail_id = $item_json->{'id'};
		$thefields = $item_json->{'fields'}; 
		if (isset($thefields->{$wiql_fieldname}))
			$thevalue = strval($thefields->{$wiql_fieldname});
		else
			$thevalue = "(blank}";
		
		print "<PRE>thevalue is:" . $thevalue . "</PRE>\n";
		
		$srch = array_search($thevalue, $index_array, true);
		if ($srch === FALSE) { // need to add
			array_push($index_array,$thevalue);
			array_push($value_array, 1);
		} else { // need to increment
			$value_array[$srch] = $value_array[$srch] + 1;
		}
	}

	print "<PRE>";
	print_r($index_array);
	print "</PRE><P>";

	$chartid = "myChart_" . rand();  //this allows my code be on the page more than once
	print '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"> </script>';
	print "\n";
	print '<canvas id="' . $chartid . '" style="width:100%;max-width:1200px"></canvas>';
	print "\n";
	

	$acount = count($index_array);
	print '	<script> ' . "\n";
	print '	var xValues = [';
	for($i=0; $i < $acount; $i++) {
		print '"' . $index_array[$i] . '"';
		if ($i < ( $acount - 1) )
			print ',';
	}
	print '];' . "\n";
	
	
	print '	var yValues = [';
	for($i=0; $i < $acount; $i++) {
		print '"' . $value_array[$i] . '"';
 		if ($i < ( $acount - 1) )
			print ',';
	}
	print '];' . "\n";
	
	print '	var barColors = [';
	for($i = 0; $i < $acount; $i++) {
		print '"' . $barColors[$i] . '"';
		if ($i < ( $acount - 1) )
			print ',';
	}
	print '];' . "\n";
	
	print '	new Chart("' . $chartid . '", {' . "\n";
	print '			type: "' . $charttype . '",' . "\n";
	print '			data: {' . "\n";
	print '				labels: xValues,' . "\n";
	print '				datasets: [{' . "\n";
	print '				backgroundColor: barColors,' . "\n";
	print '				data: yValues' . "\n";
	print '			}]' . "\n";
	print '		},' . "\n";
	print '		options: {' . "\n";
	if (($charttype == "pie"))  {
		print '			legend: {display: true},' . "\n";
	} else {
		print '			legend: {display: false},' . "\n";
	}
	//print "			indexAxis: 'y', \n";
	print '			title: {' . "\n";
	print '			display: true,' . "\n";
	print '			text: "Query Name:' . $queryname . '"' . "\n";
	print '			}' . "\n";
	print '		}' . "\n";
	print '	});' . "\n";
	print '	</script>' . "\n";
			
	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}

//
// this function allows us to show the current sprint, current+1, current+2, etc
// in a jquery datatable 
//

function wp_devops_list_sprint($atts = [], $content = null) {
	global $turn_on_debug;
	global $wpdb;
	global $wp;
	
		
	ob_start(); // this allows me to use echo instead of using concat all strings

	$months = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
 	// Epic array to make sure we only print an Epic once
	$EpicArray = array();
	
	$sprint_to_show = sanitize_text_field($atts['sprint_number']); 
	$record = sanitize_text_field($atts['record']); /* this gets the field list */
	
	$sql = "select entry_index, wiql,fields_to_query," .
		"header_fields,field_style,char_count from " . 
		$wpdb->base_prefix . "ucf_devops_main where wiql_index = " . $record;
	$wp_devops_return = $wpdb->get_row($sql);
	if ($wp_devops_return == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
		
	$entry_index = $wp_devops_return->entry_index;

	$sql_setup = "select entry_index,pat_token," . 
		"description,organization,project from " . 
		$wpdb->base_prefix . "ucf_devops_setup where entry_index = " . $entry_index;
	$wp_devops_setup = $wpdb->get_row($sql_setup);
	if ($wp_devops_setup == false) {
		$wpdb->show_errors();
		$wpdb->flush();
	}
		
	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ';
	
	$tableid = "table_" . rand();  //this allows my code be on the page more than once
	
	$Organization = str_replace(" ", "%20", $wp_devops_setup->organization); 
	$Project = str_replace(" ", "%20", $wp_devops_setup->project); 
	$PAT= $wp_devops_setup->pat_token;  
	//'"Select [System.Id] from WorkItems where [Custom.DisplayonWebsite] = True and [System.State] = \"Done\" "';
	$FieldsToQuery = explode(",",$wp_devops_return->fields_to_query);
	$HeaderFields = explode(",",$wp_devops_return->header_fields);
	$FieldStyle = explode(",",$wp_devops_return->field_style);
	$CharCount = explode(",",$wp_devops_return->char_count);
	
	$FieldArraySize = count($FieldsToQuery);
	// need to make sure that all our arrays don't have extra spaces
	for($x = 0; $x < $FieldArraySize; $x++){
		$FieldsToQuery[$x] = trim($FieldsToQuery[$x]);
		$HeaderFields[$x] = trim($HeaderFields[$x]);
		$FieldStyle[$x] = trim($FieldStyle[$x]);
		$CharCount[$x] = trim($CharCount[$x]);
		
	}	
	
	#
	# In order to get all sprints/iterations we first need to get the Project ID
	$url = "https://dev.azure.com/" . $Organization . "/_apis/projects?api-version=6.0";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$ListOfProjects = $myjson->value;

	
	$ProjectID = $ListOfProjects[0]->{'id'};
	$ProjectName = $ListOfProjects[0]->{'name'};
	#
	# Next up is to get the Team
	$url = "https://dev.azure.com/" . $Organization . "/_apis/projects/" . $ProjectID . "/properties?api-version=5.1-preview.1";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$List = $myjson->value;
	$sizeof = count($List);
	$team_id = "";
	for($x = 0; $x < $sizeof; $x++){
		$t_name = $List[$x]->{'name'};
		$t_value = $List[$x]->{'value'};
		if ( $t_name == "System.Microsoft.TeamFoundation.Team.Default" ) {
			$team_id = $t_value;
			break;
		}
	}
	
	//print("Team Name:" . $t_name . " -> Team id:" . $team_id . "\n");
	
	#
	# Next up is to get all iterations and loop through them
	$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/" . $team_id . "/_apis/work/teamsettings/iterations?api-version=6.0";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	$myjson  = json_decode($data , false );
	
	$List = $myjson->value;
	$sizeof = count($List);
	

	$done=1;
	
	$tz = 'America/New_York';
	date_default_timezone_set($tz);
	//$cur_day_str  = new Date("now", new DateTimeZone($tz)); //first argument "must" be a string
	$cur_day_str = date_create( date("Y-m-d"));


	$count_word = array("one","two","three","four","five","six","seven", "eight", "nine", "ten");



	$display_row = 0;
	for($x = 0; $x < $sizeof; $x++){
		/* collect all the needed information */
		$sprint_name = $List[$x]->{'name'};
		$sprint_id = $List[$x]->{'id'};
		$sprint_path = $List[$x]->{'path'};
		$sprint_attrib = $List[$x]->{'attributes'};
		// format: 2022-10-24T00:00:00Z
		$sprint_startDate = $sprint_attrib->{'startDate'}; 
		$sprint_finishDate = $sprint_attrib->{'finishDate'};
		$sprint_timeFrame = $sprint_attrib->{'timeFrame'};
		// format: 2022-10-24T00:00:00Z
		//$sprint_startDate
		$sprint_str = date_create(substr($sprint_startDate, 0, 10));
		$sprint_end = date_create(substr($sprint_finishDate, 0, 10));
		
		if ( $sprint_end >= $cur_day_str) {	// Show/count this b/c it ends after our current date	
			$display_row = $display_row + 1;
			if ( $sprint_to_show == $display_row) { 	

				print "<CENTER>\n";
				print "<table style='width: 100%;'><tr><td><B>Sprint ID:&nbsp;</B>" . $sprint_name . "</td><td><B>Start Date:</B>&nbsp;" . date_format($sprint_str, 'M j, Y') . 
					"</td><td><B>End Date:</B>&nbsp;" .  date_format($sprint_end, 'M j, Y') . "</td></tr></table>\n";
				print "</CENTER>";

				print '<table id="' . $tableid . '" class="display" style="border-collapse: collapse; width: 100%; font-size: 12px;">' . "\n";
				print "    <thead>\n";
				print '        <tr style="background-color:#FFC409; border-bottom: 1px solid black;">' . "\n";
				print '<th >&nbsp;</th>';
				for($xx = 0; $xx < $FieldArraySize; $xx++) {
					if ($HeaderFields[$x][0] != "|" ) {
						// if the first field is a vertical bar we will skip it for now b/c it will be handled later
						print "            <th style=\"". $FieldStyle[$xx] . "\" >" . $HeaderFields[$xx] . "</th>\n";
					} else {
						$col_span = 1;
						$count_pipe = $count_pipe + 1;
					}
				}
				print "        </tr>\n";
				print "    </thead>\n";
				print "    <tbody>\n";
				
				$sprint_url = $List[$x]->{'url'};
				# For each sprint/iterations, we will get all the items
				$url2 = "https://dev.azure.com/" . $Organization . "/" . $Project . "/" . $team_id . "/_apis/work/teamsettings/iterations/" . $sprint_id . "/workitems?api-version=6.0-preview.1";
				
				$curl2 = curl_init();
				curl_setopt($curl2, CURLOPT_URL, $url2);
				curl_setopt($curl2, CURLOPT_POST, FALSE);
				curl_setopt($curl2, CURLOPT_USERPWD, ':' . $PAT );
				curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true );
				$data2 = curl_exec($curl2);
				curl_close($curl2);
				$myjson2  = json_decode($data2 , false );
				
				//print("should show all iteractions\n");
				//print_r($myjson2);
				
				$worklistitems = $myjson2->workItemRelations;
				$sizeof2 = count($worklistitems);

				$sprint_goal = "";
				$sprint_text = "";
				
				for ( $w_z = 0; $w_z < $sizeof2 ; $w_z++) {
					$w_target = $worklistitems[$w_z]->{'target'};
					$w_id = $w_target->id;
					$w_url = $w_target->url;	
					
					$sprint_detail = "";
					

					# new url to get all info
					$url3 = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $w_id . '&$expand=all&api-version=6.0';
					# next up go and get the information for each workitem
					#$url3 = $w_url . '&$expand=all&api-version=6.0';
										
					$curl3 = curl_init();
					curl_setopt($curl3, CURLOPT_URL, $url3);
					curl_setopt($curl3, CURLOPT_POST, FALSE);
					curl_setopt($curl3, CURLOPT_USERPWD, ':' . $PAT );
					curl_setopt($curl3, CURLOPT_RETURNTRANSFER, true );
					$data3 = curl_exec($curl3);
					curl_close($curl3);
					$myjson3  = json_decode($data3 , false );
					
					$item_json = $myjson3->{'value'}[0]; // trasverse json structure
					$detail_fields = $item_json->fields;
					$detail_id = $item_json->{'id'};
					
					
					$WorkItemType = $detail_fields->{'System.WorkItemType'};
					if ($WorkItemType != 'Issue') 
						$skip_for_epic = 1; // set to 1 b/c we only show issues
					else
						$skip_for_epic = 0; // set to 1 if we need to skip
					// At this point we need to see if this this one has a parent.
					//
					// we will do now is to see if this issue is tied to an Epic 
					// the array we will check is EpicArray
					$ParentID = (isset($detail_fields->{'System.Parent'}) ? $detail_fields->{'System.Parent'} : false) ;
					// so if ParentId is not false we need to grab it instead

					$saveid = $detail_id;

					if (($ParentID != false) && ( $skip_for_epic == 0)) {
					// we have a parent id, so we need to check our EpicArry and see if it is there
					// if it there, that means we have already shown it, and we can skip this one

						$srch = array_search($ParentID, $EpicArray);

						if ( $srch === FALSE ) {
							// not found, need to get it
							array_push($EpicArray, $ParentID);
							$epic_url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/workitems?ids=" . $ParentID . '&$expand=all&api-version=6.0';
							$curl_workitem = curl_init();
							curl_setopt($curl_workitem, CURLOPT_URL, $epic_url);
							curl_setopt($curl_workitem, CURLOPT_POST, 0);  // this is a GET
							curl_setopt($curl_workitem, CURLOPT_USERPWD, ':' . $PAT);
							curl_setopt($curl_workitem, CURLOPT_RETURNTRANSFER, true );
							$item_data = curl_exec($curl_workitem);
			
							curl_close($curl_workitem);
			
							$item_decode  = json_decode($item_data , false );
							$item_json = $item_decode->{'value'}[0]; // trasverse json structure
							$detail_fields = $item_json->fields;
				
							// So at this point item_json should hold the new Epic
						} else {
							// need to skip
							$skip_for_epic = 1;
						}
					}
					/*
					print "<!-- Debugging List\n";
					print "Workitemtype: " . $WorkItemType . "\n";
					print "skip_for_epic: " . $skip_for_epic . "\n";
					print "ParentID: " . $ParentID . "\n";
					print "detail_id: " . $detail_id . "\n";
					print "\ndetail_fields:\n";
					print_r($detail_fields);
					print "\n-->\n";
					*/

					if ( $skip_for_epic == 0) {
						$detail_title = $detail_fields->{'System.Title'};
						if (isset($detail_fields ->{'System.AssignedTo'})) {
							// have an assignee
							$stdClass_object = $detail_fields ->{'System.AssignedTo'};
							$detail_assignee = $stdClass_object ->{'displayName'};
						}else {
							$detail_assignee = "[Unassigned]";
						}
						
						$detail_descr = isset($detail_fields->{'System.Description'}  ) ? $detail_fields->{'System.Description'} : '';
						
						
						$detail_IterationPath = isset($detail_fields->{'System.IterationPath'}) ? $detail_fields->{'System.IterationPath'} : '';
						
						// if this gives an error, maybe the field is not defined, if you set it to '1' it will always show the workitem
						$detail_ShowOnWebsite = isset($detail_fields->{'Custom.DisplayonWebsite'}) ? $detail_fields->{'Custom.DisplayonWebsite'} : '0';
						
						$detail_createdDate = 	isset( $detail_fields->{'System.CreatedDate'} ) ? $detail_fields->{'System.CreatedDate'} : '';
						$detail_UCFCategory = 	isset( $detail_fields->{'Custom.Category'} ) ? $detail_fields->{'Custom.Category'} : '';
						$detail_Priority = 		isset( $detail_fields->{'Microsoft.VSTS.Common.Priority'} ) ? $detail_fields->{'Microsoft.VSTS.Common.Priority'} : '' ;
						$detail_State = 		isset( $detail_fields->{'System.State'} ) ? $detail_fields->{'System.State'} :  '';						
						$detail_ImpactedAudience = isset( $detail_fields->{'Custom.ImpactedAudience'}) ? $detail_fields->{'Custom.ImpactedAudience'} : '' ;
						$detail_LevelofEffort = isset($detail_fields->{'Custom.LevelofEffort'}) ? $detail_fields->{'Custom.LevelofEffort'} : '' ;
						$detail_EstimatedCompletion = isset($detail_fields->{'Custom.EstimatedCompletion'}) ? $detail_fields->{'Custom.EstimatedCompletion'} : '' ;
						$detail_ClosedDate = isset($detail_fields->{'Microsoft.VSTS.Common.ClosedDate'}) ? $detail_fields->{'Microsoft.VSTS.Common.ClosedDate'} : '' ;
						$detail_show_workitem = show_workitem($detail_id, $detail_title, $detail_assignee, '', $detail_descr, ' ', $detail_IterationPath );
						
						print "<!-- wp_devops_list_sprint Debugging Detail\n";
						print "detail_id: " . $detail_id . "\n";
						print "detail_createdDate: " . $detail_createdDate . "\n";
						print "detail_UCFCategory: " . $detail_fields->{'Custom.Category'} . "\n";
						print "detail_LevelofEffort: " . $detail_fields->{'Custom.LevelofEffort'} . "\n";
						print "detail_Priority: " . $detail_Priority . "\n";
						print "detail_ShowOnWebsite: " . $detail_ShowOnWebsite . "\n";
						print "Custom.DisplayonWebsite: " . $detail_fields->{'Custom.DisplayonWebsite'} . "\n";
						print "detail_id: " . $detail_id . "\n";
						
						print "-->\n";
						
						if ( $detail_ShowOnWebsite == '1') { // this is our flag to only show those items that are flagged.
							if ( $x == ($sizeof -1))  {// last row need top and bottom line
								print '<tr style="background-color:#FFC409; border-top: 1px solid black; border-bottom: 1px solid black;">' . "\n";
								print('<td style="width: 0px; background-color:White; vertical-align: top; visibility: hidden;">' . $x . ".0</td>");
							} else {
								print '<tr style="background-color:#FFC409; border-top: 1px solid black;">' . "\n";
								print('<td style="width: 0px; background-color:White; vertical-align: top; visibility: hidden; ">' . $x . ".0</td>");
							}
							for($y = 0; $y < $FieldArraySize; $y++) {
								$do_col_span = 0;
								if (strtolower($FieldsToQuery[$y]) == "id") {
									// special case for id of the issue
									if ($ParentID != False)
										$CellValue = $ParentID;
									else
										$CellValue = $detail_id;
								} else {
									if ($FieldsToQuery[$y][0] == "|") { 
										$do_col_span = $do_col_span + 1;
										print "</tr>\n<tr >";
										print('<td style="background-color:White; vertical-align: top; visibility: hidden;">' . $x . "." . $do_col_span . "</td>");
										print '<td colspan="' . ( $FieldArraySize - $count_pipe) . '" style="background-color:White; vertical-align: top;" ><B>' . 
											substr($HeaderFields[$y], 1) . ':&nbsp</B>';
									}
									// okay at this point we will check to see if we have a question mark
									// if we do we will take the first field and if there is something there
									// we will use it, if not we will print the second field regardless of what
									// is there
									$has_question_mark = strpos($FieldsToQuery[$y], "?");
									if ($has_question_mark == FALSE) {
										# no question mark 
										$CellValue = (isset($detail_fields->{$FieldsToQuery[$y]}) ? 
											$detail_fields->{$FieldsToQuery[$y]} : false);
										// need to check for priority Microsoft.VSTS.Common.Priority
										if ($FieldsToQuery[$y]== "Microsoft.VSTS.Common.Priority") {
											switch($CellValue) {
												case "1":
													$CellValue = "Critical";
													break;
												case "2":
													$CellValue = "High";
													break;
												case "3":
													$CellValue = "Med";
													break;
												case "4":
													$CellValue = "Low";
													break;
											}
										}
										if ($CharCount[$y] > 0) 
											$CellValue = substr($CellValue, 0, 10);
									} else {
										$condit_array = explode("?",$FieldsToQuery[$y]);
										$condit_array[0] = trim($condit_array[0]);
										$condit_array[1] = trim($condit_array[1]);
										$CellValue = (isset($detail_fields->{$condit_array[0]}) ? 
											$detail_fields->{$condit_array[0]} : false);
										if ($CellValue == FALSE) {
											$CellValue = (isset($detail_fields->{$condit_array[1]}) ? 
											$detail_fields->{$condit_array[1]} : false);
										}
										if ($CharCount[$y] > 0) 
											$CellValue = substr($CellValue, 0, 10);
									}	
								}
								if ($do_col_span > 0) {
									print $CellValue . "</td>";
									for ($yy = 1; $yy < ($FieldArraySize - $count_pipe) ; $yy++) {
											print '<td style="display: none"></td>';
									}
								}else
									print '<td style="background-color:White; vertical-align: top;">' . $CellValue . "</td>";
							}
							print "</tr>\n";
						}
					}
		
				}
				$x = $sizeof;
			}
		}
	}	
	print "    </tbody>\n";
	print "</table>\n";
	print '<script type="text/javascript" charset="utf8" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>';

	print'
<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>';

	// simulate mouse click on first tab to load after window is done loading
	print '<script>
    $( window ).on( "load", function() {
        console.log( "window loaded" );	
		document.getElementById("tab1").style.display = "block";
		/* not needed? evt.currentTarget.className += " active"; */
    });

(function() {
	$("#' . $tableid . '").dataTable({});
	}
)(jQuery);
    </script>';

	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}

add_shortcode ('wp_devops_list_sprint','wp_devops_list_sprint');

add_shortcode ('wp_devops_wiql','wp_devops_wiql');
add_shortcode ('wp_devops_current_sprint','wp_devops_current_sprint');
add_shortcode ('wp_devops_tab_start','wp_devops_tab_start');
add_shortcode ('wp_devops_tab_end','wp_devops_tab_end');
add_shortcode ('wp_devops_query' , 'wp_devops_query');

?>
