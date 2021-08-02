<?php
/**
 *
 * @page: USERS
 */

$linkType = $editColumn = $popUpBtn = "";

$hasObjectColumn = ["procedure","groups","path","activity","workflow"];
if(in_array($objectAlias,$hasObjectColumn)){
    switch($objectAlias){
        case "path":
            $linkType = "elements";
            break;
        case "groups":
            $linkType = "forms";
            break;
        default:
            $linkType = "edit";
            break;
    }
}

$dataParams['schema'] = Info::DB_SYSTEMS;//json_decode(json_encode($fieldRows));
if($linkType){
    $dataParams['link_type'] = $linkType;
    $editColumn = "<th class='no-padding status groups'>Edit</th>";
}
$outputFields = array_diff($fieldRows,["tokens"]);
//$dataParams['head_lists'] = $fieldRows;
if($_SESSION['userrole'] >= 2) unset($outputFields[9]); // STATUS FIELD
$dataParams['row_fields'] = $outputFields;//json_decode(json_encode($fieldRows));
$dataParams['data_lists'] = $getData;
$cntField = 0;
foreach($outputFields as $dataField){
	if($dataField == "status"){
		$theadValue = "&nbsp;";
	}else{
		$theadValue = str_replace('_',' ',$dataField);
	}
    $listHead .= '<th class="'.$dataField.'">'.$theadValue.'</th>';
	$cntField++;
}
$statusCol = "<th width='4%' class='no-padding no-sort actionBtn'>&nbsp;</th>";
if($_SESSION["userrole"] <= 2) $popUpBtn = '<button type="button" onclick="getPopup(this,\''.$thisView.'\',\''.$popupType.'\')" value="0" class="btn" data-toggle="modal" data-target=".'.$popupType.'"><i class="fa fa-plus"></i></button>';
$listHead .= $editColumn."<th width='4%' class='no-padding no-sort actionBtn'>{$popUpBtn}</th></tr>";

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
  if($selections)echo '<form action="'.HOST_SELF_URL.queryURL('').'" method="get" class="filterBox"><span class="fa fa-filter"></span><input type="hidden" id="module_type" name="module_type" value="'.$_GET['module_type'].'" /><input type="hidden" id="view" name="view" value="'.$_GET['view'].'" />'.inputFieldBox('text','filter_date_in','filter_date_in',$filterDateRange,'Reservation Date','','fa-calendar','','').$selections.'<button class="btn filter" type="submit">SUBMIT</button>'.$boxBtnRoom.'</form>';//<i class="fa fa-binoculars"></i>?>

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
