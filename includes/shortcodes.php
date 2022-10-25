<?php
function wp_devops_wiql($atts = [], $content = null) {
	global $wpdb;
	 
	 
	ob_start(); // this allows me to use echo instead of using concat all strings

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

//according to Jim Barnes remove for now
	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ';
	
	
	$tableid = "table_" . rand();  //this allows my code be on the page more than once
	$tableid = "table_1";
	
	$Organization = str_replace(" ", "%20", $wp_devops_setup->organization); //"UCF-Operations";  // this is the organization name from Azure DevOps - needs to be html escaped
	$Project = str_replace(" ", "%20", $wp_devops_setup->project); //"Workday%20Operations"; // this is the project name from Azure DevOps - needs to be html escaped
	$PAT= $wp_devops_setup->pat_token; //"gx6jvhpecqdaneevel7owmtr7yd5ja5p675t3hwnbamvmgkuyq7q"; // This is currently my Personal Access Token. This needs to be changed
	$Wiql = $wp_devops_return->wiql; 
	//'"Select [System.Id] from WorkItems where [Custom.UCFDisplayOnWebsite] = True and [System.State] = \"Done\" "';
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
	
	$url = "https://dev.azure.com/" . $Organization . "/" . $Project . "/_apis/wit/wiql?api-version=6.0";
	
	$curl = curl_init();
	
	$json_string = '{ "query": '. $Wiql . ' }';
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($curl, CURLOPT_USERPWD, ':' . $PAT );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec($curl);
	curl_close($curl);
	
	
	$myjson  = json_decode($data , false );
	
	$workitems = $myjson->workItems;
	$sizeof = count($workitems);
	//

	//echo "sizeof:" . $sizeof;
	print '<table id="' . $tableid . '" class="display" style="border-collapse: collapse; width: 100%; font-size: 12px;">' . "\n";
	print "    <thead>\n";
	print '        <tr style="background-color:#FFC409; border-bottom: 1px solid black;">' . "\n";
	for($x = 0; $x < $FieldArraySize; $x++) {
		print "            <th style=\"". $FieldStyle[$x] . "\" >" . $HeaderFields[$x] . "</th>\n";
	}
	print "        </tr>\n";
	print "    </thead>\n";
	print "    <tbody>\n";
	for($x = 0; $x < $sizeof; $x++){
		$workitem_id = $workitems[$x]->{'id'};
		$workitem_url = $workitems[$x]->{'url'};
		
		$item_url = $workitem_url;
		$curl_workitem = curl_init();
	
		curl_setopt($curl_workitem, CURLOPT_URL, $item_url);
		curl_setopt($curl_workitem, CURLOPT_POST, 0);  // this is a GET
		curl_setopt($curl_workitem, CURLOPT_USERPWD, ':' . $PAT);
		curl_setopt($curl_workitem, CURLOPT_RETURNTRANSFER, true );
		$item_data = curl_exec($curl_workitem);
		
		//print("<PRE>");
		//print_r($item_data);
		//print("</PRE>");
		
		curl_close($curl_workitem);
		
		$item_json  = json_decode($item_data , false );
		print '<tr style="background-color:#FFC409; border-bottom: 1px solid black;">' . "\n";
	
		for($y = 0; $y < $FieldArraySize; $y++) {
			if (strtolower($FieldsToQuery[$y]) == "id") {
				// special case for id of the issue
				$CellValue = $item_json->{'id'};
			} else {
				// okay at this point we will check to see if we have a question mark
				// if we do we will take the first field and if there is something there
				// we will use it, if not we will print the second field regardless of what
				// is there
				$has_question_mark = strpos($FieldsToQuery[$y], "?");
				if ($has_question_mark == FALSE) {
					# no question mark 
					$CellValue = (isset($item_json->{'fields'}->{$FieldsToQuery[$y]}) ? 
						$item_json->{'fields'}->{$FieldsToQuery[$y]} : false);
					if ($CharCount[$y] > 0) 
						$CellValue = substr($CellValue, 0, 10);
				} else {
					$condit_array = explode("?",$FieldsToQuery[$y]);
					$condit_array[0] = trim($condit_array[0]);
					$condit_array[1] = trim($condit_array[1]);
					$CellValue = (isset($item_json->{'fields'}->{$condit_array[0]}) ? 
						$item_json->{'fields'}->{$condit_array[0]} : false);
					if ($CellValue == FALSE) {
						$CellValue = (isset($item_json->{'fields'}->{$condit_array[1]}) ? 
						$item_json->{'fields'}->{$condit_array[1]} : false);
					}
					if ($CharCount[$y] > 0) 
						$CellValue = substr($CellValue, 0, 10);
				}	
			}
			print '<td style="background-color:White; vertical-align: top;">' . $CellValue . "</td>";
		}
		print "</tr>\n";
		print "\n\n";
	}
	print "    </tbody>\n";
	print "</table>\n";
	
//	print plugins_url( '/js/init.js', __FILE__ ) . "<P>";


//    bradtest();
//	print '<script type="text/javascript" charset="utf8" src="' . plugins_url( '/js/init.js', __FILE__ ) . '"></script>';
	
//print '<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.8.2.min.js"></script>';
print '<script type="text/javascript" charset="utf8" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>';

print'
<script type="text/javascript" charset="utf8" src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>';


print'
	<script>

(function() {
	$("#' . $tableid . '").dataTable({});
	}
)(jQuery);
</script>
';
	
	
	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}


add_shortcode ('wp_devops_wiql','wp_devops_wiql');
?>
