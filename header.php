<?php
session_start();
//if($_SESSION["userID"] != 1) unset($_SESSION["userID"]); // UNDER MAINTENANCE
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
$isMobile = false;
if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android')) $isMobile = true;

include 'functions-methods.php';

$infoSystems = Info::value('systems');

$dataParams = $fieldRows = $params = [];
$params = $_GET;
$objectAlias = "";

if(isset($params['object']) && $params['object'] != ""){
  $objectAlias = $params['object'];
}elseif(isset($params['alias']) && $params['alias'] != ""){
  $objectAlias = $params['alias'];
}elseif(isset($params['type']) && $params['type'] != ""){
  $objectAlias = $params['type'];//$params['view'];
}elseif(isset($params['view']) && $params['view'] != ""){
  $objectAlias = "page";//$params['view'];
}

$listPrefix = $_GET['view'];//(isset($params['view']) && $params['view'] != "") ? $params['alias'] : $params['view'];
$params['pageName'] = $_GET['view']."-".$objectAlias;//implode("-",$paramsAlias);
$params['typeList'] = $objectAlias;
$methods = new Method($params);

if (!isset($_SESSION["userID"])) { header("Location:".Info::URL."/logout.php"); } 
if (!isset($_SESSION["server_host"]) || $_SESSION["server_host"] != Info::URL) { header("Location:".Info::URL."/logout.php"); } 

//include "functions-methods.php";
if($_REQUEST){
  foreach($_REQUEST as $key => $value){
    $$key = $value;
    $parameters[$key] = $value;
  }
}

$moduleName = $thisType = $action = $theID = $theTable = $popupType = $titleHeader = $getLists = $arrayLists = $listHead = $viewTitle = $viewType = $parentMenu = $subMenu = $currentPage = $selections = $popUpBtn = "";
$theType = $viewID = 0;
$isDataTable = $hasPopup = true;
$error404 = $isOptions = $isFilterQuery = $isFilterDate = $isSystems = false;
$contentPage = 'methods-page.php';

if(isset($_SESSION["module_type"][$module_type])){
  //$stmtOptions = ['schema'=>Info::DB_NAME,'table'=>'options','arguments'=>['option_meta'=>'module_type','option_name'=>$module_type],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['option_name','id','option_name','option_value']];
  //$getModuleType = $methods->selectDB($stmtOptions);
  $thisType = $module_type;//$getModuleType[]->option_name;
  $moduleName = $_SESSION["module_type"][$module_type];
}

if(isset($view)){
  $stmtMetaCode = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>$thisType,'meta_option'=>$view],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['meta_option','id','meta_id','meta_option','meta_value']];
  $getMetaCode = $methods->selectDB($stmtMetaCode);
  $viewID = (isset($getMetaCode[$view]->id)) ? $getMetaCode[$view]->id : 0;
  $theView = (isset($getMetaCode[$view]->meta_id)) ? $getMetaCode[$view]->meta_id : "";
  $thisView = (isset($getMetaCode[$view]->meta_option)) ? $getMetaCode[$view]->meta_option : "";
  
  if(isset($params['type']) && $params['type'] != "") $viewType = $params['type'];
}

$dataTableID = ($isDataTable) ? PAGE_TYPE.'_'.$thisType : '';
// var_dump($thisType,$moduleName);
?>

