<?php
//include "functions-methods.php";
if($_REQUEST){
    foreach($_REQUEST as $key => $value){
        $$key = $value;
        $parameters[$key] = $value;
    }
}
$action=$theID=$thisType=$theView=$thisView=$theTable=$popupType=$titleHeader=$getLists=$arrayLists=$listHead=$viewTitle=$viewType='';
$theType=$viewID=0;
$error404=$isOptions=$isFilterQuery=$isFilterDate=$isSystems = false;
$isDataTable = true;
if(isset($module_type)){
	$stmtOptions = ['schema'=>Info::DB_NAME,'table'=>'options','arguments'=>['option_meta'=>'module_type','option_name'=>$module_type],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['option_name','id','option_name','option_value']];
	$getModuleType = $methods->selectDB($stmtOptions);
	$theType = $getModuleType[$module_type]->id;
	$thisType = $getModuleType[$module_type]->option_name;
}
if(isset($view)){
	$stmtMetaCode = ['schema'=>DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>$thisType,'meta_option'=>$view],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['meta_option','id','meta_id','meta_option','meta_value']];
	$getMetaCode = $methods->selectDB($stmtMetaCode);
    $viewID = $getMetaCode[$view]->id;
	$theView = $getMetaCode[$view]->meta_id;
	$thisView = $getMetaCode[$view]->meta_option;
}

if(isset($params['type']) && $params['type'] != ""){
	$viewType = $params['type'];
}

if(!$theType || !$theView){
	throw new \Exception($methods->errorPage('404'), 1);
}

if(isset($params['id'])) $theID = getMetaValue($thisType,array('id'=>$params['id']),'id'); // to check if ID exists
$selections = $popUpBtn = '';
$hasPopup=true;

