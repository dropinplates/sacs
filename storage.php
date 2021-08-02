<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE); // HIDE STANDARD ERROR DISPLAY
session_start();
include 'functions-methods.php';
include 'php-logger.php';
$action = (isset($_POST['action']) && $_POST['action'] != "") ? $_POST['action'] : "";
//$getDateTime = getTime('datetime');
//(true)?$ipAddress = $_SERVER['SERVER_ADDR']:$ipAddress = getHostByName(php_uname('n'));
//(false)?$theIPaddress = getHostByName(php_uname('n')).' ('.php_uname('n').')':$theIPaddress = $_SERVER['REMOTE_ADDR'];
$alert = '';
//$theIPaddress = getIpAddress();
if(isset($_POST['sessionUserID'])&&$_POST['sessionUserID']!=''){
	$sessionUserID = $_POST['sessionUserID'];
}

try{
	if (!EMPTY($_FILES)) {
		//$getFiles = new Upload($_FILES,$_GET);
		$getFiles = new Upload($_FILES,['get'=>$_GET,'post'=>$_POST]);
		$getFiles->uploadFiles();
	}elseif(isset($_GET["action"]) && $_GET["action"] != ""){
		$getLoad = new Load($_GET);
		$getLoad->processAction();
	}else{
		$getStorage = new Storage($_POST);
		$getStorage->processAction();
	}
}catch(Exception $e){
	echo $e->getMessage();
}

class Load {
	public $Params;
	public $method;
	public $action;
	public $db;
	public $session;
	//public $schemaProjectZero = ['staffs','payroll','payroll_adjustment','payroll_logs','salary_set','holidays'];

	function __construct($params) {
		$this->Params = $params;
	}

	function processAction() { // DISPLAY THE PROCESS
		$formData['status'] = 'success';
		$action = $this->Params['action'];

		$this->method = new Method($this->Params);
		//var_dump();
		if($action && $_SESSION['userID']){ // PUT USER ACCESS AUTHENTICATION HERE
			$this->$action();
		}else{
			header('Content-Type: application/json; charset=UTF-8');
		}//throw new \Exception('ACTION ERROR', 1);
	}
	
	// function generateDocTBS(){
		// include_once('plugins/tbs/file_ms_word.php'); // Load the TinyButStrong template engine
	// }
	
