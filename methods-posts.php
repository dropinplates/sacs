<?php
/**
* @author	Amatz Fox - ZERO32
* @since	September 21, 2018
* @type		Create Data on Custom Modules
*/


//$requestAPI = new Request();

//var_dump($getPathActivity);
//throw new \Exception($methods->errorPage('403'), 1);
// $stmtJoinUnitDivision = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook AS codebook','arguments'=>['codebook.meta_id'=>$getDataQueue[$theID]->unit,'meta_key'=>'unit'],'join'=>'JOIN '.Info::PREFIX_SCHEMA.Info::DB_SYSTEMS.'.codemeta AS codemeta ON codemeta.meta_value = codebook.id AND codemeta.key_value = "unit" AND codemeta.key_parent = "division" ','pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['codemeta.meta_parent']];
// $getJoinUnitDivision = $methods->selectDB($stmtJoinUnitDivision);
// $unitCode = str_pad($getJoinUnitDivision[0], 2, '0', STR_PAD_LEFT)."-".str_pad($getDataQueue[$theID]->unit, 2, '0', STR_PAD_LEFT);
//$methods->globalDataValue	$getDataQueue[$theID]->unit;

//var_dump($getUsername);

if((isset($params['id']) && $methods->globalQueueData->id < 1)){
	throw new \Exception($methods->errorPage('404'), 1);
}
$activityAction = "createData";
switch($params['alias']){//
	default:
		$isViewActivity = explode(",", Info::COMPLETED_ACTIVITIES);
		$alertActivities = [];
		$numActivities = count($getPathActivity);
		$setStepActivities = 100 / $numActivities;
		if($getPathID == 6) $setStepActivities = 50; // SHARE CAPITAL
		$wizardStepWidth = round($setStepActivities)."%";
	break;
}

if(in_array($postActivity, $isViewActivity)) $methods->pageAction = "view"; 
if(!$activityTokens || !$postActivity){
	$methods->pageAction = "view"; 
	$postActivity = "";
}

$getPostElements = $methods->setGroupElements(["elements"=>$postGroupElements,"pathID"=>$getPathID,"dataID"=>$paramsID]);
$getElementForm = $methods->elementForm($getPostElements);
$methods->getElementType = "1";
$checkElements = $methods->getElements($params['alias']);
//$memberID = $dataLoanNum = $checkElements['membership_information']['member_id'];

//Method::EMPTY_VAL;
//$GLOBALS['post_id'] = $postID;
//$dataLoanType = $checkElements['loan_details']['loan_types'];
// var_dump($getExtrasValue);
if(($activityTokens && $postActivity) && in_array($activityTokens,$_SESSION["tokens"]) || ($theID > 0 && $getDataQueue[$theID]->user == $_SESSION["userID"])){ //|| $_SESSION["userrole"] <= 2 || in_array($getDataQueue[$theID]->unit,explode(",",$_SESSION['unit']))
	$canView = true;
}else{
	if($postActivity) throw new \Exception($methods->errorPage('403'), 1);
}

if(!$getElementForm) throw new \Exception($methods->errorPage('408'), 1); //  || sizeof($loanTypes) < 1

$getRole = $_SESSION['codebook']['role'];

$getPostUnit = ($_SESSION["userrole"] == 3 || $postUnit > 0) ? $postUnit : $_SESSION["unit"];


if($theID > 0){ // FOR DOCUMENT PLACEHOLDERS
	$_SESSION['data'][$params['alias']] = ['unit'=>$getDataQueue[$theID]->unit];
	$_SESSION['data'][$params['alias']] += $methods->globalDataValue;
}