switch($thisType){ //module_type
	case 'systems':
		$isSystems = true;
		if(isset($object)){
			$theTable=$theObject=$object;
		}
		$contentPage = 'methods-lists.php';
		switch($thisView){ //view
			default: //case 'options':
				$thLists = $formCol = '';
				$isDataTable=true;$hasPopup=true;$action=$popupType;
				//$selections = inputFieldBox('select','type','type','','','','','','');
				$viewTitle=getMetaValue('codebook',array('id'=>$viewID),'meta_value');
				$popupType='view'.$theTable;
				$titleHeader = '<span class="subTitle">'.$thisType.':</span> '.$object.' '.$viewTitle;

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
	
	case 'admin': //admin
		switch($thisView){ //view
            case 'lists':
                $isDataTable = true;
				$methods->viewRestricted = true;
                $extraArguments = "";$dataArguments = $getActivity = [];

				$stmtPath = ['schema'=>DB_SYSTEMS,'table'=>'path','arguments'=>['alias'=>$params['alias'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','description','groups']];
				$getPath = $methods->selectDB($stmtPath);
				$getPathID = $getPath[$params['alias']]->id;
				
				$stmtWorkflow = ['schema'=>DB_SYSTEMS,'table'=>'workflow','arguments'=>['path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['alias','id','path','tokens','description','value']];
				$getWorkflow = $methods->selectDB($stmtWorkflow);
				$getWorkFlowTokens = explode(",",$getWorkflow[$params['alias']."_module"]->tokens);

				if(isset($params['activity']) && $params['activity'] != ""){
					$stmtActivity = ['schema'=>DB_SYSTEMS,'table'=>'activity','arguments'=>['alias'=>$params['activity'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','tokens']];
					$getActivity = $methods->selectDB($stmtActivity);
					$dataArguments['activity_id'] = $getActivity[$params['activity']]->id;
					$getWorkFlowTokens = explode(",",$getActivity[$params['activity']]->tokens);
				}

				$workflowTokenAuth = array_intersect($getWorkFlowTokens,$_SESSION["tokens"]);
				if(sizeof($workflowTokenAuth) > 0 || $_SESSION["userrole"] < 3) $methods->viewRestricted = false; // TO RESTRICT EXPECT SUPER-ADMN AND ADMIN

				if($_SESSION['userrole'] >= 3){
					$extraArguments = "AND unit IN ({$_SESSION['unit']}) ";
				}
				
				$dataArguments['path_id'] = $getPathID;
				$stmtData = ['schema'=>DB_DATA,'table'=>'data_queue','arguments'=>$dataArguments,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>$extraArguments.'ORDER BY id ASC','fields'=>['data_id','date_created','activity_id','unit','user']];
				$getData = $methods->selectDB($stmtData);

				$stmtUserListings = ['schema'=>DB_SYSTEMS,'table'=>'workflow','arguments'=>['id'=>2],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','path','name','description','value','status']];
				$getUserListings = $methods->selectDB($stmtUserListings);
				$workflowValuesArray = explode(",",$getUserListings["loan_application_module"]->value);

				foreach($workflowValuesArray as $rowValue){
					$fieldValue = explode(":",$rowValue);
					$fieldRows[$fieldValue[1]] = $fieldValue[0];
				}

				$getFieldRows = implode("','",array_keys($fieldRows));
				$stmtFieldCodebook = ["schema"=>DB_SYSTEMS,"table"=>"fields","arguments"=>["field_type"=>1],"pdoFetch"=>PDO::FETCH_COLUMN,"extra"=>"AND alias IN ('{$getFieldRows}')","fields"=>["alias"]];
				$getFieldCodebook = $methods->selectDB($stmtFieldCodebook);

				$dataParams['schema'] = DB_DATA;//json_decode(json_encode($fieldRows));
				$dataParams['path_id'] = $getPathID;
				$dataParams['row_fields'] = $fieldRows;//json_decode(json_encode($fieldRows));
				$dataParams['codebook_fields'] = $getFieldCodebook;
				$dataParams['data_lists'] = $getData;

				$listHead = '<tr class="uppercase"><th class="alignCenter idNum">TXN NUM</th><th class="alignCenter">DATE</th>';

				foreach($fieldRows as $dataField => $dataGroup){
					$theadValue = str_replace('_',' ',$dataField);
					$listHead .= '<th>'.$theadValue.'</th>';
				}

				$listHead .= '<th>Activity Status</th><th>Unit</th><th width="4%" class="no-padding no-sort actionBtn"><button id="addPost" type="button" class="btn"><i class="fa fa-plus"></i></button></th></tr>';
				$titleHeader = "{$getPath[$params['alias']]->name} Listings <span class='subTitle'>|| See details by clicking the view icon.</span>";

				$parentMenu = $params["alias"];
				$subMenu = $parentMenu."_".$thisView;
				$currentPage = (isset($params['activity']) && $params['activity'] != "") ? $params['activity'] : "view_all";

				$contentPage = 'methods-records.php';
                
			break;
            case 'posts':
                $hasPopup=true;
				$canView = false;
				$valueActivity = $getActivity = $getDataQueue = [];
				$theID = $postID = $dataLoanID = $isDone = 0;
				$stepsWizard = $paramsID = ''; $stepCnt = 1;
				$parentMenu = $params["alias"];
				$subMenu = $parentMenu."_create";
				//$popupType = 'viewclients';
				
				$stmtPath = ['schema'=>DB_SYSTEMS,'table'=>'path','arguments'=>['alias'=>$params['alias'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','description','groups']];
				$getPath = $methods->selectDB($stmtPath);
				$getPathID = $getPath[$params['alias']]->id;
				$getPathGroup = $getPath[$params['alias']]->groups;
				
				$stmtPathActivity = ['schema'=>DB_SYSTEMS,'table'=>'activity','arguments'=>['path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>["id","name","tokens"]];
				$getPathActivity = $methods->selectDB($stmtPathActivity);
				$postActivity = key($getPathActivity);
				$activityTokens = $getPathActivity[$postActivity]->tokens;

				if(isset($params['id']) && $params['id'] != ""){
					$extraArguments = "";
					$paramsID = $params['id'];
					if($_SESSION['userrole'] >= 3) $extraArguments = "AND unit IN ({$_SESSION['unit']}) ";
					$dataQueueFields = array_merge(['data_id'],$methods->getTableFields(['table'=>'data_queue','exclude'=>['data_id','path_id'],'schema'=>DB_DATA]));
					$stmtDataQueue = ['schema'=>DB_DATA,'table'=>'data_queue','arguments'=>['path_id'=>$getPathID,'data_id'=>$paramsID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>$extraArguments.'ORDER BY id ASC','fields'=>$dataQueueFields];
					$getDataQueue = $methods->selectDB($stmtDataQueue);
					$theID = key($getDataQueue);
					$postID = $getDataQueue[$theID]->id;
					$postActivity = $getDataQueue[$theID]->activity_id;
					$subMenu = $parentMenu."_lists";
				}

				$methods->postID = $theID; // DATA_ID
				$methods->postActivityID = $postActivity;

				$activityFields = array_merge(['path'],$methods->getTableFields(['table'=>'activity','exclude'=>['date','status','user'],'schema'=>DB_SYSTEMS]));
				$stmtActivity = ['schema'=>DB_SYSTEMS,'table'=>'activity','arguments'=>['id'=>$postActivity,'path'=>$getPathID,'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$activityFields];
				$getActivity = $methods->selectDB($stmtActivity);
				$activityTokens = $getActivity[$getPathID]->tokens;
				$methods->postActivityToken = $activityTokens;
				
				$getGroupFields = array_merge(['alias'],$methods->getTableFields(['table'=>'groups','exclude'=>['alias','date','status','user'],'schema'=>DB_SYSTEMS]));
				$stmtGroupFields = ['schema'=>DB_SYSTEMS,'table'=>'groups','arguments'=>['alias'=>$params['alias'],'status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$getGroupFields];
				$postGroupFields = $methods->selectDB($stmtGroupFields);
				$postGroupElements = $postGroupFields[$params['alias']]->elements;


				$currentPage = ($paramsID > 0) ? $getActivity[$getPathID]->alias : "";
				
                $contentPage = 'methods-posts.php';
			break;
            case 'messages':
				$contentPage = 'methods-messages.php';
			break;
			case 'clients':
				$thLists = $formCol = '';
				$isDataTable=true;$hasPopup=true;$action=$popupType;
				$theTable = $thisView;
				//$selections = inputFieldBox('select','type','type','','','','','','');
				$viewTitle=getMetaValue('codebook',array('id'=>$viewID),'meta_value');
				$popupType='view'.$thisView;
				$subTitle = 'Administration '.$thisView.' settings';
				switch($thisView){
                    case 'clients': $thExcludeCols = getTableFields($thisView,['gender','birth_date','civil_status','city','country','nationality','date']); break;
                    
				}
				$tableFields = $thExcludeCols;//getTableFields($thisView,$thExcludeCols);
				foreach($tableFields as $theField){
					$thLists .= '<th class="uppercase '.$theField.'">'.$theField.'</th>';
				}
				$listHead = '<tr>'.$thLists;
				$getLists=getMetaValue($thisView,array('id>'=>1),'id');//,'status'=>1
					
				if(hasRights($thisView,$_SESSION["userID"],1))$popUpBtn = '<button type="button" onclick="getPopup(this,\''.$theTable.'\',\''.$popupType.'\')" value="0" class="btn" data-toggle="modal" data-target=".'.$popupType.'"><i class="fa fa-plus"></i></button>';
				$listHead .= $formCol.'<th width="4%" class="no-padding no-sort actionBtn">'.$popUpBtn.'</th></tr>';
				if($getLists)$arrayLists=explode(',',$getLists);
				$titleHeader=getMetaValue('options',array('id'=>$theType),'option_value').' '.$viewTitle.' Listings <small>'.$subTitle.'</small>';
				$contentPage = 'methods-lists.php';
			break;
			case 'users':
				$isDataTable=true;$hasPopup=true;$action=$popupType;
				//$selections = inputFieldBox('select','type','type','','','','','','');
				$viewTitle=getMetaValue('codebook',array('id'=>$viewID),'meta_value');
				$popupType='view'.$viewTitle;
				$titleHeader=getMetaValue('options',array('id'=>$theType),'option_value').' '.$viewTitle.' Listings <small class="capitalize">'.$thisView.' settings</small>';
				$contentPage = 'methods-lists.php';
				$theTable=$thisType;
				$fieldRows = ["username","firstname","lastname","position","description","unit","role"];
//				$listHead = '<tr><th class="alignCenter">Username</th><th>Full Name</th><th>Access Role</th><th>Position / Details</th><th>Contact</th><th>Remarks</th>';
//				if(hasRights($theTable,$_SESSION["userID"],1))$popUpBtn = '<button type="button" onclick="getPopup(this,\''.$theTable.'\',\''.$popupType.'\')" value="0" class="btn" data-toggle="modal" data-target=".'.$popupType.'"><i class="fa fa-plus"></i></button>';
//				$listHead .= '<th width="4%" class="no-padding no-sort">'.$popUpBtn.'</th></tr>';
//				$getLists=getMetaValue($theTable,array('id>'=>1),'id');
//				if($getLists)$arrayLists=explode(',',$getLists);
				$contentPage = 'methods-admin.php';
			break;
			case 'info': case 'upload_members':
				
				$contentPage = 'methods-page.php';
			break;
			case 'settings':
				$hasPopup=true;
				//$selections = inputFieldBox('select','type','type','','','','','','');
				$viewTitle=getMetaValue('codebook',array('id'=>$viewID),'meta_value');
				$popupType = 'view'.$viewType;
				$action = $viewType."Form";
				$titleHeader = getMetaValue('options',array('id'=>$theType),'option_value').' '.$viewTitle.' Listings <small class="capitalize">'.$thisView.' settings</small>';

				$contentPage = 'methods-settings.php';
				break;
		}
		
	break;
	
} // endswitch on module type
($isDataTable)?$dataTableID = PAGE_TYPE.'_'.$thisType:$dataTableID='';
?>
<!-- Font Awesome -->
<link href="<?php echo URL?>/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- NProgress -->
<link href="<?php echo URL?>/vendors/nprogress/nprogress.css" rel="stylesheet">
<?php if($isDataTable){?>
<!-- Datatables -->
<link href="<?php echo URL?>/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
<!-- Custom Theme Style -->
<?php } ?>