	function getElementData(){
		$result = "";
		switch($_GET['meta']){
			case "member_loan_ledger":
				$stmtMembershipLoan['fields'] = [
					'loans_details.id loan_id',
					'loans_details.data_id',
					'loans_details.loan_types',
					'loans_details.payment_mode',
					'loans_details.loan_interest',
					'queue.journals_id',
					'queue.unit',
					'loan_summary.interest_type',
					'loan_summary.amortization_type',
					'loan_summary.loan_granted',
					'loans_details.loan_terms',
					'loan_summary.approve_date',
					'loan_summary.maturity_date',
					'loan_summary.remarks',
					'IFNULL((SELECT SUM(payments.payment_principal) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions payments JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue queue ON (queue.data_id = payments.data_id) WHERE queue.path_id = 8 AND queue.activity_id = 18 AND payments.loans_id = loans_details.id ), 0) AS amount_paid',
					'IFNULL((SELECT SUM(payments.payment_interest) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions payments JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue queue ON (queue.data_id = payments.data_id) WHERE queue.path_id = 8 AND queue.activity_id = 18 AND payments.loans_id = loans_details.id ), 0) AS interest_paid',
					'IFNULL((SELECT SUM(interest_due) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE data_id = loans_details.data_id), 0) AS interest_due',
					'loans_details.mem_info member_name'
				];
				$stmtMembershipLoan['table'] = 'data_queue as queue';
				$stmtMembershipLoan['join'] = '
					JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
					JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
				   ';
				$stmtMembershipLoan['extra'] = 'ORDER BY member_name ASC';
				$stmtMembershipLoan['arguments']["loans_details.id"] = $_GET['value'];
				$stmtMembershipLoan['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembershipLoan['arguments']["queue.path_id"] = 7;
				$stmtMembershipLoan['arguments']["queue.activity_id"] = 16; // APPROVED LOANS
				$stmtMembershipLoan += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembershipLoan = $this->method->selectDB($stmtMembershipLoan);
				$loanInfo = $getMembershipLoan[$_GET['value']];
				$memberInfo = $loanInfo->member_name;
				$memberInfo = explode(":",$memberInfo);
				$refNum = $this->method->formatValue(['prefix'=>7,'id'=>$loanInfo->data_id],"app_id");
				$principalBalance = $loanInfo->loan_granted - $loanInfo->amount_paid;
				$interestBalance = $loanInfo->interest_due - $loanInfo->interest_paid;
				$output = "
				<button class='elementPrint savingsBtnBG' onclick='printWindow(\"#element_printable\", \"Individual Loan Ledger\")'><i class='fa fa-print'></i>Print Report</button>
				<link href='".Info::URL."/css/print.css' rel='stylesheet'>
				<div id='element_printable'>
				<div id='{$_GET['meta']}' class='x_panel no-padding' style='margin-top:3px'>
					<div class='box_title'><h2 class='left'>Loan Information/Details</h2></div>
					<div class='form-group'>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Ref. Num.</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$refNum}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Full Name</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberInfo[0]}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Loan Type</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$_SESSION['codebook']['loan_types'][$loanInfo->loan_types]->meta_value}</div>
						</div>
					</div>
					<div class='form-group'>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Pymt. Mode</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$_SESSION['codebook']['payment_mode'][$loanInfo->payment_mode]->meta_value}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Interest/Terms</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$loanInfo->loan_interest}% <span class='small'>per annum</span> | {$loanInfo->loan_terms} <span class='small'>months</span></div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Amortization</label>
							<div class='col-md-8 col-xs-12 text-input bold ellipsis no-margin'><span class=''>{$_SESSION['codebook']['amortization_type'][$loanInfo->amortization_type]->meta_value} | {$_SESSION['codebook']['interest_type'][$loanInfo->interest_type]->meta_value}</span></div>
						</div>
					</div>
					<div class='form-group'>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Loan Date</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$loanInfo->approve_date}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Principal Amt.</label>
							<div class='col-md-8 col-xs-12 text-input bold alignRight'>".number_format($loanInfo->loan_granted, 2)."</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Interest Amt.</label>
							<div class='col-md-8 col-xs-12 text-input bold alignRight'>".number_format($loanInfo->interest_due, 2)."</div>
						</div>
					</div>
					<div class='form-group'>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Maturity Date</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$loanInfo->maturity_date}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Prin. Balance</label>
							<div class='col-md-8 col-xs-12 text-input bold alignRight'><span id='principal_balance'>".number_format($principalBalance, 2)."</span></div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight control-label col-md-4 col-sm-4 col-xs-12'>Int. Balance</label>
							<div class='col-md-8 col-xs-12 text-input bold alignRight'><span id='interest_balance'>".number_format($interestBalance, 2)."</span></div>
						</div>
					</div>
				</div>
				";
				$arrayFields = ['trans_date'=>'Trans Date','trans_type'=>'Trans Type','data_id'=>'Ref Num','voucher'=>'Voucher','payment_principal'=>'Principal','payment_interest'=>'Interest','payment_penalty'=>'Penalty','remarks'=>'Remarks'];
				$stmtCheckPayments['fields'] = [
					//'loans_payment.loans_id',
					'queue.data_id',
					'queue.journals_id voucher',
					'queue.unit',
					'loans_payment.trans_date',
					'IFNULL(loans_payment.payment_principal, 0) payment_principal',
					'IFNULL(loans_payment.payment_interest, 0) payment_interest',
					'IFNULL(loans_payment.payment_penalty, 0) payment_penalty',
					'loans_payment.remarks',
				];
				$stmtCheckPayments['table'] = 'data_queue as queue';
				$stmtCheckPayments['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions AS loans_payment ON (loans_payment.data_id = queue.data_id)
				   ';
				$stmtCheckPayments['extra'] = 'ORDER BY queue.data_id ASC';
				$stmtCheckPayments['arguments']["loans_payment.loans_id"] = $_GET['value'];
				//$stmtCheckPayments['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtCheckPayments['arguments']["queue.path_id"] = 8;
				$stmtCheckPayments['arguments']["queue.activity_id"] = 18;
				$stmtCheckPayments += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_CLASS];
				$checkPayments = $this->method->selectDB($stmtCheckPayments);
				$fieldAmounts = ['payment_principal','payment_interest','payment_penalty'];
				
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$class = "";
					if(in_array($field, $fieldAmounts)) $class = " alignRight";
					$tableHead .= "<th class='{$field}{$class}'>{$value}</th>"; 
				}
				$output .= "<div class='dataRecord'><table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive tablex' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				$disbursementDataID = $this->method->formatValue(['prefix'=>7,'id'=>$loanInfo->data_id],"app_id");
				$disbursementJournalID = ($loanInfo->journals_id) ? $this->method->formatValue(['prefix'=>$loanInfo->unit,'id'=>$loanInfo->journals_id],"app_id") : Info::EMPTY_VAL;
				$disbursementRemarks = ($loanInfo->remarks) ? $loanInfo->remarks : Info::EMPTY_VAL;
				$tbodyRow .= "<tr id='disbursement-{$loanInfo->data_id}'><td class='num paddingHorizontal'>{$cnt}</td>
					<td class='trans_date paddingHorizontal'>{$loanInfo->approve_date}</td>
					<td class='trans_type paddingHorizontal'>Disbursement</td>
					<td class='data_id paddingHorizontal'>{$disbursementDataID}</td>
					<td class='voucher paddingHorizontal'>{$disbursementJournalID}</td>
					<td class='principal paddingHorizontal alignRight'>".number_format($loanInfo->loan_granted, 2)."</td>
					<td class='interest paddingHorizontal alignRight'>".number_format($loanInfo->interest_due, 2)."</td>
					<td class='penalty paddingHorizontal alignRight'>0.000</td>
					<td class='remarks paddingHorizontal'><span class='ellipsis'>{$disbursementRemarks}</span></td>
				</tr>";
				$cnt++;
				foreach($checkPayments as $queueID => $paymentDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$class = "";
						if(in_array($field, $fieldAmounts)) $class = " alignRight";
						$value = $paymentDetails->$field;
						switch($field){
							case "payment_principal": case "payment_interest":
								$value = number_format($value, 2);
							break;
							case "trans_type":
								$value = "Re-Payment";
							break;
							case "remarks":
								if(!$value) $value = Info::EMPTY_VAL;
								$value = "<span class='ellipsis'>{$value}</span>";
							break;
							case "voucher":
								$value = ($value) ? $this->method->formatValue(['prefix'=>$paymentDetails->unit,'id'=>$value],"app_id") : Info::EMPTY_VAL;
							break;
							case "data_id":
								$value = $this->method->formatValue(['prefix'=>8,'id'=>$value],"app_id");
							break;
						}
						$tbodyColumns .= "<td class='{$field} paddingHorizontal{$class}'>{$value}</td>";
					}
					$tbodyRow .= "<tr id='payments-{$paymentDetails->data_id}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table></div></div>";
				//$output .= "<button onclick='printDiv(\"element_printable\");'>fox</button>";
				
				$output .= "
					<script>
					$(document).on('click', '.some-print', function() {
						PrintElem($(this), 'My Print Title');
						return false;
					});

					// $('#datatable-{$_GET['meta']}').DataTable({
						// 'aoColumnDefs': [{'aTargets': [0,1,2,3,4], 'className': 'alignCenter'},{'aTargets': [5,6,7], 'className': 'alignRight'}],
						// 'paging':   true,
						// 'searching': true,
						// //'ordering': false,
						// 'info': true
					// });
					
					</script>
					";
				echo $output;
				//var_dump($checkPayments);
			break;
			case "loan_balance_box": // REFINANCE GROUP BOX
				$memID = $_GET['mem_id']; // MEMBER ID
				$loanType = $_GET['loan_type']; // LOAN TYPE
				$stmtData['fields'] = [
					'queue.id',
					'loan_summary.approve_date',
					'loan_summary.maturity_date',
					'queue.data_id',
					'loans_details.loan_terms',
					'loans_details.loan_types',
					'loan_summary.loan_granted',
					'loans_details.id loan_id',
					'IFNULL((SELECT SUM(payment_principal) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions WHERE loans_id = loans_details.id), 0) AS amount_paid',
					'IFNULL((SELECT SUM(payment_interest) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions WHERE loans_id = loans_details.id), 0) AS interest_paid'
				];
				$stmtData['table'] = 'data_queue as queue';
				$stmtData['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
				   ';
				$stmtData['extra'] = 'AND loan_summary.maturity_date >= NOW() ORDER BY data_id DESC';
				$stmtData['arguments']["loans_details.mem_id"] = $memID;
				$stmtData['arguments']["loans_details.loan_types"] = $loanType;
				$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtData['arguments']["queue.activity_id"] = 16;
				$stmtData['arguments']["queue.path_id"] = 7;

				$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getData = $this->method->selectDB($stmtData);
				$queueID = array_keys($getData);
				$queueID = $queueID[0];
				
				$loanBalance = [];
				$jqueryVal = "";
				$balanceFields = ['balance_loan_id','balance_loan_amount','balance_loan_maturity','balance_loan_principal'];
				$loanBalance['balance_loan_id'] = ($queueID) ? $this->method->formatValue(['prefix'=>7,'id'=>$getData[$queueID]->loan_id],"app_id") : "";
				$loanBalance['balance_loan_maturity'] = ($queueID) ? $getData[$queueID]->maturity_date : "";
				$loanBalance['balance_loan_amount'] = ($queueID) ? $getData[$queueID]->loan_granted : "";
				$loanBalance['balance_loan_principal'] = ($queueID) ? number_format($getData[$queueID]->loan_granted - $getData[$queueID]->amount_paid, 2, '.','') : 0;
				if($loanBalance['balance_loan_principal'] > 0){
					foreach($balanceFields as $field){
						$jqueryVal .= "$('#{$field}').val('".$loanBalance[$field]."');";
					}
					if($queueID){
						$jqueryVal .= "$('input#loan_balance_info').val('{$getData[$queueID]->data_id}:{$loanBalance['balance_loan_maturity']}:{$loanBalance['balance_loan_amount']}:{$loanBalance['balance_loan_principal']}');";
					}else{
						$jqueryVal .= "$('input#loan_balance_info').val('');";
					}
				}else{ // IF NO BALANCE
					$jqueryVal = "$('#balance_loan_id').val('');$('#balance_loan_amount').val('');$('#balance_loan_maturity').val('');$('#balance_loan_principal').val('');$('input#loan_balance_info').val('');";
				}

				$jqueryVal .= "computeDeductions('loan_balance');";
				echo "<script>{$jqueryVal}</script>";
				//var_dump($loanBalance);
			break;
			
			case "amortization_calculator":
				//$this->method->Params['approved_date'] = '2021-05-05';
				if($_GET['loan_amount'] != "" && $_GET['terms'] != "" && $_GET['interest_annum'] != "" && $_GET['payment_mode'] != "" && $_GET['amortization_type'] != "" && $_GET['dd_type'] != ""){
					$getResults = [];
					$getDate = $this->method->getDate();
					$this->method->Params['approved_date'] = $_GET['approve_date'];
					$this->method->Params['start_date'] = $_GET['start_date'];
					$this->method->Params['loan_amount'] = floatVal($_GET['loan_amount']);
					$this->method->Params['terms'] = (int)$_GET['terms'];
					$this->method->Params['interest_annum'] = floatVal($_GET['interest_annum']);
					$this->method->Params['payment_mode'] = floatVal($_GET['payment_mode']);
					$this->method->Params['amortization_type'] = floatVal($_GET['amortization_type']); // 1:DEMINISHING, 2:STRAIGHT-LINE, 3:ANNUITY
					$this->method->Params['dd_type'] = floatVal($_GET['dd_type']);
					$amortizationDates = $this->method->generateAmortizationSchedule();
					$getLoanScheduleAssoc = $amortizationDates['schedule'];
					$getSummarySchedule = $amortizationDates['summary'];
					
					if($this->method->Params['loan_id'] > 0){ // IF HAS LOAN ID, TO REGENERATE THE AMORTIZATION SCHEDULE
						$dbConnect = Info::DBConnection();
						# CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE START
						$deleteLoanSchedule = [];
						$stmtLoanSchedule = ['schema'=>Info::DB_DATA,'table'=>'loan_schedule','arguments'=>['data_id'=>$this->method->Params['loan_id']],'extra'=>'','pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
						$getLoanScheduleID = $this->method->selectDB($stmtLoanSchedule);
						if(count($getLoanScheduleID) > 0){
							$deleteLoanSchedule = $this->method->deleteDB(['table'=>'loan_schedule','id'=>$getLoanScheduleID,'schema'=>Info::DB_DATA]);
						}
						# CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE END
						
						# CREATING LOAN SCHEDULE ON ECAPPS DATABASE
						$insertFields = $this->method->getTableFields(['table'=>'loan_schedule','exclude'=>[],'schema'=>Info::DB_DATA]);
						$stmtFields = implode(",",$insertFields);
						$stmtClause = implode(",",array_fill(0, count($insertFields), '?'));
						$stmt = $dbConnect->prepare("INSERT INTO ".Info::PREFIX_SCHEMA.Info::DB_DATA.".loan_schedule ({$stmtFields}) VALUES ({$stmtClause})");
						//$scheduleValue = [];
						foreach($getLoanScheduleAssoc as $num => $loanSchedDetail){
							$arrayLoanDetail1 = [NULL, $getDate, $this->method->Params['loan_id'], $num];
							$arrayLoanDetail2 = [$loanSchedDetail['due_date'],$loanSchedDetail['principal_due'],$loanSchedDetail['interest_due'],$loanSchedDetail['payment_due'],$loanSchedDetail['total_due'],$loanSchedDetail['loan_balance']];
							$arrayLoanDetail3 = [NULL, $getDate];
							$stmtValues = array_merge($arrayLoanDetail1, $arrayLoanDetail2, $arrayLoanDetail3);
							$stmt->execute($stmtValues);
							$loanSchedID = $dbConnect->lastInsertId();
							$dueDate = $loanSchedDetail['due_date'];
							$getResults[] = $stmtValues;
						} // END FOREACH
						# END CREATING LOAN SCHEDULE ON ECAPPS DATABASE
						
						$stmtLoanID = ['schema'=>Info::DB_DATA,'table'=>'path_7_loan_summary','arguments'=>['data_id'=>$this->method->Params['loan_id']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'ORDER BY data_id DESC LIMIT 1','fields'=>['data_id','id']];
						$getLoanID = $this->method->selectDB($stmtLoanID);
						$pathElements = []; // UPDATING THE MATURITY_DATE
						$pathElements['fieldValues'] = ["maturity_date"=>$dueDate];
						$pathElements['schema'] = Info::DB_DATA;
						$pathElements['table'] = "path_7_loan_summary";
						$pathElements['id'] = $getLoanID[$this->method->Params['loan_id']];
						$cntPathUpdate = $this->method->updateDB($pathElements);
						//var_dump($getResults);
					} // END IF HAS LOAN ID
					
					$arrayFields = ['due_date'=>'Due Date','principal_due'=>'Prin Due','interest_due'=>'Int Due','total_due'=>'Amount Due','loan_balance'=>'Prin Balance'];
					$amountFields = ["total_due","interest_due","principal_due","loan_balance","others_cbu","others_savings"];
					$tableHead = "<th class='num alignRight'>&nbsp;</th>";
					foreach($arrayFields as $field => $value){ // TABLE THEAD
						$class = "alignCenter";
						if(in_array($field, $amountFields)) $class= "alignRight";
						$tableHead .= "<th class='{$field} {$class}'>{$value}</th>"; 
					}
					$tbodyRow = "";$cnt=1;
					$output = "<table id='loan_amortization' class='table' width='100%'><thead><tr>{$tableHead}</tr></thead>";
					$getFields = array_keys($arrayFields);
					$totals = [];
					foreach($getLoanScheduleAssoc as $key=>$getValue){
						$tbodyColumns = "<td class='num alignRight paddingHorizontal'>{$key}</td>";
						foreach($getFields as $field){
							$class= "alignCenter";
							$value = $getValue[$field];
							if(in_array($field, $amountFields)){
									$class = "alignRight";
									$totals[$field][] = $value;
							}
							if($field != 'due_date' && $field != 'day_name') $value = number_format($value,2);
							
							$tbodyColumns .= "<td class='{$field} {$class} paddingHorizontal'><span>{$value}</span></td>";
						}
						
						$tbodyRow .= "<tr id='member-{$key}'>{$tbodyColumns}</tr>";
						$cnt++;
						
					}
					$output .= "<tbody>{$tbodyRow}</tbody>";
					$output .= "<tfoot>
						<tr>
							<td colspan='2'>&nbsp;</td>
							<td class='alignRight'>".number_format(array_sum($totals['principal_due']), 2)."</td>
							<td class='alignRight'>".number_format(array_sum($totals['interest_due']), 2)."</td>
							<td class='alignRight'>".number_format(array_sum($totals['total_due']), 2)."</td>
							<td class='alignRight'>&nbsp;</td>
						</tr>
					</tfoot>";
					$output .= "</table>";
					//var_dump($methods->Params);
					echo $output;
				}else{
					echo "<span>Please check and fill significant fields and try again.</span>";
				}
				
			break;
			
			case "execute_manual_process": // MANUAL PROCESS
			    $stmtQuery['fields'] = [
					'id'
				];
				$stmtQuery['table'] = 'data_queue as queue';
				//$stmtMembership['extra'] = 'ORDER BY client_name ASC';
				$stmtQuery['arguments']["queue.path_id"] = 6;
				$stmtQuery['arguments']["queue.activity_id"] = 12;
                $stmtQuery['between'] = ['date_created','2021-04-19 00:00:01','2021-04-19 23:59:59'];
				$stmtQuery += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_COLUMN];
				$getQuery = $this->method->selectDB($stmtQuery);
				$newDateTime = "2020-12-31 11:29:47";
				//foreach($getQuery as $key => $queueID){
				//    $queueElements = [];
        		//	$queueElements['fieldValues'] = ["date_created"=>$newDateTime];
        		//	$queueElements['schema'] = Info::DB_DATA;
        		//	$queueElements['table'] = "data_queue";
        		//	$queueElements['id'] = $queueID;
        		//	$this->method->updateDB($queueElements);
				//}
				var_dump($getQuery);
			break;
			case "execute_default_loans":
				$stmtData['fields'] = [
					'queue.id',
					'loan_summary.approve_date AS approve_date',
					'loan_summary.maturity_date AS maturity_date',
					'queue.data_id',
					'loans_details.loan_terms',
					'loans_details.loan_types',
					'loan_summary.loan_granted',
					'loan_summary.remarks',
					'IFNULL((SELECT SUM(payment_principal) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions WHERE loans_id = loans_details.id), 0) AS amount_paid'
				];
				$stmtData['table'] = 'data_queue as queue';
				$stmtData['join'] = '
				   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
				   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
				   ';
				$stmtData['extra'] = 'AND loan_summary.maturity_date < NOW() ORDER BY data_id DESC';
				//$stmtData['arguments']["loans_details.mem_id"] = $memID;
				//$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtData['arguments']["queue.activity_id"] = 16;
				$stmtData['arguments']["queue.path_id"] = 7;

				$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getData = $this->method->selectDB($stmtData);
				var_dump($getData);
			break;
			case "execute_time_deposit_interest":
				echo $_GET['meta']." has been executed.";
			break;
			case "execute_regular_savings_interest":
				//echo $_GET['meta']." has been executed.";
				var_dump($_SESSION['codebook']['unit'][2]->meta_value);
			break;
			case "execute_cbu_share_capital":
				$cutOffDate = '2020-12-31';
				$result = []; $totalShareCapitalCBU = 0;
				
				### GET MEMBERS LISTS ###
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'membership_information.account_type',
				'membership_information.membership_type',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_id ASC';
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["queue.path_id"] = 1;
				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				### GET MEMBERS LISTS ###
				
				$shareCapitalOptions = $this->method->methodSettings(['meta_key'=>'module_policy','name'=>'share_capital_policy']);
				$minimumAmountCommon = floatVal($shareCapitalOptions['common[minimum][capital]']);
				$parValue = floatVal($shareCapitalOptions['common[par_value]']);
				$stmtDetailsCBU = [
					'schema'=>Info::DB_DATA,
					'table'=>'data_queue queue',
					'fields'=>['cbu_details.mem_id','IFNULL((SELECT MAX(balance) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_5_cbu_details WHERE data_id = queue.data_id AND mem_id = cbu_details.mem_id), 0) AS balance'],
					'join'=>'JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_5_cbu_details AS cbu_details ON (cbu_details.data_id = queue.data_id)',
					'arguments'=>[
						'queue.path_id'=>5,
						'queue.activity_id'=>10,
						'queue.unit'=>$_SESSION['unit'],
						'cbu_details.date<'=>$cutOffDate,
						//'cbu_details.mem_id'=>105
					],
					'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC
				];
				$getDetailsCBU = $this->method->selectDB($stmtDetailsCBU);
				ob_start();
				if($getDetailsCBU){
					$cnt = 0;
					foreach($getDetailsCBU as $memID => $cbuDetails){
						//$totalCBU = $totalCBU + $cbuDetails['balance'];
						$shareValue = floor($cbuDetails['balance'] / $parValue);
						if($shareValue > 1){ // HAS SHARE VALUE CONVERTION
							$memInfo = $getMembership[$memID];
							$shareAmount = $shareValue * $parValue;
							$balanceCBU = $cbuDetails['balance'] - $shareAmount;
							$totalShareCapitalCBU = $totalShareCapitalCBU + $shareAmount;
							
							$postMemInfo = "{$memInfo->member_name}:{$memID}:{$_SESSION['codebook']['account_type'][$memInfo->account_type]->meta_value}:{$_SESSION['codebook']['membership_type'][$memInfo->membership_type]->meta_value}";
							
							### SETTING MODULE SHARE CAPITAL ###
							$post['source'] = false;
							$post['action'] = "createData";
							$post['table'] = "data_queue";
							$post['path_alias'] = "share_capital";
							$post['post_id'] = "0";
							$post['data_id'] = "0";
							$post['path_id'] = "6";
							$post['activity_id'] = "12";
							$post['post_activity_id'] = "12";
							$post['user'] = $_SESSION['userID'];
							$post['unit'] = $_SESSION['unit'];
							$post['share_capital_details']['mem_id'] = $memID;
							$post['share_capital_details']['mem_info'] = $postMemInfo;
							$post['share_capital_details']['amount'] = $shareAmount;
							$post['share_capital_details']['share_value'] = $shareValue;
							$post['share_capital_details']['share_capital_type'] = 1;
							$post['share_capital_details']['remarks'] = "Generating CBU to Share Capital, Cutt-off Date: ".$cutOffDate;
							$post['share_capital_details']['share_capital_status'] = 1;
							$_POST = $post;
							$getStorage = new Storage($_POST);
							//$data = $getStorage->processAction();
							### SETTING MODULE SHARE CAPITAL ###
							
							$stmtBalanceCBU = ['schema'=>Info::DB_DATA,'table'=>'path_5_cbu_details','arguments'=>['mem_id'=>$memID],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'ORDER BY data_id DESC LIMIT 1','fields'=>['mem_id','balance']];
							$getBalanceCBU = $this->method->selectDB($stmtBalanceCBU);
							$currentBalanceCBU = $getBalanceCBU[$memID] - $shareAmount;
							
							$result[$memID][] = ["member"=>$memInfo->member_name,"share_value"=>$shareValue,"share_amount"=>$shareAmount,'cbu_balance'=>$balanceCBU,'current_balance'=>$currentBalanceCBU];
							
							### SETTING MODULE CBU ###
							$post['source'] = false;
							$post['action'] = "createData";
							$post['table'] = "data_queue";
							$post['path_alias'] = "cbu";
							$post['post_id'] = "0";
							$post['data_id'] = "0";
							$post['path_id'] = "5";
							$post['activity_id'] = "10";
							$post['post_activity_id'] = "10";
							$post['user'] = $_SESSION['userID'];
							$post['unit'] = $_SESSION['unit'];
							$post['cbu_details']['mem_id'] = $memID;
							$post['cbu_details']['mem_info'] = $postMemInfo;
							$post['cbu_details']['amount'] = -$shareAmount;
							$post['cbu_details']['balance'] = $currentBalanceCBU;
							$post['cbu_details']['remarks'] = "Updating CBU Amount from Share Capital Conversion, Cutt-off Date: ".$cutOffDate;
							$_POST = $post;
							$getStorage = new Storage($_POST);
							//$data = $getStorage->processAction();
							### SETTING MODULE CBU ###
						} // END IF SHARE VALUE CONVERTION
						$cnt++;
					} // END FOREACH
					
					if($totalShareCapitalCBU > 0){
						$journalParams = [];
						$recipientName = "System Administration";//$_SESSION['codebook']['unit'][$_SESSION['unit']]->meta_value;
						$journalParams["table"] = "journals";
						$journalParams["unit"] = $_SESSION['unit'];
						$journalParams["theID"] = "0";
						$journalParams["entry"] = "2";
						$journalParams["journals"] = ["entry_date" => $cutOffDate, "recipient"=>$recipientName, "particulars" => "Converting ".$cnt." Members Capital Build-Up to Share Capital-Common"];
						$journalParams["journals_entry"] = [
								1 => ["charts_id" => $_SESSION["module_charts"]['cbu'], "debit" => $totalShareCapitalCBU, "credit" => "0.00"],
								2 => ["charts_id" => $_SESSION["module_charts"]['share_common'], "debit" => "0.00", "credit" => $totalShareCapitalCBU]
							];
						//$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
					}
				} // END IF
				//echo json_encode($getDetailsCBU);
				ob_end_clean();
				var_dump(json_encode($result));
			break;
			
			case "load_voucher":
				echo $this->method->voucherBoxDetails($_GET["value"]);
				echo "<script>generateCashVoucher(0);</script>";
			break;
			case "journal_entry":
				$accountCharts = $this->method->getOptionLists();
				$listID = $_GET['value'];
				echo $this->method->journalEntry($accountCharts,$listID);
				echo "<script>$('select#selectAccount-{$listID}').select2({placeholder: 'Select Account Title/Name'});</script>";
			break;
			case "release_date": // FOR LOAN APPLICATIONS APPROVAL
				$stmtDateApproved = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$_GET["value"]],'pdoFetch'=>PDO::FETCH_COLUMN,'extra'=>'','fields'=>['date_approved']];
				$getDateApproved = $this->method->selectDB($stmtDateApproved);
				$startDate = ($getDateApproved[0]) ? $getDateApproved[0] : $this->method->dateFrom;
				$inputReleaseDate = $this->method->inputGroup(['label'=>'Release Date','type'=>'date','id'=>'release_date','name'=>'release_date','placeholder'=>'Disbursement Date','value'=>$startDate]);
				echo "
				<div class='x_panel no-padding no-margin'>
					<div class='box_title'><h2 class='left'>Loan Disbursement</h2></div>
					<div class='form-group no-padding item'>
						<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$inputReleaseDate}</div>
					</div>
				</div>";
				echo "
				<script src='".Info::URL."/vendors/moment/min/moment.min.js'></script>
			<script src='".Info::URL."/vendors/bootstrap-daterangepicker/daterangepicker.js'></script>
			<script>
				$('input[name=release_date]').daterangepicker({
					singleDatePicker: true,
					opens: 'left',
					//minDate: '".$this->method->dateFrom."',
					startDate: '".$startDate."',
					//endDate: '".$this->method->dateTo."',
					//maxDate: '".$this->method->getTime("date")."',
					
					autoUpdateInput: true,	calender_style: 'picker_4',
					locale: {
						format: 'YYYY-MM-DD'
					}
				}, function (start, end, label) {
					console.log(start.toISOString(), end.toISOString(), label);
				});
				
				$('input[name=release_date]').on('change',function () {
					val = $(this).val();
					$('input#approve_date').val(val);
				});
				
				dateVal = $('input[name=release_date]').val();
				$('input#approve_date').val(dateVal);
				
				$('<div/>',{
					html: '<button type=\"button\" class=\"btn btn-default\" id=\"closeBtn\" data-dismiss=\"modal\">Close</button> <button type=\"button\" name=\"SubmitData\" class=\"btn btn-default\" id=\"loans\" activity=\"16\" onclick=\"submitData(\'createData\',this)\">Approve Loan</button>',
					class: 'modal-footer'
				}).appendTo('.modal-content');
				
			</script>
				";
			break;
			
			case "member_profile":
				$output = "";
				$stmtDataID = ['schema'=>Info::DB_DATA,'table'=>'path_1_membership_information','arguments'=>['member_id'=>$_GET['value']],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['data_id']];
				$getDataID = $this->method->selectDB($stmtDataID);
				$this->method->Params["dataID"] = $getDataID[0];
				$this->method->Params["pathID"] = 1;
				$this->method->getElementType = "1";
				$checkElements = $this->method->getElements('membership');
				unset($checkElements['provincial_address']);
				$codebookFields = array_keys($_SESSION['codebook']);
				//var_dump($codebookFields);
				foreach($checkElements as $group => $fields){
					$fieldElements = "";
					foreach($fields as $field => $value) {
						if(in_array($field, $codebookFields)){
							$value = $_SESSION['codebook'][$field][$value]->meta_value;
						}
						if(!$value) $value = Info::EMPTY_VAL;
						$fieldValue = str_replace("_"," ",$field);
						$fieldElements .= "
						<div class='col-md-6 col-sm-6 col-xs-12 no-padding edit item'>
							<label for='field_name' class='alignRight number control-label col-md-5 col-sm-5 col-xs-12'>{$fieldValue}</label>
							<div class='col-md-7 col-xs-12 text-input bold'>{$value}</div>
						</div>
						";
					}
					$group = str_replace("_"," ",$group);
					$output .= "
						<div id='{$group}' class='x_panel no-padding' style='margin-top:3px'>
							<div class='box_title'><h2 class='left capitalize'>{$group}</h2></div>
							<div class='form-group multi-col'>{$fieldElements}</div>
						</div>
						";
				}
				
				echo $output;
			break;
			case "member_savings":
				$pathValueMembership = "path_1_";
				$memID = $_GET['value']; // MEMBER ID
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name',
				'IFNULL(savings_details.id, 0) AS regular_savings_id',
				'savings_details.date AS savings_date',
				'savings_details.data_id AS savings_data_id',
				'savings_details.remarks AS savings_remarks',
				'IFNULL(savings_details.amount, 0) AS savings_amount',
				'IFNULL(savings_details.balance, 0) AS regular_savings'
				//'IFNULL((SELECT balance FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_2_savings_details WHERE product_savings = 1 AND mem_id = membership_information.member_id ORDER BY data_id DESC LIMIT 1), 0) AS regular_savings'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_2_savings_details AS savings_details ON (savings_details.product_savings = 1 AND savings_details.mem_id = membership_information.member_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_name ASC';
				//$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				//$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["membership_information.member_id"] = $memID;
				$stmtMembership['arguments']["queue.path_id"] = 1;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$memberName = $getMembership[$memID]->member_name;
				$memberRegularSavings = number_format($getMembership[$memID]->regular_savings, 2);
				$memberID = $this->method->formatValue(['prefix'=>$_SESSION["unit"],'id'=>$memID],"app_id");
				$memberRegularSavingsID = $getMembership[$memID]->regular_savings_id;
				
				$output = "
				<div id='{$_GET['meta']}' class='x_panel no-padding' style='margin-top:3px'>
					<div class='box_title'><h2 class='left'>Member Savings Details</h2></div>
					<div class='form-group'>
						<div class='col-md-5 col-sm-5 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Full Name</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberName}</div>
						</div>
						<div class='col-md-3 col-sm-3 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Mem ID</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberID}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Savings</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberRegularSavings}</div>
						</div>
					</div>
				</div>
				";
				if($memberRegularSavingsID > 0){ // IF HAS REGULAR SAVINGS
					
					$arrayFields = ['date'=>'Date','data_id'=>'Ref Num','deposit'=>'Deposit','withdraw'=>'Withdraw','remarks'=>'Remarks'];
					$stmtData['fields'] = [
						'queue.id',
						'queue.data_id',
						'queue.path_id',
						'CASE WHEN queue.path_id = 3
							THEN deposits_transactions.amount
							ELSE withdrawal_transactions.amount
						END AS amount',
						'CASE WHEN queue.path_id = 3
							THEN deposits_transactions.remarks
							ELSE withdrawal_transactions.remarks
						END AS remarks'
						//SELECT amount FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_3_deposits_transactions WHERE data_id = queue.data_id ORDER BY data_id DESC LIMIT 1
						
						//'IFNULL(deposits_transactions.amount, withdrawal_transactions.amount) AS amount',
						//'IFNULL(deposits_transactions.remarks, withdrawal_transactions.remarks) AS remarks'
					];
					$stmtData['table'] = 'data_queue as queue';
					$stmtData['join'] = '
					   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_3_deposits_transactions AS deposits_transactions ON (deposits_transactions.data_id = queue.data_id)
					   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_4_withdrawal_transactions AS withdrawal_transactions ON (withdrawal_transactions.data_id = queue.data_id)
					   ';
					$stmtData['extra'] = 'AND queue.path_id IN(3,4) ORDER BY id DESC';
					$stmtData['arguments']["deposits_transactions.savings_id"] = $memberRegularSavingsID;
					$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
					//$stmtData['arguments']["queue.activity_id"] = 10;
					//$stmtData['arguments']["queue.path_id"] = 3;

					$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
					$getData = $this->method->selectDB($stmtData);
					//var_dump($getData);
					$tableHead = "<th class='num'>&nbsp;</th>";
					foreach($arrayFields as $field => $value){ // TABLE THEAD
						$tableHead .= "<th class='{$field}'>{$value}</th>"; 
					}
					$output .= "<div class='dataRecord'><table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
					$getFields = array_keys($arrayFields);
					$cnt = 1;
					$tbodyRow = "";
					foreach($getData as $queueID => $memberDetails){
						$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
						foreach($getFields as $field){
							$value = $memberDetails->$field;
							switch($field){
								case "date":
									$value = $this->method->timeDateFormat($value, 'date');
								break;
								case "data_id":
									$value = $this->method->formatValue(['prefix'=>$memberDetails->path_id,'id'=>$value],"app_id");
								break;
								case "deposit":
									$value = ($memberDetails->path_id == 3) ? $memberDetails->amount : Info::EMPTY_VAL;
								break;
								case "withdraw":
									$value = ($memberDetails->path_id == 4) ? $memberDetails->amount : Info::EMPTY_VAL;
								break;
							}
							$tbodyColumns .= "<td class='{$field} paddingHorizontal'><span>{$value}</span></td>";
						}
						$tbodyRow .= "<tr id='savingsAccount-{$queueID}'>{$tbodyColumns}</tr>";
						$cnt++;
					}
					
					$memberRegularSavingsAmount = $getMembership[$memID]->savings_amount;
					$memberRegularSavingsDataID = $value = $this->method->formatValue(['prefix'=>2,'id'=>$getMembership[$memID]->savings_data_id],"app_id");
					$memberRegularSavingsDate = $this->method->timeDateFormat($getMembership[$memID]->savings_date, 'date');
					$memberRegularSavingsRemarks = $getMembership[$memID]->savings_remarks;
					$tbodyRow .= "
						<tr id='savingsAccount-{$queueID}'>
							<td class='num paddingHorizontal'>{$cnt}</td>
							<td class='date paddingHorizontal'><span>{$memberRegularSavingsDate}</span></td>
							<td class='data_id paddingHorizontal'><span>{$memberRegularSavingsDataID}</span></td>
							<td class='deposit paddingHorizontal'><span>{$memberRegularSavingsAmount}</span></td>
							<td class='withdraw paddingHorizontal'><span>".Info::EMPTY_VAL."</span></td>
							<td class='remarks paddingHorizontal'><span>{$memberRegularSavingsRemarks}</span></td>
						</tr>
					";
					
					$output .= "<tbody>{$tbodyRow}</tbody>";
					$output .= "</table></div>";
					$output .= "
						<script>
						$('#datatable-{$_GET['meta']}').DataTable({
							'aoColumnDefs': [{'aTargets': [0,1,2], 'className': 'alignCenter'},{'aTargets': [3,4], 'className': 'alignRight'}],
							'paging':   true,
							'searching': true,
							//'ordering': false,
							'info': true
						});
						</script>
						";
				}
				
				echo $output;
			break;
			case "member_cbu":
				$pathValueMembership = "path_1_";
				$memID = $_GET['value']; // MEMBER ID
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name',
				'IFNULL((
						SELECT cbu.balance
						FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_5_cbu_details cbu
						JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue queue ON (queue.data_id = cbu.data_id)
						WHERE queue.path_id = 5 AND queue.activity_id = 10 AND cbu.mem_id = membership_information.member_id ORDER BY cbu.data_id DESC LIMIT 1
					), 0) AS cbu'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_name ASC';
				//$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				//$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["membership_information.member_id"] = $memID;
				$stmtMembership['arguments']["queue.path_id"] = 1;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$memberName = $getMembership[$memID]->member_name;
				$memberCBU = number_format($getMembership[$memID]->cbu, 2);
				$memberID = $this->method->formatValue(['prefix'=>$_SESSION["unit"],'id'=>$memID],"app_id");
				//var_dump($getMembership);
				$output = "
				<div id='{$_GET['meta']}' class='x_panel no-padding' style='margin-top:3px'>
					<div class='box_title'><h2 class='left'>Capital Build-Up Details</h2></div>
					<div class='form-group'>
						<div class='col-md-5 col-sm-5 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Full Name</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberName}</div>
						</div>
						<div class='col-md-3 col-sm-3 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Mem ID</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberID}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>CBU</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberCBU}</div>
						</div>
					</div>
				</div>
				";
				$arrayFields = ['date'=>'Date','data_id'=>'Ref Num','amount'=>'Amount','balance'=>'Balance','remarks'=>'Remarks'];
				$stmtData['fields'] = [
					'queue.id',
					'cbu_details.date',
					'queue.data_id',
					'cbu_details.amount',
					'cbu_details.balance',
					'cbu_details.remarks'
				];
				$stmtData['table'] = 'data_queue as queue';
				$stmtData['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_5_cbu_details AS cbu_details ON (cbu_details.data_id = queue.data_id)
				   ';
				$stmtData['extra'] = 'ORDER BY data_id DESC';
				$stmtData['arguments']["cbu_details.mem_id"] = $memID;
				$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtData['arguments']["queue.activity_id"] = 10;
				$stmtData['arguments']["queue.path_id"] = 5;

				$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getData = $this->method->selectDB($stmtData);
				//var_dump($getData);
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$output .= "<div class='dataRecord'><table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				foreach($getData as $queueID => $memberDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$value = $memberDetails->$field;
						switch($field){
							case "amount": case "balance":
								$value = number_format($value, 2);
							break;
							case "date":
								$value = $this->method->timeDateFormat($value, 'date');
							break;
							case "data_id":
								$value = $this->method->formatValue(['prefix'=>5,'id'=>$value],"app_id");
							break;
						}
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'><span>{$value}</span></td>";
					}
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table></div>";
				$output .= "
					<script>
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0,1,2], 'className': 'alignCenter'},{'aTargets': [3,4], 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info': true
					});
					</script>
					";
				echo $output;
			break;
			
			case "member_share_capital":
				$pathValueMembership = "path_1_";
				$memID = $_GET['value']; // MEMBER ID
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				//'IFNULL((SELECT balance FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_5_cbu_details WHERE mem_id = membership_information.member_id ORDER BY data_id DESC LIMIT 1), 0) AS cbu'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_name ASC';
				//$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				//$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["membership_information.member_id"] = $memID;
				$stmtMembership['arguments']["queue.path_id"] = 1;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$memberName = $getMembership[$memID]->member_name;
				$memberID = $this->method->formatValue(['prefix'=>$_SESSION["unit"],'id'=>$memID],"app_id");
				//var_dump($getMembership);
				$output = "
				<div id='{$_GET['meta']}' class='x_panel no-padding' style='margin-top:3px'>
					<div class='box_title'><h2 class='left'>Share Capital Details</h2></div>
					<div class='form-group'>
						<div class='col-md-5 col-sm-5 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Full Name</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberName}</div>
						</div>
						<div class='col-md-3 col-sm-3 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Mem ID</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberID}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Total Shares</label>
							<div class='col-md-8 col-xs-12 text-input bold' id='share_capital'></div>
						</div>
					</div>
				</div>
				";
				$arrayFields = ['date'=>'Date','data_id'=>'Ref Num','amount'=>'Amount','share_value'=>'Share Value','remarks'=>'Remarks'];
				$stmtData['fields'] = [
					'queue.id',
					'share_capital.date',
					'queue.data_id',
					'share_capital.amount',
					'share_capital.share_value',
					'share_capital.remarks'
				];
				$stmtData['table'] = 'data_queue as queue';
				$stmtData['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_6_share_capital_details AS share_capital ON (share_capital.data_id = queue.data_id)
				   ';
				$stmtData['extra'] = 'ORDER BY data_id DESC';
				$stmtData['arguments']["share_capital.mem_id"] = $memID;
				$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtData['arguments']["queue.activity_id"] = 12;
				$stmtData['arguments']["queue.path_id"] = 6;

				$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getData = $this->method->selectDB($stmtData);
				//var_dump($getData);
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$output .= "<div class='dataRecord'><table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				$totalShareCapitalValue = 0;
				foreach($getData as $queueID => $memberDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$value = $memberDetails->$field;
						switch($field){
							case "amount":
								$value = number_format($value, 2);
							break;
							case "date":
								$value = $this->method->timeDateFormat($value, 'date');
							break;
							case "data_id":
								$value = $this->method->formatValue(['prefix'=>5,'id'=>$value],"app_id");
							break;
						}
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'><span>{$value}</span></td>";
					}
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
					$shareCapitalValue = floatVal($memberDetails->share_value);
					$totalShareCapitalValue = $shareCapitalValue + $totalShareCapitalValue;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table></div>";
				$output .= "
					<script>
					$('#share_capital').text('".number_format($totalShareCapitalValue,0)."');
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0,1,2], 'className': 'alignCenter'},{'aTargets': [3,4], 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info': true
					});
					</script>
					";
				echo $output;
			break;
			case "member_loans":
				$pathValueMembership = "path_1_";
				$memID = $_GET['value']; // MEMBER ID
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValueMembership.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_name ASC';
				//$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				//$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["membership_information.member_id"] = $memID;
				$stmtMembership['arguments']["queue.path_id"] = 1;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$memberName = $getMembership[$memID]->member_name;
				$memberLoansPayable = 0.00;
				$memberID = $this->method->formatValue(['prefix'=>$_SESSION["unit"],'id'=>$memID],"app_id");
				//var_dump($getMembership);
				$output = "
				<div id='{$_GET['meta']}' class='x_panel no-padding' style='margin-top:3px'>
					<div class='box_title'><h2 class='left'>Loan Application Details</h2></div>
					<div class='form-group'>
						<div class='col-md-5 col-sm-5 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Full Name</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberName}</div>
						</div>
						<div class='col-md-3 col-sm-3 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-4 col-sm-4 col-xs-12'>Mem ID</label>
							<div class='col-md-8 col-xs-12 text-input bold'>{$memberID}</div>
						</div>
						<div class='col-md-4 col-sm-4 col-xs-12 no-padding edit item'>
							<label for='less_1_year' class='alignRight number control-label col-md-5 col-sm-5 col-xs-12'>Loans Payable</label>
							<div class='col-md-7 col-xs-12 text-input bold' id='loans_payable'>{$memberLoansPayable}</div>
						</div>
					</div>
				</div>
				";
				$arrayFields = ['data_id'=>'Ref Num','approve_date'=>'Loan Date','maturity_date'=>'Maturity','loan_types'=>'Type','loan_terms'=>'Terms','loan_granted'=>'Amount','amount_paid'=>'Paid Amt','remarks'=>'Remarks']; //,'interest_paid'=>'Paid Int'
				$stmtData['fields'] = [
					'queue.id',
					'loan_summary.approve_date AS approve_date',
					'loan_summary.maturity_date AS maturity_date',
					'queue.data_id',
					'loans_details.loan_terms',
					'loans_details.loan_types',
					'loan_summary.loan_granted',
					'loan_summary.remarks',
					'IFNULL((
						SELECT SUM(payments.payment_principal)
						FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions payments
						JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue queue ON (queue.data_id = payments.data_id)
						WHERE queue.path_id = 8 AND queue.activity_id = 18 AND payments.loans_id = loans_details.id
					), 0) AS amount_paid'
					//'IFNULL((SELECT SUM(payment_interest) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions WHERE loans_id = loans_details.id), 0) AS interest_paid'
				];
				$stmtData['table'] = 'data_queue as queue';
				$stmtData['join'] = '
				   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
				   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
				   ';
				$stmtData['extra'] = 'ORDER BY data_id DESC';
				$stmtData['arguments']["loans_details.mem_id"] = $memID;
				$stmtData['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtData['arguments']["queue.activity_id"] = 16;
				$stmtData['arguments']["queue.path_id"] = 7;

				$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getData = $this->method->selectDB($stmtData);
				//var_dump($getData);
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$output .= "<div class='dataRecord'><table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				$totalLoanAmount = 0;
				$totalLoanPaidAmount = 0;
				foreach($getData as $queueID => $memberDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					$totalLoanAmount = $totalLoanAmount + $memberDetails->loan_granted;
					$totalLoanPaidAmount = $totalLoanPaidAmount + $memberDetails->amount_paid;
					foreach($getFields as $field){
						$class = "";
						$value = $memberDetails->$field;
						switch($field){
							case "approve_date": case "maturity_date":
								$value = $this->method->timeDateFormat($value, 'date');
							break;
							case "loan_granted": case "amount_paid": // case "interest_paid": 
								$value = number_format($value, 2);
							break;
							case "data_id":
								$value = $this->method->formatValue(['prefix'=>7,'id'=>$value],"app_id");
							break;
							case "loan_types":
								$value = $_SESSION['codebook'][$field][$value]->meta_value;
							break;
							case "remarks":
								$class = "ellipsis";
							break;
						}
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'><span class='{$class}'>{$value}</span></td>";
					}
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$loanBalanceAmount = $totalLoanAmount - $totalLoanPaidAmount;
				$loanBalanceAmount = number_format($loanBalanceAmount, 2);
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table></div>";
				$output .= "
					<script>
					$('#loans_payable').text('{$loanBalanceAmount}');
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0,1,2,3,4,5], 'className': 'alignCenter'},{'aTargets': [6,7], 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info': true
					});
					</script>
					";
				echo $output;
			break;
			
			case "recipient_listings":
				$output = "";
				$isDefault = true;
				$isMember = false;
				$pathID = $_GET['value']; // PATH ID
				$keyword = $_GET['keyword']; // KEYWORD
				$meta_name = "cash";
				switch($_GET['cash_trans']){
					case "5": case "10": // OFFICERS HONORARIUM, BIRTHDAY GIFT
						$isMember = true;
						$isDefault = false;
						$arrayFields = ['client_id'=>'Member ID','client_name'=>'Members Name','client_type'=>'Membership Type','address'=>'Account Type'];
						$pathValue = "path_1_";
						$stmtMembership['fields'] = [
						'queue.data_id id',
						'membership_information.member_id client_id',
						'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS client_name',
						'membership_information.membership_type client_type',
						'membership_information.account_type address'
						];
						$stmtMembership['table'] = 'data_queue as queue';
						$stmtMembership['join'] = '
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValue.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValue.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
						   ';
						$stmtMembership['extra'] = "AND (personal_information.first_name LIKE '%{$keyword}%' OR personal_information.middle_name LIKE '%{$keyword}%' OR personal_information.last_name LIKE '%{$keyword}%') ORDER BY client_name ASC";
						if($keyword == "") $stmtMembership['extra'] = 'ORDER BY client_name ASC';
						$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
						$stmtMembership['arguments']["membership_information.membership_status"] = 1;
						$stmtMembership['arguments']["queue.path_id"] = 1;
						$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
						$getMembership = $this->method->selectDB($stmtMembership);
						//var_dump($getMembership);
					break;
					case "1": case "4": case "6": // CASH ADVANCE, REPLINISHMENT, EXCESS FUND LIQUIDATION
						$isDefault = false;
						$arrayFields = ['client_id'=>'User ID','client_name'=>'Staff/Employee','client_type'=>'Position','address'=>'Branch/Unit'];
						$stmtMembership['fields'] = [
							'users.id',
							'users.unit address',
							'users.id client_id',
							'users.position client_type',
							'CONCAT(IFNULL(users.firstname,""),", ",IFNULL(users.lastname,"")) AS client_name'
						];
						$stmtMembership['table'] = 'users as users';
						$stmtMembership['extra'] = "AND (users.firstname LIKE '%{$keyword}%' OR users.lastname LIKE '%{$keyword}%') ORDER BY client_name ASC";
						if($keyword == "") $stmtMembership['extra'] = 'ORDER BY client_name ASC';
						$stmtMembership['arguments']["users.status"] = 1;

						$stmtMembership += ['schema'=>Info::DB_SYSTEMS,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
						$getMembership = $this->method->selectDB($stmtMembership);
					break;
					default: // USERS WILL DISPLAY
						$arrayFields = ['client_id'=>'Client ID','client_name'=>'Client Name','client_type'=>'Client Type','address'=>'Address'];
						$stmtMembership['fields'] = [
							'queue.data_id',
							'queue.unit',
							'client_information.client_id',
							'client_information.client_type', 
							'client_information.client_name',
							'CONCAT(IFNULL(address_information.address_barangay,""),", ",IFNULL(address_information.address_city,""),", ",IFNULL(address_information.address_province,"")) AS address'
						];
						$stmtMembership['table'] = 'data_queue as queue';
						$stmtMembership['join'] = '
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_9_client_information AS client_information ON (client_information.data_id = queue.data_id)
						   LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_9_address_information AS address_information ON (address_information.data_id = queue.data_id)
						   ';
						$stmtMembership['extra'] = "AND client_information.client_name LIKE '%{$keyword}%' ORDER BY client_name ASC";
						if($keyword == "") $stmtMembership['extra'] = 'ORDER BY client_name ASC';
						$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
						$stmtMembership['arguments']["queue.path_id"] = 9;

						$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
						$getMembership = $this->method->selectDB($stmtMembership);
					break;
				}
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$tableHead .= "<th class='action status no-padding' width='3%'>&nbsp;</th>";
				$output = "<h2 class='title'><span class='sub'>Module: </span>".str_replace("_"," ",$meta_name)." Transaction</h2>";
				$output .= "<table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				foreach($getMembership as $queueID => $memberDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$class = "";
						$value = $memberDetails->$field;
						$thisValue = $value;
						switch($field){
							case "client_id":
								if($isDefault){
									$thisValue = $this->method->formatValue(['prefix'=>$memberDetails->client_type,'id'=>$value],"client_id");
								}
							break;
							case "client_type":
								if($isMember){
									$thisValue = $_SESSION['codebook']['membership_type'][$value]->meta_value;
								}elseif($isDefault){
									$thisValue = $_SESSION['codebook'][$field][$value]->meta_value;
								}
							break;
							case "address":
								$class = "ellipsis";
								if($isMember){
									$thisValue = $_SESSION['codebook']['account_type'][$value]->meta_value;
								}elseif(!$isDefault){
									$thisValue = $_SESSION['codebook']['unit'][$value]->meta_value;
								}
								if($value == ", , ") $thisValue = Info::EMPTY_VAL;
							break;
						}
						$inputHidden = $this->method->inputGroup(['type'=>'hidden','id'=>$field,'name'=>$field.'-'.$queueID,'value'=>$value]);
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'>{$inputHidden}<span class='{$class}'>{$thisValue}</span></td>";
					}
					$tbodyColumns .= "<td class='action no-padding'><button type='button' onclick='setRecipientInformation(this)' value='{$queueID}' class='btn btn-primary popupBtn' data-dismiss='modal'><i class='fa fa-sign-in'></i></button></td>";
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table>";
				//var_dump($getMembership);
				$output .= "
					<script>
					function setRecipientInformation(me){
						id = me.value;
						tableElement = 'table#datatable-{$_GET['meta']} tr#member-'+id;
						recipients_name = $(tableElement+' input[name=client_name-'+id+']').val();
						recipients_id = $(tableElement+' input[name=client_id-'+id+']').val();
						recipients_id_value = $(tableElement+' td.client_id > span').text();
						recipients_type = $(tableElement+' input[name=client_type-'+id+']').val();
						recipients_type_value = $(tableElement+' td.client_type > span').text();
						recipients_address = $(tableElement+' input[name=address-'+id+']').val();
						recipients_address_value = $(tableElement+' td.address > span').text();
						
						$('#cashInformation input[name=recipients_name]').val(recipients_name);
						$('#cashInformation input[name=recipients_id]').val(recipients_id_value);
						$('#cashInformation input[name=recipients_type]').val(recipients_type_value);
						$('#cashInformation input[name=recipients_address]').val(recipients_address_value);
						
						recipientInfo = \"\"+recipients_name+\":\"+recipients_id_value+\":\"+recipients_type_value+\":\"+recipients_address_value+\"\";  
						recipient_info = recipientInfo;
						$('input#recipient_id').val(recipients_id);
						$('input#recipient_info').val(recipient_info);
						
						$('#{$meta_name}_details, #path_{$meta_name}.submitBottomBox').removeClass('lock');

						$('button[name=SubmitData], button[name=SaveData]').attr('onclick','submitData(\"createData\",this)');
					}
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0,1], 'className': 'alignCenter'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info':     true
					});
					</script>
					";
				echo $output;
			break;
			
			case "loans_listings":
				$output = "";
				$activityLoanCompleted = 16; // LOANS COMPLETED ACTIVITY
				$activityLoanPaymentCompleted = 18; // LOANS COMPLETED ACTIVITY
				$penaltyRate = 0.1;
				$pathID = $_GET['value']; // PATH ID
				$keyword = $_GET['keyword']; // KEYWORD
				switch($pathID){
					case "8": // LOANS PAYMENT
						$meta_name = "loans_payment";
					break;
				}
				$arrayFields = ['loan_id'=>'Ref Num','mem_info'=>'Members Name','loan_types'=>'Loan Type','approve_date'=>'Date','due_date'=>'Due Date','loan_terms'=>'Terms','loan_granted'=>'Loan Amt','arrears'=>'Arrears','interest_due'=>'Interest','principal_due'=>'Principal','penalty_due'=>'Penalty','total_due'=>'Total Due'];				
				### CHECKING LOAN PAYMENTS ###
				// $stmtCheckPayments['fields'] = [
					// 'loans_payment.loans_id',
					// 'queue.data_id',
					// 'loans_payment.payment_principal',
					// 'loans_payment.payment_interest',
				// ];
				// $stmtCheckPayments['table'] = 'data_queue as queue';
				// $stmtCheckPayments['join'] = '
				   // JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions AS loans_payment ON (loans_payment.data_id = queue.data_id)
				   // ';
				// $stmtCheckPayments['extra'] = 'AND ORDER BY queue.data_id ASC';
				// $stmtCheckPayments['arguments']["queue.unit"] = $_SESSION["unit"];
				// $stmtCheckPayments['arguments']["queue.path_id"] = 8;
				// $stmtCheckPayments['arguments']["queue.activity_id"] = $activityLoanPaymentCompleted;
				// $stmtCheckPayments += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC];
				// $checkPayments = $this->method->selectDB($stmtCheckPayments);
				### CHECKING LOAN PAYMENTS ###
				
				$stmtMembership['fields'] = [
					'queue.data_id',
					'loans_details.id AS loan_id',
					'loans_details.loan_types',
					'loan_summary.interest_type',
					'loans_details.mem_id',
					'loans_details.mem_info',
					'loans_details.loan_terms',
					'loan_summary.loan_granted',
					'loan_summary.approve_date',
					'IFNULL((SELECT SUM(interest_due) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id), 0) AS total_interest',
					'IFNULL((SELECT SUM(interest_due) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id AND due_date <= NOW() ORDER BY num ASC), 0) AS should_interest',
					'IFNULL((SELECT SUM(principal_due) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id AND due_date <= NOW() ORDER BY num ASC), 0) AS should_principal',
					'IFNULL((SELECT due_date FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id AND due_date >= NOW() ORDER BY num ASC LIMIT 1), 0) AS due_date',
					'IFNULL((SELECT interest_due FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id AND due_date >= NOW() ORDER BY num ASC LIMIT 1), 0) AS interest_due',
					'IFNULL((SELECT principal_due FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE loan_schedule.data_id = loans_details.data_id AND due_date >= NOW() ORDER BY num ASC LIMIT 1), 0) AS principal_due',
					'IFNULL((SELECT SUM(principal_due) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_schedule WHERE data_id = loans_details.data_id AND due_date <= NOW()), 0) AS principal_should_be',
					'IFNULL((SELECT SUM(payment_principal) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions payment_transaction JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue data_queue ON data_queue.data_id = payment_transaction.data_id WHERE data_queue.activity_id = 18 AND payment_transaction.loans_id = loans_details.id), 0) AS payment_principal',
					'IFNULL((SELECT SUM(payment_interest) FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions payment_transaction JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue data_queue ON data_queue.data_id = payment_transaction.data_id WHERE data_queue.activity_id = 18 AND payment_transaction.loans_id = loans_details.id), 0) AS payment_interest'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = loans_details.data_id)
				   ';
				$stmtMembership['extra'] = "AND loans_details.mem_info LIKE '%{$keyword}%' ORDER BY mem_info ASC";
				//$stmtMembership['arguments']["loan_summary.maturity_date <"] = $this->method->getDate('date');
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["queue.activity_id"] = $activityLoanCompleted;
				$stmtMembership['arguments']["queue.path_id"] = 7;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$tableHead .= "<th class='action status no-padding' width='3%'>&nbsp;</th>";
				$output = "<h2 class='title'><span class='sub'>Module: </span>".str_replace("_"," ",$meta_name)."</h2>";
				$output .= "<table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				foreach($getMembership as $queueID => $loanDetails){
					$principalBalance = floatVal($loanDetails->loan_granted) - floatVal($loanDetails->payment_principal);
					$principalBalanceRound = round($principalBalance, 2);
					if($principalBalanceRound > 0){
						$penaltyDue = $arrears = 0;
						$dueDate = $loanDetails->due_date;
						$getPaymentRow = (isset($checkPayments[$loanDetails->loan_id])) ? $checkPayments[$loanDetails->loan_id] : [];
						$totalPaymentInterest = floatVal($loanDetails->payment_interest);
						
						$monthlyAmortization = $loanDetails->loan_granted / $loanDetails->loan_terms;
						$loanMonths = $this->method->computeTime($loanDetails->approve_date,$this->method->getDate('date'),"months");
						//$getShouldBe = $monthlyAmortization * $loanMonths;
						//$shouldBe = $loanDetails->loan_granted - $getShouldBe;
						$shouldBe = floatVal($loanDetails->loan_granted) - floatVal($loanDetails->principal_should_be);
						if($shouldBe < 0) $shouldBe = 0;
						$arrears = $principalBalance - $shouldBe;
						if($arrears < 0) $arrears = 0;
						// $thisDueDate = date('Y-m-d', strtotime($dueDate));
						// $thisDate = date('Y-m-d', strtotime($this->method->getDate('date')));
						$dueInterest = $loanDetails->interest_due; //($loanDetails->should_interest > 0) ? $loanDetails->should_interest : $loanDetails->interest_due;
						$duePrincipal = $loanDetails->principal_due; //($loanDetails->should_principal > 0) ? $loanDetails->should_principal : $loanDetails->principal_due;
						// if($loanDetails->should_principal > 0){
							// $arrears = $loanDetails->should_principal - $totalPaymentPrincipal;
						// }
						$interestType = $loanDetails->interest_type;
						if($interestType == 1){ // PREPAID
							$dueInterest = 0;
						}else{
							//$dueInterest = $dueInterest - $totalPaymentInterest;
							//$interestDue = $dueInterest;
						}
						if($arrears > 0){
							$penaltyDue = $arrears * $penaltyRate;
						}
						$principalDue = $duePrincipal;
						if($loanDetails->due_date == "0"){ // OVERDUE
							$dueInterest = $loanDetails->total_interest - $totalPaymentInterest;
							$principalDue = $principalBalance;
						}
						$totalDue = $duePrincipal + $dueInterest + $arrears + $penaltyDue;
						
						$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
						foreach($getFields as $field){
							$value = $loanDetails->$field;
							$thisValue = $value;
							switch($field){
								case "mem_info":
									$arrayMemInfo = explode(":", $value);
									$value = $arrayMemInfo[0];
									$thisValue = $value;
								break;
								case "loan_id":
									$thisValue = $this->method->formatValue(['prefix'=>$_SESSION["unit"],'id'=>$value],"app_id");
								break;
								case "loan_types":
									$thisValue = $_SESSION['codebook'][$field][$value]->meta_value;
								break;
								case "loan_granted": case "principal_due": case "interest_due": case "arrears": case "penalty_due":
									switch($field){
										case "principal_due":
											$value = $duePrincipal;
											$thisValue = $principalDue;
										break;
										case "interest_due":
											$value = $dueInterest;
											$thisValue = $value;
										break;
										case "arrears":
											$value = $arrears;
											$thisValue = $value;
										break;
										case "penalty_due":
											$value = $penaltyDue;
											$thisValue = $value;
										break;
									}
									$thisValue = number_format($thisValue, 2);
								break;
								case "total_due":
									$interestType = $loanDetails->interest_type;
									$dueInterest = ($interestType == 1) ? 0 : $loanDetails->interest_due; // REMOVING INTEST DUE (PREPAID)
									$value = $totalDue;//$loanDetails->principal_due + $interestDue + $penaltyDue;
									$thisValue = number_format($value, 2);
								break;
								case "due_date":
									$dayDifference = $this->method->computeTime(date('Y-m-d', strtotime($dueDate)),$this->method->getDate('date'),'days');
									//$value = $dueDate;//$dayDifference;
									$thisValue = ($value == 0) ? "Default" : $value;
								break;
							}
							$inputHidden = $this->method->inputGroup(['type'=>'hidden','id'=>$field,'name'=>$field.'-'.$queueID,'value'=>$value]);
							$tbodyColumns .= "<td class='{$field} paddingHorizontal'>{$inputHidden}<span>{$thisValue}</span></td>";
						} // END FOREACH
						$inputInterestType = $this->method->inputGroup(['type'=>'hidden','id'=>'loan_interest_type','name'=>'loan_interest_type-'.$queueID,'value'=>$loanDetails->interest_type]);
						$tbodyColumns .= "<td class='action no-padding'>{$inputInterestType}<button type='button' onclick='setLoansInformation(this)' value='{$queueID}' class='btn btn-primary popupBtn' data-dismiss='modal'><i class='fa fa-sign-in'></i></button></td>";
						$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
						$cnt++;
					} // END IF HAS LOAN_BALANCE
					
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table>";
				//var_dump($getMembership);
				$output .= "
					<script>
					function setLoansInformation(me){
						id = me.value;
						tableElement = 'table#datatable-{$_GET['meta']} tr#member-'+id;
						members_name = $(tableElement+' input[name=mem_info-'+id+']').val();
						loan_id = $(tableElement+' input[name=loan_id-'+id+']').val();
						loan_amount = parseFloat($(tableElement+' input[name=loan_granted-'+id+']').val());
						interest_due = parseFloat($(tableElement+' input[name=interest_due-'+id+']').val());
						principal_due = parseFloat($(tableElement+' input[name=principal_due-'+id+']').val());
						penalty_due = parseFloat($(tableElement+' input[name=penalty_due-'+id+']').val());
						interest_type = parseFloat($(tableElement+' input[name=loan_interest_type-'+id+']').val());
						arrears = parseFloat($(tableElement+' input[name=arrears-'+id+']').val());
						principalDue = principal_due + arrears;
						
						
						ref_num = $(tableElement+' td.loan_id span').text();
						loan_granted = $(tableElement+' td.loan_granted span').text();
						total_due = $(tableElement+' td.total_due span').text();
						
						$('#loansInformation input[name=members_name]').val(members_name);
						$('#loansInformation input[name=loan_amount]').val(loan_granted);
						$('#loansInformation input[name=mem_loans_id]').val(ref_num);
						$('#loansInformation input[name=total_due]').val(total_due);
						$('#loansInformation input[name=interest_type]').val(interest_type);
						
						$('input[name=\"loans_payment_transactions[payment_principal]\"]').val(principalDue);
						$('input[name=\"loans_payment_transactions[payment_interest]\"]').val(interest_due);
						$('input[name=\"loans_payment_transactions[payment_savings]\"]').val(0.00);
						$('input[name=\"loans_payment_transactions[payment_cbu]\"]').val(0.00);
						$('input[name=\"loans_payment_transactions[payment_penalty]\"]').val(penalty_due);
						
						loansInfo = \"\"+members_name+\":\"+ref_num+\":\"+loan_granted+\":\"+total_due+\":\"+interest_type+\"\";  
						loans_info = loansInfo;//JSON.stringify(loansInfo); 
						$('input#loans_id').val(loan_id);
						$('input#loans_info').val(loans_info);

						$('#{$meta_name}_transactions, #path_{$meta_name}.submitBottomBox').removeClass('lock');

						$('button[name=SubmitData], button[name=SaveData]').attr('onclick','submitData(\"createData\",this)');
						computeAmountDue();
					}
					
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0,1,3,4,5,6], 'className': 'alignCenter'},{'aTargets': [7,8,9,10,11,12], 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info': true
					});
					</script>
					";
				echo $output;
			break;
			
			case "savings_listings":
				$output = "";
				$activitySavingsCompleted = 4; // SAVINGS COMPLETED ACTIVITY
				$pathID = $_GET['value']; // PATH ID
				$keyword = $_GET['keyword']; // KEYWORD
				switch($pathID){
					case "3": // DEPOSITS
						$meta_name = "deposits";
					break;
					case "4": // WITHDRAWAL
						$meta_name = "withdrawal";
						$withdrawalSettings = $this->method->methodSettings(['meta_key'=>'module_policy','name'=>'withdrawal_policy']); // GET METHODS MODULE SETTINGS
						$minimumBalance = $withdrawalSettings['regular_savings[minimum_balance]'];
					break;
				}
				$arrayFields = ['mem_info'=>'Members Name','savings_id'=>'Acnt Number','product_savings'=>'Account Type','balance'=>'Balance'];
				$pathValue = "path_2_";
				$stmtMembership['fields'] = [
					'queue.data_id',
					'queue.unit',
					'savings_details.id AS savings_id',
					'savings_details.mem_info',
					'savings_details.product_savings',
					'savings_details.product_savings AS account_type',
					'savings_details.balance'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValue.'savings_details AS savings_details ON (savings_details.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = "AND savings_details.mem_info LIKE '%{$keyword}%' ORDER BY savings_id ASC";
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["queue.activity_id"] = $activitySavingsCompleted;
				$stmtMembership['arguments']["savings_details.savings_status"] = 1;
				$stmtMembership['arguments']["queue.path_id"] = 2;
				if($pathID == 3) $stmtMembership['arguments']["savings_details.product_savings"] = 1;

				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$tableHead .= "<th class='action status no-padding' width='3%'>&nbsp;</th>";
				$output = "<h2 class='title'><span class='sub'>Module: </span>".str_replace("_"," ",$meta_name)."</h2>";
				$output .= "<table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				foreach($getMembership as $queueID => $memberDetails){
					$maxBalance = $memberDetails->balance;
					switch($pathID){ 
						case 4: // WITHDRAWAL
							if($memberDetails->product_savings == 1) $maxBalance = floatVal($maxBalance) - floatVal($minimumBalance);
						break;
					}
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$value = $memberDetails->$field;
						switch($field){
							case "mem_info":
								$arrayMemInfo = explode(":", $value);
								$value = $arrayMemInfo[0];
							break;
							case "product_savings":
								$value = $_SESSION['codebook'][$field][$value]->meta_value;
							break;
							case "balance":
								$value = number_format($value, 2);
							break;
							case "savings_id":
								$value = $this->method->formatValue(['prefix'=>$memberDetails->unit,'id'=>$value,'savings_type'=>$memberDetails->product_savings],"savings_id");
							break;
						}
						$inputHidden = $this->method->inputGroup(['type'=>'hidden','id'=>$field,'name'=>$field.'-'.$queueID,'value'=>$value]);
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'>{$inputHidden}{$value}</td>";
					}
					$inputMaxAmount = $this->method->inputGroup(['type'=>'hidden','id'=>'max_balance','name'=>'max_balance-'.$queueID,'value'=>$maxBalance]);
					$inputSavingsType = $this->method->inputGroup(['type'=>'hidden','id'=>'savings_type','name'=>'savings_type-'.$queueID,'value'=>$memberDetails->product_savings]);
					$inputSavingsID = $this->method->inputGroup(['type'=>'hidden','id'=>'mem_savings_id','name'=>'mem_savings_id-'.$queueID,'value'=>$memberDetails->savings_id]);
					$tbodyColumns .= "<td class='action no-padding'>{$inputSavingsType}{$inputSavingsID}{$inputMaxAmount}<button type='button' onclick='setSavingsInformation(this)' value='{$queueID}' class='btn btn-primary popupBtn' data-dismiss='modal'><i class='fa fa-sign-in'></i></button></td>";
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table>";
				//var_dump($getMembership);
				$output .= "
					<script>
					function setSavingsInformation(me){
						id = me.value;
						tableElement = 'table#datatable-{$_GET['meta']} tr#member-'+id;
						members_name = $(tableElement+' input[name=mem_info-'+id+']').val();
						savings_id = $(tableElement+' input[name=savings_id-'+id+']').val();
						product_savings = $(tableElement+' input[name=product_savings-'+id+']').val();
						mem_savings_id = $(tableElement+' input[name=mem_savings_id-'+id+']').val();
						savings_type = $(tableElement+' input[name=savings_type-'+id+']').val();
						balance = $(tableElement+' input[name=balance-'+id+']').val();
						maxAmount = $(tableElement+' input[name=max_balance-'+id+']').val();
						
						$('#savingsInformation input[name=members_name]').val(members_name);
						$('#savingsInformation input[name=mem_savings_id]').val(savings_id);
						$('#savingsInformation input[name=account_type]').val(product_savings);
						$('#savingsInformation input[name=savings_type]').val(savings_type);
						$('#savingsInformation input[name=balance]').val(balance);
						$('input[name=max_amount]').val(maxAmount);
						
						".($pathID == 4 ? 'setSavingsDetails();' : '')." // ON PROCEDURE
						savingsInfo = \"\"+members_name+\":\"+savings_id+\":\"+product_savings+\":\"+balance+\"\";  
						savings_info = savingsInfo;//JSON.stringify(savingsInfo); 
						$('input#savings_id').val(mem_savings_id);
						$('input#savings_info').val(savings_info);

						$('#{$meta_name}_transactions, #path_{$meta_name}.submitBottomBox').removeClass('lock');

						$('button[name=SubmitData], button[name=SaveData]').attr('onclick','submitData(\"createData\",this)');
					}
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0], 'className': 'alignCenter'},{'aTargets': [4], 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info':     false
					});
					</script>
					";
				echo $output;
			break;
			case "member_listings":
				$output = "";
				$colAlignRight = "[5]";
				$pathID = $_GET['value']; // PATH ID
				$keyword = $_GET['keyword']; // KEYWORD
				$activityAction = "createData";
				$extraJScript = "$('button[name=SaveData], button[name=SubmitData]').attr('onclick','submitData(\"{$activityAction}\",this)').removeClass('lock');";
				switch($pathID){
					case "7": // LOANS
						$colAlignRight = "[5,6]";
						$meta_name = "loans";
						$tableTitle = "Loan Applications";
						$checkPendingActivity = [];
						### CHECKING LOANS PAYMENTS AND PAYABLES ###
						$stmtCheckMember['fields'] = [
							'loans_details.mem_id',
							'loan_summary.loan_granted amount',
							'IFNULL((
								SELECT SUM(payment_principal)
								FROM '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.data_queue data_queue
								JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_8_loans_payment_transactions loans_payment ON (data_queue.data_id = loans_payment.data_id)
								WHERE data_queue.path_id = 8 AND data_queue.activity_id = 18 AND loans_payment.loans_id = loans_details.id
							), 0) AS payments',
						];
						$stmtCheckMember['table'] = 'data_queue as queue';
						$stmtCheckMember['join'] = '
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
						   ';
						$stmtCheckMember['extra'] = 'ORDER BY queue.data_id ASC';
						$stmtCheckMember['arguments']["queue.unit"] = $_SESSION["unit"];
						$stmtCheckMember['arguments']["queue.path_id"] = 7;
						$stmtCheckMember['arguments']["queue.activity_id"] = 16;
						$stmtCheckMember += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC];
						$checkMember = $this->method->selectDB($stmtCheckMember);
						### CHECKING LOANS PAYMENTS AND PAYABLES ###
						
						$extraJScript = "
							$('button[name=SaveData]').attr('onclick','submitData(\"{$activityAction}\",this)').removeClass('lock');
							memID = $('input#mem_id').val();
							loanType = $('#loan_types').val();
							if(loanType){
								setLoanBalanceBox({loan_type:loanType,mem_id:memID});
							}
						";
					break;
					case "2": // SAVINGS
						$meta_name = "savings";
						$tableTitle = "Members Savings";
						$checkPendingActivity = [];
					break;
					case "5": // CBU
						$meta_name = "cbu";
						$tableTitle = "Members Capital Build-Up";
						$pathCBU = "path_5_";
						$stmtCheckMember['fields'] = [
							'cbu_details.mem_id',
						];
						$stmtCheckMember['table'] = 'data_queue as queue';
						$stmtCheckMember['join'] = '
						   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathCBU.'cbu_details AS cbu_details ON (cbu_details.data_id = queue.data_id)
						   ';
						$stmtCheckMember['extra'] = 'ORDER BY queue.data_id ASC';
						$stmtCheckMember['arguments']["queue.unit"] = $_SESSION["unit"];
						$stmtCheckMember['arguments']["queue.activity_id"] = 9;
						$stmtCheckMember['arguments']["queue.path_id"] = 5;
						$stmtCheckMember += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_COLUMN];
						$checkPendingActivity = $this->method->selectDB($stmtCheckMember);
					break;
					case "6": // SHARE CAPITAL
						$meta_name = "share_capital";
						$tableTitle = "Members Share Capital";
						$checkPendingActivity = [];
					break;
				}
				$arrayFields = ['member_name'=>'Members Name','member_id'=>'Member ID','account_type'=>'Acnt Type','membership_type'=>'Mem Type'];
				$pathValue = "path_1_";
				$stmtMembership['fields'] = [
				'queue.id',
				'queue.data_id',
				'membership_information.member_id',
				'membership_information.membership_date',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name',
				'membership_information.account_type',
				'membership_information.membership_type'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValue.'membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$pathValue.'personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				//var_dump($keyword);
				//$stmtMembership['extra'] = 'ORDER BY member_name ASC';
				$stmtMembership['extra'] = "AND (personal_information.first_name LIKE '%{$keyword}%' OR personal_information.middle_name LIKE '%{$keyword}%' OR personal_information.last_name LIKE '%{$keyword}%') ORDER BY member_name ASC";
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["membership_information.membership_type<"] = 2;
				$stmtMembership['arguments']["queue.path_id"] = 1;

				if($pathID == 7){ // LOANS, ADDING QUERY STATEMENTS
					//unset($arrayFields['account_type']); // REMOVING DEFAULT FIELDS
					$arrayFields['balance'] = "Savings";
					$arrayFields['loans_payable'] = "Loans Payable";
					$stmtMembership['fields'][] = "savings_details.balance";
					$stmtMembership['fields'][] = "0 AS loans_payable";
					$stmtMembership['join'] = $stmtMembership['join'].'LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_2_savings_details AS savings_details ON (savings_details.mem_id = membership_information.member_id)';
				}
				
				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				//var_dump($getMembership);
				$tableHead = "<th class='num'>&nbsp;</th>";
				foreach($arrayFields as $field => $value){ // TABLE THEAD
					$tableHead .= "<th class='{$field}'>{$value}</th>"; 
				}
				$tableHead .= "<th class='action status no-padding' width='3%'>&nbsp;</th>";
				$output = "<h2 class='title'><span class='sub'>Module: </span>{$tableTitle}</h2>";
				$output .= "<table id='datatable-{$_GET['meta']}' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$tableHead}</tr></thead>";
				$getFields = array_keys($arrayFields);
				$tbodyRow = "";
				$cnt = 1;
				foreach($getMembership as $queueID => $memberDetails){
					$tbodyColumns = "<td class='num paddingHorizontal'>{$cnt}</td>";
					foreach($getFields as $field){
						$value = $memberDetails->$field;
						switch($field){
							case "balance":
								$value = number_format($value, 2);
							break;
							case "loans_payable":
								$loanDetails = (isset($checkMember[$memberDetails->member_id])) ? $checkMember[$memberDetails->member_id] : [];
								$loanAmount = array_column($loanDetails, 'amount');
								$totalLoanAmount = array_sum($loanAmount);
								$loanPayments = array_column($loanDetails, 'payments');
								$totalLoanPayments = array_sum($loanPayments);
								$loansPayable = $totalLoanAmount - $totalLoanPayments;
								$value = number_format($loansPayable, 2);
							break;
							case "membership_type": case "account_type": // CODEBOOK
								$value = $_SESSION['codebook'][$field][$value]->meta_value;
							break;
						}
						### CONVERTING THE 2 COLUMNS FIELDS
						if($pathID == 7){ // LOANS
							if($field == "balance") $field = "column_3";
							if($field == "loans_payable") $field = "column_4";
						}else{
							if($field == "account_type") $field = "column_3";
							if($field == "membership_type") $field = "column_4";
						}
						### CONVERTING THE 2 COLUMNS FIELDS END
						$inputHidden = $this->method->inputGroup(['type'=>'hidden','id'=>$field,'name'=>$field.'-'.$queueID,'value'=>$value]);
						$tbodyColumns .= "<td class='{$field} paddingHorizontal'>{$inputHidden}<span>{$value}</span></td>";
					}
					$onClickMemberInfo = "onclick='setMemberInformation(this)'";
					$onClickBtnLock = "";
					if(in_array($memberDetails->member_id , $checkPendingActivity)){
						$onClickMemberInfo = "";
						$onClickBtnLock = "lock";
					}
					$tbodyColumns .= "<td class='action no-padding'><button type='button' {$onClickMemberInfo} value='{$queueID}' class='btn btn-primary popupBtn {$onClickBtnLock}' data-dismiss='modal'><i class='fa fa-sign-in'></i></button></td>";
					$tbodyRow .= "<tr id='member-{$queueID}'>{$tbodyColumns}</tr>";
					$cnt++;
				}
				$output .= "<tbody>{$tbodyRow}</tbody>";
				$output .= "</table>";
				//var_dump($getMembership);
				$output .= "
					<script>
					function setMemberInformation(me){
						id = me.value;
						tableElement = 'table#datatable-{$_GET['meta']} tr#member-'+id;
						members_name = $(tableElement+' input[name=member_name-'+id+']').val();
						member_id = $(tableElement+' input[name=member_id-'+id+']').val();
						column_3 = $(tableElement+' input[name=column_3-'+id+']').val();
						column_4 = $(tableElement+' input[name=column_4-'+id+']').val();
						console.log(tableElement);
						
						$('#memberInformation input[name=members_name]').val(members_name);
						$('#memberInformation input[name=member_id]').val(member_id);
						$('#memberInformation input[name=column_3]').val(column_3);
						$('#memberInformation input[name=column_4]').val(column_4);
						
						members_name = $('#memberInformation input[name=members_name]').val();
						member_id = $('#memberInformation input[name=member_id]').val();
						//account_type = $('#memberInformation input[name=account_type]').val();
						//membership_type = $('#memberInformation input[name=membership_type]').val();

						memberInfo = \"\"+members_name+\":\"+member_id+\":\"+column_3+\":\"+column_4+\"\";  
						member_info = memberInfo;//JSON.stringify(memberInfo); 

						$('input#mem_id').val(member_id);
						$('input#mem_info').val(member_info);

						$('#{$meta_name}_details, #path_{$meta_name}.submitBottomBox').removeClass('lock');
						
						{$extraJScript}
					}
					
					$('#datatable-{$_GET['meta']}').DataTable({
						'aoColumnDefs': [{'aTargets': [0], 'className': 'alignCenter'},{'aTargets': {$colAlignRight}, 'className': 'alignRight'}],
						'paging':   true,
						'searching': true,
						//'ordering': false,
						'info':     true
					});
					</script>
					";
				//var_dump($stmtMembership['fields']);
				echo $output;
			break;
			
			case "cash_type":
				$options = "";
				$arrayOptions = $this->method->array_search_by_key($_SESSION["codemeta"]["cash_option"], 'meta_parent', $_GET["value"], 'meta_value');
				$getOptions = implode(",",array_keys($arrayOptions));
				$stmtCashOptions = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['status'=>1,'meta_key'=>'cash_option'],'extra'=>' AND id IN ('.$getOptions.')','pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC,'fields'=>['meta_id','meta_option','meta_value']];
				$getCashOptions = $this->method->selectDB($stmtCashOptions);
				$options .= "<select title='Select Cash Transaction Type...' id='cash_transactions_option' name='cash_transactions_option' class='select2_group form-control select2-hidden-accessible' tabindex='-1' required='' placeholder='Select Cash Transaction Type...'>";
				$options .= "<option></option>";
				foreach($getCashOptions as $metaID => $metaDetails){
					$options .= "<option value='{$metaID}'>".$metaDetails['meta_value']."</option>";
				}
				$options .= "</select>";
				
				$scriptJS = "
				<script>
					function resetCashInformation(){
						metaArray = ['recipients_name','recipients_id','recipients_type','recipients_address'];
						$.each(metaArray, function( index, value ) {
							$('#cashInformation input[name='+value+']').val('');
						});
						$('input#recipient_id').val('');
					}
					
					$('select#cash_transactions_option').select2({placeholder: 'Select Transaction Type...',allowClear: false});
					$('select#cash_transactions_option').on('change', function (){
						value = $(this).val();
						$('input#cash_trans').val(value);
						$('button#viewRecipient').attr('cash_trans',value);
						$('#cashInformation').removeClass('lock');
						resetCashInformation();
					});
					
					resetCashInformation();
				</script>
				";
				echo $options.$scriptJS;
				//var_dump($getCashOptions);
			break;
			
			case "post_logs":
				$stmtDataLogs = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$_GET["value"]],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'','fields'=>['path_id','data_logs']];
				$getDataLogs = $this->method->selectDB($stmtDataLogs);
				$pathID = array_keys($getDataLogs);
				echo $this->method->postLogs(json_decode($getDataLogs[$pathID[0]], true),$pathID[0]);
				echo "
				<script>
				$('#datatable-logLists').DataTable({
					'aoColumnDefs': [{'aTargets': [1,2], 'className': 'alignCenter'}],
					'paging':   false,
					'searching': false,
					//'ordering': false,
					'info':     false
				});
				</script>
				";
			break;
			case "industry_section":
				if(isset($_GET["value"]) && $_GET["value"] != ""){
					$elementValue = $_GET["value"];
					$stmtIndustrySectionDivision['fields'] = ['division.title','internal.division_id','internal.id','internal.code','internal.title'];
					$stmtIndustrySectionDivision['table'] = 'loan_industry_section AS section';
					$stmtIndustrySectionDivision['join'] = '
						LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_industry_division AS division ON section.id = division.section_id
						LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.loan_industry_internal AS internal ON internal.division_id = division.id
						';
					//$stmtIndustrySectionDivision['extra'] = 'ORDER BY users.staff_id ASC';
					$stmtIndustrySectionDivision['arguments'] = ["section.industry_code"=>14, "division.section_id"=>"{$elementValue}"];
					$stmtIndustrySectionDivision += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC];
					$getIndustrySectionDivision = $this->method->selectDB($stmtIndustrySectionDivision);

					$optionGroup = ""; $metaCnt = 0;
					$optionGroup .= "<select title='Select Industry Division...' id='industry_division' name='industry_division' meta='loan_industry_division' meta_key='loan_industry_internal' class='select2_group form-control select2-hidden-accessible' tabindex='-1' required='' placeholder='Select Industry Division...'>";
					$optionGroup .= "<option></option>";
					foreach($getIndustrySectionDivision as $divisionTitle => $sectionDivision) {
						$options = "";
						foreach($sectionDivision as $industryValue){
							$industryID = utf8_encode($industryValue["id"]);
							$industryCode = utf8_encode($industryValue["code"]);
							$industryDivisionID = utf8_encode($industryValue["division_id"]);
							$industryTitle = utf8_encode($industryValue["title"]);
							$options .= "<option division_id='{$industryDivisionID}' code='{$industryCode}' value='{$industryID}'>{$industryTitle}</option>";
						}
						$optionGroup .= "<optgroup label='{$divisionTitle}'>{$options}</optgroup>";
						$metaCnt++;
					}
					$optionGroup .= "</select>";
					$placeholder = utf8_encode($_GET["title"]);
					$result = $optionGroup;
					$result .= $this->method->input(['type'=>'hidden','id'=>'industry_division','name'=>'loans_details[industry_division]','value'=>'','placeholder'=>'']);
					$result .= '
						<script>
							$("[name=industry_division]").select2({placeholder: "Select '.$placeholder.' Industry...",allowClear: false});
							$("[name=industry_division]").on("change",function () {
								val = $(this).val();
								$("[name=\"loans_details[industry_division]\"]").val(val);
							});
						</script>
						';
					//$result = json_encode($getIndustrySectionDivision);
				}
			break;
			case "members_list":
				$stmtMemberMasterlist = ['schema'=>Info::DB_DATA,'table'=>'temp_masterlist','arguments'=>['meta'=>'member_masterlist','unit'=>$_SESSION["unit"]],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['unit','id','value']];
				$getMembersLists = $this->method->selectDB($stmtMemberMasterlist);
				$membersLists = json_decode($getMembersLists[$_SESSION['unit']]->value, true);
				//$unitCode = (isset($_SESSION["unit_code"])) ? $_SESSION["unit_code"] : '';
				$paramValue = explode(",",$_GET["value"]);
				
				$clientOption = "<option></option>";
				foreach($membersLists as $memberDetails){
				  $getMemDetails = json_decode(json_encode($memberDetails), false);
				  $memberShowDetail = $getMemDetails->member_name;//implode(" - ",[$getMemDetails->member_no,$getMemDetails->member_name]);
				  $selected = "";
				  if(in_array($getMemDetails->member_no, $paramValue)) $selected = "selected='selected'";
				  //$memberDetailLenght = strlen($memberShowDetail);
				  //if($memberDetailLenght >= 16) 
				  //$clientOption .= "<option title='' member_type='{$getMemDetails->member_type}' gender='{$getMemDetails->gender}' contact_no='' membership_date='{$getMemDetails->membership_date}' loan_balance='{$getMemDetails->loan_balance}' loan_cycle='{$getMemDetails->loan_cycle}' savings_amount='{$getMemDetails->savings_amount}' fixed_amount='{$getMemDetails->fixed_amount}' alias='{$getMemDetails->member_no}' value='{$getMemDetails->member_name}'>{$memberShowDetail}</option>";
				  $clientOption .= "<option title='' {$selected} alias='{$getMemDetails->member_name}' value='{$getMemDetails->member_no}'>{$memberShowDetail}</option>";
				}
				$result = "<select title='Select a member here...' id='".$_GET['name']."' name='".$_GET['group']."_".$_GET['name']."' meta='clients' meta_key='clients' multiple class='select2_multiple ".$_GET['name']." form-control select2-hidden-accessible' tabindex='-1' required='' placeholder='Select a member here...' aria-hidden='true'>{$clientOption}</select>";
				//$result .= "<input title='Co-Maker ID' value='' type='hidden' id='".$_GET['name']."' name='".$_GET['group']."_".$_GET['name']."' placeholder='Co-Maker ID' class='form-control block hidden '>";
				$result .= "<script>$('#".$_GET['name']."').select2({maximumSelectionLength: 2, placeholder: $('.select2_multiple').attr('placeholder'), allowClear: false});</script>";
				
			break;
		}
		echo $result;
	}
}

