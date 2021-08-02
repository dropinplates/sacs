<?php
global $getActivity;
global $theID;
global $getPathID;
global $optionLoanTypes;
global $optioninstallmentType;

$scriptJS = '';
$btnLock = ' btnLock';
if($theID > 0){
	$btnLock = '';
}

$willingnessBtn = "<button id='addWillingness' title='willingness' type='button' alias='willingness_pay' value='".$theID."' class='btn postPopupBtn extra".$btnLock."' data-toggle='modal' data-target='.viewextras'><i class='fa fa-upload'></i></button>";
$abilityBtn = "<button id='addAbility' title='ability' type='button' alias='ability_pay' value='".$theID."' class='btn postPopupBtn extra".$btnLock."' data-toggle='modal' data-target='.viewextras'><i class='fa fa-upload'></i></button>";
$collateralBtn = "<button id='addCollateral' title='collateral' type='button' alias='collateral_pay' value='".$theID."' class='btn postPopupBtn extra".$btnLock."' data-toggle='modal' data-target='.viewextras'><i class='fa fa-upload'></i></button>";
$scriptJS = '
	$("[name=members_credit_rating_ability_pay], [name=loan_details_loan_amount]").on("change",function () {
		setLoanGranted();computeSummary("compute_summary");
	});
';
switch($getActivity[$getPathID]->id){
	case 1:
		$scriptJS .= '
		$("[name=loan_details_loan_types]").on("change",function () {
			//getServiceFee = $(this).find("option:selected").attr("alias");
			//theServiceFee = getServiceFee.split("-");
			//serviceFee = parseFloat(theServiceFee[1]);
			//$("[name=loan_summary_service_fee]").val(serviceFee);
			computeSummary("compute_summary");
		});
		';
	break;
	case 2: case 3: case 4: case 5:
		$scriptJS .= '$("#profileBox > .fieldGroup, #loan_details > .fieldGroup, #member_loan_information > .fieldGroup").addClass("disabled");';
	break;
	case 6:
		$scriptJS .= '$("form[name='.$_GET['alias'].'] > .x_panel > .fieldGroup").addClass("disabled");$("ul.wizard_steps li:last-of-type a").addClass("done");';
	break;
}

$scriptJS .= '
$("select#loan_types").on("change",function () {
	int_rate = $(this).find("option:selected").attr("int_rate");
	installment_type = $(this).find("option:selected").attr("installment_type");
	mode_of_payments = $(this).find("option:selected").attr("mode_of_payments");
	
	$("[name=loan_details_loan_interest_percentage]").val(int_rate);
	//$("[name=loan_details_payment_terms]").val(installment_type);
});

$("select#loan_types").html("'.$optionLoanTypes.'");
//$("select#payment_mode").html("'.$optioninstallmentType.'");

$("#members_credit_rating_willingness_pay").append("'.$willingnessBtn.'");
	$("button#addWillingness").on("click",function () {
		getPopup(this,"methods","viewextras");
});
$("#members_credit_rating_ability_pay").append("'.$abilityBtn.'");
	$("button#addAbility").on("click",function () {
		getPopup(this,"methods","viewextras");
});
$("#members_credit_rating_collateral_pay").append("'.$collateralBtn.'");
	$("button#addCollateral").on("click",function () {
		getPopup(this,"methods","viewextras");
});

function setLoanGranted(){
	loanRequested = $("[name=loan_details_loan_amount]").val();
	abilityPay = $("[name=members_credit_rating_ability_pay]").val();
	theLoanAmount = [loanRequested,abilityPay];
	loanGranted = Math.min.apply(Math, theLoanAmount);
	$("[name=loan_summary_loan_granted]").val(loanGranted);
	paymentTerms = $("input[name=loan_details_payment_terms]").val();
	if(paymentTerms != ""){
		$("button[name=SubmitData]").removeClass("disabled");
	}
}
';

echo '<script>'.$scriptJS.'</script>';
