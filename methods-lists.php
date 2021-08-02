<?php
$linkType = $editColumn = "";
$fieldRows = ["alias","id","name","alias","description","status"];
$hasObjectColumn = ["procedure","groups","path","activity","workflow"];
if(in_array($objectAlias,$hasObjectColumn)){
    switch($objectAlias){
        case "path":
			$fieldRows = ["alias","id","name","alias","description","groups","status"];
            $linkType = "elements";
			break;
        case "groups":
			$fieldRows = ["alias","id","name","alias","description","elements","status"];
            $linkType = "forms";
            break;
        default:
			if($objectAlias == "activity" || $objectAlias == "workflow") $fieldRows = ["alias","id","name","alias","description","path","tokens","status"];
            $linkType = "edit";
            break;
    }
}

if($objectAlias == "fields"){
	$fieldRows = ["alias","id","name","alias","description","field_type","status"];
}

$stmtData = ['schema'=>Info::DB_SYSTEMS,'table'=>$params['object'],'arguments'=>['id >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$fieldRows];
$getData = $methods->selectDB($stmtData);

$dataParams['schema'] = Info::DB_SYSTEMS;//json_decode(json_encode($fieldRows));
if($linkType){
    $dataParams['link_type'] = $linkType;
    $editColumn = "<th class='no-padding status groups'>Edit</th>";
}
$dataParams['row_fields'] = $fieldRows;//json_decode(json_encode($fieldRows));
$dataParams['head_lists'] = $fieldRows;
$dataParams['data_lists'] = $getData;
$cntField = 0;
foreach($fieldRows as $dataField){
    if($cntField > 0){
		$theadValue = str_replace('_',' ',$dataField);
		if($dataField == "status"){
			$theadValue = "&nbsp;";
		}
		$listHead .= '<th class="'.$dataField.'">'.$theadValue.'</th>';
	}
	$cntField++;
}
$popUpBtn = "<button type='button' onclick='getPopup(this,\"{$params['typeList']}\",\"{$popupType}\")' value='0' class='btn' data-toggle='modal' data-target='.{$popupType}'><i class='fa fa-plus'></i></button>";
$listHead .= $editColumn.'<th width="4%" class="no-padding no-sort actionBtn">'.$popUpBtn.'</th></tr>';

// $sessionGroups = json_decode(json_encode($_SESSION["groups"]),true);
// $ddd = $methods->array_recursive_search_key_map("7",$sessionGroups);
//var_dump($_SESSION["path"]);
if(true){//$isDataTable && hasRights($theTable,$_SESSION["userid"],1)
?>
	<div class="container">
		<div class="x_title">
			<h2><?php echo $titleHeader?></h2>
		</div>
		<div id="pageElement">
			<div class="x_content">

			<!--<p class="text-muted font-13 m-b-30"></p>-->
			<?php
			$boxBtnRoom = '<button type="button" onclick="getPopup(this,\'rooms\',\'queryBox\')" value="0" class="btn popBoxBtn" data-toggle="modal" data-target=".'.$popupType.'"><i class="fa fa-external-link"></i></button>';
			if($selections)echo '<form action="'.HOST_SELF_URL.queryURL('').'" method="get" class="filterBox"><span class="fa fa-filter"></span><input type="hidden" id="module_type" name="module_type" value="'.$_GET['module_type'].'" /><input type="hidden" id="view" name="view" value="'.$_GET['view'].'" />'.inputFieldBox('text','filter_date_in','filter_date_in',$filterDateRange,'Reservation Date','','fa-calendar','','').$selections.'<button class="btn filter" type="submit">SUBMIT</button>'.$boxBtnRoom.'</form>';//<i class="fa fa-binoculars"></i>
			?>

			<table id="<?php echo $dataTableID;?>" width="100%" class="<?php echo $popupType.'Table'?> table table-striped table-bordered">
			  <thead>
				<?php echo $listHead?>
			  </thead>
			  <tbody>
				<?php
				if(true){ //$arrayLists&&hasRights($theTable,$_SESSION["userid"],3)
				   // foreach($arrayLists as $theID){
					// echo getListings($theTable,$theID,$popupType,'');
					 echo $methods->getTableListings($dataParams);
				   // }
				}
				?>
			  </tbody>
			</table>
			</div>
		</div>
	</div>
<?php
}else{
    echo pageError('403');
}

?>
</div>