class Upload {

	public $Params;
	public $ParamsFile;
	public $method;
	public $db;
	public $get;
	public $post;

	function __construct($paramsFile,$params){
		$this->Params = $params;
		$this->get = $this->Params['get'];
		$this->post = $this->Params['post'];
		$this->ParamsFile = $paramsFile;
		
		$this->db = Info::DBConnection();
        //$this->Params['db'] = $this->db;
		$this->Params['schema'] = Info::DB_SYSTEMS;
		$this->method = new Method($this->Params);

	}

	function uploadFiles(){
		$toUpload = true;
		$arrayFields = [];
		switch($this->get['upload_type']){
			case "loans_payment":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportCreateData();
			break;
			case "cbu":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportCreateData();
			break;
			case "share_capital":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportCreateData();
			break;
			case "membership":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportCreateData();
			break;
			case "loans":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportCreateData();
			break;
			case "charts":
				$subFolder = $this->get['upload_type'];
				$target_dir = "files/{$subFolder}/";
				$this->targetFile = $target_dir . basename($this->ParamsFile["file"]["name"]);
				$arrayFields['params'] = $this->ImportData();
			break;
			case "templates":
				$target_dir = "files/document_format/";
			break;
			case "profile_attachments":
				$fileNameExt = strtolower(pathinfo($this->ParamsFile['file']['name'], PATHINFO_EXTENSION));
				$setFileName = [$this->post['member_id'],$this->post['data_id'],$this->method->getTime('min_sec')];
				$getFileName = implode("_",$setFileName);
				//$this->ParamsFile['file']['name'] = "{$getFileName}.{$fileNameExt}";
				$target_dir = "files/".$this->get['upload_type']."/";
				
				$fileExtention = strtolower(pathinfo($this->ParamsFile["file"]["name"], PATHINFO_EXTENSION));
				//$this->ParamsFile['file']['name'] = $this->post["username"].'.'.$fileExtention;
				$target_file = $target_dir . basename($this->ParamsFile["file"]["name"]);
				
				$max_file_size = 1024*800; // 819kb 819200
				$valid_exts = array('jpeg', 'jpg', 'png', 'gif');
				// thumbnail sizes
				$sizes = array(80 => 80,210 => 210);

				//if($this->ParamsFile["file"]["size"] < $max_file_size ){
				//$ext = strtolower(pathinfo($this->ParamsFile["file"]["name"], PATHINFO_EXTENSION));
				if (in_array($fileExtention, $valid_exts)) {
					/* resize image */
					foreach ($sizes as $w => $h) {
						$files[] = $this->method->resize($w, $h, 'file',$target_dir);
					}
				}
				
			break;
			case "attachments":
				// $fileExtention = strtolower(pathinfo($this->ParamsFile["file"]["name"], PATHINFO_EXTENSION));
				// $getFileName = str_replace(".".$fileExtention,"",$this->ParamsFile['file']['name']);
				// $suffixTime = date('His');
				// $this->ParamsFile['file']['name'] = $getFileName."_".$this->get['alias']."_".$this->get['data_id']."_".$suffixTime.".".$fileExtention;
				$target_dir = "files/attachments/";
				
			break;
			case "upload_members":
				if(!in_array($_SESSION["userrole"],[4,5])) $toUpload = false;
				$target_dir = "files/";
				$this->ParamsFile['file']['name'] = "cpmpc_members.txt";
				//$this->ParamsFile['file']['name'] = "cpmpc_members_".$_SESSION["unit_code"].".txt";
				break;
			case "upload_loan_types":
				if(!in_array($_SESSION["userrole"],[4,5])) $toUpload = false;
				$target_dir = "files/";
				$this->ParamsFile['file']['name'] = "loan_types_".$_SESSION["unit_code"].".txt";
				break;
			default:
				$target_dir = "files/";
			break;
		} // END SWITCH
		if($toUpload){
			$target_file = $target_dir . basename($this->ParamsFile["file"]["name"]);
			if (move_uploaded_file($this->ParamsFile["file"]["tmp_name"], $target_dir.$this->ParamsFile['file']['name'])) {
				$arrayFields['file_name'] = $this->ParamsFile['file']['name'];
				$arrayFields['status'] = 1;
				$arrayFields['upload_type'] = $this->get["upload_type"];
				header("Content-Type: text/json; charset=utf8");
				//$ddd = file_get_contents($this->get);
				//$input = json_decode($ddd, TRUE); //convert JSON into array
				
				echo json_encode($arrayFields, true);
			}
			//self::ImportCSV();
		}
	}
	