?>
<!-- jsPDF -->
<link rel="stylesheet" href="<?php echo Info::URL?>/plugins/jspdf/examples/libs/pure-min.css">
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pure/0.6.0/grids-responsive-min.css">-->
<link rel="stylesheet" href="<?php echo Info::URL?>/plugins/jspdf/examples/libs/grids-responsive-min.css">
<!-- jsPDF -->
<link href="<?php echo Info::URL?>/vendors/dropzone/dist/dropzone.css" rel="stylesheet"><!-- Font Awesome -->
<script src='<?php echo Info::URL?>/vendors/dropzone/dist/dropzone.js'></script>
<script src="<?php echo Info::URL?>/vendors/moment/min/moment.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<div class="container">
    <div class="col-md-12 col-sm-12 col-xs-12 alignCenter">
        <div class="x_title">
            <h2><?php echo $getPath[$params['alias']]->name?></h2><span class="subTitle">|| <?php ($postActivity) ? print $getPathActivity[$postActivity]->description : print $getPath[$params['alias']]->description; ?></span>
        </div>
    </div>
	<?php
	//var_dump($thisParams);
	//phpinfo();
	// $keyReplace = Info::value("eval_key_strict");
	//echo $promissoryParagraph;//	$getDataQueue[$theID]->data_logs	print_r("'".implode("','",$dataQueueFields)."'");	//$methods->globalDataValue	$getDataQueue[$theID]->unit; ,$_SESSION["codebook"]["unit"]?>
    <div id="wizard" class="form_wizard wizard_horizontal">

        <ul class="wizard_steps anchor <?php $theID > 0 ? print 'update': print 'create';?>">
        <?php
		$stepCnt = 1;
        foreach($getPathActivity as $activityID => $activityDetail){
			$isDone = 0;
			$wizardSelected = 'disabled';
			if($activityID <= $postActivity){
				$isDone = 1;
				$wizardSelected = ' done';
			}
			if($postActivity == $activityID) $wizardSelected .= ' selected';
            $stepsWizard .= '<li style="width:'.$wizardStepWidth.'" id="'.$activityDetail->alias.'"><a class="'.$wizardSelected.'" rel="1" isdone="'.$isDone.'"><span class="step_no">'.$stepCnt.'</span><span class="step_descr"><small>'.$activityDetail->name.'</small></span></a></li>';
			$stepCnt++;
        }
		
        echo $stepsWizard;
        ?>
        </ul>
    <div id="transDetail" class="transPostDetail"><?php $theID > 0 ? print '<span class="transRow"><span class="subTitle">Trans Date:</span><span>'.$methods->timeDateFormat($getDataQueue[$theID]->date_created,'').'</span></span><span class="transRow"><span class="subTitle">Trans Num:</span><span>'.$methods->formatValue(['prefix'=>$getPath[$params['alias']]->id,'id'=>$theID],"app_id").'</span></span><span class="transRow"><span class="subTitle">Branch/Unit:</span><span>'.$_SESSION['codebook']['unit'][$getDataQueue[$theID]->unit]->meta_value.'</span></span>': print '';?></div>
    </div>
	
    <div class="col-sm-12">
	   <div id="pageElement" class="row boxPadding">
		<form name="<?php echo $params['alias']?>" id="create_<?php echo $params['alias']?>" data-toggle="validator" class="form-label-left input_mask postForm" novalidate>
			<input type="hidden" name="action" id="action" value="createData" />
			<input type="hidden" name="table" id="table" value="data_queue" />
			<?php /* ?><input type="hidden" name="queue_id" id="queue_id" value="<?php ($theID > 0) ? print $getDataQueue[$theID]->id : print 0 ?>" /><?php */ ?>
			<input type="hidden" name="path_alias" id="path_alias" value="<?php echo $params['alias'] ?>" />
			<input type="hidden" name="post_id" id="post_id" value="<?php echo (isset($methods->globalQueueData->id) && $methods->globalQueueData->id != "") ? $methods->globalQueueData->id : 0 ?>" />
			<input type="hidden" name="data_id" id="data_id" value="<?php echo (isset($methods->globalQueueData->data_id) && $methods->globalQueueData->data_id != "") ? $methods->globalQueueData->data_id : 0 ?>" />
			<input type="hidden" name="path_id" id="path_id" value="<?php echo $getPath[$params['alias']]->id ?>" />
			<input type="hidden" name="post_activity_id" id="post_activity_id" value="<?php echo $postActivity ?>" />
			<?php
				echo $getElementForm;//.$theForm;
				//var_dump($methods->hasActivityBtn);
				//if((in_array($activityTokens,$_SESSION["tokens"]) || ($_SESSION["userrole"] < 2 && in_array($postActivity,$loanApprovalActivities))) || $methods->hasActivityBtn){
			?>
			<div class="submitBottomBox alignCenter" id="path_<?php echo $params['alias']?>">
				<div id="successBox" class="col-md-12 col-sm-12 col-xs-12 no-padding">
				<button type="button" class="btn btn-success left" id="<?php echo $params['alias']?>" activity="0" name="listData" onclick="location.href='<?php echo Info::URL.'/methods?module_type='.$thisType.'&view=lists&alias='.$alias;?>'"><i class="fa fa-chevron-left"></i>Back</button>
				<button type="button" class="btn btn-success left iconRight" id="<?php echo $params['alias']?>" activity="0" name="createData" onclick="location.href='<?php echo Info::URL.'/methods?module_type='.$thisType.'&view='.$view.'&alias='.$alias;?>'"><i class="fa fa-plus"></i><?php echo $getPath[$params['alias']]->name?></button>
					<?php
					$activityValue = ($postActivity) ? $getPathActivity[$postActivity]->value : "";
					if($postActivity && $activityValue){
						//if(($params['alias'] == "loan_application" && $postActivity > 1) || $_SESSION['userrole'] < 2) echo "<button type='button' id='loanSchedulePDF' class='generatePDF btn btn-success noIcon right ".($postActivity <> 6 ? 'hide':'')."' onclick='generateSchedulePDF(0)'>Amortization Schedule</button>";
						//echo '<button type="button" class="btn btn-success right btnCancel uppercase" id="'.$_GET['alias'].'" activity="8" name="SubmitData" onclick="submitData('.$activityAction.',this)"><i class="fa fa-times"></i>Cancel Loan Application</button>';
						echo $methods->setProcedure($activityValue);
					}else{
						echo '<span style="color:#FFF">Learning is Endless, Carpe Diem!</span>';
					}
					?>
				</div>
				<?php
					if(isset($methods->globalQueueData->id) && $methods->globalQueueData->id > 0) echo "<button type='button' onclick='loadStorage(this);' action='getElementData' block='.modal.viewLogs .modal-content .modal-body' meta='post_logs' id='view_logs' value='".$methods->globalQueueData->id."' class='btn btnIcon right no-padding' data-toggle='modal' data-target='.viewLogs'><i class='fa fa-list-ol no-margin no-border'></i></button>"; // VIEW LOGS FOR ADMIN ONLY// VIEW LOGS FOR ADMIN ONLY
				?>
			</div>
			<?php
				//}
			?>
		</form>
	</div>
    </div>
</div>
<div id='voucher_details' class='hide'></div>

<div id="htmlPDF_panel" class="hide">
	<object id="jspdf-container" type="application/pdf"></object>
</div>
<div id="htmlPDFx">
	<!-- PDF DATA HERE -->
</div>
<script>if (!window.Promise) window.Promise = {prototype: null}; // Needed for jspdf IE support</script>

<script src="<?php echo Info::URL;?>/js/convertNumber.js"></script>
<!-- jsPDF scripts -->
<script src='<?php echo Info::URL?>/dist/jspdf.min.js'></script>
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>-->
<script src="<?php echo Info::URL?>/plugins/jspdf/examples/libs/jspdf.debug.js"></script>
<script src="<?php echo Info::URL?>/plugins/jspdf/dist/jspdf.plugin.autotable.js"></script>
<script src="<?php echo Info::URL?>/plugins/jspdf/examples/examples.js"></script>
<script src="<?php echo Info::URL?>/js/generateVoucher.js"></script>
<!-- jsPDF scripts -->

