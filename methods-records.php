<?php
/**
* @author	Amatz Fox - ZERO32
* @since	September 21, 2018
* @type		Data Records listings
*/

// $dataParams = $fieldRows = [];
// $params = $_GET;
// $params['pageName'] = implode("-",[$params['view'],$params['alias']]);
// $methods = new Method($params);
//$_SESSION["notifications"][9]["status"] = 1;

// $areaCode = $_SESSION["codebook"]["unit"][$_SESSION["unit"]]->meta_option;
// $unitArea = $_SESSION["codebook"]["area_code"][$areaCode]->meta_value;

//var_dump($getDataRecord);
if($methods->viewRestricted || (!in_array($getActivityTokens, $_SESSION["tokens"]) && !$isActivityCancelled && !$isActivityViewAll)) throw new \Exception($methods->errorPage('403'), 1);

$unitArea = [];
$unitAreaListings = $_SESSION['unit_area'];
foreach($unitAreaListings as $metaID => $metaValue){
	$unitArea[$metaValue->meta_parent][] = $metaID;
}
ksort($unitArea); // SORTING BY AREA

$optionParent = "<option></option>";//(false && isset($params['unit']) && $params['unit'] != "") ? "<option meta='{$params['unit']}'>{$_SESSION['codebook']['unit'][$paramUnitKey[0]]->meta_value}</option>" : "<option></option>";
foreach($unitArea as $areaID => $unitLists){
	$optionChild = "";$areaActive = "";
	if($_SESSION["userrole"] < 3 || ($_SESSION["userrole"] == 3 && $sessionUnitAreaID == $areaID)){ //$areaID == (int)$_SESSION["unit_area"]
		//$optionParent .= "";
		foreach($unitLists as $unitID){
			$active = "";
			if(isset($params['unit']) && $params['unit'] === $_SESSION['codebook']['unit'][$unitID]->meta_option){
				$areaActive = "selected";
				$active = "selected";
			}
			$optionChild .= "<option {$active} meta='{$_SESSION['codebook']['unit'][$unitID]->meta_option}'>{$_SESSION['codebook']['unit'][$unitID]->meta_value}</option>";
		}
		$parentName = (!EMPTY($_SESSION['codebook']['division'][$areaID]->meta_value)) ? $_SESSION['codebook']['division'][$areaID]->meta_value : "";
		$optionParent .= "<optgroup {$areaActive} label='{$parentName}'>{$optionChild}</optgroup>";
	}
}
//$filterBranch = "<select id='selectBranch' name='selectBranch' class='select2_group form-control option-branch' onchange=''>{$optionParent}</select>";
$filterBranch = $methods->optionsBranchUnit(["name"=>"selectBranch","unit"=>$reportUnit]);

//var_dump();
//$session = $methods->sessionAuth(32);
?>
<div class="container">
    <div class="x_title">
        <h2><?php echo $titleHeader?></h2>
    </div>
	<div id="pageElement">
  <div class="x_content">
  <?php //var_dump($recordFields,$fieldRows,$getDataRecord) //$stmtData,$_SESSION['codebook']['unit']?>
	<div class="no-padding date-calendar filter-box floatRight">
	<?php if($_SESSION["userrole"] <= 3){ ?>
		<div class="col-md-5 col-xs-5 titleDropdown btn-group no-padding dropdown-listings">
			<?php echo $filterBranch?>
		</div>
	<?php } ?>
		<div class="col-md-<?php ($_SESSION["userrole"] <= 3) ? print 7 : print 8 ?> col-xs-<?php ($_SESSION["userrole"] <= 3) ? print 7 : print 8 ?> no-padding type-date edit">
			<input title="Report Date" value="<?php //echo $this->method->dateRange?>" type="text" id="inputDate" name="reportDate" placeholder="yyyy-mm-dd" class="form-control date-picker">
			<button type="submit" title="Submit Date as of" value="VIEW" id="reportDate" name="date" class="btn bgOrange btn-date floatRight bold" onclick="submitURL(this)"><i class="fa fa-calendar"></i></button>
		</div>
	</div>
  <table id="<?php echo $dataTableID;?>" width="100%" class="table table-striped table-bordered">
      <thead>
        <?php echo $listHead?>
      </thead>
      <tbody id="record-lists">
		<?php
		echo $methods->getTableListings($dataParams);
		//$listRecords = getDataListings($thisPath[0]['id'],$thisData,$thisColumns);
		//echo $listRecords;
        ?>
      </tbody>
    </table>
  </div>
  </div>