	function ImportCreateData(){ // PLEASE CHECK COMMAS ON CSV FILE
		$output = $primaryData = [];
		$csv_file = $this->ParamsFile["file"]["tmp_name"];
		$csvFile = fopen($csv_file, 'r');
		$csvFields = fgets($csvFile);
		$arrayFields = explode(",",$csvFields);
		switch($this->get['upload_type']){
			case "loans_payment":
				$post['action'] = "createData";
				$post['table'] = "data_queue";
				$post['path_alias'] = $this->get['upload_type'];
				$post['post_id'] = "0";
				$post['data_id'] = "0";
				$post['path_id'] = "8";
				$post['activity_id'] = "18";
				$post['post_activity_id'] = "18";
				$post['user'] = $_SESSION['userID'];
				$post['unit'] = $_SESSION['unit'];
				$pathGroupData = [
					"loans_payment_transactions"=>["loans_id","trans_date","payment_principal","payment_interest","remarks"]
				];
				
				$stmtLoans = ['schema'=>Info::DB_DATA,'table'=>'path_7_loans_details','arguments'=>['id>'=>1],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'ORDER BY id ASC','fields'=>['id','mem_info']];
				$getLoans = $this->method->selectDB($stmtLoans);
				$GLOBALS['membership'] = $getLoans;
			break;
			case "cbu":
				$post['action'] = "createData";
				$post['table'] = "data_queue";
				$post['path_alias'] = $this->get['upload_type'];
				$post['post_id'] = "0";
				$post['data_id'] = "0";
				$post['path_id'] = "5";
				$post['activity_id'] = "10";
				$post['post_activity_id'] = "10";
				$post['user'] = $_SESSION['userID'];
				$post['unit'] = $_SESSION['unit'];
				$pathGroupData = [
					"cbu_details"=>["mem_id","amount","remarks"]
				];
				
				### GET MEMBERS LISTS ###
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_id ASC';
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["queue.path_id"] = 1;
				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$GLOBALS['membership'] = $getMembership;
				### GET MEMBERS LISTS ###
			break;
			case "share_capital":
				$post['action'] = "createData";
				$post['table'] = "data_queue";
				$post['path_alias'] = $this->get['upload_type'];
				$post['post_id'] = "0";
				$post['data_id'] = "0";
				$post['path_id'] = "6";
				$post['activity_id'] = "12";
				$post['post_activity_id'] = "12";
				$post['user'] = $_SESSION['userID'];
				$post['unit'] = $_SESSION['unit'];
				$pathGroupData = [
					"share_capital_details"=>["mem_id","amount","share_value","share_capital_type","remarks","share_capital_status"]
				];
				
				### GET MEMBERS LISTS ###
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_id ASC';
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["queue.path_id"] = 1;
				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$GLOBALS['membership'] = $getMembership;
				### GET MEMBERS LISTS ###
			break;
			case "loans":
				$post['action'] = "createData";
				$post['table'] = "data_queue";
				$post['path_alias'] = $this->get['upload_type'];
				$post['post_id'] = "0";
				$post['data_id'] = "0";
				$post['path_id'] = "7";
				$post['activity_id'] = "16";
				$post['post_activity_id'] = "16";
				$post['user'] = $_SESSION['userID'];
				$post['unit'] = $_SESSION['unit'];
				$pathGroupData = [
					"loans_details"=>["mem_id","loan_types","payment_mode","amount","loan_interest","loan_terms","payment_form"],
					"loan_summary"=>["amortization_type","interest_type","loan_granted","approve_date","remarks"]
				];
				
				### GET MEMBERS LISTS ###
				$stmtMembership['fields'] = [
				'membership_information.member_id',
				'CONCAT(IFNULL(personal_information.last_name,""),", ",IFNULL(personal_information.first_name,"")," ",IFNULL(personal_information.middle_name,"")) AS member_name'
				];
				$stmtMembership['table'] = 'data_queue as queue';
				$stmtMembership['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_personal_information AS personal_information ON (personal_information.data_id = queue.data_id)
				   ';
				$stmtMembership['extra'] = 'ORDER BY member_id ASC';
				$stmtMembership['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtMembership['arguments']["membership_information.membership_status"] = 1;
				$stmtMembership['arguments']["queue.path_id"] = 1;
				$stmtMembership += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getMembership = $this->method->selectDB($stmtMembership);
				$GLOBALS['membership'] = $getMembership;
				### GET MEMBERS LISTS ###
			break;
			case "membership":
				$stmtCheckMember['fields'] = ['membership_information.member_id','queue.id AS post_id','queue.data_id'];
				$stmtCheckMember['table'] = 'data_queue as queue';
				$stmtCheckMember['join'] = '
				   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_1_membership_information AS membership_information ON (membership_information.data_id = queue.data_id)
				   ';
				$stmtCheckMember['extra'] = 'ORDER BY queue.data_id ASC';
				$stmtCheckMember['arguments']["queue.unit"] = $_SESSION["unit"];
				$stmtCheckMember['arguments']["queue.path_id"] = 1;
				$stmtCheckMember += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$checkPrimary = $this->method->selectDB($stmtCheckMember);
				$primaryData = array_keys($checkPrimary);
				
				$post['action'] = "createData";
				$post['table'] = "data_queue";
				$post['path_alias'] = $this->get['upload_type'];
				$post['post_id'] = "0";
				$post['data_id'] = "0";
				$post['path_id'] = "1";
				$post['activity_id'] = "1";
				$post['post_activity_id'] = "1";
				$post['user'] = $_SESSION['userID'];
				$post['unit'] = $_SESSION['unit'];
				$pathGroupData = [
					"membership_information"=>["member_id","membership_date","account_type","membership_type","membership_status"],
					"personal_information"=>["first_name","middle_name","last_name","birth_date","gender","civil_status"],
					"contact_information"=>["contact_mobile","email_address"],
					"residential_address"=>["address_street","address_barangay","address_city","address_province"]
				];
			break;
		}
		$csvData = [];
		$cnt = 0;
		while (!feof($csvFile)) {
			$data = [];
			$_POST = $post;
			$csvData[] = fgets($csvFile, 1024);
			$csvArray = explode(",", $csvData[$cnt]);
			if($csvArray[0]){ // FIRST FIELD IS NOT EMPTY
				$primary_id = $csvArray[0]; // GETTING THE PRIMARY KEY TO CHECK EXISTING DATA
				if(in_array($primary_id,$primaryData)){
					$_POST['post_id'] = (int)$checkPrimary[$primary_id]->post_id;
					$_POST['data_id'] = (int)$checkPrimary[$primary_id]->data_id;
				}
				switch($this->get['upload_type']){ // CUSTOM POST VALUES
					case "loans_payment":
						$loansInfo = $GLOBALS['membership'][$primary_id];
						$_POST['loans_payment_transactions']['loans_info'] = $loansInfo;
						$_POST['loans_payment_transactions']['payment_type'] = 1;
					break;
					case "loans":
						$memName = $GLOBALS['membership'][$primary_id]->member_name;
						$_POST['loans_details']['mem_info'] = "{$memName}:{$primary_id}:0.00:0.00";
						//$_POST['interest_amount'] = $csvArray[11]; // GET INTEREST AMOUNT COLUMN
					break;
					case "share_capital": case "cbu":
						$memName = $GLOBALS['membership'][$primary_id]->member_name;
						$_POST[$this->get['upload_type'].'_details']['mem_info'] = "{$memName}:{$primary_id}:Individual:Retirees";
					break;
				}
				foreach($arrayFields as $key => $value){
					$fieldName = trim(preg_replace('/\s+/', ' ', $value));
					$fieldValue = trim(preg_replace('/\s+/', ' ', $csvArray[$key]));
					$groupAlias = $this->method->array_recursive_search_key_map($fieldName, $pathGroupData);
					$_POST[$groupAlias[0]][$fieldName] = $fieldValue;
				}
				
				$getStorage = new Storage($_POST);
				$data = $getStorage->processAction();
				$output[] = $_POST;
			}
			$cnt++;
		}
		return $output;
	}

	function ImportData(){ // PLEASE CHECK COMMAS ON CSV FILE
		$output = [];
		$csv_file = $this->ParamsFile["file"]["tmp_name"];
		$csvFile = fopen($csv_file, 'r');
		$csvFields = fgets($csvFile);
		$arrayFields = explode(",",$csvFields);
		switch($this->get['upload_type']){
			case "charts":
				$stmtCharts = ['schema'=>Info::DB_ACCOUNTING,'table'=>'charts','arguments'=>['id>'=>1],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'ORDER BY id ASC','fields'=>['code','id']];
				$getCharts = $this->method->selectDB($stmtCharts);
				$chartCodes = array_keys($getCharts);
				$post['schema'] = "accounting";
				$post['action'] = "saveCharts";
				$post['theID'] = "";
			break;
		}
		$csvData = [];
		$cnt = 0;
		while (!feof($csvFile)) {
			$data = [];
			$_POST = $post;
			$csvData[] = fgets($csvFile, 1024);
			$csvArray = explode(",", $csvData[$cnt]);
			if($csvArray[0]){ // FIRST FIELD IS NOT EMPTY
				if($this->get['upload_type'] == "charts"){ // CHARTS
					$code = $csvArray[0];
					if(in_array($code,$chartCodes)) $_POST['theID'] = $getCharts[$code];
				}
				foreach($arrayFields as $key => $value){
					$fieldName = trim(preg_replace('/\s+/', ' ', $value));
					$fieldValue = trim(preg_replace('/\s+/', ' ', $csvArray[$key]));
					$_POST[$fieldName] = $fieldValue;
				}
				
				$getStorage = new Storage($_POST);
				$data = $getStorage->processAction();
				$output[] = $_POST;
			}
			$cnt++;
		}
		return $output;
	}

	function ImportCSV(){
		$fields = "BIOID,DATETIME";
		$fieldsArray = explode(',',$fields);
		$csv_file = $this->FilePath . $this->FileName;
		$csvFile = fopen($csv_file, 'r');
		$theData = fgets($csvFile);
		$i = 0; $countInsert = 1; $countUpdate = 1; $alertInsert = ''; $alertUpdate = ''; $separator = '';
		$output = "";
		while (!feof($csvFile)) {
			$checkCode = $logsValue = "";
			$csvData[] = fgets($csvFile, 1024);
			$csvArray = explode(",", $csvData[$i]);
			$csvField = array();
			$theDate=$theTime=$checkStaffDate='';
			$cnt=0;
			//'record_id','type','salary_set'
			foreach($fieldsArray as $theField){
				$thisFieldValue = $csvArray[$cnt];

				if($theField=='DATETIME'){
					$theDateTime = 	$thisFieldValue;
					$getDateTime = date_create($theDateTime);
					$thisDate = date_format($getDateTime, 'Y-m-d');
					$thisTime = date_format($getDateTime, 'H:i:s');
				}else{
					$theBioID = $thisFieldValue;
					$this->method->schema = "projectzero";
					$getStaffValue = $this->method->getValueDB(['table'=>'staffs','bio_id'=>$theBioID]);
					$theStaffID = $getStaffValue['id'];
				}
				$cnt++;
			}
			if($theStaffID){//
				$getFields = $postID = $postValue = "";
				$checkStaffLogs = ['schema'=>'projectzero','table'=>'payroll_logs','arguments'=>['record_id'=>$this->Params['record_id'],'staff_id'=>$theStaffID,'logDate'=>$thisDate],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>['id','logIn','logOut']];
				$getStaffLogs = $this->method->selectDB($checkStaffLogs);
				if(EMPTY($getStaffLogs)){
					$theSalarySet = $getStaffValue['salary_set'];
					$getFields = ['record_id','log_set','salary_set','staff_id','logDate','logIn','logOut','user','date'];
					$postValue = [$this->Params['record_id'],1,$theSalarySet,$theStaffID,$thisDate,$thisTime,$thisTime,$_SESSION['userID'],$this->method->getDate('dateTime')];
					$stmtElements = ['schema'=>'projectzero','table'=>'payroll_logs','fields'=>$getFields,'values'=>$postValue];
					$postID = $this->method->insertDB($stmtElements);
				}else{
					$logID = $getStaffLogs[0]['id'];
					$postUpdate['logOut'] = $thisTime;
					$stmtElements['schema'] = 'projectzero';
					$stmtElements['fieldValues'] = $postUpdate;
					$stmtElements['table'] = 'payroll_logs';
					$stmtElements['id'] = $logID;
					$this->method->updateDB($stmtElements);
				}
			}
			$i++;
		} // END WHILE
		echo $this->Params;
	}
}

class Storage {

	public $Params;
	public $method;
	public $action;
	public $db;
	public $session;
	public $resultElement;
	//public $schemaProjectZero = ['staffs','payroll','payroll_adjustment','payroll_logs','salary_set','holidays'];

	function __construct($params) {
		$this->Params = $params;
	}

	function processAction() { // DISPLAY THE PROCESS
		$formData['status'] = 'success';
		$this->resultElement['success'] = 0;
		$this->resultElement['result'] = "info";
		$this->resultElement['message'] = "No Data/Record has been changed!";
		$this->Params['source'] = (isset($this->Params['source'])) ? $this->Params['source'] : true;
		$action = $this->Params['action'];
		$this->method = new Method($this->Params);
		//var_dump();
		if($action && $_SESSION['userID']){ // PUT USER ACCESS AUTHENTICATION HERE
			if(isset($this->Params["form"])) $action = $this->Params["form"]; // saveRecords()
			$this->$action();
		}else{
			header('Content-Type: application/json; charset=UTF-8');
		}//throw new \Exception('ACTION ERROR', 1);
	}
	
	function sample(){
		return $this->Params;
	}

	function viewOptionForm(){
		$getFormData = [];
		(true) ? print $this->method->optionForm() : print pageError('403'); //hasRights($getTable,$sessionUserID,2)
	}

	function createRecords(){
		$cntUpdate = $postID = 0;
		$postData = $dataParams = $insertValues = [];
		$this->resultElement['record_type'] = "create";
		$this->resultElement['result_type'] = "post";
		$this->resultElement['table'] = $this->Params['table'];
		
		$updateFields = $this->method->getTableFields(['table'=>$this->Params['table'],'exclude'=>['id','status','user','date'],'schema'=>Info::DB_SYSTEMS]);
		if($this->Params['theID'] < 1){ // CREATE DATA ON QUEUE //
			foreach($updateFields as $theField){
				$postValue = '';
				if(isset($this->Params[$theField]) && $this->Params[$theField] != ''){
					if($theField == "password") $this->Params[$theField] = md5($this->Params[$theField]); // CONVERT PASSWORD MD5
					$postValue = $this->Params[$theField]; // SET POST VALUES
					$postData[$theField] = $postValue;
				}
				$insertValues[] =  $postValue;//$postValue; // SET VALUES ON CREATES
			}
			$updateFields =  array_merge($updateFields,['status','user','date']);
			$insertValues[] = 1;
			$insertValues[] = $_SESSION['userID'];
			$insertValues[] = $this->method->getTime('datetime');
			$stmtElements = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->Params['table'],'fields'=>$updateFields,'values'=>$insertValues];
			$postID = $this->method->insertDB($stmtElements);
			if($postID > 0){
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = "Data {$this->Params['table']} been added successfully!";
				$this->Params['theID'] = $postID;
				$this->resultElement['success'] = 1;
			}

		}else{ // UPDATE DATA ON QUEUE
			$this->resultElement['record_type'] = "update";
			$stmtElements = [];
			$updateFields = $this->method->getTableFields(['table'=>$this->Params['table'],'exclude'=>['id','status','user','date'],'schema'=>Info::DB_SYSTEMS]);
			foreach($updateFields as $field){
				if(!EMPTY($this->Params[$field])){
					if($field == "password") $this->Params[$field] = md5($this->Params[$field]); // CONVERT PASSWORD MD5
					$postUpdate[$field] = $this->Params[$field];
				}
			}
			$stmtElements['fieldValues'] = $postUpdate;
			$stmtElements['schema'] = Info::DB_SYSTEMS;
			$stmtElements['table'] = $this->Params['table'];
			$stmtElements['id'] = $this->Params['theID'];
			$cntStmtElements = $this->method->updateDB($stmtElements);
			if($cntStmtElements > 0){
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = "{$this->Params['table']} data has been updated successfully!";
				$this->resultElement['success'] = 1;
				$cntUpdate = $cntUpdate + $cntStmtElements;
			}
		}
		$this->resultElement['theID'] = $this->Params['theID'];
		$this->resultElement['data_updated'] = $cntUpdate;
		$this->resultElement['parseLogs'] = [$updateFields,$insertValues];
		
		switch($this->Params['table']){
			case "users":
				$this->resultElement['result_type'] = "lists";
				$this->method->Params['pageName'] = "users-page";
				$this->method->Params['view'] = $this->Params['table'];
				$outputFields = $this->method->getTableFields(['table'=>$this->Params['table'],'exclude'=>['password','tokens','user','date'],'schema'=>Info::DB_SYSTEMS]);
				$dataParams['codebook_fields'] = [];
				$dataParams['row_fields'] = $outputFields;
				$postData['date_created'] = $this->method->getTime('datetime');
				$postData['status'] = 1;
				$this->resultElement['post_data'] = $postData;
				$dataParams['data_lists'] = [$this->Params['theID']=>json_decode(json_encode($postData))];
				$this->method->Params['typeList'] = "";
				$this->resultElement['html'] = $this->method->getTableListings($dataParams);
			break;
			case "codebook":
				$metaValue = [];
				$this->resultElement['result_type'] = "lists";
				$this->method->Params['pageName'] = "settings-options";
				$metaValue["id"] = $this->Params['theID'];
				$metaValue["meta_id"] = $this->Params["meta_id"];
				$metaValue["meta_option"] = $this->Params["meta_option"];
				$metaValue["meta_value"] = $this->Params["meta_value"];
				$dataParams = ["metaType"=>$this->Params["meta_key"],"metaValue"=>$metaValue];
				$this->method->parseType = false;
				$this->resultElement['html'] = $this->method->getTableListings($dataParams);
			break;
		}
		# START PHP LOGGER
		$log = new projectzero\phplogger\logWriter('logs/log-' . date('d-M-Y') . '.txt');
		$log->info(json_encode($this->Params, JSON_FORCE_OBJECT));
		# END PHP LOGGER
		
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	
	function saveCharts(){
		$chartID = (isset($this->Params['id'])) ? $this->Params['id'] : $this->Params['theID'];
		$chartFields = [
			"charts"=>["code","title","description"],
			"charts_meta"=>["type","parent","debit","credit","opening_date"]
		];
		if($chartID != ""){ // UPDATE CHART
			foreach($chartFields as $table => $fields){
				$postUpdate = [];
				foreach($fields as $field){
					$postUpdate[$field] = $this->Params[$field];
				}
				$stmtElements['fieldValues'] = $postUpdate;
				$stmtElements['schema'] = Info::DB_ACCOUNTING;
				$stmtElements['table'] = $table;
				$stmtElements['id'] = $chartID;
				$cntStmtElements = $this->method->updateDB($stmtElements);
			}
		}else{ // INSERT CHART
			foreach($chartFields as $table => $fields){
				$postUpdate = $insertFields = $stmtElements = [];
				foreach($fields as $field){
					if($table == "charts"){
						$insertFields[] = $field;
						$insertValues[] = $this->Params[$field];
					}else{ // CHARTS_META
						$postUpdate[$field] = $this->Params[$field];
					}
				}
				
				if($table == "charts"){ // INSERT FOR CHARTS
					$updateFields =  array_merge($insertFields,['user']);
					$insertValues[] = $_SESSION['userID'];
					$stmtElements = ['schema'=>Info::DB_ACCOUNTING,'table'=>$table,'fields'=>$updateFields,'values'=>$insertValues];
					$chartID = $this->method->insertDB($stmtElements);
				}else{ // CHARTS_META
					$stmtElements['fieldValues'] = $postUpdate;
					$stmtElements['schema'] = Info::DB_ACCOUNTING;
					$stmtElements['table'] = $table;
					$stmtElements['id'] = $chartID;
					$cntStmtElements = $this->method->updateDB($stmtElements);
				}
				
			}
		}
		
		$this->resultElement['result'] = "success";
		$this->resultElement['message'] = "Accounting Chart has been updated!";
		$this->resultElement['success'] = 1;
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}

	function saveRecords(){ //CREATE PROCEDURE // SIGNATORY SETTINGS
		//$arrayFields = $this->Params;
		$postUpdate = [];
		$cntUpdate = 0;
		$this->resultElement['record_type'] = "create";
		$dbSchema = Info::DB_SYSTEMS;
		
		switch($this->Params['table']){
			case "methods":
				$methodPostName = $methodPostValue = [];
				$nameNotArray = ["module_policy"]; // METHODS NAME NOT ARRAY/JSON
				$metaKey = $this->Params['metaKey'];
				$attrID = $this->Params['attrID'];
				$valueLength = $this->Params['valueLength'];
				$metaPostValue = $this->Params["{$metaKey}"][$attrID];
				foreach($metaPostValue as $field_key => $field_value){
					$methodPostName[$metaPostValue['name'][0]['key']] = $metaPostValue['name'][0]['val'];
					$postUpdateName = json_encode($methodPostName,JSON_UNESCAPED_SLASHES);
					$postUpdate['name'] = (in_array($metaKey, $nameNotArray)) ? $metaPostValue['name'][0]['val'] : "[{$postUpdateName}]";
					
					$arrayPostMetaValue = $metaPostValue['value'];
					foreach($arrayPostMetaValue as $value){
						if(!empty($value['key']) && !empty($value['val'])) $methodPostValue[$value['key']] = $value['val'];
					}
					$postUpdateValue = json_encode($methodPostValue,JSON_UNESCAPED_SLASHES);
					$postUpdate['value'] = "[{$postUpdateValue}]";
				}
			break;
			default:
				$tableFields = $this->method->getTableFields(["table"=>$this->Params['table'],'exclude'=>['id','date'],'schema'=>$dbSchema]);
				foreach($tableFields as $field){
					if(!EMPTY($this->Params[$field])) $postUpdate[$field] = $this->Params[$field];
				}
			break;
		}
		if($this->Params['theID'] > 0){ // UPDATE RECORDS
			$this->resultElement['record_type'] = "update";
			$stmtElements['fieldValues'] = $postUpdate;
			$stmtElements['schema'] = $dbSchema;
			$stmtElements['table'] = $this->Params['table'];
			$stmtElements['id'] = $this->Params['theID'];
			$cntUpdate = $this->method->updateDB($stmtElements);
			if($cntUpdate > 0){
				$this->resultElement['message'] = "Data/Record has been updated successfully!";
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
			}
			
			$this->resultElement['dataID'] = $this->Params['theID'];
			$this->resultElement['meta'] = $this->Params['table'];
			$this->resultElement['values'] = $postUpdate;
		}else{ // CREATE RECORDS
			$updateFields = $this->method->getTableFields(['table'=>$this->Params['table'],'exclude'=>['id','status','user','date'],'schema'=>Info::DB_SYSTEMS]);
			foreach($updateFields as $theField){
				$postValue = '';
				if(isset($this->Params[$theField]) && $this->Params[$theField] != ''){
					if($theField == "password") $this->Params[$theField] = md5($this->Params[$theField]); // CONVERT PASSWORD MD5
					$postValue = $this->Params[$theField]; // SET POST VALUES
					$postData[$theField] = $postValue;
				}
				$insertValues[] =  $postValue;//$postValue; // SET VALUES ON CREATES
			}
			$updateFields =  array_merge($updateFields,['user']);
			$insertValues[] = $_SESSION['userID'];
			$stmtElements = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->Params['table'],'fields'=>$updateFields,'values'=>$insertValues];
			$postID = $this->method->insertDB($stmtElements);
			if($postID > 0){
				$this->Params['theID'] = $postID;
				$this->resultElement['dataID'] = $this->Params['theID'];
				$this->resultElement['meta'] = $this->Params['table'];
				$this->resultElement['values'] = $postUpdate;
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
				if($postID > 0) $this->resultElement['message'] = "Data has been created successfully!";
			}
		}
		//$stmtProcedure = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->Params['table'],'arguments'=>['id'=>$theField],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>$tableFields];
		//$getProcedure = $this->method->selectDB($stmtProcedure);
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	
	private function customPathSavings(){ // SAVINGS
		$cntPathUpdate = "";
		if($this->Params['post_id'] > 0){
			$pathElements = [];
			$pathElements['fieldValues'] = ["balance"=>$this->Params['savings_details']['amount']];
			$pathElements['schema'] = Info::DB_DATA;
			$pathElements['table'] = "path_2_savings_details";
			$pathElements['id'] = $this->Params['data_id'];
			$cntPathUpdate = $this->method->updateDB($pathElements);
			
			if($this->Params['activity_id'] == 4){ // SAVINGS COMPLETED | CREATE AUTO JOURNAL ENTRY
				switch($this->Params['savings_details']['product_savings']){
					case "1": // REGULAR SAVINGS
						$savingChartID = $_SESSION["module_charts"]['savings_deposit'];
					break;
					case "2": // TIME-DEPOSIT SAVINGS
						$savingChartID = $_SESSION["module_charts"]['time_deposit'];
					break;
				}
				if($this->Params['source']){ // DIRECT CREATE_DATA
					$journalParams = [];
					$journalParams["table"] = "journals";
					$journalParams["unit"] = $_SESSION['unit'];
					$journalParams["theID"] = "0";
					$journalParams["entry"] = "2";
					$journalParams["journals"] = ["entry_date" => $this->method->getDate('date'), "recipient"=>$this->Params['members_name'], "particulars" => "Savings Application of MemberID: ".$this->Params['savings_details']['mem_id']];
					$journalParams["journals_entry"] = [
							1 => ["charts_id" => $_SESSION["module_charts"]['cash_hand'], "debit" => $this->Params['savings_details']['amount'], "credit" => "0.00"],
							2 => ["charts_id" => $savingChartID, "debit" => "0.00", "credit" => $this->Params['savings_details']['amount']]
						];
					$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
					$getJournalID = $generateJournalEntry['post_id'];
					$queueUpdateElements = [];
					$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
					$queueUpdateElements['schema'] = Info::DB_DATA;
					$queueUpdateElements['table'] = "data_queue";
					$queueUpdateElements['id'] = $this->Params['post_id'];
					$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
				}
			}
		}
		return $cntPathUpdate;
	}
	
	private function customPathCBU(){ // CBU
		$cntPathUpdate = "";
		$table = "path_5_cbu_details";
		if($this->Params['activity_id'] == 10){ // CBU COMPLETED
			$pathElements = [];
			$balance = floatVal($this->Params['cbu_details']['amount']);
			$memberID = $this->Params['cbu_details']['mem_id'];
			
			$stmtCheckLastTrans['fields'] = ['cbu_details.id','cbu_details.balance'];
			$stmtCheckLastTrans['table'] = 'data_queue as queue';
			$stmtCheckLastTrans['join'] = '
			   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.'.$table.' AS cbu_details ON (cbu_details.data_id = queue.data_id)
			   ';
			$stmtCheckLastTrans['extra'] = 'ORDER BY id DESC LIMIT 1';
			$stmtCheckLastTrans['arguments']["cbu_details.mem_id"] = $memberID;
			$stmtCheckLastTrans['arguments']["queue.data_id!"] = $this->Params['data_id'];
			$stmtCheckLastTrans['arguments']["queue.path_id"] = 5;
			$stmtCheckLastTrans['arguments']["queue.activity_id"] = 10;
			$stmtCheckLastTrans['arguments']["queue.unit"] = $_SESSION["unit"];
			$stmtCheckLastTrans += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_KEY_PAIR];
			$getCheckLastTrans = $this->method->selectDB($stmtCheckLastTrans);
			if($getCheckLastTrans){
				$pathTransID = array_keys($getCheckLastTrans);
				$balance = floatVal($getCheckLastTrans[$pathTransID[0]]) + $balance;
			}
			
			$pathElements['fieldValues'] = ["balance"=>$balance];
			$pathElements['schema'] = Info::DB_DATA;
			$pathElements['table'] = $table;
			$pathElements['id'] = $this->Params['data_id'];
			$cntPathUpdate = $this->method->updateDB($pathElements);
			
			if($this->Params['source']){ // DIRECT CREATE_DATA
				$particulars = "[Capital Build-Up] MemberID: ".$this->Params['cbu_details']['mem_id']."<br>".$this->Params['cbu_details']['remarks'];
				$journalParams = [];
				$journalParams["table"] = "journals";
				$journalParams["unit"] = $_SESSION['unit'];
				$journalParams["theID"] = "0";
				$journalParams["entry"] = "2";
				$journalParams["journals"] = ["entry_date" => $this->method->getDate('date'), "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
				$cbuAmount = $this->Params['cbu_details']['amount'];
				if($cbuAmount < 0){ // IF NEGATIVE VALUE
					$cbuAmount = abs($cbuAmount);
					$journalParams["journals_entry"] = [
						1 => ["charts_id" => $_SESSION["module_charts"]['cbu'], "debit" => $cbuAmount, "credit" => "0.00"],
						2 => ["charts_id" => $_SESSION["module_charts"]['cash_hand'], "debit" => "0.00", "credit" => $cbuAmount]
					];
				}else{
					$journalParams["journals_entry"] = [
						1 => ["charts_id" => $_SESSION["module_charts"]['cash_hand'], "debit" => $cbuAmount, "credit" => "0.00"],
						2 => ["charts_id" => $_SESSION["module_charts"]['cbu'], "debit" => "0.00", "credit" => $cbuAmount]
					];
				}
				
				$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
				$getJournalID = $generateJournalEntry['post_id'];
				$queueUpdateElements = [];
				$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
				$queueUpdateElements['schema'] = Info::DB_DATA;
				$queueUpdateElements['table'] = "data_queue";
				$queueUpdateElements['id'] = $this->Params['post_id'];
				$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
			}
			
		}elseif(!$this->Params['activity_id']){ // REVERSING DATA AND JOURNALS
			$stmtQueueData = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$this->Params['post_id']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','journals_id']];
			$getQueueData = $this->method->selectDB($stmtQueueData);
			$journalID = $getQueueData[$this->Params['post_id']];
			$this->method->reverseJournals($journalID);
		}
		return $cntPathUpdate;
	}
	
	private function customPathShareCapital(){
		switch($this->Params['share_capital_details']['share_capital_type']){
			case "1": // COMMON
				$shareTypeCodeID = $_SESSION["module_charts"]['share_common'];
				$shareCapitalTitle = "Common";
			break;
			case "2": // PREFERRED
				$shareTypeCodeID = $_SESSION["module_charts"]['share_preferred'];
				$shareCapitalTitle = "Preferred";
			break;
		}
		if($this->Params['activity_id'] == 12 && $this->Params['source']){ // SHARE CAPITAL COMPLETED 
			$particulars = "[Share Capital - {$shareCapitalTitle}]  MemberID: ".$this->Params['share_capital_details']['mem_id']."<br>".$this->Params['share_capital_details']['remarks'];
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$journalParams["entry"] = "2";
			$journalParams["journals"] = ["entry_date" => $this->method->getDate('date'), "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = [
					1 => ["charts_id" => $_SESSION["module_charts"]['cash_hand'], "debit" => $this->Params['share_capital_details']['amount'], "credit" => "0.00"],
					2 => ["charts_id" => $shareTypeCodeID, "debit" => "0.00", "credit" => $this->Params['share_capital_details']['amount']]
				];
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
		}elseif($this->Params['activity_id'] == 13){ // SHARE CAPITAL WITHDRAWN
			$stmtVoucherID = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$this->Params['post_id']],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['journals_id']];
			$getVoucherID = $this->method->selectDB($stmtVoucherID);
			$preVoucherID = ($getVoucherID[0]) ? $getVoucherID[0] : "---";
			$particulars = "[Withdrawn Share Capital - {$shareCapitalTitle}]  MemberID: ".$this->Params['share_capital_details']['mem_id']."<br>Voucher Reference ID: ".$preVoucherID;
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$journalParams["entry"] = "4";
			$journalParams["journals"] = ["entry_date" => $this->method->getDate('date'), "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = [
					1 => ["charts_id" => $shareTypeCodeID, "debit" => $this->Params['share_capital_details']['amount'], "credit" => "0.00"],
					2 => ["charts_id" => $_SESSION["module_charts"]['subscription_receivable'], "debit" => $this->Params['share_capital_details']['amount'], "credit" => "0.00"],
					3 => ["charts_id" => $_SESSION["module_charts"]['treasury_share'], "debit" => "0.00", "credit" => $this->Params['share_capital_details']['amount']],
					4 => ["charts_id" => $_SESSION["module_charts"]['cash_bank'], "debit" => "0.00", "credit" => $this->Params['share_capital_details']['amount']]
				];
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
		}
	}
	