<script>

<?php
if($getPathID == 7){ // LOANS
?>
setAmortizationSchedule(0,0); // TO SET THE data_loan_schedule_summary
$('input#amount, input#loan_interest, input#loan_terms, input#ability_pay, [name=\"loan_summary[amortization_type]\"], [name=\"loan_summary[interest_type]\"], input#loan_granted').on('change', function() {
	 computeLoanSummary(this);
});

$('input#chart_amount, select#charts_id').on('change', function() {
	 computeDeductions(this);
});

function computeDeductions(me){
	output = 0;
	$('#postDeductionBox input').each(function() { // CONVERT VALUES TO AMOUNT
		amount = $(this).val();
		elementID = $(this).attr('element_id');
		chartValue = $('[name=\"deductions['+elementID+'][charts_id]\"]').val();
		
		if(amount && chartValue){
		    output = output + parseFloat(amount);   
		}
		console.log(output);
		// theValue = parseFloat(convertAmount(getValue));
		// $(this).val(theValue.toFixed(decimal));
	});
	loan_balance = parseFloat($('[name=\"balance_loan_principal\"]').val());
	if(loan_balance > 0){
	    output = output + loan_balance;
	}
	if(output > 0 || me == "loan_balance"){
		$('[name=input_total_deduction]').val(output);
		$('#loan_summary_box #total_deduction span#value').text(convertCurrency(output.toFixed(2)));
		if(me) computeLoanSummary('');
	}
}

function computeLoanSummary(me){
	inputLoanID = loanType = willingnessPay = loanAttachments = inputType = '';
    netAmount = totalDeduction = loanGranted = thisLoanGranted = lessDeduction = 0;
    inputName = me.name;
    if(inputName == null){
        inputName = 'auto';
    }else{
		inputType = $(me).attr('type');
	}
	loanAmount = parseFloat($('[name=\"loans_details[amount]\"]').val());
	loanTerms = parseFloat($('[name=\"loans_details[loan_terms]\"]').val());
	interestRate = parseFloat($('[name=\"loans_details[loan_interest]\"]').val());
	
	loanAbilityPay = parseFloat($('[name=\"members_credit_rating[ability_pay]\"]').val());
	amortizationType = parseFloat($('[name=\"loan_summary[amortization_type]\"]').val());

	interestRate = interestRate / 12;
	interestRatePercent = interestRate / 100;
	if(inputName == 'loan_summary[loan_granted]'){
		thisLoanGranted = $(me).val();
		loanAmount = parseFloat(thisLoanGranted);
	}else{
		thisLoanGranted = loanAmount;
	}
	getLoanAmount = [loanAmount,thisLoanGranted];//[loanAmount,loanAbilityPay];
	if(inputName == 'auto' || inputType != 'number'){
		loanGranted = parseFloat($('[name=\"loan_summary[loan_granted]\"]').val());
	}else{
		loanGranted = Math.min.apply(Math, getLoanAmount);
	}
	
	interestPerAnnum = interestRatePercent * loanTerms;
	totalInterest = (loanGranted * (interestPerAnnum));// * loanYrs;
	
	totalInterestTerm = (loanGranted * interestRatePercent) * loanTerms;
	principalAmortization = loanGranted / loanTerms;
	interestAmortization = totalInterest / loanTerms;

	totalAmortization = (loanGranted + totalInterest) / loanTerms; // TOTAL AMORTIZATION
	if(amortizationType === 3){ // ANNUITY
		loanFutureValue = (loanGranted / loanTerms) * ((Math.pow(1 + interestRatePercent, loanTerms) - 1) / interestRatePercent); // GETTING FUTURE VALUE
		interestPeriod = (loanFutureValue - loanGranted) / loanTerms;
		//totalAmortization = loanFutureValue / loanTerms; // MONTHLY AMORTIZATION
	}else{ // STRAIGHT-LINE AND DIMMINISHING FIRST PAYMENT INTEREST
		//totalAmortization = (loanGranted + totalInterest) / loanTerms; // TOTAL AMORTIZATION
		interestPeriod = totalInterestTerm / loanTerms;
	}
	monthlyAmortization = principalAmortization + interestPeriod;
	
	// GETTING TOTAL INTEREST THRU LOAN SCHEDULE SUMMARY
	// getScheduleSummary = $('#data_loan_schedule_summary').text();
	// if(getScheduleSummary != '' && inputName == 'auto'){
		// getScheduleSummary = JSON.parse(getScheduleSummary);
		// totalInterest = parseFloat(convertAmount(getScheduleSummary.total_interest));
	// }
	
	lessDeduction = parseFloat($('[name=input_total_deduction]').val());
	totalDeduction = totalDeduction + lessDeduction;
	
	interestType = $('[name=\"loan_summary[interest_type]\"]').val();
	getScheduleSummary = $("#data_loan_schedule_summary").text();
	if(getScheduleSummary != ""){
		
		getScheduleSummary = JSON.parse(getScheduleSummary);
		totalInterest = convertAmount(getScheduleSummary.total_interest);
		if(interestType == 1) totalDeduction = totalDeduction + totalInterest; // PRE-PAID
	}
	netAmount = loanGranted - totalDeduction;
	
	//$('#loan_summary_box #monthly_interest span#value').text(convertCurrency(interestPeriod.toFixed(2)));
	$('#loan_summary_box #total_deduction span#value').text(convertCurrency(lessDeduction.toFixed(2)));
	$('#loan_summary_box #total_interest_amount span#value').text(convertCurrency(totalInterest.toFixed(2)));
	$('#loan_summary_box #net_amount #value').text(convertCurrency(netAmount.toFixed(2)));
	
	$('[name=input_total_deduction]').val(lessDeduction);
	$('[name=input_total_interest_amount]').val(totalInterest);
	$('[name=input_net_amount]').val(netAmount);
	
	$('input[name="loan_summary[loan_granted]"]').val(loanGranted);
	//console.log(loanGranted);
	checkMandatoryFields(['amount','loan_types','payment_form','payment_mode','loan_interest','loan_terms','amortization_type','interest_type','loan_granted'],'[name=SubmitData]',{'name':'onclick','value':'submitData("createData",this)'});
}

function headRows() {
	return [{num: ' ', due_date: 'DUE DATE', principal: 'PRINCIPAL', interest: 'INTEREST', total_due: 'TOTAL DUE', balance: 'BALANCE'}];
}

function bodyRows(rowCount) {
	rowCount = rowCount || 10;
	let body = [];
	for (var j = 1; j <= rowCount; j++) {
		body.push({
			num: j,
			due_date: '2020-02-03',
			principal: '833.33',
			interest: '300.00',
			cbu: '100.00',
			savings: '100.00',
			total_due: '1,133.33',
			balance: '12,466.67'
		});
	}
	return body;
}

function setAmortizationSchedule(shouldDownload,showPDF){
	amortization = [];
	action = "getAmortizationSchedule";
	data_id = $("#data_id").val();
	formData = {'action':action,'data_id':data_id};
	jQuery.ajax({
		url: "storage.php",
		data:formData,
		type: "POST",
		success:function(data){
			$("#data_loan_schedule").html(data.data_loan_schedule);
			$("#data_loan_schedule_summary").html(data.data_loan_schedule_summary);
			console.log(data.output);
			if(showPDF > 0){
				getAmortizationSchedule(shouldDownload); // SHOWING THE POPUP
			}
		},
		error:function (data){console.log(data);}
	});
}

function generateSchedulePDF(shouldDownload){ // TO GET LOAN AMORTIZATION SCHEDULE AND SUMMARY
	setAmortizationSchedule(shouldDownload,1);
}

function getAmortizationSchedule(shouldDownload){ // SHOWING THE POPUP
	//$("#data_loan_schedule").html("");
	//$("#data_loan_schedule_summary").html("");
	
	getLoanSchedule = $("#data_loan_schedule").html(); 
	if(getLoanSchedule != ""){
		getLoanSchedule = JSON.parse(getLoanSchedule);
	}
	getScheduleSummary = $("#data_loan_schedule_summary").html();
	if(getScheduleSummary != ""){
		getScheduleSummary = JSON.parse(getScheduleSummary);
	}
	
	// amortizationSchedule = loanAmortizationSchedule["data_loan_schedule"];
	// amortizationSummary = loanAmortizationSchedule["data_loan_schedule_summary"];
	//console.log(loanAmortizationSchedule);
	
	var exitBtn = document.createElement("BUTTON");   // Create a <button> element
	exitBtn.innerHTML = "×";
	exitBtn.setAttribute('id','exitBtn');
	exitBtn.setAttribute('onclick','hideHtmlIframePDF(\'htmlPDF_panel\')');
	exitBtn.setAttribute('class','pdfExitBtn');
	
	var iframe = document.createElement('iframe');
	iframe.setAttribute('style','position:fixed;right:0; top:0; bottom:0; height:100%; width:100%; padding:32px 20%;background-color:rgba(0, 0, 0, 0.88);z-index:99;');
	
	
	
	//getLoanSchedule = amortizationSchedule;
	//getScheduleSummary = amortizationSummary;
	//console.log(getScheduleSummary);
	getLoanSummary = [[{content: "", colSpan: 2, styles: {fillColor: 164}},{content: convertCurrency(getScheduleSummary.total_principal), styles: {halign: 'right',fillColor: 72}},{content: convertCurrency(getScheduleSummary.total_interest), styles: {halign: 'right',fillColor: 72}}, {content: convertCurrency(getScheduleSummary.total_amortization), styles: {halign: 'right',fillColor: 72}}, {content: "", styles: {halign: 'right',fillColor: 164}}, {content: '', colSpan: 2}]];

	let doc = new jsPDF();
	var totalPagesExp = "{total_pages_count_string}";
	margin = {
		  top: 32,
		  bottom: 12,
		  left: 6,
		  right: 6,
		  width: 550
		};
		
	doc.setFontSize(10);
	nameTitle = "St. Alphonsus Catholic School";
	nameTitleWidth = doc.getTextWidth(nameTitle);
	doc.setFontSize(8);
	nameBranch = "Employees Multi-Purpose Cooperative";
	nameBranchWidth = doc.getTextWidth(nameBranch);
	doc.setFontSize(6);
	nameAddress = "G.Y. Dela Serna St, Poblacion, Lapu-Lapu City, 6015 Cebu";
	nameAddressWidth = doc.getTextWidth(nameAddress);
	//var dim = doc.getTextDimensions('Text');
	
	// jsPDF 1.4+ uses getWidth, <1.4 uses .width
	var pageSize = doc.internal.pageSize;
	var pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
	
	var pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
	
	//var text = doc.splitTextToSize("fox", pageWidth - 35, {});
	//doc.text(text, 14, 30);
	
	// GETTING LOAN DETAILS
	clientName = $("[name=members_name]").val();
	clientID = $("[name=\"loans_details[mem_id]\"]").val();
	loanAmount = parseFloat($("[name=\"loan_summary[loan_granted]\"]").val());
	loanAmount = convertCurrency(loanAmount.toFixed(2));
	loanPurpose = <?php ($methods->pageAction == "view") ? print '$("[field=\"loans_details[loan_purpose]\"]").text();' : print '$("[name=\"loans_details[loan_purpose]\"]").find("option:selected").text();' ?>
	loanType = <?php ($methods->pageAction == "view") ? print '$("[field=\"loans_details[loan_types]\"]").text();' : print '$("[name=\"loans_details[loan_types]\"]").find("option:selected").text();' ?>
	loanTerms = $("[name=\"loans_details[loan_terms]\"]").val();
	loanInterest = parseFloat($("[name=\"loans_details[loan_interest]\"]").val());// / 12;
	loanInterest = loanInterest.toFixed(2);
	loanPaymentMode = <?php ($methods->pageAction == "view") ? print '$("[field=\"loans_details[payment_mode]\"]").text();' : print '$("[name=\"loans_details[payment_mode]\"]").find("option:selected").text();' ?>
	basedOn = <?php ($methods->pageAction == "view") ? print '$("[field=\"loan_summary[amortization_type]\"]").text();' : print '$("select[name=\"loan_summary[amortization_type]\"]").find("option:selected").text();' ?>
	
	doc.setFontSize(8);
	doc.autoTable({ // CLIENT LOAN DETAILS
		head: [{label1: 'Label', name1: 'Name',label2: 'Label', name2: 'Name',label3: 'Label', name3: 'Name'}], 
		body: [
				{label1: 'Members Details', name1: clientName+' - '+clientID, label2: 'Date Created', name2: getScheduleSummary.date_approved, label3: 'Trans Num', name3: getScheduleSummary.reference_number},
				{label1: 'Loan Details', name1: loanType+': '+loanPurpose, label2: 'Loan Terms', name2: loanTerms+' months ('+loanInterest+'% per annum)', label3: 'Loan Amount', name3: loanAmount},
				{label1: 'Based On', name1: basedOn, label2: 'Maturity Date', name2: getScheduleSummary.maturity_date, label3: 'Payment Mode', name3: loanPaymentMode}
			], 
		//tableWidth: 'wrap',
		showHead: false,
		startY: 32,
		styles: {
			lineColor: 189,
			lineWidth: 0.2,
			overflow: 'ellipsize',
			cellWidth: 'wrap'
		},
		margin: {top: 0, left: margin.left,right: 0},
		columnStyles: {
			label1: {textColor: 255, fillColor: 112, lineColor: 255, cellWidth: 22, halign : 'right'},
			label2: {textColor: 255, fillColor: 112, lineColor: 255, cellWidth: 21, halign : 'right'},
			label3: {textColor: 255, fillColor: 112, lineColor: 255, cellWidth: 22, halign : 'right'},
			name1: {textColor: 64, fontSize: 8, fontStyle: 'bold', fillColor: 232, lineColor: 255, cellWidth: 58},
			name2: {textColor: 64, fontSize: 8, fontStyle: 'bold', fillColor: 232, lineColor: 255, cellWidth: 44},
			name3: {textColor: 64, fontSize: 8, fontStyle: 'bold', fillColor: 232, lineColor: 255, cellWidth: 29}
		},
		bodyStyles: {
			fontSize: 6,
			valign: 'middle'
		},
		alternateRowStyles: {
			fillColor: [255, 255, 255]
		},
	});
	
	doc.setLineCap(2);
	doc.line(margin.left, doc.autoTable.previous.finalY + 2, pageSize.width - margin.left, doc.autoTable.previous.finalY + 2); // horizontal line
	
	doc.autoTable({
		// head: headRows(),
		// body: bodyRows(40),
		head: [[' ', {content: 'DUE DATE', styles: {halign: 'center'}}, {content: 'PRINCIPAL', styles: {halign: 'right'}}, {content: 'INTEREST', styles: {halign: 'right'}}, {content: 'TOTAL DUE', styles: {halign: 'right'}}, {content: 'BALANCE', styles: {halign: 'right'}}]],
		body: getLoanSchedule,
		foot: getLoanSummary,
	
		didDrawPage: function (data) {
			// HEADER
			doc.setTextColor(40);
			doc.setFontStyle('normal');
			if (base64Img) {
				doc.addImage(base64Img, 'JPEG', margin.left, 3, 0, 16); //IMAGE SIZE: 216 x 65
			}
			doc.setFontSize(10);
			doc.text(nameTitle,  Math.trunc(pageSize.width) - Math.trunc(nameTitleWidth) - margin.right, 10);
			doc.setFontSize(8);
			doc.text(nameBranch,  Math.trunc(pageSize.width) - Math.trunc(nameBranchWidth) - margin.right, 14);
			doc.setFontSize(6);
			doc.text(nameAddress,  Math.trunc(pageSize.width) - Math.trunc(nameAddressWidth) - margin.right, 17);
			doc.setLineCap(2);
			doc.line(margin.left, margin.top - 12, pageSize.width - margin.left, margin.top - 12); // horizontal line
			doc.setFontSize(12);
			doc.setFontStyle('bold');
			documentTitleHeader = "LOAN AMORTIZATION SCHEDULE";
			documentTitleHeaderWidth = doc.getTextWidth(documentTitleHeader);
			doc.text(documentTitleHeader,  (Math.trunc(pageSize.width) / 2) - (Math.trunc(documentTitleHeaderWidth) / 2), 28);
			
			doc.setFontStyle('normal');
			// FOOTER PAGE DETAIL START
			doc.setFontSize(6);
			doc.setTextColor(40);
			//var totalPagesExp = doc.internal.getNumberOfPages();
			var str = "Page " + doc.internal.getNumberOfPages()
			// Total page number plugin only available in jspdf v1.0+
			if (typeof doc.putTotalPages === 'function') {
				str = str + " of " + totalPagesExp;
			}
			doc.text(str, margin.left + 2, pageHeight - 8);
			
			doc.setFontSize(6);
			footerPower = "Powered by: Management Tools Creation. All Rights Reserved";
			footerPowerWidth = doc.getTextWidth(footerPower);
			doc.text(footerPower,  Math.trunc(pageSize.width) - Math.trunc(footerPowerWidth) - 8, pageHeight - 8);
			
			doc.setLineCap(2);
			doc.line(margin.left, pageHeight - 14, pageSize.width - margin.left, pageHeight - 14); // horizontal line
			// FOOTER PAGE DETAIL END
		},
		startY: doc.autoTable.previous.finalY + 4,//margin.top + 22, // TABLE MARGIN
		margin: {top: margin.top, left: margin.left,right: margin.right},
		headStyles: {
			fillColor: [64, 64, 64],
			fontSize: 8
		},
		bodyStyles: {
			fillColor: 232,
			textColor: 32,
			fontSize: 8
		},
		alternateRowStyles: {
			fillColor: 255
		},
		
		footStyles: {
			fillColor: 98,
			fontSize: 9,
			textColor: 255,
			fontStyle: 'bold'
		},
		columnStyles: {
			0: {cellWidth: '8%', halign: 'right'},
			1: {halign: 'center'},
			2: {halign: 'right'},
			3: {halign: 'right'},
			4: {halign: 'right'},
			5: {halign: 'right', fontStyle: 'bold'}
			//principal: {halign: 'right'}
		},
		showFoot: 'lastPage'
		//tableWidth: 'wrap',
		// Default for all columns
		//styles: {overflow: 'ellipsize', cellWidth: 'wrap'},
		// Override the default above for the text column
		//columnStyles: {text: {cellWidth: 'auto'}}
		//showHead: 'firstPage'
	});
	//doc.text("Yeah", 14, doc.autoTable.previous.finalY + 10);
	doc.setFontSize(8);
	doc.text("Conforme:",  margin.left + 5, doc.autoTable.previous.finalY + 16);
	doc.setFontSize(10);
	borrowerName = clientName;
	borrowerNameWidth = doc.getTextWidth(borrowerName);
	doc.text(borrowerName,  margin.left + 16, doc.autoTable.previous.finalY + 30);
	doc.setLineCap(2);
	doc.setDrawColor(64, 64, 64);
	doc.line(margin.left + 5, doc.autoTable.previous.finalY + 32, borrowerNameWidth + 32, doc.autoTable.previous.finalY + 32); // horizontal line
	doc.setFontSize(7);
	doc.setFontType("italic");
	borrowerTitle = "Borrower's Name and Signature";
	borrowerTitleWidth = doc.getTextWidth(borrowerTitle);
	borrowerLineWidth = (margin.left + 16) + ((borrowerNameWidth - borrowerTitleWidth) / 2);
	doc.text(borrowerTitle,  borrowerLineWidth, doc.autoTable.previous.finalY + 35);
	// PREPARED BY START
	doc.setFontType("normal");
	doc.setFontSize(8);
	doc.text("Prepared:",  margin.left + ((borrowerLineWidth * 3) + 6) + 5, doc.autoTable.previous.finalY + 16);
	doc.setFontSize(10);
	preparedName = "<?php echo $_SESSION["displayname"]?>";
	preparedNameWidth = doc.getTextWidth(preparedName);
	doc.text(preparedName,  margin.left + ((borrowerLineWidth * 3) + 6) + 16, doc.autoTable.previous.finalY + 30);
	doc.setLineCap(2);
	doc.setDrawColor(64, 64, 64);
	doc.line(margin.left + ((borrowerLineWidth * 3) + 6) + 5, doc.autoTable.previous.finalY + 32, preparedNameWidth + ((borrowerLineWidth * 3) + 6) + 32, doc.autoTable.previous.finalY + 32); // horizontal line
	doc.setFontSize(7);
	doc.setFontType("italic");
	preparedTitle = "<?php echo $_SESSION["position"].': '.$_SESSION['codebook']['unit'][$_SESSION[$unitName]]->meta_value;?>";
	preparedTitleWidth = doc.getTextWidth(preparedTitle);
	doc.text(preparedTitle,  (margin.left + ((borrowerLineWidth * 3) + 6) + 16) + ((preparedNameWidth - preparedTitleWidth) / 2), doc.autoTable.previous.finalY + 35);
	
	if (typeof doc.putTotalPages === 'function') {
		doc.putTotalPages(totalPagesExp); // SHOWING THE TOTAL NUMBER OF PAGES
	}
	
	if(shouldDownload){
		doc.save('table.pdf');
	}else{
		//document.getElementById("output").src = doc.output('datauristring');
		$("#htmlPDF_panel").removeClass("hide");
		document.getElementById("htmlPDF_panel").appendChild(exitBtn);
		document.getElementById("htmlPDF_panel").appendChild(iframe);
		//document.getElementById("jspdf-container").data = doc.output('datauristring');
		iframe.src = doc.output('datauristring');
	}

} // END GENERATE AMORTIZATION SCHEDULE

var base64Img, coinBase64Img;
imgToBase64('<?php echo Info::URL?>/images/sacs-name-logo2.png', function(base64) {
	base64Img = base64;
	imgToBase64('<?php echo Info::URL?>/plugins/jspdf/examples/coin.png', function(base64) {
		coinBase64Img = base64;
		//update();
	});
});

<?php
}
?>