</div>
<script src="<?php echo Info::URL?>/vendors/moment/min/moment.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<script>
function submitURL(me){ // DATE REPORT SUBMIT
	varURL = me.id;
	varParam = me.name;
	urlValue = $("[name="+varURL+"]").val();
	window.location = replaceParams([varParam,urlValue]);
//urlParams = window.location.search;//new URLSearchParams(window.location.search);
//window.location.href = "<?php //echo Info::URL?>/"+urlParams+"&"+varParam+"="+urlValue;
//alert(window.location);
}

function replaceParams(sParam){ // DATE REPORT SUBMIT
    
	varParam = "";
	if(sParam[1]){
		varParam = sParam[0] + "=" + sParam[1];
	}
	//alert(varParam);
	var url = window.location.href.split('?')[0]+'?';
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');
		if (sParameterName[0] && sParameterName[1] && sParameterName[0] != sParam[0]) {
			url = url + sParameterName[0] + '=' + sParameterName[1] + '&'
		}
	}
	url = url + varParam;
	//return url.substring(0,url.length-1);
	return url;
}

$("[name=selectBranch]").on('change',function () {
    meta = $("[name=selectBranch]").find("option:selected").attr("meta");
	window.location = replaceParams(['unit',meta]);
	//alert(meta);
});
	
$("button#viewPost").on('click',function () {
	theID = $(this).val();
	window.location.href='<?php echo Info::URL;?>/methods?module_type=<?php echo $params['module_type']?>&view=posts&alias=<?php echo $params['alias']?>&id='+theID;
});

function viewRecords(paramReplace,trID){ // DATE REPORT SUBMIT
	if(trID != ""){ //
		url = window.location.href;
		getHref = new URL(url);
		getHost = getHref['origin']+getHref['pathname'];
		getParams = getHref['search'];
		splitParams = getParams.split('&');
		<?php if(isset($_GET["date"])) echo "splitParams.splice(-1);"?> // REMOVE THE DATE PARAMS OR LAST ARRAY VALUE
		<?php if(isset($_GET["activity"])) echo "splitParams.splice(-1);"?>

		newParam = splitParams.join('&'); // JOIN PARAM STRING
		hrefURL = getHost+newParam;

		href = new URL(hrefURL);
		replaceParam = JSON.parse(paramReplace);
		$.each(replaceParam, function( index, value ) {
			href.searchParams.set(index, value);
		});

		parameters = "&id="+trID;
		pageURL = href+parameters;
		//console.log(pageURL);
		window.location = pageURL;
	}else{
//			var sPageURL = decodeURIComponent(window.location.search.substring(1));
//			sURLVariables = sPageURL.split('&');
//			sURLVariables.splice(-1); // REMOVE THE DATE PARAMS
//			newParam = sURLVariables.join('&');
//			console.log(newParam);
	}
}

$("table tbody#record-lists tr").on('click',function () {
	$("html").addClass("loading");
	trID = $(this).attr('id');
	paramReplace = '{"view":"posts"}';
	viewRecords(paramReplace,trID);
});

//$(".daterangepicker button.applyBtn.btn.btn-success").on('submit',function () {
//	alert("fox");
//	$("button#reportDate").trigger( "click" );
//});

$("button#addPost").on('click',function () {
	window.location.href='<?php echo Info::URL;?>/methods?module_type=<?php echo $params['module_type']?>&view=posts&alias=<?php echo $params['alias']?>';
	
});

$('input[name=reportDate]').daterangepicker({
	singleDatePicker: false,
	opens: 'left',
	//minDate: '01/01/2012',
	startDate: '<?php echo $methods->dateFrom?>',
	endDate: '<?php echo $methods->dateTo?>',
	maxDate: '<?php echo $methods->getTime("date")?>',
	
	autoUpdateInput: true,<?php //(isset($_GET['date']) && $_GET['date'] != "") ? print "false": print "true"?>
	calender_style: 'picker_4',
	locale: {
		format: 'YYYY/MM/DD'
	}
}, function (start, end, label) {
	console.log(start.toISOString(), end.toISOString(), label);
});

$(document).ready(function() {
	//$("body").toggleClass("nav-md nav-sm");
	//$(".main_menu_side .child_menu ").css( "display", "none" );
	$('.select2_group').select2({placeholder: 'Filter by Branch',allowClear: true});
});

</script>