	private function customPathDeposits(){ // DEPOSITS
		$cntPathUpdate = "";
		$table = "path_2_savings_details";
		if($this->Params['activity_id'] == 6){ // DEPOSITS COMPLETED
			$savingsID = $this->Params['deposits_transactions']['savings_id'];
			$getSavingsValue = $this->method->getValueDB(['table'=>$table,'id'=>$savingsID,'schema'=>Info::DB_DATA]);
			$savingsBalance = $getSavingsValue['balance'];
			$depositAmount = $this->Params['deposits_transactions']['amount'];
			$balanceAmount = $savingsBalance + $depositAmount;
			$pathElements = [];
			$pathElements['fieldValues'] = ["balance"=>$balanceAmount];
			$pathElements['schema'] = Info::DB_DATA;
			$pathElements['table'] = $table;
			$pathElements['id'] = $savingsID;
			$cntPathUpdate = $this->method->updateDB($pathElements);
			
			switch($this->Params['deposits_transactions']['payment_type']){
				case "1": // CASH
					$paymentType = $_SESSION["module_charts"]['cash_hand'];
				break;
				case "2": // CHECK
					$paymentType = $_SESSION["module_charts"]['cash_bank']; // CASH IN BANK
				break;
			}
			$particulars = "[Deposits] Regular Savings: ".$this->Params['deposits_transactions']['savings_id']."<br>".$this->Params['deposits_transactions']['remarks'];
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$journalParams["entry"] = "2";
			$journalParams["journals"] = ["entry_date" => $this->Params['deposits_transactions']['trans_date'], "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = [
					1 => ["charts_id" => $paymentType, "debit" => $this->Params['deposits_transactions']['amount'], "credit" => "0.00"],
					2 => ["charts_id" => $_SESSION["module_charts"]['savings_deposit'], "debit" => "0.00", "credit" => $this->Params['deposits_transactions']['amount']]
				];
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
		}
		return $cntPathUpdate;
	}
	