// You could either use a function similar to this or pre convert an image with for example http://dopiaza.org/tools/datauri
// https://stackoverflow.com/a/20285053/827047
function imgToBase64(src, callback) {
	var outputFormat = src.substr(-3) === 'png' ? 'image/png' : 'image/jpeg';
	var img = new Image();
	img.crossOrigin = 'Anonymous';
	img.onload = function() {
		var canvas = document.createElement('CANVAS');
		var ctx = canvas.getContext('2d');
		var dataURL;
		canvas.height = this.naturalHeight;
		canvas.width = this.naturalWidth;
		ctx.drawImage(this, 0, 0);
		dataURL = canvas.toDataURL(outputFormat);
		callback(dataURL);
	};
	img.src = src;
	if (img.complete || img.complete === undefined) {
		img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
		img.src = src;
	}
}

function hideHtmlIframePDF(htmlPanel){
	$("#"+htmlPanel).addClass("hide").html("");
}


function submitData(action,me){
	activity_id = "";
	proceed = true;
	inputFieldName = me.name;
    inputFieldID = me.id;
    if(inputFieldName == "submitOption"){ // CREATE EXTRAS
		loaderClass = "html";
		$(loaderClass).addClass("loading");
		alias = $('#'+action+' input#meta_key').val();
		if(alias != "willingness"){
			$('#'+action+' input[type=text][meta='+alias+'_pay].number').each(function() { // CONVERT VALUES TO AMOUNT
				getValue = $(this).val();
				theValue = parseFloat(convertAmount(getValue));
				$(this).val(theValue.toFixed(decimal));
			});
		}
        formName = action;
    }else{
		loaderClass = "html";
		$(loaderClass).addClass("loading");
        formDeductionString = comma = "";
        formName = inputFieldID;
		
    }
    extraParam = '&user=<?php echo $_SESSION['userID']?>&unit=<?php echo $getPostUnit?>'; //&activity_id='+activity_id+'
    if(inputFieldName == 'SaveData' || inputFieldName == 'SubmitData' || inputFieldName == 'EditData'){
        activity_id = $(me).attr("activity");
        extraParam += '&activity_id='+activity_id;
		//alert(extraParam);
    }
    //alert(activity_id);
	if(activity_id == ""){ // CANCEL TRANSACTION
		proceed = confirm("Processing this transaction is irreversible... Click OK to continue!");
	}
	if(proceed){
		formData = $("form[name="+formName+"]").serialize()+extraParam;
		//jsonData = JSON.parse(formData);
		//jsonData = JSON.stringify(jsonData);
		//alert(formData);
		$.ajax({
		url: "storage.php",
		data:formData,
		type: "POST",
		success:function(data){
			//alert(data.dot_com_loan_id);
			console.log(data);
			$(loaderClass).removeClass("loading");
			//$('#alert').html(data.alert).fadeIn(400).delay(5000).fadeOut(400);

			$("form[name="+formName+"] input[name=loan_id]").val(data.loan_id);
			//$('input#data').val(data.data);
		   // $('input#activityID').val(data.activityID);
			//console.log(jsonData);
			//alert(inputFieldName+" - "+activity_id+" - "+data.post_activity_id);
			if(inputFieldName == 'EditData' || (inputFieldName == 'SubmitData' && data.activity_id > 1) && (data.activity_id != data.post_activity_id) || activity_id == ""){
				window.location.href='<?php echo Info::URL.'/methods.php?module_type='.$thisType.'&view='.$view.'&alias='.$alias.'&id=';?>'+data.data_id;
			}else{
				if(data.data_id > 0 && data.post_id > 0){
					$("form[name="+formName+"] input#data_id").val(data.data_id);
					$("form[name="+formName+"] input#post_id").val(data.post_id);
					// loanGranted = parseFloat($("[name=\"loan_summary[loan_granted]\"]").val());
					// if(loanGranted > 0){
						// $("form[name="+formName+"] button#loanSchedulePDF.generatePDF").removeClass("hide");
					// }
					//$('button.loanApprovalMemorandum').removeClass('hide');
				}

				$("form[name="+formName+"] [name=theID]").val(data.theID);
				//alert(action+" "+data.meta_key);
				if(action == "createExtras"){
					ratingScore = data.score;
					switch(data.meta_key){ // TO UPDATE THE EXTRAS VALUE
						case "willingness_pay":
							$("[name=\"members_credit_rating["+data.meta_key+"]\"]").find("option[value='"+ratingScore+"']").attr("selected","selected");
							scoreAdjectival = $("select[name=\"members_credit_rating["+data.meta_key+"]\"]").find("option:selected").text();
							$("[name=\"members_credit_rating["+data.meta_key+"]\"] + .select2-container .select2-selection__rendered").text(scoreAdjectival);
							getScore = $('.modal-footer #attainedScore span#resultScore').text();
							$('.modal-footer #attainedScore span#resultScore').text(getScore+" | "+scoreAdjectival);
						break;
						default:
							$("[name=\"members_credit_rating["+data.meta_key+"]\"]").val(ratingScore);
						break;
					}
					//computeSummary('compute_summary');
					$( "button[name='SaveData']" ).trigger( "click" );
					//submitData('createData',["name":""]);
				}
				if(false){ // data.api_status < 1
					$("button[name=SubmitData]").addClass('disabled').attr('onclick','');
				}
				switch(data.path_id){
					case 7: // LOANS
						$('button#addWillingness, button#addAbility, button#addCollateral').val(data.data_id); //.removeClass('btnLock')
						//$("#data_loan_schedule").text(data.client_loan_schedule);
						//$("#data_loan_schedule_summary").text(data.client_schedule_summary);
						$('#loans_details.x_panel, #members_credit_rating.x_panel, #loan_summary.x_panel, .attachmentBox, #postDeductionBox').removeClass('lock');
						setAmortizationSchedule(0,0);
						computeLoanSummary('');
					break;
				}
				
			}
			// NOTIFICATION ALERT START
			result = 'WARNING';
			result_type = 'warning';
			message = 'Please check data and try again!';
			hide_alert = true;
			if(data.result){ // NO ERROR
				result = data.result;
				result_type = result;
				message = data.message;
				hide_alert = true;
			}
			if(data.result == "error"){
				hide_alert = true;
				message = data.message;
			}
			
			new PNotify({
				title: 'Action: '+result,
				text: message,
				type: result_type,
				hide: hide_alert,
				styling: 'bootstrap3',
				delay: 3200,
				addclass:"notify-success"
			});
			// NOTIFICATION ALERT END
		},
			error:function (data){alert("WARNING: Error Found!");console.log(data);}
		});
	}else{
		$(loaderClass).removeClass("loading");
	}
}


