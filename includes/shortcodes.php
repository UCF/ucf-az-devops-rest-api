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


function wp_devops_current_sprint($atts = [], $content = null) {
	
	global $wpdb;
	global $wp;
	
	$months = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	$bg_color = array("#588BAE","#0080FF","#4682B4","#57A0D3","#0E4D92","#4F97A3","#73C2FB","#0080FF", "#588BAE","#0080FF","#4682B4","#57A0D3","#0E4D92","#4F97A3","#73C2FB","#0080FF");


	 
	$tablid = sanitize_text_field($atts['record']); 
	ob_start(); // this allows me to use echo instead of using concat all strings
	
	print '<link rel="stylesheet" type="text/css" href="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" /> ';
	

//	$css_file = ABSPATH . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/timelinegraph.css';
//	$css_open = fopen($css_file, "r");
//	$css_data = fread($css_open, filesize($css_file));
//	fclose($css_open);
//	print "<style>" . $css_data . "</stype>";
	
	$css_file = ABSPATH . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/popup.css';
	$css_open = fopen($css_file, "r");
	$css_data = fread($css_open, filesize($css_file));
	fclose($css_open);
	print "<style>" . $css_data . "</stype>";
		
//print '<link rel="stylesheet" type="text/css" href="' . get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/timelinegraph.css"> ';
//print '<link rel="stylesheet" type="text/css" href="' . get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/css/popup.css"> ';
	
	
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
	
	$cur_yr = date('Y');
	$cur_month = date('m') - 2;
	if ($cur_month < 0) {
		$cur_month = $cur_month + 12;
		$cur_yr = $cur_yr - 1;
	}
	
	$loop_month = $cur_month;
	$done=1;
	$month_to_show = 12;
	
	
	//width: ". (($month_to_show * 110)+60) . "px;
	//grid-template-columns: 50px repeat(" . $month_to_show . ", 110px); /* was 1fr */
	//grid-template-columns: repeat(" . $month_to_show . ", 1fr);
	print "<style>

mybescontainer {
	border: 10px solid green;
	display: block;
	position: relative;
	/* margin: 0; */
    padding: 50px; */
    /* box-sizing: border-box;     */
  }
 
.chart {
	width: ". (($month_to_show * 110)+50) . "px;
      display: grid;
      /* border: 2px solid #000; */
	  border: 2px solid #000;;
      position: relative;
      overflow: hidden;  
	  padding: 0px;
	  

  }
.chart-row {
    display: grid;      
    grid-template-columns: 50px 1fr;
    background-color: #DCDCDC;
  }
.chart-period {
    color:  #fff;
    background-color:  #708090 !important;
    border-bottom: 2px solid #000; 
    grid-template-columns: 50px repeat(" . $month_to_show . ", 110px); /* was 1fr */
  }
.chart-lines {
    position: absolute;
    height: 100%;
    width: 100%;
    background-color: transparent;
    grid-template-columns: 50px repeat(" . $month_to_show . ", 110px); /* was 1fr */
  }
.chart-lines > span {  
	display: block;  border-right: 1px solid rgba(0, 0, 0, 0.3);
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
    grid-template-columns: repeat(" . $month_to_show . ", 1fr);
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
		
		//$month_to_show = 12;
		//$cur_yr = date('Y'); assigned above
		//$cur_month = date('m') - 2; //assiged above 

		$mon_str = substr($sprint_attrib->{'startDate'}, 5, 2);
		$mon_end = substr($sprint_attrib->{'finishDate'}, 5, 2);
		
		$yr_str = substr($sprint_attrib->{'startDate'}, 0, 4);
		$yr_end = substr($sprint_attrib->{'finishDate'}, 0, 4);
		
		
		// so if $cur_month = start of graph 
		// then $cur_month + 12 = end of graph
		$cur_endmon = $cur_month + 12;
		$cur_endyr = $cur_yr;
		if ( $cur_endmon > 12) {
			$cur_endmon = $cur_endmon - 12;
			$cur_endyr = $cur_endyr + 1;
		}
		// so the first thing is to find out is about start date
		if(($yr_str == $cur_yr)&&($mon_str < $cur_month))
			$graph_start = 1; // starts before so.....
		else if (($yr_str == $cur_yr) && ($mon_str >= $cur_month))
			$graph_start = $mon_str - $cur_month;
		else if (($yr_str == $cur_endyr)&& ($mon_str <= $cur_endmon))
			$graph_start = $mon_str - $cur_month + 12;
		else
			$graph_start = $month_to_show;
		// next up is to figure out month length
		$graph_len = (($yr_end - $yr_str) * 12) + ($mon_end - $mon_str) + $graph_start;
		if($graph_len > $month_to_show)
			$graph_len = $month_to_show;
		
		if($graph_start < 1)
			$graph_start = 1;
		
		// graph_start = start column
		// graph_end = how man columns to span
		print 'ul .chart-li-' . $count_word[$x] . ' { ' . "\n" .
			'grid-column: '. $graph_start . '/' . ($graph_len+1) . ';' . "\n" .
			'background-color:#588BAE;  }';
		print "\n";
	}	
	print "</style>\n";
	// Setup Start of Graph
	//print '<div class="container">';
//	print '<div class="mybescontainer">';
	print '<div class="chart"> ';
	print '<div class="chart-row chart-period">';
	print '<div class="chart-row-item"></div>	';
	$done = 1;
	while($done <= $month_to_show) {
		print("<span>" . $months[$loop_month] . "</span>" );
			
		$loop_month = $loop_month + 1;
		if ($loop_month == 12)
			$loop_month = 0;
		$done = $done + 1;
	}
	print "</div>\n"; // ending class="chart-row chart-period"
	
	print '<div class="chart-row chart-lines">';
	$done=1;
	while ($done <= $month_to_show) {
		print ("<span></span>");
		$done = $done + 1;
	}
	print "</div>\n"; //ending for class="chart-row chart-lines"
	
	
//print "</div>\n"; // temp end for chart
//print "</div>\n"; // temp end for bescontainer
//return;
	
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
		print "var Text_" . $x . " = \"";
		$sprint_goal = "";
		for ( $w_z = 0; $w_z < $sizeof2 ; $w_z++) {
				$w_target = $worklistitems[$w_z]->{'target'};
				$w_id = $w_target->id;
				$w_url = $w_target->url;	
							#
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
				$detail_createdDate = 	isset( $detail_fields->{'System.CreatedDate'} ) ? $detail_fields->{'System.CreatedDate'} : '';
				$detail_UCFCategory = 	isset( $detail_fields->{'Custom.UCFCategory'} ) ? $detail_fields->{'Custom.UCFCategory'} : '';
				$detail_Area = 			isset( $detail_fields->{'Custom.WebsiteAreas'} ) ?  $detail_fields->{'Custom.WebsiteAreas'} : '';
				$detail_Priority = 		isset( $detail_fields->{'Microsoft.VSTS.Common.Priority'} ) ? $detail_fields->{'Microsoft.VSTS.Common.Priority'} : '' ;
				$detail_State = 		isset( $detail_fields->{'System.State'} ) ? $detail_fields->{'System.State'} :  '';
				$detail_WebsiteType = 	isset( $detail_fields->{'Custom.WebsiteType'} ) ? $detail_fields->{'Custom.WebsiteType'} : '' ;
				$detail_ImpactedAudience = isset( $detail_fields->{'Custom.ImpactedAudience'}) ? $detail_fields->{'Custom.ImpactedAudience'} : '' ;
				$detail_WebsiteAreas = isset($detail_fields->{'Custom.WebsiteAreas'}) ? $detail_fields->{'Custom.WebsiteAreas'} : '' ;
				$detail_LevelofEffort = isset($detail_fields->{'Custom.LevelofEffort'}) ? $detail_fields->{'Custom.LevelofEffort'} : '' ;
				$detail_EstimatedCompletion = isset($detail_fields->{'Custom.EstimatedCompletion'}) ? $detail_fields->{'Custom.EstimatedCompletion'} : '' ;
				$detail_ClosedDate = isset($detail_fields->{'Microsoft.VSTS.Common.ClosedDate'}) ? $detail_fields->{'Microsoft.VSTS.Common.ClosedDate'} : '' ;
				if ( $w_z == 0) {
					//$sprint_goal = '<font size="2">' . $detail_title . '</font>';
					$sprint_goal =  $detail_title ;
				} else {
					print "<i>" . $detail_id . "</i> - ";
					print $detail_title ;
					print "<br>";
				}
		}	
		print '";';
		print "\nvar Goal_" . $x . ' = "' . $sprint_goal . '"; ';
		print("</script>\n");
		
		
		// Add Sprint to graph
		
		
		// format: 2022-10-24T00:00:00Z
		//$sprint_startDate
		
		print '<div class="chart-row">' . "\n"; // need 1 div at end
		print '<div class="chart-row-item" >' . $x+1 . '</div>' . "\n";
		
        print '<ul class="chart-row-bars"  onclick="pop.open(\'title\' , ' . $x . ')">' ;
		
		print '  <li class="extra chart-li-' . $count_word[$x] . ' " >' ;
		print "<font size=\"2\"> " . $sprint_name . "<br><font size=\"1\"> " . date("m/d/Y", strtotime($sprint_startDate)) . "</font>";
		print '</li>';
        
		
		// now we add the popup stuff
		print '</ul>' ;

		print "</div>"; //end for class=chart-row
		
	}	
	print "</div>"; //class="chart"
	//print "</div>"; //class="mybescontainer"
	//print "</div>\n"; //class="container"
	
	
print'
<script type="text/javascript" charset="utf8" src="' . get_site_url() . '/wp-content/plugins/ucf-az-devops-rest-api/includes/js/popup.js"></script>';

	return;

		
	$content = ob_get_contents();
	ob_end_clean();
    return $content;
}
add_shortcode ('wp_devops_wiql','wp_devops_wiql');
add_shortcode ('wp_devops_current_sprint','wp_devops_current_sprint');
?>