	private function customPathWithdrawal(){ // WITHDRAWAL
		$cntPathUpdate = "";
		$table = "path_2_savings_details";
		if($this->Params['activity_id'] == 8){ // WITHDRAWAL COMPLETED
			$savingsID = $this->Params['withdrawal_transactions']['savings_id'];
			$getSavingsValue = $this->method->getValueDB(['table'=>$table,'id'=>$savingsID,'schema'=>Info::DB_DATA]);
			$savingsBalance = $getSavingsValue['balance'];
			$withdrawalAmount = $this->Params['withdrawal_transactions']['amount'];
			$balanceAmount = $savingsBalance - $withdrawalAmount;
			$pathElements = [];
			$pathElements['fieldValues'] = ["balance"=>$balanceAmount];
			$pathElements['schema'] = Info::DB_DATA;
			$pathElements['table'] = $table;
			$pathElements['id'] = $savingsID;
			$cntPathUpdate = $this->method->updateDB($pathElements);
			
			switch($this->Params['withdrawal_transactions']['payment_type']){
				case "1": // CASH
					$paymentType = $_SESSION["module_charts"]['cash_hand'];
				break;
				case "2": // CHECK
					$paymentType = $_SESSION["module_charts"]['cash_bank']; // CASH IN BANK
				break;
			}
			$particulars = "[Withdrawal] Regular Savings: ".$this->Params['withdrawal_transactions']['savings_id']."<br>".$this->Params['withdrawal_transactions']['remarks'];
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$journalParams["entry"] = "2";
			$journalParams["journals"] = ["entry_date" => $this->Params['withdrawal_transactions']['trans_date'], "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = [
					1 => ["charts_id" => $_SESSION["module_charts"]['savings_deposit'], "credit" => "0.00", "debit" => $this->Params['withdrawal_transactions']['amount']],
					2 => ["charts_id" => $paymentType, "credit" => $this->Params['withdrawal_transactions']['amount'], "debit" => "0.00"]
				];
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
		}
		return $cntPathUpdate;
	}
	