class Rectangle {
  constructor(height, width) {
    this.height = height;
    this.width = width;
  }
}

$(document).ready(function() {
//     $('input[type=number].form-control.amount').each(function() {
//         theValue = $(this).val();
//         theValue = parseFloat(theValue);
//         thisValue = convertCurrency(theValue.toFixed(2));
//         $(this).val(thisValue);
//     });
	
	// $("select#accounting_code").each(function() {
       // $(this).select2({allowClear: true});
    // });
	webshims.setOptions('forms-ext', {replaceUI: 'auto',types: 'number'});webshims.polyfill('forms forms-ext');
	
	
	/*
	requiredFields = ["loan_details_loan_amount","loan_details_loan_interest_percentage","loan_details_payment_terms","loan_details_client_name","loan_details_loan_types","loan_details_loan_purpose","loan_details_payment_mode"];
	$.each(requiredFields, function( index, value ) {
		$("[name="+value+"]").attr("required","required").addClass("required");
	});
	
	validator.message.empty = '<i class="fa fa-exclamation"></i>';

	$('form') // VALIDATE/CHECK REQUIRED FIELDS
        .on('blur', 'input[required], input.optional, select.required', validator.checkField)
        .on('change', 'select.required', validator.checkField)
        .on('keypress', 'input[required][pattern]', validator.keypress);
	*/
	
	<?php if($getPathID == 7 && $methods->globalQueueData->id > 0) echo "computeDeductions('');computeLoanSummary('');"; // LOANS?>
	<?php if($getPathID == 6 && $methods->globalQueueData->id > 0) echo "setShareCapitalSummary();"; // SHARE CAPITAL?>
	
});