<!DOCTYPE html>
<html lang="en" class="loading">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="<?php echo Info::URL?>/favicon.png">
    <title><?php echo $_SESSION["info"]["companyname"];?></title>
    <!-- Bootstrap -->
	<?php echo Method::headerJS();?>
    <!-- Font Awesome -->
    <link href="<?php echo Info::URL?>/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?php echo Info::URL?>/vendors/nprogress/nprogress.css" rel="stylesheet">
    <?php if($isDataTable){?>
      <!-- Datatables -->
      <link href="<?php echo Info::URL?>/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
      <link href="<?php echo Info::URL?>/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
      <link href="<?php echo Info::URL?>/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
      <link href="<?php echo Info::URL?>/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
      <link href="<?php echo Info::URL?>/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
      <!-- Custom Theme Style -->
    <?php } ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php
		include 'sidebar.php';
		if($thisView){
			switch($thisType){ //module_type
			  case 'admin': //admin
				switch($thisView){ //view
				  case 'lists':
					$fieldTitles = [];
					$methods->viewRestricted = true;
					$isActivityCancelled = $hasDateRange = false;
					$isActivityViewAll = true;
					$statusCol = "";
					$extraArguments = $sessionUnitAreaID = "";$dataArguments = $getActivity = $fieldTitles = $sessionUnitArray = [];
					$reportUnit = $_SESSION["unit"];
					$fieldAlignRight = ["loan_granted","loan_amount","principal","interest","penalty"];
					switch($params['alias']){
						case "loans": case "cbu": case "share_capital":
							$fieldTitles = ["mem_info"=>"Members Name"];
						break;
						case "loans_payment":
							$fieldTitles = ["loans_info"=>"Members Name"];
						break;
					}

					$stmtPath = ['schema'=>Info::DB_SYSTEMS,'table'=>'path','arguments'=>['alias'=>$params['alias'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','description','groups']];
					$getPath = $methods->selectDB($stmtPath);
					$getPathID = $getPath[$params['alias']]->id;
					$getActivityName = $getPath[$params['alias']]->name;

					$stmtWorkflow = ['schema'=>Info::DB_SYSTEMS,'table'=>'workflow','arguments'=>['path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['alias','id','path','tokens','description','value']];
					$getWorkflow = $methods->selectDB($stmtWorkflow);
					$getWorkFlowTokens = explode(",",$getWorkflow[$params['alias']."_module"]->tokens);

					if(isset($params['activity']) && $params['activity'] != ""){
						$isActivityViewAll = false;
						if($params['activity'] == "cancelled"){
							$getActivityTokens = [];
							$isActivityCancelled = true;
						}else{
							$getActivityID = $methods->array_search_by_key($_SESSION["path_activity"][$getPathID], 'alias', $params['activity'], 'id');
							$getActivityID = ($getActivityID) ? array_keys($getActivityID)[0] : "";
							$getActivityName = $methods->array_search_by_key($_SESSION["path_activity"][$getPathID], 'alias', $params['activity'], 'name');
							$getActivityName = ($getActivityName) ? array_keys($getActivityName)[0] : "";
							$getActivityTokens = $methods->array_search_by_key($_SESSION["path_activity"][$getPathID], 'alias', $params['activity'], 'tokens');
							$getActivityTokens = ($getActivityTokens) ? array_keys($getActivityTokens)[0] : "";

							$dataArguments['activity_id'] = $getActivityID;
							$getWorkFlowTokens = explode(",",$getActivityTokens);
						}
					}else{
						$getActivityID = $_SESSION["path_activity"][$getPathID][0]['id'];
						$getActivityTokens = $_SESSION["path_activity"][$getPathID][0]['tokens'];
					}
					
					if(isset($params['unit']) && $params['unit'] != ""){
						$sessionUnits = json_decode(json_encode($_SESSION["codebook"]["unit"]),true);
						$paramUnitKey = $methods->array_recursive_search_key_map((string)$params['unit'], $sessionUnits);
						$dataArguments['unit'] = $paramUnitKey[0]; //(int)$sessionUnits[$paramUnitKey[0]]['id'];
						$reportUnit = $paramUnitKey[0];
					}elseif($_SESSION["userrole"] > 3){ // FILTER LISTINGS BY ITS UNIT/BRANCH
						$dataArguments['unit'] = $_SESSION["unit"];
					}
					
					$workflowTokenAuth = ($getWorkFlowTokens) ? array_intersect($getWorkFlowTokens,$_SESSION["tokens"]) : [];
					if(sizeof($workflowTokenAuth) > 0 || $_SESSION["userrole"] < 3) $methods->viewRestricted = false; // TO RESTRICT EXPECT SUPER-ADMN AND ADMIN
					
					if($_SESSION['userrole'] >= 3){
					  $extraArguments = "AND unit IN ({$_SESSION['unit']}) ";
					  $sessionUnitArray = explode(",", $_SESSION["unit"]);
					  $sessionUnitAreaID = $_SESSION['codebook']['unit'][$_SESSION['unit_code']]->meta_option;
					  // if($_SESSION['userrole'] >= 4) { // MANAGER AND CLERK
						// $dataArguments['activity_id'] = $getPathID;
					  // }
					}
					if(isset($_GET["date"]) && $_GET["date"] != ""){
					    $hasDateRange = true;
						$getDates = explode(" - ",$_GET["date"]);
						$methods->dateFrom = $getDates[0]." 00:00:00";
						$methods->dateTo = $getDates[1]." 23:59:59";
					}else{
					  $firstDayMonth = new DateTime('first day of this month');
					  $methods->dateFrom = $firstDayMonth->format('Y-m-d')." 00:00:00";
					}
					// PLEASE CHECK DOUBLE QUERY ON WORKFLOW
					$stmtUserListings = ['schema'=>Info::DB_SYSTEMS,'table'=>'workflow','arguments'=>['id'=>$getPathID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','path','name','description','value','status']];
					$getUserListings = $methods->selectDB($stmtUserListings);
					$workflowAlias = array_keys($getUserListings);
					$workflowValuesArray = explode(",",$getUserListings[$workflowAlias[0]]->value);
					$recordFields = $recordJoinSQL = $recordFieldSQL = []; 
					foreach($workflowValuesArray as $rowValue){
					  $fieldValue = explode(":",$rowValue);
					  $fieldRows[$fieldValue[1]] = $fieldValue[0];
					  $recordFields[$fieldValue[0]][] = $fieldValue[1];
					  $recordFieldSQL[] = "{$fieldValue[0]}.{$fieldValue[1]}";
					  $recordJoinSQL[$fieldValue[0]] = "JOIN ".Info::PREFIX_SCHEMA.Info::DB_DATA.".path_{$getPathID}_{$fieldValue[0]} AS {$fieldValue[0]} ON ({$fieldValue[0]}.data_id = data_queue.data_id)";
					}
					// NEW STATEMENT START
					$stmtDataQueueFields = ['data_queue.data_id','data_queue.id','data_queue.date_created','data_queue.activity_id','data_queue.unit','data_queue.user'];
					$stmtDataRecord['fields'] = array_merge($stmtDataQueueFields,$recordFieldSQL);
					
					$stmtDataRecord['table'] = 'data_queue AS data_queue';
					$stmtDataRecord['join'] = implode(" ",$recordJoinSQL);
					
					if($getPathID != 1 || $hasDateRange) $stmtDataRecord['between'] = ["data_queue.date_created",$methods->dateFrom,$methods->dateTo]; // SHOW ALL RECORDS AS DEFAULT
					
					$stmtDataRecord['arguments'] = ["data_queue.path_id"=>$getPathID];
					$stmtDataRecord['arguments'] += $dataArguments;
					
					if($isActivityCancelled){
						unset($stmtDataRecord['between']);
						unset($stmtDataRecord['arguments']['activity_id']);
						$extraArguments = "AND data_queue.activity_id IS NULL";
					}else{
						$extraArguments .= "AND data_queue.activity_id IS NOT NULL";
					}
					
					$stmtDataRecord['extra'] = $extraArguments.' ORDER BY data_queue.date_created DESC';
					$stmtDataRecord += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
					$getDataRecord = $methods->selectDB($stmtDataRecord);
					// NEW STATEMENT END

					$getFieldRows = implode("','",array_keys($fieldRows));
					$stmtFieldCodebook = ["schema"=>Info::DB_SYSTEMS,"table"=>"fields","arguments"=>["field_type"=>1],"pdoFetch"=>PDO::FETCH_COLUMN,"extra"=>"AND alias IN ('{$getFieldRows}')","fields"=>["alias"]];
					$getFieldCodebook = $methods->selectDB($stmtFieldCodebook);

					$dataParams['schema'] = Info::DB_DATA;//json_decode(json_encode($fieldRows));
					$dataParams['workflow'] = $params['alias'];
					$dataParams['path_id'] = $getPathID;
					$dataParams['row_fields'] = $fieldRows;//json_decode(json_encode($fieldRows));
					$dataParams['codebook_fields'] = $getFieldCodebook;
					$dataParams['data_lists'] = $getDataRecord;
					//var_dump($getActivity[$);
					$listHead = "<tr class='uppercase'><th class='alignCenter date'>DATE</th><th class='alignCenter idNum'>REF NUM</th>";
					
					$headerFields = array_keys($fieldRows);
					switch($getPathID){ // UPDATING THE HEADER COLUMN NAME
						case "8":
							$headerFields[2] = "penalty";
							$headerFields[3] = "interest";
							$headerFields[4] = "principal";
						break;
					}
					foreach($headerFields as $dataField){
						$thClass = "class=''";
						if(in_array($dataField, array_keys($fieldTitles))){
							$theadValue = $fieldTitles[$dataField];
						}else{
							$theadValue = str_replace('_',' ',$dataField);
						}
						if(in_array($dataField, $fieldAlignRight)) $thClass = "class='alignRight'";
						$listHead .= "<th id='{$dataField}' {$thClass}>{$theadValue}</th>";
					}
					//if($getPathID == 7) $statusCol = "<th>Type</th>";
					$listHead .= "<th>Branch/Unit</th><th>Activity</th>{$statusCol}</tr>"; //<th width="4%" class="no-padding no-sort actionBtn"><button id="addPost" type="button" class="btn"><i class="fa fa-plus"></i></button></th>
					$titleHeader = "{$getActivityName} <span class='subTitle'>|| RESULTS: ".$methods->timeDateFormat($methods->dateFrom,'')." - ".$methods->timeDateFormat($methods->dateTo,'').".</span>";

					$parentMenu = $params["alias"];
					$subMenu = $parentMenu."_".$thisView;
					$currentPage = (isset($params['activity']) && $params['activity'] != "") ? $params['activity'] : "view_all";

					$contentPage = 'methods-records.php';

					break;
				  case 'posts':
					$canView = $isDataTable = false;
					$getActivity = $getDataQueue = [];
					$theID = $postUnit = $isDone = 0;
					$stepsWizard = $paramsID = ''; $stepCnt = 1;
					$parentMenu = $params["alias"];
					$subMenu = $parentMenu."_create";
					//$popupType = 'viewclients';

					$stmtPath = ['schema'=>Info::DB_SYSTEMS,'table'=>'path','arguments'=>['alias'=>$params['alias'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','description','groups']];
					$getPath = $methods->selectDB($stmtPath);
					$getPathID = $getPath[$params['alias']]->id;
					$getPathGroup = $getPath[$params['alias']]->groups;

					$stmtPathActivity = ['schema'=>Info::DB_SYSTEMS,'table'=>'activity','arguments'=>['path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>["id","alias","name","description","tokens","value"]];
					$getPathActivity = $methods->selectDB($stmtPathActivity);
					$postActivity = key($getPathActivity);
					$activityTokens = (int)$getPathActivity[$postActivity]->tokens;
					
					$dataQueueFields = array_merge(['data_id'],$methods->getTableFields(['table'=>'data_queue','exclude'=>['data_id','path_id'],'schema'=>Info::DB_DATA]));

					if(isset($params['id']) && $params['id'] != ""){
						$extraArguments = "";
						$paramsID = $params['id'];
						if($_SESSION['userrole'] >= 3){
							$extraArguments = "AND unit IN ({$_SESSION['unit']}) ";
							// if($_SESSION['userrole'] >= 4) { // MANAGER AND CLERK
								// $dataArguments['activity_id'] = $getPathID;
							// }
						}
						$stmtDataQueue = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['path_id'=>$getPathID,'data_id'=>$paramsID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>$extraArguments.'ORDER BY id ASC','fields'=>$dataQueueFields];
						$getDataQueue = $methods->selectDB($stmtDataQueue);
						$theID = key($getDataQueue);
						if(!EMPTY($getDataQueue[$theID]->id) && $getDataQueue[$theID]->id > 0){
							$methods->globalQueueData = $getDataQueue[$theID];
							$methods->globalQueueData->data_id = $theID;
							$methods->globalQueueData->id = (int)$methods->globalQueueData->id; // post_id
							$methods->globalQueueData->activity_id = (int)$methods->globalQueueData->activity_id;
							$methods->globalQueueData->unit = (int)$methods->globalQueueData->unit;
							
							$postUnit = $methods->globalQueueData->unit;
							$postActivity = $methods->globalQueueData->activity_id;
							$activityTokens = ($postActivity) ? (int)$getPathActivity[$postActivity]->tokens : "";
						}
						
						$subMenu = $parentMenu."_lists";
					}else{
						$methods->globalQueueData = new stdClass();
						foreach($dataQueueFields as $queueField){ // SET QUEUE DATA FIELDS TO EMPTY
							$methods->globalQueueData->$queueField = "";
						}
						$methods->globalQueueData->id = 0;
						$methods->globalQueueData->activity_id = $postActivity;
					}
					
					$methods->postActivityToken = $activityTokens;
					//$methods->postDataUnit = (isset($getDataQueue[$theID]->unit)) ? $getDataQueue[$theID]->unit : "";
					//$activityFields = array_merge(['path'],$methods->getTableFields(['table'=>'activity','exclude'=>['date','status','user'],'schema'=>Info::DB_SYSTEMS]));
					//$stmtActivity = ['schema'=>Info::DB_SYSTEMS,'table'=>'activity','arguments'=>['id'=>$postActivity,'path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$activityFields];
					//$getActivity = $methods->selectDB($stmtActivity);
					$getGroupFields = array_merge(['alias'],$methods->getTableFields(['table'=>'groups','exclude'=>['alias','date','status','user'],'schema'=>Info::DB_SYSTEMS]));
					$stmtGroupFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'groups','arguments'=>['alias'=>$params['alias'],'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$getGroupFields];
					$postGroupFields = $methods->selectDB($stmtGroupFields);
					$postGroupElements = $postGroupFields[$params['alias']]->elements;

					$currentPage = ($postActivity && $paramsID > 0) ? $getPathActivity[$postActivity]->alias : "";

					$contentPage = 'methods-posts.php';
					break;
				  case 'messages':
					$contentPage = 'methods-messages.php';
					break;
				  
				  case 'users':
					$isDataTable = true; $action = $popupType;
					//$selections = inputFieldBox('select','type','type','','','','','','');
					$viewTitle = $thisView;
					$popupType = 'view'.$viewTitle;
					$titleHeader = $_SESSION["module_type"][$module_type].' '.$viewTitle.' Listings <small class="capitalize">'.$thisView.' settings</small>';
					//$contentPage = 'methods-lists.php';
					$theTable=$thisType;
					
					switch($_SESSION["userrole"]){
						case 1: case 2: // SUPER-ADMIN / ADMIN
							$stmtArguments = ['id >'=>1];
							$stmtData['extra'] = "ORDER BY id ASC";
						break;
						case 3: case 4: // AREA MANAGERS / BRANCH MANAGERS
							$stmtArguments = ['id >'=>2,'status >'=>1];
							$userUnits = ($_SESSION["userrole"] == 3) ? $_SESSION['unit_code'].",".$_SESSION["unit"] : $_SESSION["unit"];
							$stmtData['extra'] = "AND unit IN ({$userUnits}) ORDER BY id ASC";
						break;
						default: 
							$stmtArguments = ['id'=>$_SESSION["userID"]];
							$stmtData['extra'] = "ORDER BY id ASC";
						break;
					}
					
					$fieldRows = ["id","username","firstname","lastname","position","description","unit","role","tokens","status"];
					$stmtData += ['schema'=>Info::DB_SYSTEMS,'table'=>$params['view'],'arguments'=>$stmtArguments,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>$fieldRows];
					$getData = $methods->selectDB($stmtData);

					$contentPage = 'methods-admin.php';
					break;
				  case 'info': case 'system_settings': case 'upload_members': case 'upload_loan_types': case 'settings_lam':
				  //var_dump($_SESSION["tokens"]);
					if($thisView == 'info' && !in_array(11,$_SESSION["tokens"])) $methods->authError = true; // GENERAL SETTINGS
					$contentPage = 'methods-page.php';
				  break;
				case 'settings':
					$popupType = 'view'.$viewType;
					$action = $viewType."Form";
					if(!in_array(10,$_SESSION["tokens"])) $methods->authError = true;
					$contentPage = 'methods-settings.php';
					break;
				}
				break;
				
				case 'systems':
					$isSystems = true;
					if(isset($object)){
					  $theTable=$theObject=$object;
					}
					//$parentMenu = $parentMenu;
					$subMenu = $thisType."_".$thisView;
					$currentPage = $params['object'];
					$titleHeader = '<h2>'.$module_type.' '.$params['object'].' Listings <small class="capitalize">'.$params['object'].' settings</small></h2>';
					$contentPage = 'methods-lists.php';
					switch($thisView){ //view
					  default: //case 'options':
						$thLists = $formCol = '';
						$isDataTable=true;$action=$popupType;
						//$selections = inputFieldBox('select','type','type','','','','','','');
						$popupType = 'view'.$theTable;

						break;
					  case 'edit':
						$contentPage = 'methods-'.$thisView.'.php';
						break;
					  case 'elements':
						$contentPage = 'methods-tree.php';
						break;
					  case 'forms':
						switch($object){
						  default:
							$contentPage = 'methods-forms.php';
							break;
						}
						break;
					}
				break;
				
				default:
					$popupType = "view".$thisView;
					$contentPage = "methods-{$thisType}.php";
					
				break;
				
			} // endswitch on module type
			
		}else{ // IF HAS PAGE ERROR
			$methods->authError = true;
		}
		
		?>