	private function customPathLoans(){ // LOANS
		$loanBalanceInfo = "";
		$GLOBALS['post_id'] = $this->Params['post_id'];
		$GLOBALS['data_id'] = $this->Params['data_id'];
		$GLOBALS['activity_id'] = $this->Params['activity_id'];
		$this->Params['create_schedule'] = false;
		$chartCashOnHand = $_SESSION["module_charts"]['cash_bank'];
		$chartLoansReceivable = $_SESSION["module_charts"]['loans_receivable'];
		$chartUnearnedIncome = $_SESSION["module_charts"]['unearned_income'];
		$chartCBU = $_SESSION["module_charts"]['cbu'];
		
		$totalInterest = 0; // GET THE TOTAL INTEREST
		if($this->Params['activity_id'] == 16){
			$this->Params['create_schedule'] = true;
			$setLoanSchedule = self::createLoanSchedule();
			$totalInterest = $this->Params["total_interest"];
		}
		
		//$this->resultElement["client_loan_schedule"] = json_encode($setLoanSchedule["schedule_object"]);
		//$this->resultElement["client_schedule_summary"] = json_encode($setLoanSchedule["summary"]);
		
		### SETTING DEDUCTIONS AS JOURNAL ENTRIES ###
		$journalEntries = $journalParams = $setDeductions = [];
		$totalDeductions = $cashOnHand = $incomeInterest = $loanBalanceAmount = 0;
		$getDeductions = $this->Params['deductions'];
		if(count($getDeductions) > 0){ // IF HAS DEDUCTIONS
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$cnt = 3;
			$journalEntries[1] = ['charts_id'=>$chartLoansReceivable,'debit'=>$this->Params['loan_summary']['loan_granted'],'credit'=>0];
			if($this->Params['loan_summary']['interest_type'] == 1){ // THE INTEREST IS PREPAID OR NOT AMORTIZED
				$incomeInterest = $totalInterest;
				$journalEntries[3] = ['charts_id'=>$chartUnearnedIncome,'debit'=>0,'credit'=>$incomeInterest];
				$cnt++;
			}
			
			if($this->Params['loan_summary']['loan_balance_info'] != ""){ // REFINANCING
				$loanBalanceInfo = $this->Params['loan_summary']['loan_balance_info'];
				$getLoanBalanceInfo = explode(":",$loanBalanceInfo);
				$loanBalanceID = $getLoanBalanceInfo[0];
				$loanBalanceAmount = $getLoanBalanceInfo[3];
				$journalEntries[$cnt] = ['charts_id'=>$chartLoansReceivable,'debit'=>0,'credit'=>$loanBalanceAmount];
				$cnt++;
			}
			
			foreach($getDeductions as $id => $values){
				if($values['charts_id'] && $values['amount']){
					$deductions = ['charts_id'=>$values['charts_id'],'debit'=>0,'credit'=>$values['amount']];
					$journalEntries[$cnt] = $deductions;
					$setDeductions[] = $deductions;
					$totalDeductions = $totalDeductions + $values['amount'];
					$cnt++;
				}
			}
			$loanBalanceAmount = floatVal($loanBalanceAmount);
			if($loanBalanceAmount <= 0) $loanBalanceAmount = 0; // FORCE TO ZERO IF REFINANCING IS NEGATIVE
			$cashOnHand = $this->Params['loan_summary']['loan_granted'] - ($incomeInterest + $totalDeductions + $loanBalanceAmount);
			$particulars = "[Loans] MemberID: ".$this->Params['loans_details']['mem_id']." | ReferenceID: ".$this->Params['data_id']."<br>".$this->Params['loan_summary']['remarks'];
			
			$journalEntries[2] = ['charts_id'=>$chartCashOnHand,'debit'=>0,'credit'=>$cashOnHand];
			$journalParams["entry"] = count($journalEntries);
			$journalParams["journals"] = ["entry_date" => $this->method->getDate('date'), "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = $journalEntries;
			if($this->Params['activity_id'] == 16){ // LOAN APPROVED		
				$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
				$getJournalID = $generateJournalEntry['post_id'];
				$queueUpdateElements = [];
				$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
				$queueUpdateElements['schema'] = Info::DB_DATA;
				$queueUpdateElements['table'] = "data_queue";
				$queueUpdateElements['id'] = $this->Params['post_id'];
				$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
				
				### GENERATING CBU ###
				$checkHasCBU = $this->method->array_search_by_key($journalEntries, 'charts_id', $chartCBU, 'credit');
				if($checkHasCBU){
					$cbuAmount = array_keys($checkHasCBU);
					$this->Params['source'] = false; // SET SOURCE CREATE_DATA AS FROM THIRD-PARTY
					$this->Params['path_alias'] = "cbu";
					$this->Params['post_id'] = 0;
					$this->Params['data_id'] = 0;
					$this->Params['path_id'] = 5;
					$this->Params['activity_id'] = 10;
					$this->Params['post_activity_id'] = 10;
					$this->Params['cbu_details']['mem_id'] = $this->Params['loans_details']['mem_id'];
					$this->Params['cbu_details']['mem_info'] = $this->Params['loans_details']['mem_info'];
					$this->Params['cbu_details']['amount'] = $cbuAmount[0];
					$this->Params['cbu_details']['remarks'] = "Capital Build-Up thru Loan Application, REFNUM[{$GLOBALS['data_id']}]"; // DO NOT CHANGE REFNUM[00] FORMAT
					ob_start();
					self::createData();
					ob_end_clean();
				}
				### GENERATING CBU ###
				
				### GENERATING LOANS PAYMENT FOR REFINANCING ###
				if($loanBalanceInfo){ // REFINANCING
					$this->Params['source'] = false; // SET SOURCE CREATE_DATA AS FROM THIRD-PARTY
					$this->Params['path_alias'] = "loans_payment";
					$this->Params['post_id'] = 0;
					$this->Params['data_id'] = 0;
					$this->Params['path_id'] = 8;
					$this->Params['activity_id'] = 18;
					$this->Params['post_activity_id'] = 18;
					$this->Params['loans_payment_transactions']['loans_id'] = $loanBalanceID;
					$this->Params['loans_payment_transactions']['loans_info'] = $this->Params['loans_details']['mem_info'];
					$this->Params['loans_payment_transactions']['trans_date'] = $this->method->getDate('date');
					$this->Params['loans_payment_transactions']['payment_type'] = 1;
					$this->Params['loans_payment_transactions']['payment_principal'] = $loanBalanceAmount;
					$this->Params['loans_payment_transactions']['remarks'] = "Refinancing Payments from REFNUM[".$this->method->formatValue(['prefix'=>7,'id'=>$GLOBALS['data_id']],"app_id")."]"; // DO NOT CHANGE REFNUM[00] FORMAT
					ob_start();
					self::createData();
					ob_end_clean();
				}
				### GENERATING LOANS PAYMENT FOR REFINANCING ###
			}
			
			if(in_array($this->Params['post_id'], array_keys($_SESSION["notifications"]))){ // UNSET SESSION NOTIFICATIONS
				unset($_SESSION["notifications"][$this->Params['post_id']]);
			}
			
			$this->resultElement["journals"] = $journalParams;
			$deductionValues = json_encode($setDeductions, true);
		}else{
			$deductionValues = "";
		}
		### SETTING DEDUCTIONS AS JOURNAL ENTRIES END ###
		
		### UPDATING ARRAY LOAN_DEDUCTIONS OF DATABASE ###
		$table = "path_7_loan_summary";
		$stmtLoanSummary = ['schema'=>Info::DB_DATA,'table'=>$table,'arguments'=>['data_id'=>$this->Params['data_id']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['data_id','id']];
		$getLoanSummary = $this->method->selectDB($stmtLoanSummary);
		$pathElements = [];
		$pathElements['fieldValues']['loan_deductions'] = $deductionValues;
		$pathElements['fieldValues']['approve_date'] = (isset($this->Params['loan_summary']['approve_date']) && $this->Params['loan_summary']['approve_date'] != "") ? $this->Params['loan_summary']['approve_date'] : $this->method->getDate('date');
		$pathElements['fieldValues']['maturity_date'] = $this->Params["maturity_date"];
		$pathElements['schema'] = Info::DB_DATA;
		$pathElements['table'] = $table;
		$pathElements['id'] = $getLoanSummary[$this->Params['data_id']];
		$cntPathUpdate = $this->method->updateDB($pathElements);
		### UPDATING LOAN_DEDUCTIONS OF DATABASE ###
		
		return $cntPathUpdate;
	}
	
	private function customPathLoansPayment(){
		$cntPathUpdate = "";
		$GLOBALS['post_id'] = $this->Params['post_id'];
		$GLOBALS['data_id'] = $this->Params['data_id'];
		$loansID = $this->Params['loans_payment_transactions']['loans_id'];
		$stmtLoanDetails = ['schema'=>Info::DB_DATA,'table'=>'path_7_loans_details','arguments'=>['id'=>$loansID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['id','mem_id','mem_info']];
		$getLoanDetails = $this->method->selectDB($stmtLoanDetails);
		if($this->Params['activity_id'] == 18 && $this->Params['source']){ // LOANS PAYMENT COMPLETED
			switch($this->Params['loans_payment_transactions']['payment_type']){
				default: // CASH
					$paymentType = $_SESSION["module_charts"]['cash_hand'];
				break;
				case "2": // CHECK
					$paymentType = $_SESSION["module_charts"]['cash_bank'];
				break;
			}
			
			$particulars = "[Loans Payment] LoanID: ".$this->method->formatValue(['prefix'=>7,'id'=>$loansID],"app_id")."<br>".$this->Params['loans_payment_transactions']['remarks'];
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			//$journalParams["entry"] = "2";
			$journalParams["journals"] = ["entry_date" => $this->Params['loans_payment_transactions']['trans_date'], "recipient"=>$this->Params['members_name'], "particulars" => $particulars];
		
			$cnt = 1;
			$interestAmount = $this->Params['loans_payment_transactions']['payment_interest'];
			$journalInterestAmount = ($this->Params['interest_type'] == 1) ? 0 : $interestAmount;
			$creditLoansReceivable = $this->Params['loans_payment_transactions']['payment_principal'];
			$creditPenalties = $this->Params['loans_payment_transactions']['payment_penalty'];
			$creditCBU = $this->Params['loans_payment_transactions']['payment_cbu'];
			$creditSavings = $this->Params['loans_payment_transactions']['payment_savings'];
			
			$cashLoanReceivable = ($creditLoansReceivable < 0) ? 0 : $creditLoansReceivable;
			$cashLoanInterest = ($journalInterestAmount < 0) ? 0 : $journalInterestAmount;
			$debitCash = $cashLoanReceivable + $creditCBU + $creditSavings + $creditPenalties + $cashLoanInterest;
			$journalParams["journals_entry"][$cnt] = ["charts_id"=>$paymentType,"debit"=>$debitCash,"credit"=>"0.00"]; // CASH ON HAND/BANK
			$cnt++;
			if($this->Params['interest_type'] == 1){ // PREPAID
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['unearned_income'],"debit"=>$interestAmount,"credit"=>"0.00"]; // UNEARNED INCOME
				$cnt++;
			}
			if($creditLoansReceivable < 0){ // IF NEGATIVE PRINCIPAL AMOUNT
				$creditLoansReceivable = abs($creditLoansReceivable);
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$paymentType,"debit"=>"0.00","credit"=>$creditLoansReceivable]; // CASH ON HAND/BANK
				$cnt++;
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['loans_receivable'],"debit"=>$creditLoansReceivable,"credit"=>"0.00"]; // LOANS RECEIVABLE/PRINCIPAL AMOUNT
			}else{ // NORMAL
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['loans_receivable'],"debit"=>"0.00","credit"=>$creditLoansReceivable]; // LOANS RECEIVABLE/PRINCIPAL AMOUNT
			}
			$cnt++;
			
			if($interestAmount < 0){ // IF NEGATIVE INTEREST AMOUNT
				$interestAmount = abs($interestAmount);
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$paymentType,"debit"=>"0.00","credit"=>$interestAmount]; // CASH ON HAND/BANK
				$cnt++;
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['interest_income'],"debit"=>$interestAmount,"credit"=>"0.00"]; // INTEREST INCOME
			}else{ // NORMAL
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['interest_income'],"debit"=>"0.00","credit"=>$interestAmount]; // INTEREST INCOME
			}
			$cnt++;
			
			if($creditPenalties > 0){
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['penalties'],"debit"=>"0.00","credit"=>$creditPenalties]; // PENALTIES
				$cnt++;
			}
			if($creditCBU > 0){
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['cbu'],"debit"=>"0.00","credit"=>$creditCBU]; // CBU
				$cnt++;
			}
			if($creditSavings > 0){
				$journalParams["journals_entry"][$cnt] = ["charts_id"=>$_SESSION["module_charts"]['savings_deposit'],"debit"=>"0.00","credit"=>$creditSavings]; // SAVINGS
				$cnt++;
			}
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
			
			if($creditCBU > 0){
				### GENERATING CBU ###
				$this->Params['source'] = false; // SET SOURCE CREATE_DATA AS FROM THIRD-PARTY
				$this->Params['path_alias'] = "cbu";
				$this->Params['post_id'] = 0;
				$this->Params['data_id'] = 0;
				$this->Params['path_id'] = 5;
				$this->Params['activity_id'] = 10;
				$this->Params['post_activity_id'] = 10;
				$this->Params['cbu_details']['mem_id'] = $getLoanDetails[$loansID]->mem_id;
				$this->Params['cbu_details']['mem_info'] = $getLoanDetails[$loansID]->mem_info;
				$this->Params['cbu_details']['amount'] = $creditCBU;
				$this->Params['cbu_details']['remarks'] = "Capital Build-Up thru Loans Payment, REFNUM[{$GLOBALS['data_id']}]"; // DO NOT CHANGE REFNUM[00] FORMAT
				ob_start();
				self::createData();
				ob_end_clean();
				### GENERATING CBU ###
			}
			
			if($creditSavings > 0){
				### GENERATING SAVINGS DEPOSIT ###
				$memID = $getLoanDetails[$loansID]->mem_id;
				$stmtSavingsDetails = ['schema'=>Info::DB_DATA,'table'=>'path_2_savings_details','arguments'=>['mem_id'=>$memID,'product_savings'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['mem_id','id','mem_info']];
				$getSavingsDetails = $this->method->selectDB($stmtSavingsDetails);
				$this->Params['source'] = false; // SET SOURCE CREATE_DATA AS FROM THIRD-PARTY
				$this->Params['path_alias'] = "deposits";
				$this->Params['post_id'] = 0;
				$this->Params['data_id'] = 0;
				$this->Params['path_id'] = 3;
				$this->Params['activity_id'] = 6;
				$this->Params['post_activity_id'] = 6;
				$this->Params['deposits_transactions']['savings_id'] = $getSavingsDetails[$memID]->id;
				$this->Params['deposits_transactions']['savings_info'] = $getSavingsDetails[$memID]->mem_info;
				$this->Params['deposits_transactions']['trans_date'] = $this->Params['loans_payment_transactions']['trans_date'];	
				$this->Params['deposits_transactions']['amount'] = $creditSavings;
				$this->Params['deposits_transactions']['remarks'] = "Savings Deposit thru Loans Payment, REFNUM[{$_POST['data_id']}]"; // DO NOT CHANGE REFNUM[00] FORMAT
				ob_start();
				self::createData();
				ob_end_clean();
				### GENERATING SAVINGS ###
			}
			
			$this->resultElement["journals_entry"] = $journalParams["journals_entry"];
			
		}elseif(!$this->Params['activity_id']){ // REVERSING DATA AND JOURNALS
			$stmtQueueData = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$this->Params['post_id']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','journals_id']];
			$getQueueData = $this->method->selectDB($stmtQueueData);
			$journalID = $getQueueData[$this->Params['post_id']];
			$this->method->reverseJournals($journalID); // REVERSING JOURNALS
			
			### CHECKING AND REVERSING QUEUE DATA ###
			if($this->Params['loans_payment_transactions']['payment_cbu'] > 0){ // CHECK IF HAS CBU TRANSACTION
				$refNumFormat = "REFNUM[{$this->Params['data_id']}]";
				$stmtCbuDetails = ['schema'=>Info::DB_DATA,'table'=>'path_5_cbu_details','arguments'=>['mem_id'=>$getLoanDetails[$loansID]->mem_id],'extra'=>'AND remarks LIKE "%'.$refNumFormat.'%"','pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['data_id']];
				$getCbuDetails = $this->method->selectDB($stmtCbuDetails);
				if($getCbuDetails){ // IF HAS CBU TRANSACTION
					$stmtQueueData = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['path_id'=>5,'activity_id'=>10,'data_id'=>$getCbuDetails[0]],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
					$getQueueData = $this->method->selectDB($stmtQueueData);
					$queueID = $getQueueData[0];
					$queueUpdateElements = [];
					$queueUpdateElements['fieldValues']["activity_id"] = NULL;
					$queueUpdateElements['schema'] = Info::DB_DATA;
					$queueUpdateElements['table'] = "data_queue";
					$queueUpdateElements['id'] = $queueID;
					$this->method->updateDB($queueUpdateElements);
				}
			} // END IF HAS CBU TRANSACTION
			### CHECKING AND REVERSING QUEUE DATA ###
		}
		return $cntPathUpdate;
	}
	
	private function customPathCash(){
		$cntPathUpdate = "";
		if($this->Params['activity_id'] == 23){ // TRANSACTION APPROVED
			$cashType = $this->Params['cash_transactions']['cash_type'];
			$cashTrans = $this->Params['cash_transactions']['cash_trans'];
			// $arrayOptions = $this->method->array_search_by_key($_SESSION["codemeta"]["cash_option"], 'meta_parent', $cashType, 'meta_value');
			// $getOptions = implode(",",array_keys($arrayOptions));
			// $stmtCashOptions = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['status'=>1,'meta_key'=>'cash_option'],'extra'=>' AND id IN ('.$getOptions.')','pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['meta_id','meta_value']];
			// $getCashOptions = $this->method->selectDB($stmtCashOptions);
			switch($this->Params['cash_details']['payment_type']){
				case "1": // CASH
					$accountCash = $_SESSION["module_charts"]['cash_hand'];
				break;
				case "2": // CHECK
					$accountCash = $_SESSION["module_charts"]['cash_bank']; // CASH IN BANK
				break;
			}
			$cashTranctionSettings = $this->method->methodSettings(['meta_key'=>'module_policy','name'=>'cash_transaction']); // GET METHODS MODULE SETTINGS
			switch($cashType){
				case "1": // CASH DISBURSEMENT
					$transName = "Cash Disbursement";
					$debitAccount = $cashTranctionSettings[$cashTrans];
					$creditAccount = $accountCash;
				break;
				case "2": // CASH RECEIPT
					$transName = "Cash Receipt";
					$debitAccount = $accountCash;
					$creditAccount = $cashTranctionSettings[$cashTrans];
					### TO FORCE CASH RECEIPT TRANSACTION TO COMPLETED ###
					$queueUpdateElements = [];
					$queueUpdateElements['fieldValues']["activity_id"] = $this->Params['activity_id'];//'[{"date":'.$this->method->getDate().',"user":'.$_SESSION['userID'].'}]';
					$queueUpdateElements['schema'] = Info::DB_DATA;
					$queueUpdateElements['table'] = "data_queue";
					$queueUpdateElements['id'] = $this->Params['post_id'];
					$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
					### TO FORCE CASH RECEIPT TRANSACTION TO COMPLETED ###
				break;
			}
			$particulars = "[{$transName}] ".$this->Params['cash_details']['remarks'];
			$journalParams = [];
			$journalParams["table"] = "journals";
			$journalParams["unit"] = $_SESSION['unit'];
			$journalParams["theID"] = "0";
			$journalParams["entry"] = "2";
			$journalParams["journals"] = ["entry_date" => $this->Params['cash_details']['trans_date'], "recipient"=>$this->Params['recipients_name'], "particulars" => $particulars];
			$journalParams["journals_entry"] = [
					1 => ["charts_id" => $debitAccount, "debit" => $this->Params['cash_details']['amount'], "credit" => "0.00"],
					2 => ["charts_id" => $creditAccount, "debit" => "0.00", "credit" => $this->Params['cash_details']['amount']]
				];
			$this->resultElement["cash_transaction"] = $journalParams;
			$generateJournalEntry = $this->method->generateJournalEntries($journalParams);
			$getJournalID = $generateJournalEntry['post_id'];
			$queueUpdateElements = [];
			$queueUpdateElements['fieldValues']["journals_id"] = $getJournalID;
			$queueUpdateElements['schema'] = Info::DB_DATA;
			$queueUpdateElements['table'] = "data_queue";
			$queueUpdateElements['id'] = $this->Params['post_id'];
			$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
			
		}elseif(!$this->Params['activity_id']){ // REVERSING DATA AND JOURNALS
			$stmtQueueData = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['id'=>$this->Params['post_id']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','journals_id']];
			$getQueueData = $this->method->selectDB($stmtQueueData);
			$journalID = $getQueueData[$this->Params['post_id']];
			$this->method->reverseJournals($journalID);
		}
		
		if(in_array($this->Params['post_id'], array_keys($_SESSION["notifications"]))){ // UNSET SESSION NOTIFICATIONS
			unset($_SESSION["notifications"][$this->Params['post_id']]);
		}
		return $cntPathUpdate;
	}
	
	function createData(){ // CREATING/UPDATING DATA POST
		$postStatus = [];
		$toUpdateData = true;
		$this->Params['create_schedule'] = false;
		$insertFields = $insertValues = $output = $logs = $test = [];
		$dataAction = $logAction = "create";
		$this->resultElement['success'] = $postID = $cntUpdate = $apiStatus = $cntQueueUpdate = 0;
		$this->method->getElementType = "fields";
		$this->method->getElementType = "1";
		$globalGroup = $this->method->getElements($this->Params['path_alias']);
		if(!$this->Params['activity_id']) $this->Params['activity_id'] = NULL;
		$postActivityID = $this->resultElement['activity_id'] = $this->Params['activity_id'];

		if($this->Params['post_id'] < 1){ // CREATE DATA ON QUEUE //
			$insertFields = $this->method->getTableFields(['table'=>'data_queue','exclude'=>['id','journals_id','data_logs'],'schema'=>Info::DB_DATA]);
			$insertValues[] = $this->method->getTime('datetime');
			$insertValues[] = $this->method->getTime('datetime');
			$stmtQueueData = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['path_id'=>$this->Params['path_id']],'pdoFetch'=>PDO::FETCH_COLUMN,'extra'=>'ORDER BY id DESC','fields'=>['id']];
			$getQueueData = $this->method->selectDB($stmtQueueData);
			$dataQueueID = sizeof($getQueueData) + 1;
			$insertValues[] = $dataQueueID;
			$getQueuePostFields = ['path_id','activity_id','user','unit'];

			foreach($getQueuePostFields as $queueField){
				$postValue = '';
				if(isset($this->Params[$queueField]) && $this->Params[$queueField] != ''){
					$postValue = $this->Params[$queueField]; // SET POST VALUES
				}
				$insertValues[] =  $postValue;//$postValue; // SET VALUES ON CREATES
			}
			$stmtElements = ['schema'=>Info::DB_DATA,'table'=>'data_queue','fields'=>$insertFields,'values'=>$insertValues];
			$postID = $this->method->insertDB($stmtElements);
			if($postID > 0){
				if($this->Params['source']){
					$GLOBALS['post_id'] = $postID;
					$GLOBALS['data_id'] = $dataQueueID;
				}
				$this->Params['post_id'] = $postID;
				$this->Params['data_id'] = $dataQueueID;
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = "Data/Record has been updated successfully!";
			}
		}else{ // UPDATE DATA ON QUEUE
			$dataAction = "update";
			$logAction = $dataAction;
			$queueElements = [];
			$queueElements['fieldValues'] = ["date_updated"=>$this->method->getTime('datetime'),"activity_id"=>$postActivityID];
			$queueElements['schema'] = Info::DB_DATA;
			$queueElements['table'] = "data_queue";
			$queueElements['id'] = $this->Params['post_id'];
			$cntQueueUpdate = $this->method->updateDB($queueElements);
			if($cntQueueUpdate > 1){ // DATE_UPDATED NOT TO COUNT
				$dataQueueID = $this->Params['data_id'];
				$cntUpdate = $cntUpdate + $cntQueueUpdate;
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = "Data/Record has been updated successfully!";
			}
		}
		$logs["data_queue"] = [$insertFields,$insertValues];
		
		$getPostValue = $this->method->getValueDB(['table'=>'data_queue','id'=>$this->Params['post_id'],'schema'=>Info::DB_DATA]);
		
		if($toUpdateData && $this->Params['post_id'] > 0){ // START HAS POST_ID
			$cntDataUpdate = 0;
			foreach($globalGroup as $metaType => $metaDetails){ // LOOP THE FIELDS IN GROUP
				$postUpdate = $insertValues = $insertFields = $stmtElements = [];
				$dataPathTable = "path_{$this->Params['path_id']}_{$metaType}";
				$tableFields = $this->method->getTableFields(["table"=>$dataPathTable,'exclude'=>['id','data_id','date'],'schema'=>Info::DB_DATA]);
				//$insertFields[] = "date";
				//$insertValues[] = "2019-08-23 16:18:05";
				$insertFields[] = "data_id";
				$insertValues[] = $dataQueueID;
				foreach($tableFields as $field){
					if(isset($this->Params[$metaType][$field])){ // IF HAS INPUT PARAMS/POST
						$postValue = $this->Params[$metaType][$field];
						$insertFields[] = $field;
						$insertValues[] = $postValue;
						$postUpdate[$field] = $postValue;
					}
				}
				//$insertValues =  array_merge([$dataQueueID],array_values($postUpdate)); // VALUES ON CREATES
				if($dataAction == "create"){ // INSERT DATA/RECORDS //$this->Params['data_id'] < 1
					$stmtDataElements = ["schema"=>Info::DB_DATA,"table"=>$dataPathTable,"fields"=>$insertFields,"values"=>$insertValues];
					$groupRecordID = $this->method->insertDB($stmtDataElements);
					$this->Params['data_id'] = $dataQueueID;
					$this->resultElement['success'] = 1;
					$cntUpdate = $cntUpdate + $groupRecordID;
				}else{ // UPDATE DATA/RECORDS
					$postUpdate['data_id'] = $this->Params['data_id'];
					$getValue = $this->method->getValueDB(['table'=>$dataPathTable,'data_id'=>$this->Params['data_id'],'schema'=>Info::DB_DATA]);
					if($getValue){
						$stmtElements['fieldValues'] = $postUpdate;
						$stmtElements['schema'] = Info::DB_DATA;
						$stmtElements['table'] = $dataPathTable;
						$stmtElements['id'] = $getValue['id'];
						$cntDataUpdate = $this->method->updateDB($stmtElements);
					}else{ // INSERT IF NO RECORD OR NEW GROUP HAS CREATED
						$insertValues[0] = $this->Params['data_id'];
						$stmtDataElements = ["schema"=>Info::DB_DATA,"table"=>$dataPathTable,"fields"=>$insertFields,"values"=>$insertValues];
						$groupRecordID = $this->method->insertDB($stmtDataElements);
						$cntUpdate = $cntUpdate + $groupRecordID;
					}
					$postStatus[$dataPathTable] = $insertValues;
					if($cntDataUpdate > 0){
						//$cntDataUpdate = 0;
						$this->resultElement['result'] = "success";
						$this->resultElement['message'] = "Data/Record has been updated successfully!";
					} 
				}
				$logs[$metaType] = $postUpdate;//[$dataAction,$dataQueueID,$postActivityID,$insertFields,$insertValues,$postUpdate]

				$output[] = $tableFields;
				$cntUpdate = $cntUpdate + $cntDataUpdate;
			} // END LOOP THE FIELDS IN GROUP
		}
		
		### SAVING LOGS ON QUEUE ###
		$setPostDataLog = [];
		$postDataLog = $getPostValue['data_logs'];
		$setPostDataLog = json_decode($postDataLog,true);
		$setDataLogs["date"] = $this->method->getDate();
		$setDataLogs["action"] = $logAction;
		$setDataLogs["activity"] = $postActivityID;
		$dataLogs = array_merge($setDataLogs,$logs);
		unset($dataLogs["data_queue"]);
		$dataLogs["user"] = $_SESSION['userID'];
		$setPostDataLog[] = $dataLogs;//["date"=>$this->method->getDate(),"activity"=>$_POST['post_activity_id'],"user"=>$_SESSION['userID']];
		$queueUpdateElements['fieldValues']["data_logs"] = json_encode($setPostDataLog, true);//'[{"date":'.$this->method->getDate().',"user":'.$_SESSION['userID'].'}]';
		$queueUpdateElements['schema'] = Info::DB_DATA;
		$queueUpdateElements['table'] = "data_queue";
		$queueUpdateElements['id'] = $this->Params['post_id'];
		$cntQueueUpdateElements = $this->method->updateDB($queueUpdateElements);
		### END SAVING LOGS ON QUEUE ###
		
		$sessionNotification = $_SESSION["notifications"][$_SESSION['data'][$this->Params['path_alias']]['data_id']];
		if($_SESSION["userrole"] <= 4 && $sessionNotification){ // UPDATING SESSION NOTIFICATIONS
			$notificationActivities = Info::NOTIFICATION_ACTIVITIES;
			$notificationActivities = explode(",",$notificationActivities);
			if(in_array($this->Params['post_activity_id'],$notificationActivities)) $_SESSION["notifications"][$_SESSION['data'][$this->Params['path_alias']]['data_id']]["status"] = 1;
		}
		$this->resultElement["data_action"] = $dataAction;
		$this->resultElement["data_updated"] = $cntUpdate;
		$this->resultElement["path_id"] = $this->Params['path_id'];
		$this->resultElement['data_id'] = $this->Params['data_id'];//$dataQueueID;
		$this->resultElement["post_id"] = $this->Params['post_id'];
		$this->resultElement['post_activity_id'] = $this->Params['post_activity_id']; //$postActivityID;
		$this->resultElement["parseLogs"] = $logs;//$postValue;
		$this->resultElement["params"] = $this->Params;
		
		switch($this->Params['path_id']){ // CUSTOM CALL FUNCTION
			case "2": // SAVINGS
				$this->customPathSavings();
			break;
			case "3": // DEPOSITS
				$this->customPathDeposits();
			break;
			case "4": // WITHDRAWAL
				$this->customPathWithdrawal();
			break;
			case "5": // CBU
				$this->customPathCBU();
			break;
			case "6": // SHARE CAPITAL
				$this->customPathShareCapital();
			break;
			case "7": // LOANS
				$this->customPathLoans();
				$this->resultElement["path_id"] = 7;
				$this->resultElement['data_id'] = $GLOBALS['data_id'];
				$this->resultElement["post_id"] = $GLOBALS['post_id'];
				$this->resultElement['post_activity_id'] = $_POST['post_activity_id'];
				$this->resultElement['activity_id'] = $_POST['activity_id'];
			break;
			case "8": // LOANS PAYMENT
				$this->customPathLoansPayment();
				$this->resultElement["path_id"] = 8;
				$this->resultElement['data_id'] = $GLOBALS['data_id'];
				$this->resultElement["post_id"] = $GLOBALS['post_id'];
				$this->resultElement['post_activity_id'] = $_POST['post_activity_id'];
				$this->resultElement['activity_id'] = $_POST['activity_id'];
			break;
			case "10": // CASH TRANSACTION
				if($this->Params['cash_transactions']['cash_type'] == 2) $this->resultElement['post_activity_id'] = $this->Params['activity_id'] = 23; // FORCE TO CASH TRANSACTION APPROVED
				$this->customPathCash();
			break;
		}
		
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement); //$test
	}
	
	function createLoanSchedule(){ // NEW CREATE LOAN AMORTIZATION SCHEDULE TO DB
		$getResults = [];
		$getDate = $this->method->getDate();
		$this->method->Params['approved_date'] = $this->method->getDate('date');
		$this->method->Params['start_date'] = $this->Params["loan_summary"]["first_due_date"];
		$this->method->Params['loan_amount'] = $this->Params["loan_summary"]["loan_granted"];
		$this->method->Params['terms'] = (int)$this->Params["loans_details"]["loan_terms"];
		$this->method->Params['interest_annum'] = floatVal($this->Params["loans_details"]["loan_interest"]);
		$this->method->Params['payment_mode'] = floatVal($this->Params["loans_details"]["payment_mode"]);
		$this->method->Params['amortization_type'] = floatVal($this->Params["loan_summary"]["amortization_type"]); // 1:DEMINISHING, 2:STRAIGHT-LINE, 3:ANNUITY
		$this->method->Params['dd_type'] = floatVal($this->Params["loan_summary"]["dd_type"]);
		$amortizationDates = $this->method->generateAmortizationSchedule();
		$getLoanScheduleAssoc = $amortizationDates['schedule'];
		$getSummarySchedule = $amortizationDates['summary'];
		
		$dbConnect = Info::DBConnection();
		# CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE START
		$deleteLoanSchedule = [];
		$stmtLoanSchedule = ['schema'=>Info::DB_DATA,'table'=>'loan_schedule','arguments'=>['data_id'=>$this->Params['data_id']],'extra'=>'','pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
		$getLoanScheduleID = $this->method->selectDB($stmtLoanSchedule);
		if(count($getLoanScheduleID) > 0){
			$deleteLoanSchedule = $this->method->deleteDB(['table'=>'loan_schedule','id'=>$getLoanScheduleID,'schema'=>Info::DB_DATA]);
		}
		# CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE END
		
		# CREATING LOAN SCHEDULE ON ECAPPS DATABASE
		$insertFields = $this->method->getTableFields(['table'=>'loan_schedule','exclude'=>[],'schema'=>Info::DB_DATA]);
		$stmtFields = implode(",",$insertFields);
		$stmtClause = implode(",",array_fill(0, count($insertFields), '?'));
		$stmt = $dbConnect->prepare("INSERT INTO ".Info::PREFIX_SCHEMA.Info::DB_DATA.".loan_schedule ({$stmtFields}) VALUES ({$stmtClause})");
		//$scheduleValue = [];
		foreach($getLoanScheduleAssoc as $num => $loanSchedDetail){
			$arrayLoanDetail1 = [NULL, $getDate, $this->Params['data_id'], $num];
			$arrayLoanDetail2 = [$loanSchedDetail['due_date'],$loanSchedDetail['principal_due'],$loanSchedDetail['interest_due'],$loanSchedDetail['payment_due'],$loanSchedDetail['total_due'],$loanSchedDetail['loan_balance']];
			$arrayLoanDetail3 = [NULL, $getDate];
			$stmtValues = array_merge($arrayLoanDetail1, $arrayLoanDetail2, $arrayLoanDetail3);
			$stmt->execute($stmtValues);
			$loanSchedID = $dbConnect->lastInsertId();
			$dueDate = $loanSchedDetail['due_date'];
			$getResults[] = $stmtValues;
		} // END FOREACH
		# END CREATING LOAN SCHEDULE ON ECAPPS DATABASE
		
		$this->Params["maturity_date"] = $dueDate;
		$this->Params["total_interest"] = $getSummarySchedule['interest_due'];
	}
	
	function createLoanSchedule2(){ // TO DELETE
		$stmtValues = $loanSchedID = $apiloanSchedFormat = [];
		$lastDueDate = $firstPaymentDate = "";
		$getDate = $this->method->getDate('date');
		$loanApprovedDate = (isset($this->Params['loan_summary']['approve_date']) && $this->Params['loan_summary']['approve_date'] != "") ? $this->Params['loan_summary']['approve_date'] : $getDate;
		if($this->Params['loans_details']['grace_period'] != "" && $this->Params['loans_details']['grace_period'] > 0){
			$counter = $this->Params['loans_details']['grace_period'];
			$getApproveDate = new DateTime($loanApprovedDate);
			$getApproveDate->modify('+'.$counter.' day');
			$loanApprovedDate = $getApproveDate->format('Y-m-d');
		}
		$loanInterestMethod = $this->Params["loan_summary"]["amortization_type"];
		$interestPerAnnum = floatVal($this->Params["loans_details"]["loan_interest"]) / 12;
		$loanDetails = [
			"date_approved"=>$loanApprovedDate,
			"loan_granted"=>$this->Params["loan_summary"]["loan_granted"],
			"payment_terms"=>$this->Params["loans_details"]["loan_terms"],
			"payment_mode"=>$this->Params["loans_details"]["payment_mode"],
			"loan_interest"=>$interestPerAnnum,
			"interest_type"=>$this->Params["loan_summary"]["interest_type"],
			"loan_type"=>$this->Params["loan_details_loan_types"],
			"interest_method"=>$loanInterestMethod
			//"interest_amount"=>$this->Params["interest_amount"]
		];
		$generateSchedule = $this->method->generateSchedule($loanDetails);
		$getLoanScheduleAssoc = $generateSchedule['schedule_associative'];
		$getLoanScheduleObject = $generateSchedule['schedule_object'];
		$getLoanScheduleSummary = $generateSchedule['summary'];
		
		if($this->Params['create_schedule']){ //$this->Params['activity_id'] == 6	// TO INSERT SCHEDULE IN DB
			// CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE START
			$stmtLoanSchedule = ['schema'=>Info::DB_DATA,'table'=>'loan_schedule','arguments'=>['data_id'=>$this->Params['data_id']],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
			$getLoanScheduleID = $this->method->selectDB($stmtLoanSchedule);
			if(count($getLoanScheduleID) > 0){
				$deleteLoanSchedule = $this->method->deleteDB(['table'=>'loan_schedule','id'=>$getLoanScheduleID,'schema'=>Info::DB_DATA]);
			}
			// CHECKING TO REMOVE LOAN IF HAS EXISTING SCHEDULE END
			$insertFields = $this->method->getTableFields(['table'=>'loan_schedule','exclude'=>[],'schema'=>Info::DB_DATA]);
			$stmtFields = implode(",",$insertFields);
			$stmtClause = implode(",",array_fill(0, count($insertFields), '?'));
			$stmt = $this->method->db->prepare("INSERT INTO ".Info::PREFIX_SCHEMA.Info::DB_DATA.".loan_schedule ({$stmtFields}) VALUES ({$stmtClause})");
		}
		$cnt = 1;
		foreach($getLoanScheduleAssoc as $num => $loanSchedDetail){
			$apiloanSchedFormat[] = [
				"loan_id"=>$this->method->externalLoanID,
				"member_no"=>$this->Params["loans_details"]["mem_id"],
				"loan_type"=>$this->Params["loans_details"]["loan_types"],
				"due_date"=>$loanSchedDetail['due_date'],
				"prin_due"=>$loanSchedDetail['principal_due'],
				"int_due"=>$loanSchedDetail['interest_due'],
				"payment_amt"=>$loanSchedDetail['payment_due'],
				"cumm_amt"=>$loanSchedDetail['loan_balance']
				];
			
			if($this->Params['create_schedule']){ // TO INSERT SCHEDULE IN DB
				$arrayLoanDetail1 = [NULL, $getDate, $this->Params['data_id'], $num];
				$arrayLoanDetail2 = [$loanSchedDetail['due_date'],$loanSchedDetail['principal_due'],$loanSchedDetail['interest_due'],$loanSchedDetail['payment_due'],$loanSchedDetail['total_due'],$loanSchedDetail['loan_balance']];
				$arrayLoanDetail3 = [NULL, $getDate];
				$stmtValues = array_merge($arrayLoanDetail1, $arrayLoanDetail2, $arrayLoanDetail3);
				$stmt->execute($stmtValues);
				$loanSchedID = $this->method->db->lastInsertId();
				
			}
			if($cnt == 1) $firstPaymentDate = $loanSchedDetail['due_date'];
			$lastDueDate = $loanSchedDetail['due_date'];
			//$stmt->rowCount();
			$cnt++;
		} // END FOREACH SCHEDULE COLUMNS
		//header("Content-Type: text/json; charset=utf8");
		$this->Params["maturity_date"] = $lastDueDate;
		$this->Params["first_payment_date"] = $firstPaymentDate;
		$getLoanSchedule["schedule_object"] = $getLoanScheduleObject;
		$getLoanSchedule["summary"] = $getLoanScheduleSummary;
		$getLoanSchedule["summary"]["date_approved"] = date_format(date_create($loanApprovedDate), 'F j, Y');
		$getLoanSchedule["summary"]["maturity_date"] = date_format(date_create($lastDueDate), 'F j, Y');
		$getLoanSchedule["summary"]["reference_number"] = str_pad($this->Params['unit'], 2, '0', STR_PAD_LEFT)."-".str_pad($this->method->externalLoanID, 8, '0', STR_PAD_LEFT);
		
		$this->Params["LoanScheduleClamp"] = json_encode($apiloanSchedFormat);
		return $getLoanSchedule;//json_encode($getLoanScheduleObject);
	}

	function createExtras(){
		ob_start();
		$this->resultElement = $_POST;
		$cntUpdate = 0;
		$insertValues = $insertFields = $stmtDataElements = [];
        $theID = $this->Params['theID'];
        $postMetaKey = $this->Params['meta_key'];
        $postDataID = $this->Params['data_id'];
		$postTable = $this->Params['table'];
		$this->resultElement['meta_key'] = $postMetaKey."_pay";
        //$theUser = $_SESSION['userID'];
		$postScore = $this->Params['score'];
		switch($postMetaKey){
            case 'willingness':
                $theScore = $this->method->getAdjectival($this->resultElement['meta_key'],$postScore);
                $this->resultElement['score'] = $theScore;
            break;
            default:
                $this->resultElement['score'] = $postScore;
            break;
		}

        unset($_POST['action'],$_POST['meta_key'],$_POST['theID'],$_POST['data_id'],$_POST['table'],$_POST['score'],$_POST['user']);
        $this->resultElement['value'] = json_encode([$_POST],true);
        //$setData = new setData(['schema'=>Info::DB_DATA,'table'=>'data_extras']);
		$insertFields = $this->method->getTableFields(['table'=>$postTable,'exclude'=>['id','date_created','date_updated'],'schema'=>Info::DB_DATA]);
        if($theID > 0){ // UPDATE
			$fieldValues = ["value='".$this->resultElement['value']."'"];
            $dataAction = "update";
			$stmtDataElements['fieldValues'] = ["date_updated"=>$this->method->getTime('datetime'),"value"=>$this->resultElement['value']];
			$stmtDataElements['schema'] = Info::DB_DATA;
			$stmtDataElements['table'] = $postTable;
			$stmtDataElements['id'] = $theID;
			$cntUpdate = $this->method->updateDB($stmtDataElements);
			if($cntUpdate > 0){
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = "Clients CREDIT RATING has been updated!";
			}else{
				$this->resultElement['success'] = 0;
			}
		}else{ // CREATE
			$cntUpdate = 1;
		//date_created	date_updated	meta_key	data_id	value	user
			//$insertValues[] = getTime('datetime');
			//$insertValues[] = getTime('datetime');
			$insertValues[] = $postMetaKey;
			$insertValues[] = $postDataID;
			$insertValues[] = $this->resultElement['value'];
			$insertValues[] = $_SESSION['userID'];

			$stmtDataElements = ["schema"=>Info::DB_DATA,"table"=>$postTable,"fields"=>$insertFields,"values"=>$insertValues];
			$theID = $this->method->insertDB($stmtDataElements);
			if($theID > 0){
				$this->resultElement['success'] = 1;
				$this->resultElement['message'] = "Data for {$postMetaKey} added.";
			}else{
				$this->resultElement['success'] = 0;
			}
			
		}

		if($cntUpdate > 0){ // TO UPDATE EXTRAS ON DATA
			$creditRatingTable = "path_7_members_credit_rating";
			$stmtMembersCreditRating = ['schema'=>Info::DB_DATA,'table'=>$creditRatingTable,'arguments'=>['data_id'=>$postDataID],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'ORDER BY id ASC','fields'=>['data_id','id']];
			$membersCreditRating = $this->method->selectDB($stmtMembersCreditRating);
			$creditRatingID = $membersCreditRating[$postDataID];

			$creditRatingElements = [];
			$creditRatingElements['fieldValues'] = [$this->resultElement['meta_key']=>$this->resultElement['score']];
			$creditRatingElements['schema'] = Info::DB_DATA;
			$creditRatingElements['table'] = $creditRatingTable;
			$creditRatingElements['id'] = $creditRatingID;
			$cntRatingUpdate = $this->method->updateDB($creditRatingElements);
		}

		$this->resultElement['id'] = $postDataID;
		$this->resultElement['theID'] = $theID;
		$this->resultElement["data_updated"] = $cntUpdate;
		$this->resultElement["parseLogs"] = [$insertFields,$insertValues];
		ob_end_clean();
        header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	
	
	
	function createprocedure(){

	}
	
	function viewcodefields(){
		echo $this->method->boxSystemForm();
	}

	function viewfields(){
		//(true) ? formBox($theTable,$theID,$action,$sessionUserID) : print pageError('403');
		echo $this->method->boxSystemForm();
	}
	
	function viewgroups(){
		echo $this->method->boxSystemForm();
	}
	
	function viewpath(){
		echo $this->method->boxSystemForm();
	}

	function viewactivity(){
		echo $this->method->boxSystemForm();
	}

	function viewprocedure(){
		echo $this->method->boxSystemForm();
	}

	function viewworkflow(){
		echo $this->method->boxSystemForm();
	}
	
	function viewtokens(){
		echo $this->method->boxSystemForm();
	}
	
	function viewcharts(){
		//$this->Params['table'] = $this->Params['theTable'];
		$this->method->popupFormID = $this->Params['theID'];
		echo $this->method->boxSystemForm();
	}

	function viewusers(){
		//$this->Params['table'] = $this->Params['theTable'];
		$this->method->popupFormID = $this->Params['theID'];
		echo $this->method->boxAdminForm();
	}

	function optionsForm(){ // module_type=admin&view=settings&type=options
		$this->method->popupFormID = $this->Params['theID'];
		echo $this->method->boxAdminForm();
	}

	function viewclients(){
		$formFields = array('theTable','theID');
		foreach($formFields as $theField){
			$$theField = $_POST[$theField];
		}
		switch($action){
			case 'logStaff': case 'logDetails': case 'adjustmentStaff': $getTable = 'payroll'; break;
			default: $getTable = $theTable; break;
		}
		$sessionUserID = $_POST['sessionUserID'];

		(true) ? formBox($theTable,$theID,$action,$sessionUserID) : print pageError('403'); //hasRights($getTable,$sessionUserID,2)
	}

	function viewData(){
		$arrayField = [];
		$getSchema = (isset($this->Params['schema']) && $this->Params['schema'] != "") ? $this->Params['schema'] : Info::DB_DATA;
		$getValue = $this->method->getValueDB(['table'=>$this->Params['table'],'id'=>$this->Params['theID'],'schema'=>$getSchema]);
		$arrayField = $getValue;

		header("Content-Type: text/json; charset=utf8");
		echo json_encode($arrayField);
	}


	// function getInfo(){
		// $arrayField = []; $test = "";
		// $theID = $_POST['theID'];
		// $table = $_POST['table'];
		// $getData = new getMetaValue(['schema'=>Info::DB_DATA,'table'=>$table]);
		// if($theID != '') {
			// $tableFields = getTableFields('clients',['id']);
			// //var_dump($tableFields);
			// $getDataValue = $getData->listings(['id'=>$theID],$tableFields);
			// $getCodeBook = new getMetaValue(['schema'=>Info::Info::DB_SYSTEMS,'table'=>'codebook']);
			// $cnt = 0;
			// foreach($getDataValue[0] as $theData){
				// $test .= $tableFields[$cnt]." | ";
				// $theField = $tableFields[$cnt];
				// if($theField){
					// switch($theField){
						// case 'memType': // IF CODEBOOK CONVERTION
							// $codebookValue = $getCodeBook->listings(['meta_key'=>'memType','meta_id'=>$theData],['meta_id','meta_value']);
							// $arrayField['memType'] = $codebookValue[0]['meta_value'];
							// break;
						// default:
							// $arrayField[$theField] = $theData;
							// break;
					// }
				// }
				// $cnt++;
			// }
		// }
		// header("Content-Type: text/json; charset=utf8");
		// echo json_encode($arrayField);
		// //echo sizeof($tableFields)." - ".$test;
	// }

	function viewextras(){
        //echo $_POST['meta_key'].' - '.$_POST['theTable'];
        $arrayMetaValue = []; $output = $jsComputeExtras = '';
        $tableFields = $this->method->getTableFields(['table'=>$_POST["theTable"],'exclude'=>[],'schema'=>Info::DB_SYSTEMS]);
		$methodExtraValues = ['schema'=>Info::DB_SYSTEMS,'table'=>'methods','arguments'=>['meta_key'=>$_POST["meta_key"]],'pdoFetch'=>PDO::FETCH_ASSOC,'extra'=>'order by meta_id asc','fields'=>$tableFields]; //,'option_name'=>$meta[$optionMeta]
		$getExtrasValue = $this->method->selectDB($methodExtraValues);
        $theID = 0;
        if($_POST['dataID'] > 0){
			$stmtExtrasDataValue = ['schema'=>Info::DB_DATA,'table'=>'data_extras','arguments'=>['meta_key'=>$_POST['meta_key'],'data_id'=>$_POST['dataID']],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>["id","meta_key","value"]]; //,'option_name'=>$meta[$optionMeta]
			$getExtrasDataValue = $this->method->selectDB($stmtExtrasDataValue);
            if($getExtrasDataValue){
				$theID = $getExtrasDataValue[0]['id'];
				$thisDataValue = $getExtrasDataValue[0]['value'];
				$arrayMetaValue = json_decode($thisDataValue);
			}
        }

		//$output = "<script src='".URL."/js/convertNumber.js'></script>";
		//$this->method->pageAction = "view";
        $output = $this->method->getExtras($theID,$arrayMetaValue,$getExtrasValue,['dataID'=>$_POST['dataID'],'meta_key'=>$_POST['meta_key'],'alias'=>$_POST['alias']]);
        echo $output;
	}

	function autoSave(){
		//$formData = [];
		$arrayID = [];
		$this->resultElement['record_type'] = "create";
		$this->resultElement['params'] = $this->Params;
		$dbSchema = (isset($this->Params['schema']) && $this->Params['schema'] != '') ? $this->Params['schema'] : Info::DB_SYSTEMS;//$this->method->schema;
		//if(in_array($this->Params['table'],$this->schemaProjectZero)) $dbSchema = Info::DB_NAME;
		if($this->Params['field'] === "" && $this->Params['id']){ // DELETE DATA
			if(is_array($this->Params['id'])){
				$arrayID = $this->Params['id'];
			}else{
				$arrayID[] = $this->Params['id'];
			}
			
			$deletePostValue = $this->method->deleteDB(['table'=>$this->Params['table'],'id'=>$arrayID,'schema'=>$dbSchema]);
			if($deletePostValue){
				$this->resultElement['record_type'] = "delete";
				$this->resultElement['success'] = 1;
				$this->resultElement['message'] = 'Data has been removed!';
			}
			
		}else{ // INSERT AND UPDATE
			if($this->Params['id'] == ''){
				$metaValue = ["table"=>$this->Params['table'],"schema"=>$dbSchema,"action"=>"autoSave","field"=>$this->Params['field'],"extra_fields"=>$this->Params['extra_fields']];
				switch($this->Params['table']){
					case "codemeta":
						$getFields[] = $this->Params['field'];
						$postValue[] = $this->Params['value'];
						$arrayExtraFields = json_decode(json_encode($this->Params['extra_fields']),true);
						$getExtraFields = $arrayExtraFields;
						foreach($getExtraFields as $key => $value){
							$getFields[] = $key;
							$postValue[] = $value;
							$cnt++;
						}
					break;
					default:
						$getFields[] = $this->Params['field'];
						$getFields[] = 'user';
						$getFields[] = 'date';
						//$postValue[] = utf8_encode($this->Params['value']);
						$postValue[] = htmlspecialchars($this->Params['value'], ENT_QUOTES);
						$postValue[] = $_SESSION['userID'];
						$postValue[] = $this->method->getDate();
					break;
				}
				$stmtElements = ['schema'=>$dbSchema,'table'=>$this->Params['table'],'fields'=>$getFields,'values'=>$postValue];
				$postID = $this->method->insertDB($stmtElements);
				$metaValue['id'] = $postID;
				$formData = json_encode($metaValue);
				if($this->Params['field'] == 'particulars'){
					$postUpdate['entry_date'] = $this->Params['entry_date'];
					$stmtElements['fieldValues'] = $postUpdate;
					$stmtElements['table'] = 'journal';
					$stmtElements['id'] = $postID;
					$this->method->updateDB($stmtElements);
					$formData = $postID;
				}
				if($postID > 0){
					$this->resultElement['success'] = 1;
					$this->resultElement['message'] = 'Data has been saved!';
					$this->Params['id'] = $postID;
				}
				//$dddd = [$getFields,$postValue];
				//echo $formData;
			}else{ // UPDATE
				$this->resultElement['record_type'] = "update";
				if($this->Params['field'] == "password") $this->Params['value'] = md5($this->Params['value']); // CONVERT PASSWORD MD5
				$postUpdate[$this->Params['field']] = $this->Params['value'];

				$stmtElements = [];
				$stmtElements['fieldValues'] = [$this->Params['field']=>$this->Params['value']];
				$stmtElements['schema'] = $dbSchema;
				$stmtElements['table'] = $this->Params['table'];
				$stmtElements['id'] = $this->Params['id'];
				$cntUpdate = $this->method->updateDB($stmtElements);
				if($cntUpdate > 0){
					$this->resultElement['success'] = 1;
					$this->resultElement['message'] = 'Data has been saved!';
				}
				//echo $postUpdate['Cr'];//$this->Params['table'].' | '.$this->Params['field'].' | '.$this->Params['id'].' | '.$this->Params['value'];
			}
		}
		$this->resultElement['id'] = $this->Params['id'];
		//echo $cntUpdate;
		$resultElement = $this->resultElement;
		# START PHP LOGGER
		$log = new projectzero\phplogger\logWriter('logs/log-' . date('d-M-Y') . '.txt');
		$log->info(json_encode($this->Params, JSON_FORCE_OBJECT));
		# END PHP LOGGER
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($resultElement);
		//var_dump($formData);
	}
	
	function memberMasterlist(){
		$this->resultElement = [];
		$this->resultElement['type'] = 'import_membership';
		$this->resultElement['message'] = 'Data not save.';
		include 'requestAPI.php';
		$requestAPI = new Request();
		$apiAddress = Info::getAPIAddress($this->Params['id']);
		$getURL = "http://{$apiAddress}/ecapp_api/request_api.php";
		$sessionBranch = $_SESSION['unit'];
		$getMembersLists = $requestAPI->getAPI($getURL,"type=member_masterlist&branch=".$sessionBranch);
		if($getMembersLists == "ERROR_API_GET"){
			$this->resultElement['success'] = 0;
			$this->resultElement['result'] = "error";
			$this->resultElement['message'] = "RESULT: ".$getMembersLists;
		}else{
			$stmtTempMasterlist = ['schema'=>Info::DB_DATA,'table'=>'temp_masterlist','arguments'=>['unit'=>$this->Params['id']],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>["id"]]; //,'option_name'=>$meta[$optionMeta]
			$getTempMasterlist = $this->method->selectDB($stmtTempMasterlist);
			
			$stmtElements['fieldValues']['value'] = $getMembersLists;
			$stmtElements['fieldValues']['date_updated'] = $this->method->getDate('dateTime');
			$stmtElements['schema'] = Info::DB_DATA;
			$stmtElements['table'] = "temp_masterlist";
			$stmtElements['id'] = $getTempMasterlist[0];
			$cntStmtElements = $this->method->updateDB($stmtElements);
			if($cntStmtElements > 0){
				$this->resultElement['success'] = 1;
				$this->resultElement['result'] = "success";
				$this->resultElement['message'] = 'Members records has been updated!';
			}
		}
		# START PHP LOGGER
		$log = new projectzero\phplogger\logWriter('logs/log-' . date('d-M-Y') . '.txt');
		$log->info(json_encode($this->resultElement, JSON_FORCE_OBJECT));
		# END PHP LOGGER
		
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}

	function updateMeta(){
		if(isset($this->Params['id']) && $this->Params['id'] > 0 && isset($this->Params['table']) && $this->Params['table'] != ''){
			$table = $this->Params['table'];
			$value = $this->Params['value'];
			//$setRecord = new setData(['schema'=>Info::DB_SYSTEMS,'table'=>$table]);
			//$fieldValues = [$value];
			$postUpdate['elements'] = $value['elements'];
			$stmtElements['schema'] = Info::DB_SYSTEMS;
			$stmtElements['fieldValues'] = $postUpdate;
			$stmtElements['table'] = $this->Params['table'];
			$stmtElements['id'] = $this->Params['id'];
			$result = $this->method->updateDB($stmtElements);
		}
		echo $table.' - '.$this->Params['id'].' - '.$result;
	}
	
	function generatePath(){ // CREATE PATH AND GENERATE TABLE AND FIELDS
		//$this->resultElement['params'] = $this->Params;
		$groupTable = $sqlStatment = [];
		$pathGroup = json_decode($this->Params['path_group']);
		foreach($pathGroup as $group){
			$tableColumn = "";
			$getGroup = explode("=",$group);
			$groupName = $getGroup[0];
			$groupFields = explode(",",$getGroup[1]);
			$tableName = "path_{$this->Params['path_id']}_{$groupName}";
			
			foreach($groupFields as $field){
                $tableColumn .= $field.' varchar(32) CHARACTER SET utf8 DEFAULT NULL,';
            }
			
			$sqlStatment[] = "<br>CREATE TABLE IF NOT EXISTS ".$tableName."(
			id int(12) NOT NULL AUTO_INCREMENT,
			date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			data_id int(12) NOT NULL,
            ".$tableColumn."
            primary key (id)
            );<br>"; 
			
			$groupTable[$groupName] = $groupFields;
		}
		$this->resultElement['path_id'] = $this->Params['path_id'];
		$this->resultElement['sql_statement'] = $sqlStatment;
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	
	function createJournalEntry(){ # INSERT UPDATE JOURNAL ENTRIES
		$this->resultElement['params'] = $this->Params;
		$generateJournalEntry = $this->method->generateJournalEntries($this->Params);
		$this->resultElement['record_type'] = $generateJournalEntry['record_type'];
		$this->resultElement['dataID'] = $generateJournalEntry['post_id'];
		$this->resultElement['message'] = 'Journal Entries has been saved!';
		$this->resultElement['data'] = [$dataPostJournalEntry];
		$this->resultElement['success'] = 1;
		$this->resultElement['result'] = "success";
		
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	
	function getAmortizationSchedule(){
		$this->resultElement['success'] = 1;
		$this->resultElement['result'] = "success";
		
		$stmtData['fields'] = [
			'queue.data_id',
			'queue.id',
			'queue.activity_id',
			'queue.unit',
			'loans_details.id loan_id',
			'loans_details.loan_terms',
			'loans_details.loan_types',
			'loans_details.payment_mode',
			'loans_details.loan_interest',
			'loan_summary.loan_granted',
			'loan_summary.amortization_type',
			'loan_summary.first_due_date',
			'loan_summary.dd_type',
			'loan_summary.approve_date',
			'loan_summary.maturity_date'
		];
		$stmtData['table'] = 'data_queue as queue';
		$stmtData['join'] = '
		   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)
		   JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loan_summary AS loan_summary ON (loan_summary.data_id = queue.data_id)
		   ';
		$stmtData['extra'] = 'ORDER BY data_id DESC';
		$stmtData['arguments']["queue.data_id"] = $this->Params["data_id"];
		$stmtData['arguments']["queue.path_id"] = 7;

		$stmtData += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
		$getData = $this->method->selectDB($stmtData);
		$loanData = $getData[$this->Params["data_id"]];
		
		$this->method->Params['unit'] = $loanData->unit;
		$this->method->Params['reference_number'] = $this->Params["data_id"];
		$this->method->Params['approved_date'] = $this->method->getDate('date');
		$this->method->Params['start_date'] = $loanData->first_due_date;
		$this->method->Params['loan_amount'] = $loanData->loan_granted;
		$this->method->Params['terms'] = (int)$loanData->loan_terms;
		$this->method->Params['interest_annum'] = floatVal($loanData->loan_interest);
		$this->method->Params['payment_mode'] = floatVal($loanData->payment_mode);
		$this->method->Params['amortization_type'] = floatVal($loanData->amortization_type); // 1:DEMINISHING, 2:STRAIGHT-LINE, 3:ANNUITY
		$this->method->Params['dd_type'] = floatVal($loanData->dd_type);
		$amortizationDates = $this->method->generateAmortizationSchedule();
		$getLoanScheduleAssoc = $amortizationDates['schedule'];
		$getSummarySchedule = $amortizationDates['summary'];
		
		$loanSchedule = $amortizationDates["schedule_print"];
		$loanSummary = $amortizationDates["summary_print"];
		// $loanSchedule = [[1,"Aug 13 2021","833.33","125.00","958.33","19,166.67"],[2,"Aug 28 2021","833.33","125.00","958.33","18,333.33"]];
		// $loanSummary = ["maturity_date"=>"2022-07-24","total_principal"=>"20,000.00","total_interest"=>"3,000.00","total_amortization"=>"23,000.00","date_approved"=>"2021-07-29","reference_number"=>"02-00000295"];
		$this->resultElement['data_loan_schedule'] = json_encode($loanSchedule);
		$this->resultElement['data_loan_schedule_summary'] = json_encode($loanSummary);
		$this->resultElement['output'] = $amortizationDates["summary"];
		header("Content-Type: text/json; charset=utf8");
		echo json_encode($this->resultElement);
	}
	

}


/*
GETTING API FOLDERS BY USER DETAILS
if($this->Params['post_id'] > 0){ // $_SESSION["userrole"] <= 3 // FOR ADMIN / OPERATIONS MANAGER /  AREA MANAGER 
	$getPostValue = $this->method->getValueDB(['table'=>'data_queue','id'=>$this->Params['post_id'],'schema'=>Info::DB_DATA]);
	$getPostArea = $_SESSION["unit_area"][$getPostValue['unit']]->meta_parent;
	$postArea = $_SESSION["codebook"]["division"][$getPostArea]->meta_option;
	$postUnit = $_SESSION["codebook"]["unit"][$getPostValue['unit']]->meta_option;
	$apiFolder = $postArea.'/'.$postUnit;
}
*/

/*
$apiDeduction = "";
GETTING API DEDUCTIONS
if(false && isset($this->Params["loan_summary_other_charges"]) && $this->Params["loan_summary_other_charges"] != ""){
	$getDeductionValue = $this->Params["loan_summary_other_charges"];
	$apiDeduction = json_decode($getDeductionValue,true);
	$apiDeduction = $apiDeduction[0];
}

$arrayFields['api_deduction'] = $apiDeduction;
*/