Dropzone.options.createAttachment = {
	paramName: "file",
	dictDefaultMessage: "<b>Attachments:</b> Drop files / Click here and upload",
	url: "<?php echo Info::URL?>/storage.php?upload_type=attachments&alias=<?php echo $_GET['alias']?>&data_id=<?php echo $paramsID?>",
    maxFilesize: 500,
    init: function() {
      // this.on("uploadprogress", function(file, progress) {
		  // alert(file['height']);
        // console.log("File progress", file);
      // });
	  this.on("success", function(file, response) {
		 // fileName = jQuery.parseJSON(theMeta);
		//alert(file['id']); 
		//$(this).append(file['name']);
        console.log(response);
		fileValues = [];
		$(".attachmentBox #createAttachment.dropzone .dz-preview .dz-details > .dz-filename span").each(function() {
			fileName = $(this).text();
			//responseData = jQuery.parseJSON(response);
			//fileName = responseData.file_name;
			fileValues.push(fileName);
		});
		//alert(fileValues);
		$("input#attachments").val(fileValues);
      });
	  
    }
}

$("button#clearAttachments").on('click',function () { //[name=loan_details_loan_interest_percentage]
	$(this).hide();
	attachmentBoxName = $(this).attr("meta");
	attachmentBoxField = $(this).attr("field");
	$("#"+attachmentBoxName).removeClass("dz-started");
    $("#"+attachmentBoxName+" .dz-preview.dz-complete").html("");
	$("[name=\""+attachmentBoxField+"\"]").val("");
});

$(".fileAttachment").on('click',function () { // VIEW FILE ATTACHMENTS BY POPUP
    //viewData('path_1_loan_summary', 48);
	getFile = $(this).attr("file");
	getDir = $(this).attr("meta-box");
	if(false){
		fileFormat = '<img src="<?php echo Info::URL?>/files/'+getDir+'/'+getFile+'" style="width:100%;height:auto" />';
	}else{
		fileFormat = '<embed src="<?php echo Info::URL?>/files/'+getDir+'/'+getFile+'" frameborder="0" width="100%" height="auto" style="min-height:540px">';
	}
	$(".modal.viewattachments > .modal-dialog").attr("view-type",getDir);
	$(".modal-content .modal-title").text("File Attachments / Documents: "+getFile);
	$(".modal-content .modal-body").html(fileFormat);
});

function viewData(table,theID){
	action = 'viewData';
	formData = {'action':action,'table':table,'theID':theID};
	//alert(formData);
	jQuery.ajax({
		url: "storage.php",
		data:formData,
		type: "POST",
		success:function(data){
			console.log(data);
		},
		error:function (data){console.log(data);}
	});
}

// function generateLoanSchedule(){
	// formData = {'action':'createLoanSchedule'};
	// //alert(formData);
	// jQuery.ajax({
		// url: "storage.php",
		// data:formData,
		// type: "POST",
		// success:function(data){
			// console.log(data);
		// },
		// error:function (data){console.log(data);}
	// });
// }
</script>

<div class="modal fade viewattachments" role="dialog" aria-hidden="true">
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title" id="myModalLabel"></h4>
    </div>
    <div class="modal-body"></div>
  </div>
</div>
</div>

<div class="modal fade viewextras" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title" id="myModalLabel">Modal title</h4>
			</div>
			<div class="modal-body<?php if($params['alias'] == "loan_application" && $methods->globalQueueData->activity_id > 1) echo " lock" ?>">
			</div>
			<div class="modal-footer">
				<div id="success"></div>
				<?php if($methods->pageAction != "view"){?>
					<div id="attainedScore" class="left mid bold"><span id="resultTitle" class="large"></span><span id="resultScore" class="xlarge">32,100.00</span></div>
					<button type="button" class="btn btn-default" id="closeBtn" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="saveBtn">Save changes</button>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<?php
	if($_SESSION['userrole'] < 3 && !EMPTY($getDataQueue[$theID]->data_logs)){ // VIEW LOGS: ADMIN ONLY ?>
	<div class="modal fade viewLogs" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
		  <div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title" id="myModalLabel">Loan Application Logs</h4>
			</div>
			<div class="modal-body"></div>
		  </div>
		</div>
	</div>
<?php } ?>
