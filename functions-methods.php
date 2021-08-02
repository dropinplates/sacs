<?php
date_default_timezone_set("Asia/Manila");
include 'InfoClass.php';
$thePageClass = str_replace(".php", "", basename($_SERVER['PHP_SELF']));
define('PAGE_TYPE', $thePageClass);
define('HOST_SELF_URL', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);

class Method {

	const DEDUCTION_ROW = 6;
	const EMPTY_VAL = "&ctdot;";
	
	public $Params;
	public $attr;
	public $schema;
	public $dbConnection;
	public $date;
	public $db;
	public $info;
	public $dateRange;
	public $listValue;
	public $editable;
	public $readonly;
	public $currentDate;
	public $recentDate; // IF NO END OF DAY YET
	public $recentRecord;
	public $hasCurrentRecord;
	public $tblCol;
	public $popupFormID;
	public $paramsData;
    public $modalBox;
	public $getElementType; // TO FORMAT ELEMENT TYPE RESULT
	public $authError;

	public $pageAction; // PMS e.g. formStaff EDIT/VIEW
	public $isApprove; // PMS e.g. formStaff EDIT/VIEW
	public $isApproveID;
	//public $postID;
	//public $postPathID;
	//public $postActivityID;
	public $postActivityToken;
	//public $postMemberID;
	//public $postDataUnit;
	public $externalLoanID;
	public $viewRestricted;
	public $inputValueAttr; // JQUERY INPUT VALUE ATTR e.g. $('#inputid').val();
	public $test;
	public $parseType; // FOR JQUERY INSERT/UPDATE ON CREATE RECORDS
	
	public $globalQueueData;
	public $globalDataValue;
	
	public $hasActivityBtn;

	public $dateFrom;
	public $dateTo;

	function __construct($Params) {
		$this->Params = $Params;
		$this->schema = Info::DB_SYSTEMS;
		$this->db = Info::DBConnection();
		$this->authError = false;
		//$this->db = dbConnect($this->schema);//$this->Params['db'];
        //$this->Params['db'] = $this->db;
		$this->inputValueAttr = "val";
		
		//self::setReportDate();
		$this->editable = $this->readonly = $this->getElementType = "";
		$this->modalBox = (isset($this->Params['methods']) && $this->Params['methods'] != "") ? $this->Params['methods'].'_'.$this->Params['view'] : "";

		$this->dateFrom = $this::getTime('date')." 00:00:00";
		$this->dateTo = $this::getTime('date')." 23:59:59";
		$this->hasActivityBtn = false;
		
		$this->globalQueueData = new stdClass();
		$this->globalQueueData->id = 0;
		
	}
	
	public function getError() {
		return error_get_last();
    }
	
	public function Systems($meta) {
		//$optionMeta = key($meta);
		$stmtMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>'options','arguments'=>['option_meta'=>$meta],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['option_name','option_value']]; //,'option_name'=>$meta[$optionMeta]
		$getStmtMeta = $this->selectDB($stmtMeta);
		$output = $getStmtMeta;
		return $output;
    }
	
	public function methodSettings($values){
		$output = [];
		$metaKeyNotUnit = ["module_policy","module_cash_policy"]; // META_KEY IS NO SESSION UNIT
		if(in_array($values['meta_key'],$metaKeyNotUnit)){ 
			$stmtMethodSettings['arguments'] = ['meta_key'=>$values['meta_key']];
		}else{
			$stmtMethodSettings['arguments'] = ['meta_key'=>$values['meta_key'],'meta_id'=>$_SESSION['unit']];
		}
		$stmtMethodSettings += ['schema'=>Info::DB_SYSTEMS,'table'=>'methods','pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['name','value']];
		if(isset($values['name'])) $stmtMethodSettings['arguments']['name'] = $values['name'];
		$getMethodSettings = $this->selectDB($stmtMethodSettings);
		if($getMethodSettings){
			if(isset($values['name'])){ // DISTINCT VALUE
				$getValue = json_decode($getMethodSettings[$values['name']], true);
				$output = $getValue[0];
			}else{
				$output = $getMethodSettings;
			}
		}
		return $output;
	}
	
	public function getMemberDetail($values,$type) { // VALUES = unit, id(can be array too)
		//$optionMeta = key($meta);
		$output = "";
		$cnt = 1;
		switch($type){
			default:
				$memberDetail = [];
				$stmtMemberMasterlist = ['schema'=>Info::DB_DATA,'table'=>'temp_masterlist','arguments'=>['meta'=>'member_masterlist','id'=>$values['unit']],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['unit','id','value']];
				$getMembersLists = $this->selectDB($stmtMemberMasterlist);
				if(!empty($getMembersLists)){
					$membersLists = json_decode($getMembersLists[$values['unit']]->value, true);
					$getMemID = explode(",",$values['id']);
					foreach($getMemID as $memID){
						$getSearch = $this->array_recursive_search_key_map($memID, $membersLists);
						$memberID = $this->formatValue(['prefix'=>$values['unit'],'id'=>$memID],"id");
						if(!empty($getSearch)){
							if($type == "name-id"){
								$memberDetail[$cnt] = $membersLists[$getSearch[0]]['member_name']." • ".$memberID;
							}else{
								$memberDetail[$cnt] = $membersLists[$getSearch[0]]['member_name'];
							}
							$cnt++;
						}
					}
				}
				
				$output = $memberDetail;
			break;
		}
		
		return $output;
    }
	
	public function formatValue($values,$type) { // VALUES = unit,id
		$output = "";
		$numLength = Info::staticValue('num_format')->$type;
		$values['prefix'] = ($values['prefix'] == 99) ? 0 : $values['prefix']; // FORCE BRANCH CODE FROM 99 TO 00 // HEAD OFFICE
		switch($type){
			case "savings_id": // APP DATA ID
				$output = str_pad($values['prefix'], 2, '0', STR_PAD_LEFT)."-".str_pad($values['id'], $numLength, '0', STR_PAD_LEFT)."-".$values['savings_type'];
			break;
			case "app_id": // APP DATA ID
				$output = str_pad($values['prefix'], 2, '0', STR_PAD_LEFT)."-".str_pad($values['id'], $numLength, '0', STR_PAD_LEFT);
			break;
			case "ref_id": // APP DATA ID
				$output = str_pad($values['prefix'], 2, '0', STR_PAD_LEFT)."-".str_pad($values['id'], $numLength, '0', STR_PAD_LEFT);
			break;
			case "client_id": // id, reference_id
				$output = str_pad($values['id'], $numLength, '0', STR_PAD_LEFT)."-".str_pad($values['prefix'], 1, '0', STR_PAD_LEFT);
			break;
			default: // id, reference_id
				$output = str_pad($values['prefix'], 3, '0', STR_PAD_LEFT)."-".str_pad($values['id'], $numLength, '0', STR_PAD_LEFT);
			break;
		}
		
		return $output;
    }
	
	private function customPageAction($inputAlias){ // SETTING OR CUSTOMIZE FIELDS TO FORCE EDITABLE OR VIEWABLE
		switch($this->Params['pageName']){
			case "posts-loan_application":
				$loanApprovalActivities = [2,3,4];//explode(",",Info::APPROVE_ACTIVITIES);
				$viewFields = ["loan_granted","other_information"]; // FORCING TO EDITABLE
				if(in_array($inputAlias,$viewFields) && in_array($this->globalQueueData->activity_id, $loanApprovalActivities)){
					$this->pageAction = "";
				}elseif(in_array($this->globalQueueData->activity_id, $loanApprovalActivities)){
					$this->pageAction = "view";
				}
			break;
		}
	}
	
	public function getDueDates(){ // GETTING THE DUE_DATES FOR AMORTIZATION
		$startDate = new DateTime($this->Params['start_date']);
		$monthDay = $startDate->format('d');
		$periodDueDate = new DateTime($this->Params['due_date']);
		switch($this->Params['payment_mode']){
			case 1: // DAILY
				if($this->Params['num'] > 1){
					if($this->Params['amortization_type'] == 2){ // STRAIGHT-LINE
						$periodDueDate->modify('+'.$this->Params['number_days'].' day');
						$datePeriod = $periodDueDate->format('Y-m-d');
						$datePeriodTimeStamp = strtotime($datePeriod);
						$getPeriodDay = date('D', $datePeriodTimeStamp);
						if($getPeriodDay === "Sun") $periodDueDate->modify('+1 day');
					}else{
						$periodDueDate->modify('+'.$this->Params['number_days'].' day');
					}
				}
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
			case 2: // WEEKLY
				if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['number_days'].' day');
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
			case 3: // SEMI-MONTHLY
				switch($this->Params['dd_type']){
					case 3: // 15th and End of the Month
						if($monthDay <= 15){
							$dueDateDay = [1 => 15, 2 => 'last_day_month'];
						}else{
							$dueDateDay = [1 => 'last_day_month', 2 => 15];
						}
						$dueDatePeriod = $dueDateDay[$this->Params['count_reset']];
						if(is_int($dueDatePeriod)){
							if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['number_days'].' day');
							$datePeriod = $periodDueDate->format('Y-m-'.$dueDatePeriod);
						}else{
							$monthName = $periodDueDate->format('F');
							$periodDueDate->modify('last day of '.$monthName);
							$datePeriod = $periodDueDate->format('Y-m-d');
						}
					break;
					case 5: // 5th and 20th of the Month
						if($monthDay <= 5){
							$dueDateDay = [1 => '05', 2 => 20];
						}else{
							$dueDateDay = [1 => 20, 2 => '05'];
						}
						$dueDatePeriod = $dueDateDay[$this->Params['count_reset']];
						if($dueDatePeriod == '05' && $this->Params['num'] > 1) $periodDueDate->modify('+1 month');
						$datePeriod = $periodDueDate->format('Y-m-'.$dueDatePeriod);
					break;
					case 6: // 10th and 25th of the Month
						if($monthDay <= 10){
							$dueDateDay = [1 => 10, 2 => 25];
						}else{
							$dueDateDay = [1 => 25, 2 => 10];
						}
						$dueDatePeriod = $dueDateDay[$this->Params['count_reset']];
						if($dueDatePeriod == 10 && $this->Params['num'] > 1) $periodDueDate->modify('+1 month');
						$datePeriod = $periodDueDate->format('Y-m-'.$dueDatePeriod);
					break;
					case 7: // 15th and 30th of the Month
						if($monthDay <= 15){
							$dueDateDay = [1 => 15, 2 => 30];
						}else{
							$dueDateDay = [1 => 30, 2 => 15];
						}
						$dueDatePeriod = $dueDateDay[$this->Params['count_reset']];
						if($dueDatePeriod == 15 && $this->Params['num'] > 1) $periodDueDate->modify('+28 days');
						
						$monthName = $periodDueDate->format('F');
						if($monthName == "February"){
							if($dueDatePeriod == 15){
								$datePeriod = $periodDueDate->format('Y-m-'.$dueDatePeriod);
							}else{
								$periodDueDate->modify('last day of '.$monthName);
								$datePeriod = $periodDueDate->format('Y-m-d');
							}
						}else{
							$datePeriod = $periodDueDate->format('Y-m-'.$dueDatePeriod);
						}
					break;
					default:
						if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['number_days'].' day');
						$datePeriod = $periodDueDate->format('Y-m-d');
					break;
				}
			break;
			case 4: // MONTHLY
				switch($this->Params['dd_type']){
					case 2: // END OF THE MONTH
						if($this->Params['num'] > 1){
							$periodDueDate->modify('+28 days');
							$periodDueDate->modify('last day of this month');
						}else{ // ALLOWANCE OF 15 DAYS
							$periodDueDate->modify('-1 month');
							$periodDueDate->modify('+15 days');
							$periodDueDate->modify('last day of this month');
						}
						$datePeriod = $periodDueDate->format('Y-m-d');
					break;
					case 4: // SAME DAY OF EACH MONTH
						if($this->Params['num'] > 1) $periodDueDate->modify('next month');
						
						if(isset($this->Params['isFebruary']) && $this->Params['isFebruary']){
							$startDate = new DateTime($this->Params['due_date']);
							$periodDueDate->modify('-1 month');
							$periodDueDate->modify('last day of this month');
							$datePeriod = $periodDueDate->format('Y-m-d');
							unset($this->Params['isFebruary']);
							$this->Params['isFebruary'] = false;
						}else{
							$startDate = new DateTime($this->Params['start_date']);
							$monthDay = $startDate->format('d');
							$periodDueDate->format($monthDay);
							$datePeriod = $periodDueDate->format('Y-m-'.$monthDay);
						}
						$monthName = $periodDueDate->format('F');
						$isLeapYear = $periodDueDate->format('L'); // CHECK IF LEAPYEAR
						$leapDays = ($isLeapYear > 0) ? 29 : 28;
						if($monthName == "January" && $monthDay > $leapDays){ // TO SET ON FEBRUARY
							$this->Params['isFebruary'] = true;
						}
					break;
					default:
						if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['number_days'].' day');
						$datePeriod = $periodDueDate->format('Y-m-d');
					break;
				}
				
			break;
			case 5: // QUARTERLY
				if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['monthly_terms'].' month');
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
			case 6: // SEMESTRAL
				if($this->Params['num'] > 1) $periodDueDate->modify('+'.$this->Params['monthly_terms'].' month');
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
			case 7: // YEARLY
				if($this->Params['num'] > 1) $periodDueDate->modify('+1 year');
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
			case 9: // LUMPSUM
				if($this->Params['num'] > 1) $periodDueDate->modify('next month');
				$datePeriod = $periodDueDate->format('Y-m-d');
			break;
		}
		
		return $datePeriod;
	}
	
	public function generateAmortizationDates(){
		$result = [];
		$paymentDetails = Info::paymentsTerms(["mode"=>$this->Params['payment_mode'],"terms"=>$this->Params['terms']]);
		$this->Params['number_days'] = $paymentDetails['number_days']; // NUMBER OF DAYS IN A MONTH
		$this->Params['monthly_terms'] = $paymentDetails['monthly_terms']; // NUMBER OF MONTHS PER PERIOD
		$this->Params['number_payment'] = $paymentDetails['number_payment']; // NUMBER OF MONTHS PER PERIOD
		//var_dump($paymentDetails);
		// if($this->Params['payment_mode'] != 2){ // NOT WEEKLY, TO RESET THE START_DATE		!isset($this->Params['start_date']) IF NO FIRST PRINCIPAL/INTEREST DATE
			// $periodStartDate = new DateTime($this->Params['approved_date']);
			// if($this->Params['payment_mode'] == 4){ // MONTHLY
				// $periodStartDate->modify('+1 month');
			// }else{
				// $periodStartDate->modify('+'.$paymentDetails['number_days'].' days');
			// }
			// $this->Params['start_date'] = $periodStartDate->format('Y-m-d');
		// }
		$this->Params['due_date'] = $this->Params['start_date'];
		$numPayments = $paymentDetails['number_payment'];
		$cntReset = 0;
		$xStart = 1;
		$isLumpsumStraight = false;
		if($this->Params['payment_mode'] == 9 && $this->Params['amortization_type'] == 2){ // CONVERT AMORTIZATION DATES INTO 1; LUMPSUM AND STRAIGH-LINE
			$numPayments = 1;
			$isLumpsumStraight = true;
			//$xStart = $this->Params['number_payment'];
		} 

		for($x=$xStart; $x<=$numPayments; $x++){
			$this->Params['num'] = $x;
			if($cntReset < $paymentDetails['count_reset']){
				$cntReset++;
			}else{
				$cntReset = 1;
			}
			$this->Params['count_reset'] = $cntReset;
			if($isLumpsumStraight){
				if(isset($this->Params['maturity_date']) && $this->Params['maturity_date'] != ""){ // IF HAS SET MATURITY DATE, ALREADY HAS DATA ON KOOPCAS
					$dueDate = $this->Params['maturity_date'];
				}else{
					if($this->Params['has_start_date']){
						$dueDate = $this->Params['start_date'];
					}else{
						$periodLumpsumStraight = new DateTime($this->Params['approved_date']);
						$periodLumpsumStraight->modify('+'.$this->Params['terms'].' months');
						$dueDate = $periodLumpsumStraight->format('Y-m-d');
					}
				}
			}else{ // NORMAL CREATE DUE-DATES
				$dueDate = $this->getDueDates();
			}
			$dateTimeStamp = strtotime($dueDate);
			$dayName = date('l', $dateTimeStamp);
			$result[$x] = [
				"due_date"=>$dueDate,
				"day_name"=>$dayName,
				"count_reset"=>$cntReset
			];
			$this->Params['due_date'] = $dueDate;
		} // FOR LOOP
		
		return $result;
	}
	
	public function generateAmortizationSchedule(){ // NEW GENERATE AMORTIZATION SCHEDULE
		$this->Params['has_start_date'] = false;
		if($this->Params['start_date'] != "") $this->Params['has_start_date'] = true;
		//if($this->Params['payment_mode'] != 2 && !$this->Params['start_date']) unset($this->Params['start_date']); // ONLY WEEKLY HAS START_DATE
		$loanSchedule = $loanScheduleSummary = $output = $loanSummary = $scheduleDetails = $totalInterest = [];
		$groupPaymentMode = [5,6,7]; // QUARTERLY, SEMESTRAL, YEARLY
		
		$amortizationDates = $this->generateAmortizationDates();
		
		$loanAmount = floatVal($this->Params['loan_amount']);
		$loanTerms = floatVal($this->Params['terms']);
		$loanInterestAnnum = floatVal($this->Params['interest_annum']);
		$loanInterestMonth = $loanInterestAnnum / 12;
		$loanInterestRate = $loanInterestMonth / 100;
		$amortizationType = $this->Params['amortization_type']; // 1:DEMINISHING, 2:STRAIGHT-LINE, 3:ANNUITY
		
		$monthlyPrincipalDue = $loanAmount / $loanTerms;
		$monthlyInterest = $loanAmount * $loanInterestRate;
		
		$paymentPrincipalDue = $loanAmount / $this->Params['number_payment'];
		$paymentInterestDue = $loanAmount * $loanInterestRate;
		
		switch($amortizationType){
			case "3": // ANNUITY
				if($loanInterestAnnum > 0){
					$lastPaymentCount = $this->Params['number_payment'] - 1;
					$termsInYear = floatVal($this->Params['terms']) / 12;
					$paymentAmountMonthly = $this->calPMT($loanInterestAnnum, $termsInYear, $loanAmount);
					$paymentAnnuityDue = $paymentAmountMonthly * $this->Params['monthly_terms'];
				}
				
			break;
			case "1": // DEMINISHING
				if($this->Params['payment_mode'] == 1){
					$periodApproveDate = new DateTime($this->Params["start_date"]);
					$periodApproveDate->modify('-1 day');
					$approveDate = $periodApproveDate->format('Y-m-d');
					
					$periodDueDate = new DateTime($approveDate);
					$periodDueDate->modify('+'.$this->Params['number_payment'].' day');
					$maturityDate = $periodDueDate->format('Y-m-d');
					$dates = $this->dateRange($approveDate, $maturityDate);
					$sundays = array_filter($dates, function ($date) {
					  return $date->format("N") === '7';
					});
					$countSundays = count($sundays);
					$daysYear = $this->Params['number_payment'] - $countSundays;
					$paymentPrincipalDue = $loanAmount / $daysYear;
				}
			break;
		}
		
		$paymentInterestDueLast = 0;
		$principalBalance = $loanAmount;
		$cnt = 0;
		$lastPaymentDate = $this->Params['approved_date'];
		foreach($amortizationDates as $num => $details){
			//$details['count_reset']; COUNT PAYMENTS ON MONTHS
			$isDisplay = true;
			switch($amortizationType){
				case "1": // DEMINISHING
					// if($this->Params['payment_mode'] == 1){ // DAILY
						// $interestDue = $principalBalance * ($loanInterestRate * $this->Params['monthly_terms']);
					// }else{
						// //$interestDue = $principalBalance * $loanInterestRate;
						// $daysDelay = $this->computeTime($lastPaymentDate,$details['due_date'],'days2');
						// $daysDelay = ($daysDelay < 0) ? 1 : (int)$daysDelay;
						// $interestDue = (($principalBalance * $loanInterestRate) / 30) * $daysDelay;
					// }
					// $paymentInterestDue = $interestDue + $paymentInterestDueLast;
					// $paymentDue = $paymentPrincipalDue + $paymentInterestDue;
					
					// if($details['day_name'] == "Sunday" && $this->Params['payment_mode'] == 1){ // DAILY
						// $isDisplay = false;
						// $paymentInterestDueLast = $paymentInterestDue;
					// }else{
						// $paymentInterestDueLast = 0;
					// }
					if($details['count_reset'] == 1) $paymentInterestDue = $principalBalance * ($loanInterestRate * $this->Params['monthly_terms']);
				break;
				case "2": // STRAIGHT-LINE
					$paymentInterestDue = $loanAmount * ($loanInterestRate * $this->Params['monthly_terms']);
					$paymentDue = $paymentPrincipalDue + $paymentInterestDue;
				break;
				case "3": // ANNUITY
					if($loanInterestAnnum > 0){ // HAS INTEREST
						if(in_array($this->Params['payment_mode'], $groupPaymentMode)){ // QUARTERLY, SEMESTRAL, YEARLY
							$paymentInterestDue = $principalBalance * ($loanInterestRate * $this->Params['monthly_terms']);
							$paymentPrincipalDue = $paymentAnnuityDue - $paymentInterestDue;
							if($cnt == $lastPaymentCount){ // SET THE LAST PAYMENT
								$lastPrincipalBalance = $principalBalance - $paymentPrincipalDue;
								$paymentPrincipalDue = $paymentPrincipalDue + $lastPrincipalBalance;
							}
						}else{ // DEFAULT
							if($details['count_reset'] == 1){ // RESET PERIOD PAYMENT IN MONTHLY BASIS
								$paymentInterestDue = $principalBalance * ($loanInterestRate * $this->Params['monthly_terms']);
								$paymentDue = $paymentAnnuityDue;
								$paymentPrincipalDue = $paymentDue - $paymentInterestDue;
								
								$paymentInterestDueLast = $paymentInterestDue;
								$paymentPrincipalDueLast = $paymentPrincipalDue;
							}else{
								$paymentInterestDue = $paymentInterestDueLast;
								$paymentPrincipalDue = $paymentPrincipalDueLast;
							}
						}
					}
				break;
			} // END SWITCH
			
			if($isDisplay){
				$cnt++;
				if($amortizationType == 1 && $this->Params['payment_mode'] == 9){ // DEMINISHING AND LUMPSUM
					if($cnt < $this->Params['number_payment']){
						$paymentPrincipalDue = 0;
					}else{
						$paymentPrincipalDue = $loanAmount;
					}
				}
				
				if($amortizationType == 2 && $this->Params['payment_mode'] == 9){ // STRAIGHT-LINE AND LUMPSUM
					$paymentPrincipalDue = $loanAmount;
					$paymentInterestDue = ($loanAmount * $loanInterestRate) * $this->Params['number_payment'];
				}
				
				$totalDue = $paymentPrincipalDue + $paymentInterestDue;
				$principalBalance = $principalBalance - $paymentPrincipalDue;
				$loanSchedule[$cnt] = [
					'due_date'=>$details['due_date'],
					'day_name'=>$details['day_name'],
					'principal_due'=>$paymentPrincipalDue,
					'interest_due'=>$paymentInterestDue,
					'payment_due'=>$paymentDue,
					'total_due'=>$totalDue, // WITH SAVINGS AND CBU
					'loan_balance'=>$principalBalance
				];
				
				$totalInterest[] = $paymentInterestDue;
				
				$scheduleDetails[] = [$cnt, $details['due_date'], number_format($paymentPrincipalDue, 2), number_format($paymentInterestDue, 2), number_format($totalDue, 2), number_format($principalBalance, 2)];
				
				$loanScheduleSummary['principal_due'][] = $paymentPrincipalDue;
				$loanScheduleSummary['interest_due'][] = $paymentInterestDue;
				$loanScheduleSummary['payment_due'][] = $paymentDue;
				$loanScheduleSummary['total_due'][] = $totalDue;
				$lastPaymentDate = $details['due_date'];
			} // END IF
		} // END FOREACH
		
		$loanSummary['principal_due'] = array_sum($loanScheduleSummary['principal_due']);
		$loanSummary['interest_due'] = array_sum($loanScheduleSummary['interest_due']);
		$loanSummary['payment_due'] = array_sum($loanScheduleSummary['payment_due']);
		$loanSummary['total_due'] = array_sum($loanScheduleSummary['total_due']);
		
		$totalInterestAmount = array_sum($totalInterest);
		$totalAmortization = $loanAmount + $totalInterestAmount;
		$referenceNumber = $this->formatValue(['prefix'=>$this->Params['unit'],'id'=>$this->Params['reference_number']],"ref_id");
		
		$output['schedule_print'] = $scheduleDetails;
		$output['summary_print'] = ["maturity_date"=>$details['due_date'],"total_principal"=>number_format($loanAmount, 2),"total_interest"=>number_format($totalInterestAmount, 2),"total_amortization"=>number_format($totalAmortization, 2),"date_approved"=>$this->Params['approved_date'],"reference_number"=>$referenceNumber];;
		$output['schedule'] = $loanSchedule;
		$output['summary'] = $loanSummary;
		
		return $output;
	}
	
	public function dueDateFormat($date,$mode,$counter) {
		$periodDueDate = new DateTime($date);
		$paymentDay = $periodDueDate->format('d'); // DEFAULT MONTHLY
		//$periodDueDate->modify('next month');
		switch($mode){
			case 1:
				$nextDay = 1;
				$periodDueDate->modify('+'.$counter.' day');
				$paymentDay = $periodDueDate->format('d');
			break;
			case 2: // WEEKLY
				$paymentDayName = $this->paymentDateDayName;
				// switch($counter){
					// case 1: $periodDueDate->modify('first '.$paymentDayName.' of this month'); break;
					// case 2: $periodDueDate->modify('second '.$paymentDayName.' of this month'); break;
					// case 3: $periodDueDate->modify('third '.$paymentDayName.' of this month'); break;
					// case 4: $periodDueDate->modify('fourth '.$paymentDayName.' of this month'); break;
				// }
				$periodDueDate->modify('next '.$paymentDayName);
				$paymentDay = $periodDueDate->format('d');
			break;
			case 3: // SEMI-MONTHLY
				$nextDay = 15;
				// if($counter > 1){
					// $periodDueDate->modify('last day of this month');
					// $paymentDay = $periodDueDate->format('d');
				// }
				$periodDueDate->modify('+'.$nextDay.' day');
				$paymentDay = $periodDueDate->format('d');
			break;
			case 7: // YEARLY
				$periodDueDate->modify('+'.$counter.' year');
				$paymentDay = $periodDueDate->format('d');
			break;
			case 5: case 6: // QUARTERLY
				if(!$this->isPaymentStart){
					$paymentCount = $this->paymentCount;
					$periodDueDate->modify('+'.$paymentCount.' month');
				}
			break;
			default: // MONTHLY
				if($counter > 1){
					$paymentCount = $this->paymentCount;
					$periodDueDate->modify('+'.$paymentCount.' month');
				}
				$paymentDay = $periodDueDate->format('d');
			break;
		}
		$excludeDays = ["Sat","Sun"];
		$datePeriod = $periodDueDate->format('Y-m-'.$paymentDay);
		$datePeriodTimeStamp = strtotime($datePeriod);
		$getPeriodDay = date('D', $datePeriodTimeStamp);
		if(false && in_array($getPeriodDay, $excludeDays)){ // EXCLUDING SATURDAY AND SUNDAYS
			($getPeriodDay === "Sat") ? $periodDueDate->modify('+2 day') : $periodDueDate->modify('+1 day');
			$paymentDay = $periodDueDate->format('d');
			$datePeriod = $periodDueDate->format('Y-m-'.$paymentDay);
			$datePeriodTimeStamp = strtotime($datePeriod);
			$getPeriodDay = date('D', $datePeriodTimeStamp);
		}
		//$this->test = $getPeriodDay;
		$output = $datePeriod;
		return $output;
    }
	
	public function generateSchedule($loanDetails){ // OLD GENERATE SCHEDULE
	    ob_start();
		//date_approved,loan_granted,payment_terms,payment_mode,loan_interest
		$output = []; $tdLoanSchedule = $getDate = $getPeriodDueDate = "";
		$totalPrincipalAmount = $totalInterestAmount = $totalDueAmount = 0;
		$paymentLoanSchedule = $paymentLoanScheduleAssoc = [];
		$dataPaymentTerms = (int)$loanDetails['payment_terms'];
		$dataPaymentMode = (int)$loanDetails['payment_mode'];
		$dataPrincipalAmount = $loanDetails['loan_granted'];
		$dataLoanInterest = floatval($loanDetails['loan_interest']) / 100;
		$dataInterestMethod = $loanDetails['interest_method']; // AMORTIZATION_TYPE
		
		$dataClientID = $this->globalDataValue['loans_details']['mem_id'];
		$dataExternalLoanID = (isset($this->externalLoanID) && $this->externalLoanID) ? $this->externalLoanID : "";
		$dataLoanType = $loanDetails['loan_type'];

		$loanPaymentDetails = Info::paymentsTerms(["mode"=>$dataPaymentMode,"terms"=>$dataPaymentTerms]);
		$paymentCount = $loanPaymentDetails['count_payment'];
		$paymentCountReset = $loanPaymentDetails['count_reset'];
		$numPayments = $loanPaymentDetails['number_payment'];
		$monthlyTerms = $loanPaymentDetails['monthly_terms'];
		
		//$customPeriodInterestAmount = floatval($loanDetails['interest_amount']) / $numPayments; // CUSTOM ONLY FOR FIXED INTEREST AMOUNT
		
		$dataLoanApproveDate = self::timeDateFormat($loanDetails["date_approved"],'dateField');

		$totalBalanceAmount = floatval($dataPrincipalAmount);// * floatval($getScheduleLoanTerms);
		$principalInterestAmount = $totalBalanceAmount * $dataLoanInterest;
		$monthlyTermAmount = $totalBalanceAmount / $dataPaymentTerms;
		//if($loanDetails['interest_type'] == 1){ // POST-PAID INTEREST
		//	$periodInterestAmount = 0;
		//}else{
			$periodInterestAmount = $principalInterestAmount * $monthlyTerms;
		//}
		
		$periodTermAmount = $monthlyTermAmount * $monthlyTerms;
		$paymentAmount = $periodTermAmount + $periodInterestAmount;
		$totalPeriodAmount = $periodTermAmount + $periodInterestAmount;
		if($dataInterestMethod < 3){ // DIMMINISHING AND STRAIGHT-LINE
			$totalAmortization = $totalBalanceAmount + $principalInterestAmount;
		}else{ // ANNUITY
			$totalAmortization = ($totalBalanceAmount / $dataPaymentTerms) * ((pow(1 + $dataLoanInterest, $dataPaymentTerms) - 1) / $dataLoanInterest); // GETTING FUTURE VALUE
			$periodInterestAmount = (($totalAmortization - $totalBalanceAmount) / $numPayments) / 12; //($totalAmortization - $totalBalanceAmount) / $numPayments;
		}
		
		$counter = 0;
		for($x=1;$x<=$numPayments;$x++){
			$test = "amatz";
			$this->isPaymentStart = false;
			$this->paymentCount = $paymentCount; // GETTING PAYMENT COUNT
			if(($counter < $paymentCountReset) || $dataPaymentMode == 1){
				$counter++;
			}else{
				$counter = 1;
			}
			if($x <= $counter && $counter <= $paymentCountReset){
				if($counter == 1) $this->isPaymentStart = true;
				if(($dataPaymentMode == 5 || $dataPaymentMode == 6) && !$this->isPaymentStart){ // EXCLUDE RESETTING OF DUE DATE (QUARTERLY)
					$test = "brown";
					// $periodDueDate = new DateTime(self::timeDateFormat($dataLoanApproveDate,'dateField'));
					// $periodDueDate->modify('+ '.$monthlyTerms.' month');
					// $getDate = $periodDueDate->format('Y-m-d');
				}elseif($dataPaymentMode == 2){
					$periodDueDate = ($counter == 1) ? new DateTime(self::timeDateFormat($dataLoanApproveDate,'dateField')) : new DateTime($getDate);
					$this->paymentDateDayName = $periodDueDate->format('l'); // GETTING DAY NAME
					$getDate = $periodDueDate->format('Y-m-d');
				}else{
					$test = "green";
					$periodDueDate = ($counter == 1) ? new DateTime(self::timeDateFormat($dataLoanApproveDate,'dateField')) : new DateTime($getDate);
					$this->paymentDateDayName = $periodDueDate->format('l'); // GETTING DAY NAME
					switch($dataPaymentMode){
						case 1: // DAILY
							//$periodDueDate->modify('+1 day');
						break;
						case 3: case 7: // SEMI-MONTHLY, YEARLY
							
						break;
						default:
							$periodDueDate->modify('+ '.$paymentCount.' month');
						break;
					}
					$getDate = $periodDueDate->format('Y-m-d');
				}
			}elseif($counter == 1 && $dataPaymentMode > 1){ // EXCLUDE RESETTING OF MONTHS (DAILY)
				$test = "yeah";
				if(($dataPaymentMode == 2 || $dataPaymentMode == 3 || $dataPaymentMode == 5 || $dataPaymentMode == 6 || $dataPaymentMode == 7) && !$this->isPaymentStart){ // EXCLUDE RESETTING OF DUE DATE (QUARTERLY, SEMESTRAL, WEEKLY)
					//$getDate = $getPeriodDueDate;
				}else{
					$periodDueDate = new DateTime($loanDetails["date_approved"]);
					if(!$getDate) $getDate = (string)$this->getDate('date');//"2020-06-16";
					//$periodDueDate = new DateTime($getDate);
					$periodDueDate->modify('next month');
					$getDate = $periodDueDate->format('Y-m-d');
				}
			}
			$getPeriodDueDate = self::dueDateFormat($getDate,$dataPaymentMode,$counter);
			if($dataPaymentMode == 2 || $dataPaymentMode == 3 || $dataPaymentMode == 5 || $dataPaymentMode == 6 || $dataPaymentMode == 7){ // QUARTERLY, SEMESTRAL, WEEKLY
				$getDate = $getPeriodDueDate;
			}
			
			if($dataInterestMethod == 1 && $counter == 1){ // AMORTIZATION TYPE DIMENISHING
				$periodInterestAmount = $totalBalanceAmount * ($dataLoanInterest * $monthlyTerms);
				$paymentAmount = $periodTermAmount + $periodInterestAmount;
				$totalPeriodAmount = $periodTermAmount + $periodInterestAmount;
				$totalBalanceAmount = $totalBalanceAmount - $periodTermAmount;
			}elseif($dataInterestMethod == 3){ // ANNUITY
				if($dataPaymentMode == 9) $periodTermAmount = ($x < $numPayments) ? 0 : floatval($dataPrincipalAmount); // IF LUMPSUM
				$periodInterestAmount = $totalBalanceAmount * ($dataLoanInterest * $monthlyTerms);
				$paymentAmount = $periodTermAmount + $periodInterestAmount;
				$totalPeriodAmount = $periodTermAmount + $periodInterestAmount;
				$totalBalanceAmount = $totalBalanceAmount - $periodTermAmount;
				/* THIS IS THE DEFAULT/GENERIC FORMULA FOR ANNUITY
				if($dataPaymentMode == 9) $periodTermAmount = ($x < $numPayments) ? 0 : floatval($dataPrincipalAmount); // IF LUMPSUM
				$totalBalanceAmount = $totalBalanceAmount - $periodTermAmount;
				$paymentAmount = $periodTermAmount + $periodInterestAmount;
				$totalPeriodAmount = $periodTermAmount + $periodInterestAmount + $getOtherPaymentAmount;
				*/
			}else{ // STRAIGHT-LINE 
				$totalBalanceAmount = $totalBalanceAmount - $periodTermAmount;
			}
			
			if($totalBalanceAmount < 0){
				$totalBalanceAmount = 0.00;
			}
			
			//$periodInterestAmount = $customPeriodInterestAmount; // CUSTOM FIXED INTEREST AMOUNT
			$totalPrincipalAmount = $totalPrincipalAmount + $periodTermAmount;
			$totalInterestAmount = $totalInterestAmount + $periodInterestAmount;
			$totalDueAmount = $totalDueAmount + $totalPeriodAmount;
			//$paymentLoanSchedule[] = [$x,date_format(date_create($getPeriodDueDate), 'd M Y'),number_format($periodTermAmount, 2),number_format($periodInterestAmount, 2),number_format($getLoanCBUAmount, 2),number_format($getLoanSavingsAmount, 2),number_format($totalPeriodAmount, 2),number_format($totalBalanceAmount, 2)];
			$paymentLoanSchedule[] = [$x,date_format(date_create($getPeriodDueDate), 'M d Y'),number_format($periodTermAmount, 2),number_format($periodInterestAmount, 2),number_format($totalPeriodAmount, 2),number_format($totalBalanceAmount, 2)];
			$paymentLoanScheduleAssoc[$x] = [
				'due_date'=>$getPeriodDueDate,
				'loan_id'=>$dataExternalLoanID,
				'client_id'=>$dataClientID,
				'loan_type'=>$dataLoanType,
				'principal_due'=>$periodTermAmount,
				'interest_due'=>$periodInterestAmount,
				'payment_due'=>$paymentAmount,
				'total_due'=>$totalPeriodAmount,
				'loan_balance'=>$totalBalanceAmount
			];
			
			// $paymentLoanScheduleAssoc[$x] = [
				// 'due_date'=>$getPeriodDueDate,
				// 'principal_due'=>$periodTermAmount,
				// 'interest_due'=>$periodInterestAmount,
				// 'others_cbu'=>$getLoanCBUAmount,
				// 'others_savings'=>$getLoanSavingsAmount,
				// 'total_due'=>$totalPeriodAmount,
				// 'loan_balance'=>$totalBalanceAmount
			// ];
				//$tdLoanSchedule .= "<tr id='loan_schedule_payments' class='readOnly'><td>{$x}</td><td><input type='hidden' id='loan_schedules' value='{$x}' />{$paramsID}</td><td>{$getPeriodDueDate}</td><td><input type='number' name='schedule_principal[{$x}]' value='' /></td><td><input type='number' name='schedule_interest[{$x}]' value='' /></td><td class='alignRight bold'>".number_format($getLoanCBUAmount, 2)."</td><td class='alignRight bold'>".number_format($getLoanSavingsAmount, 2)."</td><td><input type='number' name='schedule_total_due[{$x}]' value='' /></td><td><input type='number' name='schedule_total_balance[{$x}]' value='' /></td></tr>";
		} // END FOR LOOP
		
		if($dataInterestMethod == 3){ // ANNUITY RE-GENERATE THE RESULTS
			$interestAmount = 0;
			$totalPeriodAmountAnnuity = (floatval($dataPrincipalAmount) + $totalInterestAmount) / $numPayments;
			foreach($paymentLoanSchedule as $key => $loanSchedule){
				$interestAmount = str_replace(',', '', $loanSchedule[3]);
				$interestAmount = $totalPeriodAmountAnnuity - floatval($interestAmount);
				$paymentLoanSchedule[$key][2] = number_format($interestAmount, 2); // PRINCIPAL DUE
				$paymentLoanSchedule[$key][6] = number_format($totalPeriodAmountAnnuity, 2); // TOTAL DUE
			}
		}
		
		$output['schedule_object'] = $paymentLoanSchedule;//json_encode($paymentLoanSchedule, JSON_FORCE_OBJECT);
		$output['schedule_associative'] = $paymentLoanScheduleAssoc;//json_encode($paymentLoanSchedule, JSON_FORCE_OBJECT);
		$output['summary'] = [
			"maturity_date"=>$getPeriodDueDate,
			"total_principal"=>number_format($totalPrincipalAmount, 2),
			"total_interest"=>number_format($totalInterestAmount, 2),
			"total_amortization"=>number_format($totalDueAmount, 2)
		];
		ob_end_clean();
		return $output;//json_decode(json_encode($output, JSON_FORCE_OBJECT));
	}
	
	public function submitParamsAPI($postLoanID, $params){
		include 'requestAPI.php';
		$this->Params = $params;
		$requestAPI = new Request();
		$postDataAPI = "";
		$postAPI = [];
		if($this->Params["amortization_type"] == "1") $this->Params["LoanDIMB_FREQ"] = 1;
		$postLoanInterest = $this->Params["loan_details_loan_interest_percentage"];
		$loanInterest = floatval($postLoanInterest) / 100;
		$branchCode = $this->Params['unit'];
		$LoanCI = (isset($this->Params["loan_summary_credit_investigator"]) && $this->Params["loan_summary_credit_investigator"] != "") ? substr($this->Params["loan_summary_credit_investigator"], 0, 10) : "";
		$loanTransDate = $this->getTime('date');
		$apiAddress = Info::getAPIAddress($branchCode);
		$apiDir = Info::API_DIR;
		$apiParam = "&branch={$branchCode}"; 
		$apiType = ($postLoanID) ? "update_loan" : "create_loan";
		$apiURL = "http://{$apiAddress}{$apiDir}{$apiType}{$apiParam}";
		$firstPaymentDueDate = $this->Params["first_payment_date"];
		if($this->Params["loan_details_payment_mode"] == 9) $firstPaymentDueDate = $this->Params["maturity_date"];
		//"LoanScheduleClamp" => $this->Params["LoanScheduleClamp"],
		$fieldPostAPI = [
			"LoanREF_NO" => $postLoanID,
			"LoanBR_CODE" => $branchCode,
			"LoanSLC_CODE" => Info::apiDefaultValue("LoanSLC_CODE"),
			"ClientIDLoan" => floatVal($this->Params["loan_details_client_id"]),
			"LoanTR_DATE" => $loanTransDate,
			"LoanSLT_CODE" => $this->Params["loan_details_loan_types"],
			//"applied_loan_amt" => $this->Params["loan_details_loan_amount"],
			"LoanPURPOSE" => $this->Params["loan_details_loan_purpose"],
			"LoanTERMS" => $this->Params["loan_details_payment_terms"],
			"LoanINT_RATE" => $postLoanInterest,
			"LoanPAMT" => $this->Params["loan_summary_loan_granted"],
			"LoanSTATUS" => Info::apiDefaultValue("LoanSTATUS"),
			"LoanDD_FLAG" => Info::apiDefaultValue("LoanDD_FLAG"),
			"LoanRemarks" => $this->Params["loan_summary_other_information"],
			"LoanMemberRating" => $this->Params["members_credit_rating_willingness_pay"],
			"LoanINDUSTRY" => $this->Params["loan_details_industry_division"],
			"LoanProcessCredit" => $this->Params["loan_details_process_credit"],
			"LoanISDISTRIBUTEUNPAIDINT" => 0,
			//"service_fee" => $apiDeduction,//$this->Params["loan_summary_other_charges"],
			//"loan_deductions" => $this->Params["loan_summary_other_charges"],
			"LoanLOANCLASS" => 1,
			"LoanTERM_PERD" => 4,
			"LoanPPMT_MODE" => $this->Params["loan_details_payment_mode"],
			"LoanAMORTYPE" => $this->Params["amortization_type"],
			"LoanDIMB_FREQ" => $this->Params["LoanDIMB_FREQ"],
			"LoanIPMT_MODE" => $this->Params["loan_details_payment_mode"],
			"LoanPEN_RATE" => 24,
			"LoanPEN_MODE" => 4,
			"LoanFID_DATE" => $this->Params["first_payment_date"],
			"LoanFPD_DATE" => $firstPaymentDueDate,
			"LoanMAT_DATE" => $this->Params["maturity_date"],
			"LoanND_FLAG" => 1,
			"LoanMCLASS" => 1,
			"LoanACCTOFF" => "rr1",
			"LoanRESTRUCT" => 0,
			"LoanCOLLTYPE" => 1,
			"LoanCOLLAMT" => $this->Params["loan_summary_collection_fee"],
			"LoanCOLLDESC" => "N/A",
			"LoanCBUAMT" => $this->Params["loan_summary_others_cbu"],
			"LoanSAVAMT" => $this->Params["loan_summary_others_savings"],
			"LoanCI" => $LoanCI,
			"LoanPENGP" => 7,
			"LoanIsExcludeSundays" => 0,
			"LoanIsExcludeHolidays" => 0,
			"LoanPAMT2" => 0,
			"LoanSecurity" => $this->Params["loan_summary_loan_security"],
			"LoanCOLLFEE" => 0,
			"LoanCOLLECTTYPE" => $this->Params["loan_summary_collection_type"],
			"LoanCoMaker" => $this->Params["co_maker"]
			//"op_id" => "LMPCE"
			//"start_dt"=>getTime('date')
			];
		foreach($fieldPostAPI as $apiKey => $apiValue){
			$postAPI[$apiKey] = $apiValue;
			//echo $postKey." - ".$postValue."<br>";
		}
		$this->Params['post_api'] = $postAPI;
		$postDataAPI = $requestAPI->sendAPI($apiURL,$postAPI);
		if($postDataAPI == "ERROR_API_SEND"){
			$postDataAPI = $postDataAPI;
		}
		return $postDataAPI;
	}
	
	public function loanSummaryBoxInfo(){
		$totalDeduction = $this->inputGroup(['type'=>'hidden','id'=>'input_total_deduction','name'=>'input_total_deduction','placeholder'=>'','value'=>0]); //,'custom'=>'readonly="readonly"'
		$totalInterestAmount = $this->inputGroup(['type'=>'hidden','id'=>'input_total_interest_amount','name'=>'input_total_interest_amount','placeholder'=>'','value'=>0]); //,'custom'=>'readonly="readonly"'
		$netAmount = $this->inputGroup(['type'=>'hidden','id'=>'input_net_amount','name'=>'input_net_amount','placeholder'=>'','value'=>0]); //,'custom'=>'readonly="readonly"'
		$output = "
		<div class='boxSummary half' id='loan_summary_box'>
			<h2>Loan Application Summary</h2>
			<div class=''>
				<div class='form-group' id='total_deduction'>
					<label class='mid no-padding alignRight'>Less: Deduction</label>
					<div>{$totalDeduction}<span id='value'>".Info::EMPTY_VAL."</span></div>
				</div>
				<div class='form-group' id='total_interest_amount'>
					<label class='mid no-padding alignRight'>Total Interest</label>
					<div>{$totalInterestAmount}<span id='value'>".Info::EMPTY_VAL."</span></div>
				</div>
			</div>
			<div class='' id='net_amount'>
				<h2 class='alignCenter'>NET AMOUNT</h2>{$netAmount}
				<div class='alignCenter largexx' id='value'>".Info::EMPTY_VAL."</div>
			</div>
		</div>
		";
		
		return $output;
	}
	
	public function shareCapitalBoxInfo(){
		$output = "";
		//$shareCapitalOptions = $this->methodSettings(['meta_key'=>'module_policy','name'=>'share_capital_policy']); // GET METHODS MODULE SETTINGS
		$commonParValue = Info::EMPTY_VAL;//$shareCapitalOptions['common[par_value]'];
		$commonMinimumCapital = Info::EMPTY_VAL;//$shareCapitalOptions['common[minimum][capital]'];
		$commonMinimumShares = Info::EMPTY_VAL;//$shareCapitalOptions['common[minimum][shares]'];
		$output .= "
		<div class='boxSummary half' id='share_capital_box'>
			<h2>Share Capital Transaction Summary</h2>
			<div class='two-third'>
				<div class='form-group' id='par_value'>
					<label class='mid no-padding alignRight'>Par Value of Shares</label>
					<div><span id='value'>{$commonParValue}</span></div>
				</div>
				<div class='form-group' id='minimum_capital'>
					<label class='mid no-padding alignRight'>Minimum No. of Shares</label>
					<div><span id='value'>{$commonMinimumShares}</span></div>
				</div>
				<div class='form-group' id='minimum_shares'>
					<label class='mid no-padding alignRight'>Minimum Paid-Up Capital</label>
					<div><span id='value'>{$commonMinimumCapital}</span></div>
				</div>
			</div>
			<div class='one-third' id='share_value'>
				<h2 class='alignCenter'>NO. OF SHARES</h2>
				<div class='alignCenter' id='value'>00</div>
			</div>
		</div>
		";
		
		return $output;
	}
	
	public function notificationList(){
		$result = []; $output = ""; $cnt = 0;
		$getNotifications = $_SESSION["notifications"];
		//$output['lists'] .= '<li><a href="'.Info::URL.'/methods.php?module_type=admin&view=posts&alias=loan_application&id='.$dataID['data_id'].'"><span><span>'.Config::notificationContent($notificationToken,$dataID['data_id'])->title.'</span><span class="time">'.timeDateFormat(Config::notificationContent($notificationToken,$dataID['data_id'])->date_created,'date').'</span></span><span class="message">'.Config::notificationContent($notificationToken,$dataID['data_id'])->content.'</span></a></li>';
		foreach($getNotifications as $notificationID => $notificationInfo){
		    $status = $notificationInfo["status"];
			if($status == 0){
				$output .= "<li><a href='".Info::URL."/methods?module_type=admin&view=posts&alias={$notificationInfo['path_alias']}&id={$notificationInfo['data_id']}'><span><span>{$notificationInfo['path_title']}: {$notificationInfo['trans_id']}</span><span class='time'>{$notificationInfo['date']}</span></span><span class='message'>Name: <span class='bold'>{$this->stringLimit($notificationInfo['client'],22,'char')}</span><span class='right'>({$notificationInfo['unit']})</span></span></a></li>";
				$cnt++;
			}
		}
		$result["total"] = $cnt;
		$result["lists"] = $output;
		return $result;//json_decode(json_encode($result, JSON_FORCE_OBJECT));
	}
	
	public function reverseJournals($journalID){ // REVERSING JOURNALS
		if($journalID){ // IF HAS JOURNAL
			$journalUpdateElements = [];
			$journalUpdateElements['fieldValues']["status"] = 0;
			$journalUpdateElements['schema'] = Info::DB_ACCOUNTING;
			$journalUpdateElements['table'] = "journals";
			$journalUpdateElements['id'] = $journalID;
			$this->updateDB($journalUpdateElements);
			
			$stmtJournalEntry = ['schema'=>Info::DB_ACCOUNTING,'table'=>'journals_entry','arguments'=>['journals_id'=>$journalID],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
			$getJournalEntry = $this->selectDB($stmtJournalEntry);
			$entryUpdateElements = [];
			$entryUpdateElements['fieldValues']["status"] = 0;
			$entryUpdateElements['schema'] = Info::DB_ACCOUNTING;
			$entryUpdateElements['table'] = "journals_entry";
			foreach($getJournalEntry as $key => $id){
				$entryUpdateElements['id'] = $id;
				$this->updateDB($entryUpdateElements);
			}
		}
	}
	
	public function voucherBoxDetails($journalID){
		$voucherTitle = "JOURNAL VOUCHER";
		$stmtJournals = ['schema'=>Info::DB_ACCOUNTING,'table'=>'journals','arguments'=>['id'=>$journalID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['id','entry_date','recipient','particulars','unit']];
		$getJournals = $this->selectDB($stmtJournals);
		
		$voucherID = $this->formatValue(['prefix'=>$getJournals[$journalID]->unit,'id'=>$journalID],"ref_id");

		$stmtJournalEntry = ['schema'=>Info::DB_ACCOUNTING,'table'=>'journals_entry','arguments'=>['journals_id'=>$journalID],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>['IFNULL((SELECT title FROM '.Info::PREFIX_SCHEMA.Info::DB_ACCOUNTING.'.charts WHERE id = journals_entry.charts_id), 0) AS account_title','IF(debit>0, Format(debit,2), "---") as debit','IF(credit>0, Format(credit,2), "---") as credit','debit as amount']];
		$getJournalEntry = $this->selectDB($stmtJournalEntry);

		$getDebitRows = array_column($getJournalEntry, 'amount');
		$totalDebitAmount = array_sum($getDebitRows);
		$totalDebitAmountValues = ucwords($this->convertNumToWords($totalDebitAmount));
		
		### GET CHECK NUMBER ###
		$checkNum = "---";
		$stmtQueue = ['schema'=>Info::DB_DATA,'table'=>'data_queue','arguments'=>['journals_id'=>$journalID],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['data_id','path_id']];
		$getQueue = $this->selectDB($stmtQueue);
		if($getQueue){
			$getDataID = array_keys($getQueue);
			$dataID = $getDataID[0];
			$pathID = $getQueue[$dataID];
			switch($pathID){
				case "3": // DEPOSITS TRANSACTIONS
					$dataTable = "path_3_deposits_transactions";
				break;
				case "4": // WITHDRAWAL TRANSACTIONS
					$dataTable = "path_4_withdrawal_transactions";
				break;
				case "8": // LOANS PAYMENT
					$dataTable = "path_8_loans_payment_transactions";
				break;
				case "10": // CASH TRANSACTIONS
					$dataTable = "path_10_cash_details";
					$stmtCashTrans = ['schema'=>Info::DB_DATA,'table'=>'path_10_cash_transactions','arguments'=>['data_id'=>$dataID],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['cash_type']];
					$getCashTrans = $this->selectDB($stmtCashTrans);
					switch($getCashTrans[0]){
						case "1": $voucherTitle = "CASH DISBURSEMENT VOUCHER"; break;
						case "2": $voucherTitle = "CASH RECEIPT VOUCHER"; break;
					}
				break;
			}
			$stmtData = ['schema'=>Info::DB_DATA,'table'=>$dataTable,'arguments'=>['data_id'=>$dataID],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['check_number']];
			$getData = $this->selectDB($stmtData);
			$checkNum = ($getData[0]) ? $getData[0] : "---";
		}
		### GET CHECK NUMBER ###
		
		$payee = ($getJournals[$journalID]->recipient) ? $getJournals[$journalID]->recipient :  "---";
		$journalEntries = json_encode($getJournalEntry,true);
		
		$output = "
			<span id='voucher_title'>{$voucherTitle}</span>
			<span id='voucher_id'>{$voucherID}</span>
			<span id='voucher_date'>{$getJournals[$journalID]->entry_date}</span>
			<span id='voucher_check'>{$checkNum}</span>
			<span id='voucher_payee'>{$payee}</span>
			<span id='voucher_amount'>".number_format($totalDebitAmount, 2)."</span>
			<span id='voucher_amount_value'>{$totalDebitAmountValues} Pesos Only</span>
			<span id='voucher_journals'>{$journalEntries}</span>
			<span id='voucher_total'>".number_format($totalDebitAmount, 2)."</span>
			<span id='voucher_particulars'>{$getJournals[$journalID]->particulars}</span>
			<span id='voucher_prepared'>{$_SESSION["displayname"]}</span>
		";
		echo $output;
	}
	
	public function attachmentBox($attachmentName,$attachmentField,$thisAttachments){
		$cnt = 1; $attachmentElement = $dzStarted = $divBox = $btnClear = "";
		$this->getElementType = "1";
		if($thisAttachments){
			$attachmentIcon = '';//'<span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span>';
			$getAttachments = explode(",",$thisAttachments);
			$dzStarted = " dz-started";
			$setAttachmentField = $attachmentField[0]."[".$attachmentField[1]."]";//implode("_",$attachmentField);
			$btnClear = '<button id="clearAttachments" meta="'.$attachmentName.'" field="'.$setAttachmentField.'" title="Clear Attachments" type="button" value="" class="btn"><i class="fa fa-times"></i></button>';
			foreach($getAttachments as $attachment){
				$attachmentElement .= '<div class="dz-preview dz-file-preview dz-complete"><div class="dz-image"><img data-dz-thumbnail src="'.Info::URL.'/files/'.$attachmentField[1].'/'.$attachment.'"></div><div meta-box="'.$attachmentField[1].'" class="fileAttachment dz-details" data-toggle="modal" data-target=".viewattachments" file="'.$attachment.'">'.$attachmentIcon.'<div class="dz-filename"><span data-dz-name="" class="ellipsis">'.$attachment.'</span></div></div></div>';
			}
		}
		
		$divBox .= '<div class="attachmentBox '.$this->pageAction.'">'.$btnClear.'<div id="'.$attachmentName.'" class="dropzone'.$dzStarted.'">'; // name="uploadAttachments"
		$divBox .= $attachmentElement;
		$divBox .= '</div></div>';

		return $divBox;
	}
	
	public function postDeductionBox(){
		$totalDeductions = [];
		$postTotalDeductions = (isset($this->globalDataValue['loan_summary']['loan_deductions'])) ? $this->globalDataValue['loan_summary']['loan_deductions'] : "";
		if($postTotalDeductions){
			$postTotalDeductions = str_replace("'",'"',$postTotalDeductions);
			$totalDeductions = json_decode($postTotalDeductions);
		}
		
		$output = "<div id='postDeductionBox' class='{$this->pageAction}'>";
		$getCodeDeductions = $this->methodSettings(['meta_key'=>'module_policy','name'=>'loans_deduction']);
		$max = ($this->pageAction == "view") ? count($totalDeductions) : 5;
		$cnt = 0;
		for ($x = 1; $max >= $x; $x++) {
			$deductionChart = (isset($totalDeductions[$cnt]->charts_id)) ? $totalDeductions[$cnt]->charts_id : 0;
			$deductionValue = (isset($totalDeductions[$cnt]->credit)) ? $totalDeductions[$cnt]->credit : "";
			$output .= "<div class='form-group deduction'>";
			//$codeType = $this->inputGroup(['type'=>'text','id'=>'','name'=>'','placeholder'=>'Account Chart','value'=>'','custom'=>'element_id=\'ddd\'']);
			$options = "<option value=''></option>";
			foreach($getCodeDeductions as $key => $value){
				$selected = "";
				if($deductionChart == $key) $selected = "selected='selected'";
				$options .= "<option {$selected} value='{$key}'>{$value}</option>";
			}
			$codeType = "<select id='charts_id' name='deductions[{$x}][charts_id]' class='select2_single form-control' placeholder='Select Deductions {$x}'>{$options}</select>";
			$codeAmount = $this->inputGroup(['type'=>'amount','id'=>'chart_amount','name'=>'deductions['.$x.'][amount]','placeholder'=>'Amount','value'=>$deductionValue,'custom'=>'element_id=\''.$x.'\'']);
			$output .= $codeType.$codeAmount;
			$output .= "</div>";
			$cnt++;
		}
		
		$output .= "</div>";
		return $output;
	}

	public function deductionBox(){
		$deductionLimit = 5; $cnt = 1; $totalDeduction = 0; $postDeductionValue = $postDeductionValue = $deductionValues = [];
		$divBox = '<div class="fieldGroup deductionBox hide"><form id="createDeduction" name="createDeduction" novalidate>';
		if($this->globalQueueData->id > 0){
			$this->getElementType = "1";
			$thisDeduction = $this->getElements('loan_application')['loan_summary']['other_charges'];
			$getDeductionValue = json_decode($thisDeduction,true);
			$postDeductionValue = $getDeductionValue[0];
			
		}
		if(!EMPTY($postDeductionValue)){
				foreach($postDeductionValue as $key => $value){
					$deductionValues[$cnt]['accounting_code'] = $key;
					$deductionValues[$cnt]['amount_deduction'] = $value;
					$totalDeduction = $totalDeduction + $value;
					$cnt++;
				}
			}
			//$deductionValues = [];
			$divBox .= '<input type="hidden" name="post_id" value="'.$this->globalQueueData->id.'" /><input type="hidden" name="total_deduction" value="'.$totalDeduction.'" />';
			for ($x = 1; $deductionLimit >= $x; $x++) {
				$accounting_code = $amount_deduction = "";
				//if(!EMPTY($deductionValues)){
					if(isset($deductionValues[$x]['accounting_code'])) $accounting_code = $deductionValues[$x]['accounting_code'];
					if(isset($deductionValues[$x]['amount_deduction'])) $amount_deduction = $deductionValues[$x]['amount_deduction'];
				//}

				$accountingCode = $this->inputGroup(['label'=>'Deduction '.$x,'type'=>'select','id'=>'accounting_code','name'=>'accounting_code'.$x,'meta_key'=>'accounting_code','meta'=>'codebook','placeholder'=>'Accounting Code','value'=>$accounting_code,'custom'=>'element_id="'.$x.'"']);
				$amountDeduction = $this->inputGroup(['type'=>'amount','id'=>'amount_deduction','name'=>'amount_deduction'.$x,'placeholder'=>'Amount','value'=>$amount_deduction,'custom'=>'element_id='.$x.'']);
				$divBox .= '<div id="deduction_'.$x.'" class="form-group x_content no-padding"><div class="form-group two-third no-padding item">'.$accountingCode.'</div><div class="form-group one-third no-padding item">'.$amountDeduction.'</div></div>';
			}
			$divBox .= '</form>';
		$divBox .= '</div>';
		//var_dump($deductionValues);
		
		$output = $divBox;

		return $output;
	}

	public function boxAdminForm(){
		//echo $this->popupFormID." - ".$this->Params['theTable'];
		switch($this->Params['theTable']){
			case "codebook": // CREATE OPTIONS ON CODEBOOK
				$output = $formField = ""; $status = 0;
				$getFields = ['id','meta_id','meta_key','meta_option','meta_value'];

				$stmtMetaLastValue = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->Params['theTable'],'arguments'=>['meta_key'=>$this->Params['theName']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','meta_id'],'extra'=>'ORDER BY ABS(meta_id) DESC LIMIT 1'];
				$getMetaLastValue = $this->selectDB($stmtMetaLastValue);
				$getMetaID = key($getMetaLastValue);
				$thisMetaID = ($getMetaID) ? (int)$getMetaLastValue[$getMetaID] + 1 : 1;
				//$thisMetaID = $lastMetaValue + 1;
				//meta_id
				$meta_id = $this->inputGroup(['label'=>'Option Details','type'=>'number','id'=>'meta_id','name'=>'meta_id','placeholder'=>'Meta ID','value'=>$thisMetaID]);
				$meta_option = $this->inputGroup(['type'=>'text','id'=>'meta_option','name'=>'meta_option','placeholder'=>'Meta Option','value'=>'']);//$this->Params['middle_name']
				$meta_value = $this->inputGroup(['type'=>'text','id'=>'meta_value','name'=>'meta_value','placeholder'=>'Meta Value','value'=>'']);//$this->Params['middle_name']

				$popUpTitle = str_replace("_"," ",$this->Params['theName']);
				$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>Meta Name: <span class='capitalize'>{$popUpTitle}</span></h2></div><div class='x_content no-padding'>";
				$formField .= "
			<div class=''>
				<div class='form-group no-padding item'>
					<div class='col-md-3 col-sm-3 col-xs-12 no-padding col-2 alignRight'>{$meta_id}</div>
					<div class='col-md-4 col-sm-4 col-xs-12 no-padding'>{$meta_option}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$meta_value}</div>
				</div>
			</div>
			</div>
		</div>
		</div>
		";
				$output .= "
            <form id='createRecords' data-toggle='validator' name='optionsForm' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['theTable']}' />
				<input type='hidden' name='schema' id='schema' value='".Info::DB_SYSTEMS."' />
				<input type='hidden' name='meta_key' id='meta_key' value='{$this->Params['theName']}' />
				<input type='hidden' name='theID' id='theID' value='{$this->popupFormID}' />
				{$formField}
			</form>
        ";

				$output .= "<script>
			$(document).ready(function() {
				//webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});
		</script>";
				return $output;
				break;
			case "users": // USER FORM
				ob_start();
				$output = $formField = ""; $status = 1;
				$getPopupValue[] = $fieldValue[] = "";
				$varFields = ["username","firstname","lastname","position","description","role","tokens","unit"];
				
				if($this->popupFormID){
					$getPopupValue = $this->getValueDB(["table"=>$this->Params['theTable'],"id"=>$this->popupFormID,"schema"=>Info::DB_SYSTEMS]);
					$status = $getPopupValue['status'];
				}
				foreach($varFields as $variable){
					$$variable = (isset($getPopupValue[$variable])) ? $getPopupValue[$variable] : "";
				}
				//var_dump($getPopupValue);
				$this->pageAction = ($this->popupFormID) ? "view" : "";
				$username = $this->inputGroup(['label'=>'Username','type'=>'text','id'=>'username','name'=>'username','placeholder'=>'Username','value'=>$username]);
				$this->pageAction = "";
				$password = $this->inputGroup(['label'=>'Password','type'=>'password','id'=>'password','name'=>'password','placeholder'=>'&bull;&bull;&bull;&bull;&bull;&bull;','value'=>'']);
				$firstname = $this->inputGroup(['label'=>'Firstname','type'=>'text','id'=>'firstname','name'=>'firstname','placeholder'=>'Firstname','value'=>$firstname]);
				$lastname = $this->inputGroup(['label'=>'Lastname','type'=>'text','id'=>'lastname','name'=>'lastname','placeholder'=>'Lastname','value'=>$lastname]);
				$position = $this->inputGroup(['label'=>'Position','type'=>'text','id'=>'position','name'=>'position','placeholder'=>'Position','value'=>$position]);
				$description = $this->inputGroup(['label'=>'Description','type'=>'text','id'=>'description','name'=>'description','placeholder'=>'Description','value'=>$description]);
				$this->pageAction = ($_SESSION["userrole"] >= 3) ? "view" : "";
				$role = $this->inputGroup(['label'=>'Role','type'=>'select','id'=>'role','name'=>'role','meta_key'=>'role','meta'=>'codebook','placeholder'=>'Select Access Role','value'=>$role]);
				$tokens = $this->inputGroup(['label'=>'Access Tokens','type'=>'select','id'=>'tokens','name'=>'tokens','meta_key'=>'tokens','meta'=>'tokens','placeholder'=>'Select Access Role','value'=>$tokens]);
				$unit = $this->inputGroup(['label'=>'Branch','type'=>'select','id'=>'unit','name'=>'unit','meta_key'=>'unit','meta'=>'codebook','placeholder'=>'Select User\'s Department/Unit','value'=>$unit]);

				$popUpTitle = str_replace("_"," ",$this->Params['theTable']);
				$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
				$formField .= "
					<div class='one-fourth'>
				<div class='form-group no-padding item alignRight'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$username}</div>
				</div>
				<div class='form-group no-padding item alignRight'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$password}</div>
				</div>
			</div>
			<div class='one-fourth'>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$firstname}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$lastname}</div>
				</div>
			</div>
			<div class='one-fourth'>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$position}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$description}</div>
				</div>
			</div>
			<div class='one-fourth'>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$role}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$unit}</div>
				</div>
			</div>
			<div class=''>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$tokens}</div>
				</div>
			</div>
				</div>
				</div>
				";
						$output .= "
					<form id='createRecords' data-toggle='validator' name='createusers' class='form-label-left input_mask' novalidate>
						<input type='hidden' name='action' id='action' value='createRecords' />
						<input type='hidden' name='schema' id='schema' value='".Info::DB_SYSTEMS."' />
						<input type='hidden' name='table' id='table' value='{$this->Params['theTable']}' />
						<input type='hidden' name='theID' id='theID' value='{$this->popupFormID}' />
						{$formField}
					</form>
				";
				$output .= "<script>
			$(document).ready(function() {
				//webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$('.select2_multiple').select2({
				 // maximumSelectionLength: 4,
				  placeholder: 'Select User Access/Tokens',
				  allowClear: true
				});

				$(':input').inputmask();
			});

		</script>";
				ob_end_clean();
				return $output;
				break;
		}
	}

	public function boxSystemForm(){
		$boxFieldType = $boxCodebook = $boxColumn1 = $boxColumn2 = "";
		
		if($this->Params['action'] != "viewcharts"){
			$tableFields = $this->getTableFields(['table'=>$this->Params['theTable'],'exclude'=>[],'schema'=>Info::DB_SYSTEMS]);
			//$fieldInputs = array_diff($tableFields,['id','status','user','date']);
			$stmtBoxData = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->Params['theTable'],'arguments'=>['id'=>$this->Params['theID']],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$tableFields];
			$getBoxData = $this->selectDB($stmtBoxData);
			$formName = "saveRecords";
			$formNameID = "create".$this->Params['theTable'];
			$formFields = "
				<input type='hidden' name='action' id='action' value='saveRecords' />
				<input type='hidden' name='schema' id='schema' value='".Info::DB_SYSTEMS."' />
				<input type='hidden' name='table' id='table' value='".$this->Params['theTable']."' />
				<input type='hidden' name='theID' id='theID' value='".$this->Params['theID']."' />
			";
		}
		
		switch($this->Params['action']){
			case 'viewcharts':
				$formName = "saveCharts";
				$formNameID = "createcharts";
				$formFields = "
					<input type='hidden' name='action' id='action' value='".$formName."' />
					<input type='hidden' name='schema' id='schema' value='".Info::DB_ACCOUNTING."' />
					<input type='hidden' name='theID' id='theID' value='".$this->popupFormID."' />
				";
			
				$stmtAccounting['fields'] = ['charts_meta.id','charts_meta.type','charts_meta.parent','charts.code','charts_meta.debit','charts_meta.credit','charts_meta.opening_date','charts_meta.status','charts.title','charts.description'];
				$stmtAccounting['table'] = 'charts_meta AS charts_meta';
				$stmtAccounting['join'] = 'JOIN '.Info::PREFIX_SCHEMA.Info::DB_ACCOUNTING.'.charts AS charts ON charts.id = charts_meta.id';
				$stmtAccounting['extra'] = 'ORDER BY type, code ASC';
				$stmtAccounting['arguments'] = ["charts_meta.id"=>$this->popupFormID];
				$stmtAccounting += ['schema'=>Info::DB_ACCOUNTING,'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS];
				$getBoxData = $this->selectDB($stmtAccounting);
			
				$chartFields = $this->getTableFields(['table'=>'charts','exclude'=>['id','user','date_updated'],'schema'=>Info::DB_ACCOUNTING]);
				$chartMetaFields = $this->getTableFields(['table'=>'charts_meta','exclude'=>['status','user','date'],'schema'=>Info::DB_ACCOUNTING]);
				$getFields = ["charts_meta"=>$chartMetaFields,"charts"=>$chartFields];
				//var_dump(json_encode($getFields));
				
				$type = $this->inputGroup(['label'=>'Account Type','type'=>'select','id'=>'type','name'=>'type','meta_key'=>'accounting_type','meta'=>'codebook','placeholder'=>'Accounting Type','value'=>$getBoxData[$this->popupFormID]->type]);
				$parent = $this->inputGroup(['label'=>'Parent Account','type'=>'select','id'=>'codebook','name'=>'parent','title'=>'','meta_key'=>'charts','meta'=>'charts','placeholder'=>'Select Account Chart...','value'=>$getBoxData[$this->popupFormID]->parent]);
				$debit = $this->inputGroup(['label'=>'Beginning Balance','type'=>'amount','id'=>'debit','name'=>'debit','title'=>'','placeholder'=>'Debit Amount','value'=>$getBoxData[$this->popupFormID]->debit]);
				$credit = $this->inputGroup(['type'=>'amount','id'=>'credit','name'=>'credit','title'=>'','placeholder'=>'Credit Amount','value'=>$getBoxData[$this->popupFormID]->credit]);
				$openingDate = $this->inputGroup(['label'=>'Opening Date','type'=>'date','id'=>'opening_date','name'=>'opening_date','title'=>'','placeholder'=>'Opening Date','value'=>$getBoxData[$this->popupFormID]->opening_date]);
				
				$code = $this->inputGroup(['label'=>'Accounting Code','type'=>'text','id'=>'code','name'=>'code','title'=>'','placeholder'=>'Accounting Code','value'=>$getBoxData[$this->popupFormID]->code]);
				$title = $this->inputGroup(['label'=>'Accounting Title','type'=>'text','id'=>'title','name'=>'title','title'=>'','placeholder'=>'Title/Name','value'=>$getBoxData[$this->popupFormID]->title]);
				$description = $this->inputGroup(['label'=>'Description','type'=>'textarea','id'=>'description','name'=>'description','title'=>'','placeholder'=>'Accounting Description','value'=>$getBoxData[$this->popupFormID]->description]);

				$boxColumn1 = "<div class='form-group no-padding item'>{$code}</div>";
				$boxColumn1 .= "<div class='form-group no-padding item'>{$title}</div>";
				$boxColumn1 .= "<div class='form-group no-padding item'>{$description}</div>";
				
				$boxColumn2 = "<div class='form-group no-padding item'>{$type}</div>";
				$boxColumn2 .= "<div class='form-group no-padding item'>{$parent}</div>";
				$boxColumn2 .= "<div class='form-group no-padding item'>{$openingDate}</div>";
				$boxColumn2 .= "<div class='form-group no-padding item two-fields'>{$debit}{$credit}</div>";
				
			break;
			case 'viewfields':
				$fieldTypeValue = ($this->Params['theID']) ? $getBoxData[$this->Params['theID']]->field_type : "";
				$fieldCodebookID = ($this->Params['theID']) ? $getBoxData[$this->Params['theID']]->codebook_id : "";
				$fieldType = $this->inputGroup(['label'=>'Type','type'=>'select','id'=>'codebook','name'=>'field_type','title'=>'','meta_key'=>'field_type','meta'=>'codebook','placeholder'=>'Field Type','value'=>$fieldTypeValue]);
				$fieldCodeBook = $this->inputGroup(['label'=>'Codebook','type'=>'select','id'=>'codemeta','name'=>'codemeta','title'=>'','meta_key'=>'fields','meta'=>'codebook','placeholder'=>'CodeBook','value'=>$fieldCodebookID]);
				$boxColumn1 = '<div class="form-group no-padding item">'.$fieldType.'</div>';
				$boxColumn2 = '<div class="form-group no-padding item">'.$fieldCodeBook.'</div>';
			break;
			case 'viewpath':
				$fieldGroup = $this->inputGroup(['label'=>'Group','type'=>'select','id'=>'codebook','name'=>'groups','title'=>'','meta_key'=>'groups','meta'=>'groups','placeholder'=>'Field Type','value'=>$getBoxData[$this->Params['theID']]->groups]);
				$boxColumn1 = '<div class="form-group no-padding item">'.$fieldGroup.'</div>';
			break;
			case 'viewactivity': case 'viewworkflow':
				$fieldPath = $this->inputGroup(['label'=>'Path','type'=>'select','id'=>'codebook','name'=>'path','title'=>'','meta_key'=>'path','meta'=>'path','placeholder'=>'Field Type','value'=>$getBoxData[$this->Params['theID']]->path]);
				$fieldTokens = $this->inputGroup(['label'=>'Tokens','type'=>'select','id'=>'codebook','name'=>'tokens','title'=>'','meta_key'=>'tokens','meta'=>'tokens','placeholder'=>'CodeBook','value'=>$getBoxData[$this->Params['theID']]->tokens]);
				$boxColumn1 = '<div class="form-group no-padding item">'.$fieldPath.'</div>';
				$boxColumn2 = '<div class="form-group no-padding item">'.$fieldTokens.'</div>';
			break;
			default:
			
			break;
		}
		
		if($this->Params['action'] != "viewcharts"){
			$fieldNameValue = ($this->Params['theID']) ? $getBoxData[$this->Params['theID']]->name : "";
			$fieldAliasValue = ($this->Params['theID']) ? $getBoxData[$this->Params['theID']]->alias : "";
			$fieldDescriptionValue = ($this->Params['theID']) ? $getBoxData[$this->Params['theID']]->description : "";
			$fieldName = $this->inputGroup(['label'=>'Name','type'=>'text','id'=>'name','name'=>'name','title'=>'','placeholder'=>'Field Name','value'=>$fieldNameValue]);
			$fieldAlias = $this->inputGroup(['label'=>'Alias','type'=>'text','id'=>'alias','name'=>'alias','title'=>'','placeholder'=>'Alias','value'=>$fieldAliasValue]);
			$fieldDescription = $this->inputGroup(['label'=>'Description','type'=>'textarea','id'=>'description','name'=>'description','title'=>'','placeholder'=>'Field Description','value'=>$fieldDescriptionValue]);

			$boxColumn1 .= "<div class='form-group no-padding item'>{$fieldName}</div><div class='form-group no-padding item'>{$fieldAlias}</div>";
			$boxColumn2 .= "<div class='form-group no-padding item'>{$fieldDescription}</div>";
		}
		
		$formField = '
			<form id="'.$formNameID.'" data-toggle="validator" name="'.$formName.'" class="form-label-left input_mask" novalidate>
				'.$formFields.'
				<div class="half">'.$boxColumn1.'</div>
				<div class="half">'.$boxColumn2.'</div>
			</form>
			';
		//$fieldType = $getFieldGroup->inputGroup(['label'=>'Field Type','type'=>'select','id'=>'codebook','name'=>'field_type','meta_key'=>'field_type','meta'=>'codebook','placeholder'=>'Field Type','value'=>$getValue[0]['field_type']]);
		$scriptJS = "<script>
				$('select.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$('input[btnblur]').on('keyup',function () {
					termID = $(this).attr('termID');
					btnName = $(this).attr('btnBlur');
					toBlur(btnName,termID);
				});
			</script>";
		echo $formField.$scriptJS;
	}

	public function optionForm(){
		$output = "";
		$tableFields = array_merge(['alias'],$this->getTableFields(['table'=>$this->Params['theTable'],'exclude'=>['date','user'],'schema'=>Info::DB_SYSTEMS]));
		$stmtOptions = ["schema"=>Info::DB_SYSTEMS,"table"=>$this->Params['theTable'],"arguments"=>['id'=>$this->Params['theID']],"pdoFetch"=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,"fields"=>$tableFields];
		$getOptions = $this->selectDB($stmtOptions);
		$getKey = key($getOptions);
		unset($getOptions[$getKey]->value);
		$defaultFields = ["action"=>"create{$this->Params['theTable']}","theID"=>$getOptions[$getKey]->id,"table"=>$this->Params['theTable']];
		$output .= "<form id='create{$this->Params["theTable"]}' data-toggle='validator' name='{$getOptions[$getKey]->id}' class='form-label-left input_mask' novalidate>";
		foreach($defaultFields as $inputName => $inputValue){ // DISPLAY HIDDEN/DEFAULT FIELDS
			$output .= $this->input(['type'=>'hidden','id'=>$inputName,'name'=>$inputName,'value'=>$inputValue]);
		}
		unset($getOptions[$getKey]->id,$getOptions[$getKey]->status,$getOptions[$getKey]->path,$getOptions[$getKey]->tokens);
		//var_dump($getOptions);
		$dataType = "options";
		foreach($getOptions as $groupAlias => $groupInfo){
			$fieldBox = $this->fieldBox($groupAlias,$groupInfo,$dataType);
			$output .= "<div class='x_panel no-padding systemForm' id='{$groupAlias}'><div class='box_title'><h2 class='left'>Field Settings</h2><ul class='nav navbar-right panel_toolbox'><li class='right'><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li></ul></div><div class='x_content no-padding'>{$fieldBox}</div></div>";
		}

		$output .= "</form>";
		$output .= "
		<script>
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$('input[btnblur]').on('keyup',function () {
					termID = $(this).attr('termID');
					btnName = $(this).attr('btnBlur');
					toBlur(btnName,termID);
				});
				$('.modal.'+modalBox+' .modal-footer #saveBtn').attr('onclick','saveOption(\'create{$this->Params['theTable']}\')');
			</script>
		";
		return $output;
	}

	public function getTableListings($dataParams){
		//$infoYear = self::YEAR;
		//$value = 'id','type','code','title','description','status';
		$optionTbl = $listData = $tdLists = "";
		switch($this->Params['pageName']){
			default: $optionTbl = $this->Params['pageName']." | ".$this->Params['typeList']; break;
			
			case "lists-".$this->Params['typeList']: // METHOD-RECORDS
				$loanTypesArray = [];
				$amountFields = ["amount","balance","loan_granted","payment_principal","payment_interest","payment_penalty"];
				$isCompletedActivities = explode(",", Info::COMPLETED_ACTIVITIES);
				$activityFields = array_keys($dataParams['row_fields']);
				$dataLists = $dataParams['data_lists'];
				//var_dump($dataLists);
				$stmtActivity = ["schema"=>Info::DB_SYSTEMS,"table"=>"activity","arguments"=>['path >'=>$dataParams['path_id']],"pdoFetch"=>PDO::FETCH_KEY_PAIR,"fields"=>['id','name']]; // EXTRACT PATH ACTIVITIES
				$getActivity = self::selectDB($stmtActivity);

				foreach($dataLists as $dataID => $dataDetail){ // DISPLAY DETAILS IN TD
					$listData = $trLoanID = $customAttr = $dataValue = "";
					$dateCreated = date_format(date_create($dataDetail->date_created), 'd M Y');
					foreach($activityFields as $field){
						$classType = "";
						$dataValue = $dataDetail->$field;
						switch($field){
							case "cash_trans": // cash_option
								$dataValue = $_SESSION['codebook']['cash_option'][$dataValue]->meta_value;
							break;
							case "client_id":
								$dataValue = $this->formatValue(['prefix'=>$dataDetail->client_type,'id'=>$dataValue],"client_id");
							break;
							case "loans_id":
								$dataValue = $this->formatValue(['prefix'=>7,'id'=>$dataValue],"app_id");
							break;
							case "savings_id":
								$savingsInfo = explode(":",$dataDetail->savings_info);
								$dataValue = $savingsInfo[1];
							break;
							case "mem_info": case "savings_info": case "loans_info": case "recipient_info":
								$memInfo = explode(":",$dataValue);
								$dataValue = $memInfo[0];
							break;
							case "share_value":
								$dataValue = $dataValue." shares";
							break;
							case "loan_id":
								$trLoanID = $dataValue;
								if(!EMPTY($dataValue)) $dataValue = str_pad($dataValue, 8, '0', STR_PAD_LEFT);
							break;
							case "willingness_pay":
								$toolTipTitle = "";
								if(!empty($_SESSION['codebook'][$field][$dataValue]->meta_value)){
									$dataValueCodebook = $_SESSION['codebook'][$field][$dataValue]->meta_value;
									$last_word_start = strrpos($dataValueCodebook, ' ') + 1; // +1 so we don't include the space in our result
									$toolTipTitle = substr($dataValueCodebook, $last_word_start, - 7);
								}
								$customAttr = "data-toggle='tooltip' data-placement='right' data-original-title='{$toolTipTitle}'";
							break;
						}
						if(in_array($field,$dataParams['codebook_fields']) && !empty($_SESSION['codebook'][$field][$dataValue]->meta_value)) $dataValue = $_SESSION['codebook'][$field][$dataValue]->meta_value;
						$classAmount = "";
						if(in_array($field,$amountFields)){ // IF CURRENCY
							$dataAmount = $dataValue;
							$dataValue = number_format($dataAmount, 2);
							$classType = "currency-format";
							if($dataValue < 0){
								$classAmount = "negative";
								$dataValue = number_format(abs($dataAmount), 2);
							}
						}
						if(EMPTY($dataValue)) $dataValue = Info::EMPTY_VAL;
						$dataSort = substr($this->cleanString($dataValue), 0, 16);
						$listData .= "<td class='paddingHorizontal {$field} {$classType} {$classAmount}' data-sort='{$dataSort}' {$customAttr}><span class='ellipsis'>{$dataValue}</span></td>";
					}
					//$unitID = str_pad($dataDetail->unit, 2, '0', STR_PAD_LEFT);
					$transID = $this->formatValue(['prefix'=>$dataParams['path_id'],'id'=>$dataID],"app_id");
					//$transID = str_pad($dataID, 6, '0', STR_PAD_LEFT);
					$unitValue = (!EMPTY($_SESSION['codebook']['unit'][$dataDetail->unit]->meta_value)) ? $_SESSION['codebook']['unit'][$dataDetail->unit]->meta_value : "";
					//$viewBtn = "<button id='viewPost' name='postData{$dataID}' type='button' value='{$dataID}' class='btn btn-primary popupBtn editGroups'><i class='fa fa-share-square-o'></i></button>";
					//<td class='alignCenter no-padding bold'>{$viewBtn}</td>
					$activityTitle = ($dataDetail->activity_id) ? $getActivity[$dataDetail->activity_id] : "Cancelled";
					$tdLists = "<td class='alignCenter date no-padding' data-sort='".$dataDetail->date_created."'><span>{$dateCreated}</span></td><td class='trans_num alignCenter no-padding' data-sort='".$transID."'>".$this->formatValue(['prefix'=>$dataParams['path_id'],'id'=>$dataID],"app_id")."</td>{$listData}<td class='unit'><span class='ellipsis'>{$unitValue}</span></td><td class='activity'><span class='ellipsis".(in_array($dataDetail->activity_id, $isCompletedActivities) ? ' approved' : '')."'>{$activityTitle}</span></td>";
					
					// if($dataParams['path_id'] == "7"){
						// $fieldValue = $dataDetail->payment_form;
						// $loanID = $dataID;
						// $tdLists .= "<td class='no-padding' style='z-index:9999'><input id='valueBtn' name='payment_form-{$dataValue}' type='checkbox' ".($fieldValue < 2 ? "checked" : "")." class='js-switch' params='{\"table\":\"path_7_loans_details\",\"schema\":\"".Info::DB_DATA."\",\"action\":\"autoSave\",\"field\":\"payment_form\",\"id\":\"{$loanID}\"}' value='{$fieldValue}' onchange='autoSave(this)' /></td>";
					// }
					
					$optionTbl .= "<tr id='{$dataID}' {$trLoanID}>{$tdLists}</tr>";
				} // END FOREACH
				
			break;
			case 'settings-options':
				//$dataParams = ["metaType"=>$metaType,"metaValue"=>$metaValue,"getFields"=>$getFields,"getCodeMeta"=>$getCodeMeta];
				$metaType = (isset($dataParams["metaType"]) && $dataParams["metaType"] != "") ? $dataParams["metaType"] : "";
				$metaValue = (isset($dataParams["metaValue"]) && $dataParams["metaValue"] != "") ? $dataParams["metaValue"] : [];
				$getFields = (isset($dataParams["getFields"]) && $dataParams["getFields"] != "") ? $dataParams["getFields"] : [];
				$getCodeMeta = (isset($dataParams["getCodeMeta"]) && $dataParams["getCodeMeta"] != "") ? $dataParams["getCodeMeta"] : [];
				if($this->parseType){
					$this->editable = "editable"; $this->readonly = "readonly='readonly'";
					$dataValueID = $metaValue->id;
					$dataValueMetaID = $metaValue->meta_id;
					$dataValueMetaOption = $metaValue->meta_option;
					$dataValueMetaValue = $metaValue->meta_value;
				}else{
					$this->editable = ""; $this->readonly = "";
					$dataValueID = $metaValue["id"];
					$dataValueMetaID = $metaValue["meta_id"];
					$dataValueMetaOption = $metaValue["meta_option"];
					$dataValueMetaValue = $metaValue["meta_value"];

				}
				$colSpanMetaValue = "colspan='2'"; $tdMetaCodeBook = "";

				$inputMetaID = $this->inputGroup(['type'=>'text','id'=>"type-".$dataValueMetaID,'name'=>"{$metaType}-meta_id-{$dataValueID}",'title'=>'','meta_key'=>'','meta'=>'codebook','placeholder'=>'','value'=>$dataValueMetaID,'custom'=>($this->editable ? 'readonly="readonly" onchange="autoSave(this)" params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codebook","action":"autoSave","field":"meta_id","id":"'.$dataValueID.'"}\'' : '')]);
				$inputMetaOption = $this->inputGroup(['type'=>'text','id'=>"type-".$dataValueMetaOption,'name'=>"{$metaType}-meta_option-{$dataValueID}",'title'=>'','meta_key'=>'','meta'=>'codebook','placeholder'=>'','value'=>$dataValueMetaOption,'custom'=>($this->editable ? 'readonly="readonly" onchange="autoSave(this)" params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codebook","action":"autoSave","field":"meta_option","id":"'.$dataValueID.'"}\'' : '')]);
				$inputMetaValue = $this->inputGroup(['type'=>'text','id'=>"type-".$dataValueMetaValue,'name'=>"{$metaType}-meta_value-{$dataValueID}",'title'=>'','meta_key'=>'','meta'=>'codebook','placeholder'=>'','value'=>$dataValueMetaValue,'custom'=>($this->editable ? 'readonly="readonly" onchange="autoSave(this)" params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codebook","action":"autoSave","field":"meta_value","id":"'.$dataValueID.'"}\'' : '')]);
				
				$inputRemoveBtn = $this->input(['type'=>'button','id'=>'deleteBtn','name'=>'removeBtn'.$dataValueID,'value'=>1,'class'=>'btnDelete','custom'=>'meta="'.$metaType.'" onclick="autoSave(this)" params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codebook","action":"autoSave","field":"","id":"'.$dataValueID.'"}\'']);
				
				if($metaType && $getFields && isset($getFields[$metaType])){
					$colSpanMetaValue = $removeBtn = "";
					if(isset($metaValue->id)){
						$getKeyParent = (isset($getFields[$metaType])) ? $getFields[$metaType] : "";
						$getCodeMetaID = (isset($getCodeMeta[$metaValue->id]->id)) ? $getCodeMeta[$metaValue->id]->id : "";
						$inputParams = 'params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codemeta","action":"autoSave","field":"meta_parent","extra_fields":{"key_value":"'.$metaType.'","key_parent":"'.$getKeyParent.'","meta_value":'.$metaValue->id.'},"id":"'.$getCodeMetaID.'"}\'';
					}else{
						$inputParams = 'params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"codemeta","action":"autoSave","field":"meta_parent","id":"'.$getCodeMeta[$dataValueMetaID]->id.'"}\'';
					}
					//$codeMetaID = $getCodeMeta[$dataValueMetaID]->id;
					$getCodeMetaParentID = (isset($getCodeMeta[$metaValue->id]->meta_parent)) ? $getCodeMeta[$metaValue->id]->meta_parent : "";
					$metaCodeBook = $this->inputGroup(['type'=>'select','id'=>'meta_id','name'=>'code_meta'.$dataValueMetaID,'meta_key'=>$getKeyParent,'meta'=>'codebook','placeholder'=>'Select Value','value'=>$getCodeMetaParentID,'custom'=>'onchange="autoSave(this)" '.$inputParams]);
					$tdMetaCodeBook = "<td class='alignLeft no-padding'>{$metaCodeBook}{$inputRemoveBtn}</td>";
				}else{
					$removeBtn = $inputRemoveBtn;
				}
				
				$optionTd = "<td class='alignCenter no-padding'>{$inputMetaID}</td><td class='alignLeft no-padding'>{$inputMetaOption}</td><td {$colSpanMetaValue} class='alignLeft no-padding'>{$inputMetaValue}{$removeBtn}</td>{$tdMetaCodeBook}";

				$optionTbl .= "<tr id='meta_{$dataValueID}' class='editable_meta'>{$optionTd}</tr>";
				break;
			case 'info-settings':
				$infoDetails = $dataParams["info"];
				$optionTbl .= "<div class='x_panel no-padding'><div class='x_content no-padding'><div class='x_title'><h2>System Information<span class='subTitle'>|| General details and information</span></h2><ul class='nav navbar-right panel_toolbox'><li class='right'><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li></ul></div>";
				$optionTbl .= "<div class='row no-padding'>";
				//$optionTbl .= '<div id="pageElement" class="row boxPadding">';
				$infoLists = ['companyname'=>'Company Name','emailaddress'=>'Email Address','contactnumber'=>'Contact Info','theAddress'=>'Address','companySlogan'=>'Slogan','companywebsite'=>'Website'];
				 foreach($infoLists as $fieldName => $fieldTitle){
				 $thisInput = $this->inputGroup(['label'=>$fieldTitle,'type'=>'text','id'=>$fieldName,'name'=>$fieldName,'title'=>'','meta_key'=>'role','meta'=>'codebook','placeholder'=>'','value'=>$infoDetails[$fieldName]->option_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":"'.Info::DB_NAME.'","table":"options","action":"autoSave","field":"option_value","id":"'.$infoDetails[$fieldName]->id.'"}\'']);
				 $optionTbl .= '<div id="" class="form-group half no-padding edit item">'.$thisInput.'</div>'; // $inputObj
				 }
				$optionTbl .= '</div></div></div>';
				
				$amountLevel = $dataParams["approval_level"];
				$optionTbl .= "<div class='x_panel no-padding'><div class='x_title'><h2>Approval Level Amount<span class='subTitle'>|| Amount level for approval</span></h2><ul class='nav navbar-right panel_toolbox'><li class='right'><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li></ul></div><div class='x_content no-padding'>";
				$optionTbl .= "<div class='row no-padding'>";
				foreach($amountLevel as $fieldName => $fieldValue){
					$thisInput = $this->inputGroup(['label'=>$fieldName,'type'=>'amount','id'=>$fieldName,'name'=>$fieldName,'title'=>'','meta_key'=>$fieldName,'meta'=>'codebook','placeholder'=>'','value'=>$fieldValue->value,'custom'=>'onchange="autoSave(this)" data-number-to-fixed="2" params=\'{"schema":"'.Info::DB_SYSTEMS.'","table":"methods","action":"autoSave","field":"value","id":"'.$fieldValue->id.'"}\'']);
					$optionTbl .= '<div id="" class="form-group half no-padding amount edit item">'.$thisInput.'</div>'; // $inputObj
				}
				$optionTbl .= '</div></div></div>';
				break;

			case "options-".$this->Params['typeList']: // METHOD-LISTS
				$dataLists = $dataParams['data_lists'];
				$headLists = $dataParams['head_lists'];
				$popupAction = "view".$this->Params['typeList'];
				
				switch($this->Params["object"]){
					case "activity": case "workflow":
						$sessionPath = $_SESSION["path"];
						$stmtTokens = ['schema'=>Info::DB_SYSTEMS,'table'=>'tokens','arguments'=>['id>'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['id','name']];
						$getTokens = $this->selectDB($stmtTokens);
					break;
					case "path":
						$sessionGroups = json_decode(json_encode($_SESSION["groups"]),true);
					break;
					case "fields":
						$stmtFieldType = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>'field_type'],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['meta_id','meta_value']];
						$getFieldType = $this->selectDB($stmtFieldType);
					break;
				}
				
				foreach($dataLists as $alias => $detail){
					$tdLists = "";
					$cnt = 0;
					foreach($headLists as $field){
						if($cnt > 0){ // HIDE ALIAS
							$fieldValue = ($field == "alias") ? $alias : $detail->$field;
							switch($field){
								case "path": // ACTIVITY
									$fieldValue = (isset($sessionPath[$fieldValue]->name)) ? $sessionPath[$fieldValue]->name : Info::EMPTY_VAL;
								break;
								case "tokens": // ACTIVITY
									if(is_numeric($fieldValue)){
										$fieldValue = $getTokens[$fieldValue]->name;
									}else{
										$fieldValues = explode(",",$fieldValue);
										$getFieldValues = [];
										foreach($fieldValues as $value){
											$getFieldValues[] = $getTokens[$value]->name;
										}
										$fieldValue = implode(", ",$getFieldValues);
									}
								break;
								case "groups": // PATH
									$alias = $this->array_recursive_search_key_map($fieldValue,$sessionGroups);
									$fieldValue = (isset($alias[0])) ? $sessionGroups[$alias[0]]['name'] : Info::EMPTY_VAL;
								break;
								case "field_type": // FIELDS
									$fieldValue = $getFieldType[$fieldValue]->meta_value;
								break;
							}
							if($field == "status"){
								$tdLists .= "<td class='no-padding'><input id='statusBtn' name='{$this->Params['view']}_status-{$detail->id}' type='checkbox' ".($fieldValue > 0 ? "checked" : "")." class='js-switch' params='{\"table\":\"{$this->Params['object']}\",\"schema\":\"".Info::DB_SYSTEMS."\",\"action\":\"autoSave\",\"field\":\"status\",\"id\":\"{$detail->id}\"}' value='{$fieldValue}' onchange='autoSave(this)' /></td>";
							}else{
								$tdLists .= "<td meta-alias='{$this->Params['typeList']}[{$detail->id}]:{$field}' class=''><span class='ellipsis'>{$fieldValue}</span></td>";
							}
							
						}
						$cnt++;
					}
					if($tdLists){ // HIDE ALIAS
						$viewBtn = "";
						if(isset($dataParams['link_type']) && $dataParams['link_type'] != ""){
							$linkType = $dataParams['link_type'];
							$pageAlias = (is_array($alias)) ? $alias[0] : $alias; 
							$viewBtn = "<td class='alignCenter no-padding bold'><button type='button' value='{$detail->id}' class='btn btn-primary popupBtn editGroups' onclick='window.location.href=\"".Info::URL."/methods?module_type=systems&view={$linkType}&object={$this->Params["object"]}&alias={$pageAlias}\"'><i class='fa fa-code'></i></button></td>";
						}

						$popUpBtn = "<button type='button' onclick='getPopup(this,\"{$this->Params['typeList']}\",\"{$popupAction}\")' value='{$detail->id}' class='btn btn-primary popupBtn' data-toggle='modal' data-target='.{$popupAction}'><i class='fa fa-edit'></i></button>";
						$optionTbl .= "<tr>{$tdLists}{$viewBtn}<td class='alignCenter no-padding bold'>{$popUpBtn}</td></tr>";
					}
				}
				break;
			// case 'profile-page':
			// $this->Params['pageName'] = "objectives-page";
			// self::getTableListings($dataParams);
			// break;
			case 'users-page':
				$dataFields = $dataParams['row_fields'];
				$dataLists = $dataParams['data_lists'];

				foreach($dataLists as $dataID => $dataDetail){ // DISPLAY DETAILS IN TD
					$listData = "";
					$dateCreated = (isset($dataDetail->date_created)) ? date_format(date_create($dataDetail->date_created), 'd M Y') : "";
					foreach($dataFields as $field){  // DISPLAY DETAILS IN GROUP FIELDS
						$this->pageAction = "";
						$dataDetailField = (isset($dataDetail->$field)) ? $dataDetail->$field : "";
						if($field == "id"){
							$getFieldData = $dataID;
						}elseif($field == "unit"){
							$sessionCodeUnitValue = (isset($_SESSION['codebook']['unit'][$dataDetailField]->meta_value)) ? $_SESSION['codebook']['unit'][$dataDetailField]->meta_value : "";
							$getFieldData = "<div class='paddingHorizontal'>{$sessionCodeUnitValue}</div>";
						}elseif($field == "role"){
							$sessionCodeRoleValue = (isset($_SESSION['codebook']['role'][$dataDetailField]->meta_value)) ? $_SESSION['codebook']['role'][$dataDetailField]->meta_value : "";
							$getFieldData = "<div class='paddingHorizontal'>{$sessionCodeRoleValue}</div>";
						}elseif($field == "status"){
							$getFieldData = "<input id='statusBtn' name='{$this->Params['view']}_status-{$dataID}' type='checkbox' ".($dataDetailField > 0 ? "checked" : "")." class='js-switch' params='{\"table\":\"{$this->Params['view']}\",\"schema\":\"".Info::DB_SYSTEMS."\",\"action\":\"autoSave\",\"field\":\"status\",\"id\":\"{$dataID}\"}' value='{$dataDetailField}' onchange='autoSave(this)' />";
						}else{
							$customAttr = "params='{\"table\":\"{$this->Params['view']}\",\"schema\":\"".Info::DB_SYSTEMS."\",\"action\":\"autoSave\",\"field\":\"{$field}\",\"id\":\"{$dataID}\"}' onchange='autoSave(this)'"; // readonly='readonly'
							//if($tblKey == 'id_number') $customAttr .= " data-inputmask=\"'mask': '99-999999'\"";
							$tblField = "<span class='hide'>{$dataDetailField}</span>";
							if($field == "username"){
							    $this->pageAction = "view";
							    $customAttr = "";
							}
							$getFieldData = $this->inputGroup(["type"=>"text","id"=>$field."-".$dataID,"name"=>"{$this->Params['view']}_{$field}-{$dataID}","placeholder"=>"{$field}","value"=>"{$dataDetailField}","title"=>"{$field}","custom"=>$customAttr]);
							//$getFieldData = $dataDetailField;
						}
						$listData .= "<td class='bold no-padding' data-sort='{$field}'>{$getFieldData}</td>";
					}
					$unitID = (isset($dataDetail->unit)) ? str_pad($dataDetail->unit, 2, '0', STR_PAD_LEFT) : "";
					$transID = str_pad($dataID, 6, '0', STR_PAD_LEFT);
					$popUpBtn = '<button type="button" onclick="getPopup(this,\'users\',\'viewusers\')" value="'.$dataID.'" class="btn btn-primary popupBtn" data-toggle="modal" data-target=".viewusers"><i class="fa fa-edit"></i></button>';
					//$popUpEditBtn = "<button type='button' title='Update Employee Profile' value='{$dataID}' id='{$this->Params['view']}Btn' name='create{$this->Params['view']}-{$dataID}' handle='createRecords' action='popupBox' table='{$this->Params['view']}' class='btn edit popupBtn' onclick=\"getPopup(this,'viewUsers')\" data-toggle='modal' data-target='.popup-viewUsers'><i class='fa fa-edit'></i></button>";
					//$viewBtn = "<button id='viewPost' name='postData{$dataID}' type='button' value='{$dataID}' class='btn btn-primary popupBtn editGroups'><i class='fa fa-share-square-o'></i></button>";
					$tdLists = "{$listData}<td class='alignCenter no-padding bold'>{$popUpBtn}</td>";
					$optionTbl .= "<tr>{$tdLists}</tr>";
				}
				break;

			case 'settings-general':
				$formField = "";
				switch($dataParams['type']){
					case 'pms':
						$stmtMeta = ['schema'=>Info::DB_NAME,'table'=>'options','arguments'=>['option_meta'=>'info'],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'fields'=>['option_name','id','option_value']];
						$getStmtMeta = self::selectDB($stmtMeta);

						$stmtOptionAdjectival = ['schema'=>Info::DB_SYSTEMS,'table'=>'meta_terms','arguments'=>['meta_key'=>'adjectival'],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'fields'=>['meta_id','id','meta_option','meta_value']];
						$getOptionAdjectival = self::selectDB($stmtOptionAdjectival);

						$inputSem = $this->inputGroup(['label'=>'Rating Period','type'=>'number','id'=>'sem','name'=>'sem','title'=>'Settings','placeholder'=>'Semester','value'=>$getStmtMeta['sem'][0]->option_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_NAME,"table":"options","action":"autoSave","field":"option_value","id":"'.$getStmtMeta['sem'][0]->id.'"}\'']);
						$inputPeriod = $this->inputGroup(['type'=>'number','id'=>'period','name'=>'period','title'=>'Settings','placeholder'=>'Period','value'=>$getStmtMeta['period'][0]->option_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_NAME,"table":"options","action":"autoSave","field":"option_value","id":"'.$getStmtMeta['period'][0]->id.'"}\'']);

						$valuePoor = $this->inputGroup(['label'=>'POOR','type'=>'text','id'=>'valuePoor','name'=>'valuePoor','title'=>'Title Poor','placeholder'=>'Title Poor','value'=>$getOptionAdjectival[1][0]->meta_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_value","id":"'.$getOptionAdjectival[1][0]->id.'"}\'']);
						$adjectivalPoor = $this->inputGroup(['type'=>'text','id'=>'adjectivalPoor','name'=>'adjectivalPoor','title'=>'Adjectival Poor','placeholder'=>'Adjectival Poor','value'=>$getOptionAdjectival[1][0]->meta_option,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_option","id":"'.$getOptionAdjectival[1][0]->id.'"}\'']);

						$valueFair = $this->inputGroup(['label'=>'FAIR','type'=>'text','id'=>'$valueFair','name'=>'$valueFair','title'=>'Title Fair','placeholder'=>'Title Fair','value'=>$getOptionAdjectival[2][0]->meta_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_value","id":"'.$getOptionAdjectival[2][0]->id.'"}\'']);
						$adjectivalFair = $this->inputGroup(['type'=>'text','id'=>'adjectivalFair','name'=>'adjectivalFair','title'=>'Adjectival Fair','placeholder'=>'Adjectival Fair','value'=>$getOptionAdjectival[2][0]->meta_option,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_option","id":"'.$getOptionAdjectival[2][0]->id.'"}\'']);

						$valueGood = $this->inputGroup(['label'=>'GOOD','type'=>'text','id'=>'valueGood','name'=>'valueGood','title'=>'Title Good','placeholder'=>'Title Good','value'=>$getOptionAdjectival[3][0]->meta_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_value","id":"'.$getOptionAdjectival[3][0]->id.'"}\'']);
						$adjectivalGood = $this->inputGroup(['type'=>'text','id'=>'adjectivalGood','name'=>'adjectivalGood','title'=>'Adjectival Good','placeholder'=>'Adjectival Good','value'=>$getOptionAdjectival[3][0]->meta_option,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_option","id":"'.$getOptionAdjectival[3][0]->id.'"}\'']);

						$valueExcellent = $this->inputGroup(['label'=>'EXCELLENT','type'=>'text','id'=>'valueExcellent','name'=>'valueExcellent','title'=>'Title Excellent','placeholder'=>'Title Excellent','value'=>$getOptionAdjectival[4][0]->meta_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_value","id":"'.$getOptionAdjectival[4][0]->id.'"}\'']);
						$adjectivalExcellent = $this->inputGroup(['type'=>'text','id'=>'adjectivalExcellent','name'=>'adjectivalExcellent','title'=>'Adjectival Excellent','placeholder'=>'Adjectival Excellent','value'=>$getOptionAdjectival[4][0]->meta_option,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_SYSTEMS,"table":"meta_terms","action":"autoSave","field":"meta_option","id":"'.$getOptionAdjectival[4][0]->id.'"}\'']);

						$formField .= "
							<div class='form-group no-padding item'>
								<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$inputSem}</div>
								<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$inputPeriod}</div>
							</div>
							<div class='form-group no-padding item'>
								<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$valuePoor}</div>
								<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$adjectivalPoor}</div>
							</div>
							<div class='form-group no-padding item'>
								<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$valueFair}</div>
								<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$adjectivalFair}</div>
							</div>
							<div class='form-group no-padding item'>
								<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$valueGood}</div>
								<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$adjectivalGood}</div>
							</div>
							<div class='form-group no-padding item'>
								<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$valueExcellent}</div>
								<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$adjectivalExcellent}</div>
							</div>
						";
						break;

					case 'general':
						$stmtMeta = ['schema'=>Info::DB_NAME,'table'=>'options','arguments'=>['option_meta'=>'info'],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'fields'=>['option_name','id','option_value']];
						$getStmtMeta = self::selectDB($stmtMeta);

						$metaName = [
							'companyname'=>'Business Name',
							'theAddress'=>'Address',
							'email'=>'Email Address',
							'contactnumber'=>'Contact Information',
							'companySlogan'=>'Slogan'
						];

						foreach($metaName as $optionKey => $optionValue){
							$inputField = $this->inputGroup(['label'=>$optionValue,'type'=>'text','id'=>$getStmtMeta[$optionKey][0]->option_name,'name'=>$getStmtMeta[$optionKey][0]->option_name,'title'=>'Settings','placeholder'=>'','value'=>$getStmtMeta[$optionKey][0]->option_value,'custom'=>'onchange="autoSave(this)" params=\'{"schema":Info::DB_NAME,"table":"options","action":"autoSave","field":"option_value","id":"'.$getStmtMeta[$optionKey][0]->id.'"}\'']);
							$formField .= "
								<div class='form-group no-padding item'>
									<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$inputField}</div>
								</div>
							";
						}
						break;
				}

				$optionTbl = "
				<div class='boxCell elementBox generalSettings edit'>{$formField}</div>
				";
				break;

			case 'settings-kra':
				$this->editable = "editable"; $this->readonly = "readonly='readonly'";

				$metaID = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['id']}","placeholder"=>"meta","value"=>"{$dataParams['meta_id']}","title"=>"Meta ID","custom"=>"params='{\"table\":\"kra\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"meta_id\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
				$metaOption = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['title']}","title"=>"Meta Alias","custom"=>"params='{\"table\":\"kra\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"title\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				$metaValue = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['weight']}","title"=>"Meta Name/Title","custom"=>"params='{\"table\":\"kra\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"weight\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = "
			        <td class='alignCenter no-padding'><span class='inputPrefix'>{$metaID}</td><td class='alignLeft no-padding'>{$metaOption}</td><td colspan='2' class='alignLeft no-padding'>{$metaValue}</td>
                ";
				break;

			case 'settings-optionsxxx':
				$this->editable = "editable"; $this->readonly = "readonly='readonly'";

				$metaID = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['meta_key']}-{$dataParams['id']}","placeholder"=>"meta","value"=>"{$dataParams['meta_id']}","title"=>"Meta ID","custom"=>"params='{\"table\":\"meta_terms\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"meta_id\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
				$metaOption = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['meta_key']}-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['meta_option']}","title"=>"Meta Alias","custom"=>"params='{\"table\":\"meta_terms\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"meta_option\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				$metaValue = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"meta_id-{$dataParams['meta_key']}-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['meta_value']}","title"=>"Meta Name/Title","custom"=>"params='{\"table\":\"meta_terms\",\"action\":\"autoSave\",\"schema\":\"{$this->schema}\",\"field\":\"meta_value\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = "
			        <td class='alignCenter no-padding'><span class='inputPrefix'>{$metaID}</td><td class='alignLeft no-padding'>{$metaOption}</td><td colspan='2' class='alignLeft no-padding'>{$metaValue}</td>
                ";
				break;

			case 'holidays-page':
				$tblRow = "";
				//$dataParams = 'id','type','code','title','description','status';
				$codebookFields = ['holiday_type','set_date'];
				$cnt = 0;
				foreach($this->tblCol as $tblKey => $tblValue){
					$dataSort = substr($this->cleanString($dataParams[$tblKey]), 0, 16);
					$this->editable = "editable"; $this->readonly = "readonly='readonly'";
					$type = "text";
					$alignClass = "alignLeft";
					if($tblKey == 'set_date'){
						$alignClass = "alignCenter";
						$setDate = date_create($dataParams[$tblKey]);
						$setDate = date_format($setDate, 'F d');
						$dataParams[$tblKey] = $setDate;
					}
					if(in_array($tblKey, $codebookFields)){
						$this->editable = "";
						$tblField = "<span>{$dataParams[$tblKey]}</span>";
					}else{
						$tblField = "<span class='hide'>{$dataParams[$tblKey]}</span>";
						$tblField .= $this->inputGroup(["type"=>$type,"id"=>$tblKey."-".$dataParams['id'],"name"=>"{$this->Params['view']}_{$tblKey}-{$dataParams['id']}","placeholder"=>"{$tblValue[0]}","value"=>"{$dataParams[$tblKey]}","title"=>"{$tblValue[0]}","custom"=>"params='{\"table\":\"{$this->Params['view']}\",\"action\":\"autoSave\",\"field\":\"{$tblKey}\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
					}
					$tblRow .= "<td class='{$alignClass} no-padding' data-sort='{$dataSort}'>{$tblField}</td>";
					$cnt++;
				}
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = $tblRow;
				break;
			case 'salary_set-page':
				$tblRow = "";
				$codebookFields = ['salary_type'];
				$cnt = 0;
				foreach($this->tblCol as $tblKey => $tblValue){
					$dataSort = substr($this->cleanString($dataParams[$tblKey]), 0, 16);
					$this->editable = "editable"; $this->readonly = "readonly='readonly'";
					$type = "text";
					if($tblKey == 'amount'){
						$type = "amount";
					}
					if(in_array($tblKey, $codebookFields)){
						$this->editable = "";
						$tblField = "<span class='ellipsis paddingLeft'>{$dataParams[$tblKey]}</span>";
					}else{
						$tblField = "<span class='hide'>{$dataParams[$tblKey]}</span>";
						$tblField .= $this->inputGroup(["type"=>$type,"id"=>$tblKey."-".$dataParams['id'],"name"=>"{$this->Params['view']}_{$tblKey}-{$dataParams['id']}","placeholder"=>"{$tblValue[0]}","value"=>"{$dataParams[$tblKey]}","title"=>"{$tblValue[0]}","custom"=>"params='{\"table\":\"{$this->Params['view']}\",\"action\":\"autoSave\",\"field\":\"{$tblKey}\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
					}
					$tblRow .= "<td class='alignLeft no-padding' data-sort='{$dataSort}'>{$tblField}</td>";
					$cnt++;
				}
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = "<td class='alignCenter' data-sort='{$dataParams['id']}'>{$dataParams['id']}</td>{$tblRow}";
				break;

			case 'payroll_logs-page':
				$tblRow = $optionTbl = "";
				//$dataParams = 'id','type','code','title','description','status';
				$stmtStaff = ['schema'=>Info::DB_NAME,'table'=>'staffs','arguments'=>['id'=>$dataParams[0]->staff_id],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>['bio_id','id_number','employment_level','first_name','middle_name','last_name','gender','unit','designation']];
				$getStaff = self::selectDB($stmtStaff);
				if($getStaff){
					$codebookFields = ['unit','designation'];
					$cnt = 0;
					//$popUpEditBtn = "<button type='button' title='Create New Logs' value='{$dataParams['value']}' id='{$this->Params['table']}LogsBtn' name='create{$this->Params['table']}Logs' handle='createLogRecords' action='popupBox' table='{$this->Params['table']}_logs' class='btn add paddingHorizontal' onclick=\"getPopup(this,'console_{$this->Params['table']}')\" data-toggle='modal' data-target='.popup-console_{$this->Params['table']}'><i class='fa fa-plus'></i></button>";
					$popUpEditBtn = "<button type='button' title='Create New Logs' value='' id='{$this->Params['table']}LogsBtn' name='create{$this->Params['table']}Logs' handle='createLogRecords' action='popupBox' table='{$this->Params['table']}_logs' class='btn popupBtn bgTeal paddingHorizontal' onclick=\"getPopup(this,'console_{$this->Params['table']}')\" data-toggle='modal' data-target='.popup-console_{$this->Params['table']}'><i class='fa fa-expand'></i></button>";

					$setCodebookValue = ['employment_level','gender','unit','designation'];
					foreach($setCodebookValue as $fieldKey){
						$stmtCodebook = ['schema'=>Info::DB_SYSTEMS,'table'=>'meta_terms','arguments'=>['meta_key'=>$fieldKey,'meta_id'=>$getStaff[0][$fieldKey]],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>['meta_key','meta_option','meta_value']];
						$getCodebook[$fieldKey] = self::selectDB($stmtCodebook);
					}
					//var_dump();
					$tblRow = "<td class='no-padding alignCenter bold'>".($getStaff[0]['id_number'] ? $getStaff[0]['id_number'] : '&ctdot;')."</td><td class='no-padding alignCenter bold'>".($getStaff[0]['bio_id'] > 0 ? $getStaff[0]['bio_id'] : '&ctdot;')."</td><td class='no-padding' data-sort='{$getStaff[0]['last_name']}'><span class='ellipsis text-view'>{$getStaff[0]['last_name']}, {$getStaff[0]['first_name']} {$getStaff[0]['middle_name']}</span></td><td class='no-padding'><span class='ellipsis text-view'>".(isset($getCodebook['employment_level'][0]['meta_value']) ? $getCodebook['employment_level'][0]['meta_value'] : '&ctdot;')."</span></td><td class='no-padding'><span class='ellipsis text-view'>".(isset($getCodebook['gender'][0]['meta_value']) ? $getCodebook['gender'][0]['meta_value'] : '&ctdot;')."</span></td><td class='no-padding'><span class='ellipsis text-view'>{$getCodebook['unit'][0]['meta_value']}</span></td><td class='no-padding'><span class='ellipsis text-view'>{$getCodebook['designation'][0]['meta_value']}</span></td><td class='no-padding'>{$popUpEditBtn}</td>";
					//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
					$optionTbl = "<tr>{$tblRow}</tr>";
				}
				break;

			case 'payroll-page':
				$tblRow = "";
				$codebookFields = ['date','payroll_set'];
				$cnt = 0;
				foreach($this->tblCol as $tblKey => $tblValue){
					$alignClass = "alignLeft";
					$dataSort = substr($this->cleanString($dataParams[$tblKey]), 0, 16);
					$this->editable = "editable"; $this->readonly = "readonly='readonly'";
					$this->editable = ($tblKey == 'title') ? "editable" : "";
					if(in_array($tblKey, $codebookFields)){
						$alignClass = "alignCenter";
						$this->editable = "";
						if($tblKey == 'date'){
							$setDate = date_create($dataParams[$tblKey]);
							$setDate = date_format($setDate, 'd M Y');
							$dataParams[$tblKey] = $setDate;
						}
						$tblField = "<span class='ellipsis paddingLeft'>{$dataParams[$tblKey]}</span>";
					}else{
						$tblField = "<span class='hide'>{$dataParams[$tblKey]}</span>";
						$tblField .= $this->inputGroup(["type"=>"text","id"=>$tblKey."-".$dataParams['id'],"name"=>"{$this->Params['view']}_{$tblKey}-{$dataParams['id']}","placeholder"=>"{$tblValue}","value"=>"{$dataParams[$tblKey]}","title"=>"{$tblValue}","custom"=>"params='{\"table\":\"{$this->Params['view']}\",\"action\":\"autoSave\",\"field\":\"{$tblKey}\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
					}
					$tblRow .= "<td class='{$alignClass} no-padding bold' data-sort='{$dataSort}'>{$tblField}</td>";
					$cnt++;
				}
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = $tblRow;
				break;
			case 'staffs-page':
				$tblRow = "";
				$colCenter = ['id_number','bio_id','employment_level'];
				$codebookFields = ['gender','designation','unit'];
				$cnt = 0;
				foreach($this->tblCol as $tblKey => $tblValue){
					$dataSort = substr($this->cleanString($dataParams[$tblKey]), 0, 16);
					$this->editable = "editable"; $this->readonly = "readonly='readonly'";
					$alignClass = (in_array($tblKey, $colCenter)) ? "alignCenter" : "alignLeft";
					if(in_array($tblKey, $codebookFields)){
						$this->editable = "";
						$dataParams[$tblKey] = ($dataParams[$tblKey]) ? $dataParams[$tblKey] : self::EMPTY_VAL;
						$tblField = "<span class='ellipsis paddingLeft'>{$dataParams[$tblKey]}</span>";
						// if(!$dataParams[$tblKey."Value"]) $dataParams[$tblKey."Value"] = "<span class='placeholder'>{$tblValue[0]}</span>";
						// $tblField = "<span class='ellipsis paddingLeft'>{$dataParams[$tblKey."Value"]}</span>";
					}else{
						$customAttr = "params='{\"table\":\"{$this->Params['view']}\",\"action\":\"autoSave\",\"field\":\"{$tblKey}\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'";
						if($tblKey == 'id_number') $customAttr .= " data-inputmask=\"'mask': '99-999999'\"";
						$tblField = "<span class='hide'>{$dataParams[$tblKey]}</span>";
						$tblField .= $this->inputGroup(["type"=>"text","id"=>$tblKey."-".$dataParams['id'],"name"=>"{$this->Params['view']}_{$tblKey}-{$dataParams['id']}","placeholder"=>"{$tblValue[0]}","value"=>"{$dataParams[$tblKey]}","title"=>"{$tblValue[0]}","custom"=>$customAttr]);
					}
					$tblRow .= "<td class='{$alignClass} no-padding' data-sort='{$dataSort}'>{$tblField}</td>";
					$cnt++;
				}
				$popUpEditBtn = "<button type='button' title='Update Employee Profile' value='{$dataParams['id']}' id='{$this->Params['view']}Btn' name='create{$this->Params['view']}-{$dataParams['id']}' handle='createRecords' action='popupBox' table='{$this->Params['view']}' class='btn edit popupBtn' onclick=\"getPopup(this,'{$this->modalBox}')\" data-toggle='modal' data-target='.popup-{$this->modalBox}'><i class='fa fa-edit'></i></button>";
				$tblRow .= "<td class='no-padding'>{$popUpEditBtn}</td>";
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = $tblRow;
				break;
			case 'settings-reportings':
				//$dataParams = 'id','type','code','title','description','status';
				$this->editable = "editable"; $this->readonly = "readonly='readonly'";
				$metaID = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"{$this->Params['view']}_metaID-{$dataParams['id']}","placeholder"=>"meta","value"=>"{$dataParams['meta_id']}","title"=>"Accounting Code","custom"=>"params='{\"table\":\"methods\",\"action\":\"autoSave\",\"field\":\"meta_id\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)' readonly='readonly'"]);
				$metaName = $this->inputGroup(["type"=>"text","id"=>"type-".$dataParams['meta_id'],"name"=>"{$this->Params['view']}_metaName-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['name']}","title"=>"Account Title","custom"=>"params='{\"table\":\"methods\",\"action\":\"autoSave\",\"field\":\"name\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				$metaValue = $this->inputGroup(["type"=>"textarea","id"=>"type-".$dataParams['meta_id'],"name"=>"{$this->Params['view']}_metaValue-{$dataParams['id']}","placeholder"=>"","value"=>"{$dataParams['value']}","title"=>"Account Description","custom"=>"params='{\"table\":\"methods\",\"action\":\"autoSave\",\"field\":\"value\",\"id\":\"{$dataParams['id']}\"}' onchange='autoSave(this)'"]);
				//$statusBtn = "<input id='statusBtn' name='statusBtn_{$dataParams['id']}' type='checkbox' ".($dataParams['status'] > 0 ? "checked" : "")." class='js-switch' meta=\"{'table':'{$this->Params['view']}','action':'updateMeta','field':'status','id':'{$dataParams['id']}'}\" value='{$dataParams['status']}' />";
				$optionTbl = "
			        <td class='alignCenter no-padding'><span class='inputPrefix'>{$metaID}</td><td class='alignLeft no-padding'>{$metaName}</td><td class='alignLeft no-padding'>{$metaValue}</td>
                ";
				break;

			case 'charts-page':
				$optionTbl = "fox";
				break;

		}

		$output = $optionTbl;
		return $output;
	}

	public function getExtras($theID,$arrayMetaValue,$getExtrasValue,$postParam){
		$jsComputeExtras = "";
		$cnt = 0; $inputAlign = 'alignRight'; $minMax = 'min="0"'; $colValue = [8,4]; $decimal = 2; $collateralQuality = ""; $fixedDecimal = "2"; $weightedValue = [];
		$output = '<form id="createExtras" data-toggle="validator" name="createExtras" class="form-label-left input_mask" novalidate><input type="hidden" name="action" id="action" value="createExtras" /><input type="hidden" name="theID" id="theID" value="'.$theID.'" /><input type="hidden" name="data_id" id="data_id" value="'.$postParam['dataID'].'" /><input type="hidden" name="score" id="extra_score" value="0" /><input type="hidden" name="table" id="table" value="data_extras" /><input type="hidden" name="meta_key" id="meta_key" value="'.$postParam['meta_key'].'" />';
		switch($postParam['meta_key']){
			case 'willingness':
				$decimal = 1;
				$colValue = [9,3];
				$inputAlign = 'alignCenter';
				$minMax = 'min="0" max="1"';
				$getWeightedValues = Info::value("willingness_value");
				break;
			case 'collateral':
				$decimal = 2;
				$colValue = [6,6];
				break;
		}
		foreach($getExtrasValue as $metaID){
			$cnt++; $metakeyValue = "";
			$methodMetaName = json_decode($metaID['name']);
			foreach ($methodMetaName as $fields => $metaValue) {
				foreach($metaValue as $metaName => $metaTotal) {
					$nameTitle = $metaName;
					$totalTitle = $metaTotal;
				}
			}
			$output .= '<div id="'.$metaID['meta_key'].$metaID['meta_id'].'" meta-cnt="'.$metaID['meta_id'].'" class="x_panel '.$metaID['meta_key'].' half no-padding" style="margin-top:3px"><div class="box_title"><h2 class="left">'.$nameTitle.'</h2></div>';
			$arrayCustomInformation = json_decode($metaID['value']);
			foreach ($arrayCustomInformation as $fields => $metaValue) {
				$cntMeta = 0;
				$thisAlign = $inputAlign;
				foreach($metaValue as $metaField => $metaValue) {
					$inputClass = $metaFieldValue = "";
					$cntMeta++; $typeNumber = true;
					if($arrayMetaValue){
						$metaFieldValue = (isset($arrayMetaValue[0]->$metaField) && $arrayMetaValue[0]->$metaField != '') ? $arrayMetaValue[0]->$metaField : ''; //number_format($arrayMetaValue[0]->$metaField,1)
					}
					
					if($postParam['meta_key'] == 'collateral' && $cntMeta < 2){ // INPUT TEXT VALUE
						$inputType = 'text';
						$typeNumber = false;
						$inputPlaceholder = 'Name/Description';
						$inputAlign = 'alignLeft text';
						$inputValue = $metaFieldValue;
						$metakeyValue = $metaField;
						$fixedDecimal = "";
					}else{
						//$metaFieldValue = 0;
						if($postParam['meta_key'] == 'willingness') $fixedDecimal = "1";
						if($postParam['meta_key'] == 'collateral'){
							$fixedDecimal = (($metaID['meta_id'] < 3 && $cntMeta < 3) || ($metaID['meta_id'] > 2 && $cntMeta > 2) || ($metaID['meta_id'] > 4)) ? "2" : "0";
						} 
						$inputAlign = $thisAlign." number";
						$inputType = 'text';
						$inputPlaceholder = 'Either 1 or 0';
						$inputValue = ($metaFieldValue) ? number_format($metaFieldValue,2) : 0;//sprintf("%.".$decimal."f",$metaFieldValue);
					}
					$onChange = ($typeNumber) ? 'onchange="computeExtras(\''.$metaID['meta_key'].$metaID['meta_id'].'\',\''.$metaID['meta_key'].'\',\''.$postParam['alias'].'\')"' : '';
					if($postParam['meta_key'] == 'willingness'){
						$inputValue = number_format($inputValue,0);
						$inputClass = " toggleBtn btn";
						if($inputValue > 0) $inputClass .= " active";
						$weightedValue = $getWeightedValues[$cnt][$metaField];
						$onChange = "weighted_value='{$weightedValue}' key_id='".$metaID['meta_key'].$metaID['meta_id']."'";
						//$inputField = '<input type="button" meta="'.$postParam['alias'].'" id="'.$metaField.'" name="'.$metaField.'" onclick="" value="'.$inputValue.'" class="btn" '.$onChange.'>';
					}
					$inputField = '<input meta="'.$postParam['alias'].'" '.$minMax.' value="'.$inputValue.'" type="'.$inputType.'" id="'.$metaField.'" name="'.$metaField.'" '.($fixedDecimal != "" ? 'data-number-to-fixed="'.$fixedDecimal.'"' : '').' placeholder="'.$inputPlaceholder.'" class="form-control '.$inputType.' '.$inputAlign.$inputClass.'" '.$onChange.'>';
					$output .= '<div class="form-group no-padding edit item"><label for="'.$metaField.'" class="paddingLeft number control-label col-md-'.$colValue[0].' col-sm-'.$colValue[0].' col-xs-12">'.$metaValue.'</label><div class="col-md-'.$colValue[1].' col-xs-12 no-padding type-number edit">'.$inputField.'</div></div>';
				}
			}
			$jsComputeExtras .= 'computeExtras("'.$metaID['meta_key'].$metaID['meta_id'].'","","'.$postParam['alias'].'");';

			switch($postParam['meta_key']){
				case 'ability':
					$output .= '<div class="form-group no-padding edit item"><label for="'.Info::value("tax_type")[$cnt][0].'" class="alignRight number bold control-label col-md-'.$colValue[0].' col-sm-'.$colValue[0].' col-xs-12">'.Info::value("tax_type")[$cnt][1].'</label><div class="col-md-'.$colValue[1].' col-xs-12 no-padding type-number edit"><input taxValue="'.Info::value("tax_type")[$cnt][2].'" placeholder="0.00" value="" type="text" name="'.Info::value("tax_type")[$cnt][0].'" id="tax_'.$metaID['meta_key'].$metaID['meta_id'].'" class="form-control '.$inputAlign.' bold '.Info::value("tax_type")[$cnt][0].'" readonly></div></div>';
					break;
				case 'collateral':
					//$getFieldGroup = new fieldGroup;
					$ratingQualityField = Info::value("rating_quality")[$cnt][0].$metaID['meta_id'];
					$collateralQuality = (isset($arrayMetaValue[0]->$ratingQualityField) && $arrayMetaValue[0]->$ratingQualityField) ? $arrayMetaValue[0]->$ratingQualityField : "";
					$selectRatingQuality = $this->inputGroup(['type'=>'select','id'=>Info::value("rating_quality")[$cnt][0].$metaID['meta_id'],'name'=>Info::value("rating_quality")[$cnt][0].$metaID['meta_id'],'meta_key'=>Info::value("rating_quality")[$cnt][0],'meta'=>'codebook','placeholder'=>'Rating Quality','value'=>$collateralQuality,'required'=>'onchange="computeExtras(\''.$metaID['meta_key'].$metaID['meta_id'].'\',\''.$postParam['meta_key'].'\',\''.$postParam['alias'].'\')"']);
					$output .= '<div id="collateralRating" class="form-group no-padding edit item"><label for="'.Info::value("rating_quality")[$cnt][0].$metaID['meta_id'].'" class="control-label col-md-'.$colValue[0].' col-sm-'.$colValue[0].' col-xs-12">'.Info::value("rating_quality")[$cnt][1].'</label><div class="col-md-'.$colValue[1].' col-xs-12 no-padding type-number edit">
                '.$selectRatingQuality.'
                </div></div>';
					if(in_array($metaID['meta_id'], range(3,4))){
						$subTotalTitle = "Total Selling Value";
					}elseif(in_array($metaID['meta_id'], range(5,6))){
						$subTotalTitle = "Value of Financial Securities";
					}else{
						$subTotalTitle = "Present Value";
					}
					$output .= '<div class="form-group no-padding edit item"><label for="" class="alignRight number bold control-label col-md-'.$colValue[0].' col-sm-'.$colValue[0].' col-xs-12">'.$subTotalTitle.'</label><div class="col-md-'.$colValue[1].' col-xs-12 no-padding type-number edit"><input  placeholder="0.00" value="" type="text" name="'.$metakeyValue.'_value" id="'.$metakeyValue.'_value" class="form-control '.$inputAlign.' bold" readonly></div></div>';
					break;
			}
			$labelOverall = ($postParam['meta_key'] == "collateral" || $postParam['meta_key'] == "willingness") ? 'overallTotal large' : '';
			$output .= '<div id="groupTotal_'.$metaID['meta_key'].'" class="form-group no-padding edit item '.$labelOverall.'"><label for="score_'.$metaID['meta_id'].'" class="alignRight bold number control-label col-md-'.$colValue[0].' col-sm-'.$colValue[0].' col-xs-12">'.$totalTitle.'</label><div class="col-md-'.$colValue[1].' col-xs-12 no-padding type-number edit"><input value="0.00" type="text" name="'.$metaID['meta_key'].$metaID['meta_id'].'" id="total_'.$metaID['meta_key'].$metaID['meta_id'].'" placeholder="5.0" class="form-control '.$inputAlign.' bold total_'.$metaID['meta_key'].'" readonly></div></div>';
			$output .= '</div>';
			
		}
		switch($postParam['meta_key']){
			case "ability":
				//$jsComputeExtras = "getScore = $('.modal-footer #attainedScore span#resultScore').text();alert(getScore);";
				//$jsComputeExtras = 'computeOverall("input.total_'.$postParam['meta_key'].'","'.$postParam['alias'].'")';
				$output .= '<div class="x_panel half no-padding overallTotal"><div id="" class="form-group no-padding edit item"><label for="score_4" class="alignRight uppercase bold number control-label col-md-8 col-sm-8 col-xs-12">Net Household Income</label><div class="col-md-4 col-xs-12 no-padding type-number edit"><input value="" type="text" id="netHouseholdIncome" placeholder="0.00" class="form-control alignRight bold large" readonly=""></div></div></div><div class="x_panel half no-padding overallTotal"><div id="" class="form-group no-padding edit item"><label for="score_4" class="alignRight uppercase bold number control-label col-md-8 col-sm-8 col-xs-12">Net Business Income</label><div class="col-md-4 col-xs-12 no-padding type-number edit"><input value="" type="text" id="netBusinessIncome" placeholder="0.00" class="form-control alignRight bold large" readonly=""></div></div></div>';
				break;
			case "collateral":
				//$jsComputeExtras .= '$("form#createExtras .select2_single").each(function() {$(this).select2({placeholder: $(this).attr("placeholder"),allowClear: false});});';
				break;
		}
		$output .= '</form>
		<script src="'.Info::URL.'/js/convertNumber.js"></script>
		<script>
		webshims.setOptions("forms-ext", {replaceUI: "auto",types: "number"});webshims.polyfill("forms forms-ext");
		$(".select2_single").each(function() {
			$(this).select2({
				placeholder: $(this).attr("placeholder"),
				allowClear: false
			});
		});
		'.$jsComputeExtras.'computeOverall("input.total_'.$postParam['meta_key'].'","'.$postParam['alias'].'");
		$("input.toggleBtn").on("click",function () {
			theName = $(this).attr("name");
			keyID = $(this).attr("key_id");
			theValue = $(this).val();
			theValue ^= 1;
			$("input[name="+theName+"]").val(theValue);
			//theValue = $(this).val();
			computeExtras(keyID,"willingness","willingness_pay")
			//alert(theValue);
			$(this).toggleClass("active");
		});
		</script>';
		return $output;
	}

	public function setReportDate(){
		$this->beginningDate = date('Y-m-t', strtotime('last month'));
		$this->date = $this->currentDate = $this->recentDate = $this->getDate('date');
		$this->dateRange = [$this->date,$this->date];
		$this->endDay = self::getValueDB(['table'=>'end_of_day','report_date'=>date('Y-m-d')]);
		if($this->endDay){
			$getRecentRecord = self::getValueDB(["table"=>"end_of_day","report_date"=>date('Y-m-d')]);
			//$this->currentDate = date('Y-m-d', strtotime("last day"));
		}else{
			$getRecentRecord = self::getValueDB(["table"=>"end_of_day","id >"=>0]);
			$this->recentDate = $getRecentRecord['report_date'];

		}
		$this->recentRecord = $getRecentRecord['value'];
		//$getRecentRecord = $this->getValueDB(["table"=>"end_of_day","report_date <"=>date('Y-m-d', strtotime("last day"))]);
		//if(!$this->endDay) $this->recentDate = date('Y-m-d', strtotime("last day"));
		//$stmtRecentRecord = ["arguments" => ["id>"=>1],"extra" => "ORDER BY report_date DESC LIMIT 1","table" => "end_of_day","pdoFetch" => PDO::FETCH_CLASS,"fields" => ['id','report_date','value']];

	}

	public function NetIncome($inputDate){
		$total = $amountIncome = $amountExpenses = 0; //$output = "";
		$netAcctgID = [8,87,11,37,55,74,12,85,59,61,68,79,10,78,9,89,80];
		$incomeID = [8,9,87];
		$getStatementCurrent = $this->getValueDB(['table'=>'end_of_day','report_date'=>$inputDate]);
		$incomeStatement = json_decode($getStatementCurrent['value']);
		foreach ($incomeStatement as $beginningValue) {
			foreach($beginningValue as $code => $amount) {
				if(in_array($code,$netAcctgID)){
					if(in_array($code,$incomeID)){
						$amountIncome = $amountIncome + round(abs($amount),2);
					}else{
						$amountExpenses = $amountExpenses + round(abs($amount),2);
					}
					//$output .= $code." : ".round(abs($amount),2)."<br>";
				}
			}
			$total = $amountIncome - $amountExpenses;
		}
		return $total;
	}
	
	public function popupElements($values){
		$name = $values["name"];
		$title = $values["title"];
		$elementFooter = (isset($values["isFooter"])) ? "<button type='button' class='btn btn-default' id='closeBtn' data-dismiss='modal'>Close</button><button type='button' class='btn btn-primary' id='saveBtn'>Save changes</button>" : ""; 
		$output = "
		<div class='modal fade {$name}' role='dialog' aria-hidden='true'>
			<div class='modal-dialog modal-lg'>
			  <div class='modal-content'>
				<div class='modal-header'>
					<button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>×</span></button>
					<h4 class='modal-title' id='myModalLabel'>{$title}</h4>
				</div>
				<div class='modal-body'></div>
				{$elementFooter}
			  </div>
			</div>
		</div>
		";
		return $output;
	}

	public function popupFormBox(){
		$getForm = 'form'.$this->Params['table'];
		echo $this->$getForm();
	}

	function formMeta_terms() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = ""; $status = 0;
		//this->schema = Info::DB_NAME;
		$getFields = ['id','meta_id','meta_key','meta_option','meta_value'];

		$stmtMetaLastValue = ['schema'=>$this->schema,'table'=>'meta_terms','arguments'=>['meta_key'=>$this->popupFormID],'pdoFetch'=>PDO::FETCH_ASSOC,'fields'=>['id','meta_id'],'extra'=>'ORDER BY meta_id desc LIMIT 1'];
		$getMetaLastValue = self::selectDB($stmtMetaLastValue);
		$thisMetaID = $getMetaLastValue[0]['meta_id'] + 1;

		$meta_id = $this->inputGroup(['label'=>'Option Details','type'=>'text','id'=>'meta_id','name'=>'meta_id','placeholder'=>'Meta ID','value'=>$thisMetaID]);
		$meta_option = $this->inputGroup(['type'=>'text','id'=>'meta_option','name'=>'meta_option','placeholder'=>'Meta Alias','value'=>'']);//$this->Params['middle_name']
		$meta_value = $this->inputGroup(['type'=>'text','id'=>'meta_value','name'=>'meta_value','placeholder'=>'Meta Name/Title','value'=>'']);//$this->Params['middle_name']

		$popUpTitle = str_replace("_"," ",$this->Params['table']);
		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
		$formField .= "
			<div class='half'>
				<div class='form-group no-padding item'>
					<div class='col-md-4 col-sm-4 col-xs-12 no-padding col-2 alignRight'>{$meta_id}</div>
					<div class='col-md-3 col-sm-3 col-xs-12 no-padding'>{$meta_option}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$meta_value}</div>
				</div>
			</div>
			</div>

		</div>
		</div>
		";

		$output .= "
            <form id='createRecords' data-toggle='validator' name='createMeta_terms' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='meta_key' id='meta_key' value='{$this->popupFormID}' />
				<input type='hidden' name='schema' id='schema' value='{$this->schema}' />
				<input type='hidden' name='theID' id='theID' value='' />
				{$formField}
			</form>
        ";

		$output .= "<script>
			$(document).ready(function() {
				//webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});
		</script>";
		return $output;
	}

	function formUsers() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = ""; $status = 1;
		$getPopupValue[] = $fieldValue[] = "";
		if($this->popupFormID){
			$getPopupValue = $this->getValueDB(["table"=>$this->Params['table'],"id"=>$this->popupFormID]);
			$status = $getPopupValue['status'];
		}
		$varLists = ['username','password','staff_id','role','supervisor','unit'];
		foreach($varLists as $var){
			$fieldValue[$var] = (isset($getPopupValue[$var]) && $this->popupFormID) ? $getPopupValue[$var] : "";
		}

		$username = $this->inputGroup(['label'=>'Username','type'=>'text','id'=>'username','name'=>'username','placeholder'=>'Username','value'=>$fieldValue['username']]);//$this->Params['first_name']
		$password = $this->inputGroup(['label'=>'Password','type'=>'password','id'=>'password','name'=>'password','placeholder'=>'&bull;&bull;&bull;&bull;&bull;&bull;','value'=>'']);//$this->Params['middle_name']
		$staff_id = $this->inputGroup(['label'=>'Employee','type'=>'select','id'=>'staff_id','name'=>'staff_id','meta_key'=>'staffs','meta'=>'staffs','placeholder'=>'Select to link User to Employee','value'=>$fieldValue['staff_id']]);
		$role = $this->inputGroup(['label'=>'Access Role','type'=>'select','id'=>'role','name'=>'role','meta_key'=>'role','meta'=>'codebook','placeholder'=>'Select Access Role','value'=>$fieldValue['role']]);

		$supervisor = $this->inputGroup(['label'=>'Supervisor','type'=>'select','id'=>'supervisor','name'=>'supervisor','meta_key'=>'users','meta'=>'users','placeholder'=>'Select User\'s Supervisor','value'=>$fieldValue['supervisor']]);
		$unit = $this->inputGroup(['label'=>'Deparment','type'=>'select','id'=>'unit','name'=>'unit','meta_key'=>'unit','meta'=>'codebook','placeholder'=>'Select User\'s Department/Unit','value'=>$fieldValue['unit']]);

		$popUpTitle = str_replace("_"," ",$this->Params['table']);
		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
		$formField .= "
			<div class='one-fourth'>
				<div class='form-group no-padding item alignRight'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$username}</div>
				</div>
				<div class='form-group no-padding item alignRight'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$password}</div>
				</div>
			</div>
			<div class='three-fourth'>
			<div class='form-group no-padding item'>
				<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$staff_id}</div>
				<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$supervisor}</div>
			</div>
			<div class='form-group no-padding item'>
				<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$role}</div>
				<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$unit}</div>
			</div>
		</div>
		</div>
		</div>
		";
		$output .= "
            <form id='createRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='theID' id='theID' value='{$this->Params['value']}' />
				{$formField}
			</form>
        ";
		$output .= "<script>
			$(document).ready(function() {
				webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});

		</script>";
		return $output;
	}

	function formHolidays() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = $deductionBox = ""; $status = 1;
		$this->schema = Info::DB_NAME;
		$getPopupValue[] = $fieldValue[] = "";
		if($this->popupFormID){
			$getPopupValue = $this->getValueDB(["table"=>$this->Params['table'],"id"=>$this->popupFormID]);
			$status = $getPopupValue['status'];
		}
		$varLists = ['holiday_type','set_date','title','description'];
		foreach($varLists as $var){
			$fieldValue[$var] = (isset($getPopupValue[$var]) && $this->popupFormID) ? $getPopupValue[$var] : "";
		}
		$holiday_type = $this->inputGroup(['label'=>'Type','type'=>'select','id'=>'holiday_type','name'=>'holiday_type','meta_key'=>'holiday_type','meta'=>'codebook','placeholder'=>'Type of Holiday','value'=>$fieldValue['holiday_type']]);
		$title = $this->inputGroup(['label'=>'Title','type'=>'text','id'=>'title','name'=>'title','placeholder'=>'Title/Name','value'=>$fieldValue['title']]);//$this->Params['first_name']
		$set_date = $this->inputGroup(['label'=>'Date','type'=>'date','id'=>'set_date','name'=>'','placeholder'=>'Date of Holiday','value'=>$fieldValue['set_date']]);//$this->Params['middle_name']
		$description = $this->inputGroup(['label'=>'Description','type'=>'text','id'=>'description','name'=>'description','title'=>'Description','placeholder'=>'Description/Notes','value'=>$fieldValue['description']]);//$getValue[0]['address']
		$popUpTitle = str_replace("_"," ",$this->Params['table']);
		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
		$formField .= "
			<div class=''>
				<div class='form-group no-padding item'>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$holiday_type}</div>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$set_date}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$title}</div>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$description}</div>
				</div>
			</div>
		</div>
		</div>
		";
		$output .= "
            <form id='createRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='set_date' id='this_date' value='{$fieldValue['set_date']}' />
				<input type='hidden' name='theID' id='theID' value='{$this->Params['value']}' />
				{$formField}
			</form>
        ";
		$output .= "<script>
			$(document).ready(function() {
				webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
				$('input#set_date.date-picker').val(moment($('input#set_date.date-picker').val(),'YYYY/MM/DD').format('MMMM D'));
			});
			
			$('input#set_date.date-picker').daterangepicker({
				singleDatePicker: true,
				opens: 'right',
				calender_style: 'picker_4',
				autoUpdateInput: false,
				locale: {
					format: 'MMMM D'
				}
			}, function (start, end, label) {
				$('input[name=set_date]').val(moment(end,'MMMM D').format('YYYY/MM/DD'));
				console.log(start.toISOString(), end.toISOString(), label);
			});
			
			$('input#set_date').on('apply.daterangepicker', function(ev, picker) {
				$(this).val(picker.startDate.format('MMMM D'));
				$('input[name=set_date]').val(picker.startDate.format('YYYY/MM/DD'));
			});
			
			
		</script>";
		return $output;
	}

	function formPayroll_logs() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = $deductionBox = $getPopupValue = ""; $status = 0;
		$this->schema = Info::DB_NAME;

		$getFields = $this->getTableFields(['table'=>$this->Params['table'],'exclude'=>['id','user','date']]);
		$getPopupValue = $this->getValueDB(["table"=>'payroll',"id"=>$this->popupFormID]);
		$getStartDate = date_create($getPopupValue['start']);
		$getStartDate = date_format($getStartDate, 'Y/m/d');
		$getEndDate = date_create($getPopupValue['end']);
		$getEndDate = date_format($getEndDate, 'Y/m/d');

		$log_set = $this->inputGroup(['label'=>'Log Type','type'=>'select','id'=>'log_set','name'=>'log_set','meta_key'=>'log_set','meta'=>'codebook','placeholder'=>'Log Set/Type','value'=>'']);
		$staff_id = $this->inputGroup(['label'=>'Employee','type'=>'select','id'=>'staff_id','name'=>'staff_id','meta_key'=>'staffs','meta'=>'staffs','placeholder'=>'Select Employee','value'=>'']);
		$logDate = $this->inputGroup(['label'=>'Log Date','type'=>'date','id'=>'logDate','name'=>'logDate','placeholder'=>'Log Date','value'=>'']);//$this->Params['middle_name']
		$logIn = $this->inputGroup(["label"=>"Log In/Out","type"=>"text","id"=>"logIn","name"=>"logIn","placeholder"=>"00:00","value"=>"","title"=>"Start Time","custom"=>"data-inputmask=\"'mask': '99:99'\""]);
		$logOut = $this->inputGroup(["type"=>"text","id"=>"logOut","name"=>"logOut","placeholder"=>"00:00","value"=>"","title"=>"End Time","custom"=>"data-inputmask=\"'mask': '99:99'\""]);

		$popUpTitle = str_replace("_"," ",$this->Params['table']);
		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
		$formField .= "
			<div class='one-third'>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding'>{$log_set}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding alignRight'>{$logDate}</div>
				</div>
			</div>
			<div class='three-fourth'>
				<div class='form-group no-padding item'>
					<div class='col-md-12 col-sm-12 col-xs-12 no-padding col-4'>{$staff_id}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding alignRight'>{$logIn}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding alignRight'>{$logOut}</div>
				</div>
			</div>

		</div>
		</div>
		";

		$output .= "
            <form id='createLogRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='record_id' id='record_id' value='{$this->popupFormID}' />
				<input type='hidden' name='theID' id='theID' value='' />
				{$formField}
			</form>
        ";

		$output .= "<script>
			$(document).ready(function() {
				//webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});
			$('input#logDate.date-picker').daterangepicker({
				singleDatePicker: true,
				opens: 'right',
				minDate: '{$getStartDate}',
				maxDate: '{$getEndDate}',
				autoUpdateInput: true,
				calender_style: 'picker_4',
				locale: {
					format: 'YYYY/MM/DD'
				}
			}, function (start, end, label) {
				console.log(start.toISOString(), end.toISOString(), label);
			});
		</script>";
		return $output;
	}

	function formSalary_set() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = $deductionBox = ""; $status = 0;
		$this->schema = Info::DB_NAME;
		$getPopupValue[] = $fieldValue[] = "";
		if($this->popupFormID){
			$getPopupValue = $this->getValueDB(["table"=>$this->Params['table'],"id"=>$this->popupFormID]);
			$status = $getPopupValue['status'];
		}
		$varLists = ['salary_type','alias','title','amount','description'];
		foreach($varLists as $var){
			$fieldValue[$var] = (isset($getPopupValue[$var]) && $this->popupFormID) ? $getPopupValue[$var] : "";
		}
		$salary_type = $this->inputGroup(['label'=>'Salary Type','type'=>'select','id'=>'salary_type','name'=>'salary_type','meta_key'=>'salary_type','meta'=>'codebook','placeholder'=>'Salary Type','value'=>$fieldValue['salary_type']]);
		$title = $this->inputGroup(['label'=>'Title','type'=>'text','id'=>'title','name'=>'title','placeholder'=>'Title/Name','value'=>$fieldValue['title']]);//$this->Params['first_name']
		$alias = $this->inputGroup(['label'=>'Alias','type'=>'text','id'=>'alias','name'=>'alias','placeholder'=>'Alias','value'=>$fieldValue['alias']]);//$this->Params['middle_name']
		$amount = $this->inputGroup(['label'=>'Amount','type'=>'amount','id'=>'amount','name'=>'amount','placeholder'=>'Salary Amount','value'=>$fieldValue['amount']]);//$this->Params['middle_name']
		$description = $this->inputGroup(['label'=>'Description','type'=>'textarea','id'=>'description','name'=>'description','title'=>'Description','placeholder'=>'Description/Notes','value'=>$fieldValue['description']]);//$getValue[0]['address']
		$popUpTitle = str_replace("_"," ",$this->Params['table']);
		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>{$popUpTitle} Information Details</h2></div><div class='x_content no-padding'>";
		$formField .= "
			<div class=''>
				<div class='form-group no-padding item'>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$salary_type}</div>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$title}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$alias}</div>
					<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$amount}</div>
				</div>
				<div class='form-group no-padding item col-4'>{$description}</div>
			</div>
		</div>
		</div>
		";
		$output .= "
            <form id='createRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='theID' id='theID' value='{$this->Params['value']}' />
				{$formField}
			</form>
        ";
		$output .= "<script>
			$(document).ready(function() {
				webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});

		</script>";
		return $output;
	}

	function formStaffs() { // CONSOLE >> CHARTS : POPUPBOX
		$output = $formField = $deductionBox = "";
		//global $staffParams;
		$this->schema = Info::DB_NAME;
		if(isset($GLOBALS['staff_id']) && $GLOBALS['staff_id'] != "") $this->popupFormID = $GLOBALS['staff_id'];
		$getPopupValue[] = $fieldValue[] = "";
		if($this->popupFormID){
			$getPopupValue = $this->getValueDB(["table"=>$this->Params['table'],"id"=>$this->popupFormID]);
		}
		$varLists = ['id_number','last_name','employment_level','first_name','middle_name','suffix_name','gender','birth_date','remarks','bio_id','salary_set','email','contact','designation','unit','address'];
		$defaultEmpty = ['bio_id','birth_date'];
		foreach($varLists as $var){
			$fieldValue[$var] = (isset($getPopupValue[$var]) && $this->popupFormID) ? $getPopupValue[$var] : "";
		}
		//var_dump($getPopupValue);
		$suffix_name = $this->inputGroup(['type'=>'text','id'=>'suffix_name','name'=>'suffix_name','placeholder'=>'e.g. Jr., III','value'=>$fieldValue['suffix_name']]);//$this->Params['suffix_name']
		$gender = $this->inputGroup(['type'=>'select','id'=>'gender','name'=>'gender','meta_key'=>'gender','meta'=>'codebook','placeholder'=>'Gender','value'=>$fieldValue['gender']]);
		$birth_date = $this->inputGroup(['label'=>'Birth Info','type'=>'text','id'=>'birth_date','name'=>'birth_date','placeholder'=>'yyyy-mm-dd','value'=>$fieldValue['birth_date'],'required'=>'data-inputmask="\'mask\': \'9999-99-99\'"']);//$this->Params['birth_date']
		$remarks = $this->inputGroup(['label'=>'Remarks','type'=>'textarea','id'=>'remarks','name'=>'remarks','title'=>'Remarks','placeholder'=>'Employee addition information','value'=>$fieldValue['remarks']]);//$getValue[0]['remarks']

		//$bio_id = $this->inputGroup(['label'=>'Biometrics','type'=>'text','id'=>'bio_id','name'=>'bio_id','placeholder'=>'Biometrics ID','value'=>$fieldValue['bio_id']]);//$this->Params['bio_id']
		$id_number = $this->inputGroup(['label'=>'ID Number','type'=>'text','id'=>'id_number','name'=>'id_number','placeholder'=>'Company ID Number','value'=>$fieldValue['id_number'],'required'=>'data-inputmask="\'mask\': \'99-999999\'"']);//$this->Params['bio_id']
		//$salary_set = $this->inputGroup(['type'=>'select','id'=>'salary_set','name'=>'salary_set','meta_key'=>'salary_set','meta'=>'salary_set','placeholder'=>'Salary Set','value'=>$fieldValue['salary_set']]);
		$contact = $this->inputGroup(['label'=>'Contact Info','type'=>'text','id'=>'contact','name'=>'contact','placeholder'=>'Contact Numbers','value'=>$fieldValue['contact']]);//$this->Params['contact']
		$email = $this->inputGroup(['type'=>'text','id'=>'email','name'=>'email','placeholder'=>'Email Address','value'=>$fieldValue['email']]);//$this->Params['email']
		$employment_level = $this->inputGroup(['type'=>'select','id'=>'employment_level','name'=>'employment_level','meta_key'=>'employment_level','meta'=>'codebook','placeholder'=>'Employment Level','value'=>$fieldValue['employment_level']]);
		$designation = $this->inputGroup(['label'=>'Employment','type'=>'select','id'=>'designation','name'=>'designation','meta_key'=>'designation','meta'=>'codebook','placeholder'=>'Position/Designation','value'=>$fieldValue['designation']]);
		$unit = $this->inputGroup(['type'=>'select','id'=>'unit','name'=>'unit','meta_key'=>'unit','meta'=>'codebook','placeholder'=>'Unit/Department','value'=>$fieldValue['unit']]);
		$address = $this->inputGroup(['label'=>'Address','type'=>'textarea','id'=>'address','name'=>'address','title'=>'Address','placeholder'=>'Current and Complete Address','value'=>$fieldValue['address']]);//$getValue[0]['address']

		$paramUsername = (isset($_GET['username']) && $_GET['username'] != "") ? ',"username":"'.$_GET["username"].'"': '';

		if($this->Params['view'] != "profile"){
			if($this->isApprove && $_SESSION['role'] <= 2){
				$statusBox = "<div class='recordStatusBox right statusBtn'><input id='statusBtn' name='statusBtn_' type='checkbox' ".($this->isApprove ? 'checked' : '')." class='js-switch' value='1' params='{\"schema\":\"projectzero_pms\",\"table\":\"meta_status\",\"action\":\"autoSave\",\"field\":\"value\",\"id\":\"{$this->isApproveID}\"}' onchange='autoSave(this)'/></div>";
			}else{
				$statusBox = "<div class='recordStatusBox right status ".($this->isApprove? "bgGreen" : "" )."'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span></div>";
			}

			$orsBtn = "";
			if($this->Params['view'] == "indicator"){

				$getKRAMeasures = $_SESSION['kra'][$GLOBALS['designation']];
				foreach($getKRAMeasures as $kraCnt => $kraValue){
					$linkBtn = "onclick='viewPage(this)' page-content='{\"methods\":\"pms\",\"view\":\"indicator\",\"ors\":\"{$getKRAMeasures[$kraCnt]->meta_id}\"{$paramUsername}}'";
					$orsBtn .= "<button data-toggle='tooltip' data-placement='top' title='' data-original-title='{$getKRAMeasures[$kraCnt]->title}' {$linkBtn} onclick='viewPage(this)' class='btn btn-info".($this->Params['ors'] == $getKRAMeasures[$kraCnt]->meta_id ? ' active' : '')."' type='button'>ORS {$getKRAMeasures[$kraCnt]->meta_id}</button>";
				}
				// GET ORS WEIGHT
				$sessionValuesKRA = $_SESSION['kra'][$fieldValue['designation']];
				$thisWeightKRA = "";
				foreach($sessionValuesKRA as $kraKey => $kraValue){
					$kraMetaID = $sessionValuesKRA[$kraKey]->meta_id;
					if(isset($_GET['ors']) && $_GET['ors'] == $kraMetaID){
						$thisWeightKRA = $sessionValuesKRA[$kraKey]->weight;
						break;
					}
				}
				// END GET ORS WEIGHT
				//var_dump(array_column($_SESSION['kra'][$fieldValue['designation']],'meta_id'));//$_SESSION['kra'][$fieldValue['designation']][0]->weight;
				$statusBox .= "<div class='recordStatusBox buttons'><div class='btn-group'>{$orsBtn}</div></div>";
				$statusBox .= "<div class='recordStatusBox right bold'><span class='large lightGrey'>Weight</span><span class='xbig bgTeal'>{$thisWeightKRA}%</span></div>";
			}
		}

		$getStaffUserDetails = $this->getValueDB(["table"=>"users","staff_id"=>$this->popupFormID,"schema"=>Info::DB_SYSTEMS]);

		if($this->pageAction == "view"){
			$getUserName = (isset($_GET["username"]) && $_GET["username"] != "") ? $_GET["username"] : $_SESSION['username'];
			$recordSheetBox = self::recordSheetBox($getUserName);
			$statusBox .= "<span class='recordSheetBox'>{$recordSheetBox}</span>";
		}

		$formField .= "<div class='x_panel no-padding'><div class='box_title'><h2 class='left capitalize'>Employee's Details</h2>{$statusBox}</div><div class='x_content no-padding boxFlex'>";
		if($this->pageAction){ // STAFFBOX
			if($this->pageAction == "edit"){
				$this->editable = "editable"; $this->readonly = "";
			}
			$last_name = $this->inputGroup(['type'=>'text','id'=>'last_name','name'=>'last_name','placeholder'=>'Last Name','value'=>$fieldValue['last_name']]);//$this->Params['last_name']
			$first_name = $this->inputGroup(['label'=>'Full Name','type'=>'text','id'=>'first_name','name'=>'first_name','placeholder'=>'First Name','value'=>$fieldValue['first_name']]);//$this->Params['first_name']
			$middle_name = $this->inputGroup(['type'=>'text','id'=>'middle_name','name'=>'middle_name','placeholder'=>'Middle Name','value'=>$fieldValue['middle_name']]);//$this->Params['middle_name']
			if($_SESSION['role'] >= 4){ // STAFF
				$getStaffUserSupervisor = $this->getValueDB(["table"=>"users","id"=>$this->popupFormID,"schema"=>Info::DB_SYSTEMS]);
				$checkStaffSupervisor = $this->getValueDB(["table"=>"staffs","id"=>$getStaffUserSupervisor['supervisor'],"schema"=>Info::DB_NAME]);
				$getStaffSupervisor = implode(" ",[$checkStaffSupervisor['first_name'],$checkStaffSupervisor['middle_name'],$checkStaffSupervisor['last_name'],$checkStaffSupervisor['suffix_name']]);
				$getStaffSupervisorUnit = $_SESSION['meta']['unit'][$checkStaffSupervisor['unit']][0]->meta_value;
				//}elseif($_SESSION['role'] >= 3){ // SUPERVISOR
				//	$getStaffSupervisor = implode(" ",[$_SESSION['first_name'],$_SESSION['middle_name'],$_SESSION['last_name'],$_SESSION['suffix_name']]);
				//	$getStaffSupervisorUnit = $_SESSION['meta']['unit'][$_SESSION['unit']][0]->meta_value;
			}else{ // SUPER ADMIN, ADMIN, MANAGER
				$getStaffUser = $this->getValueDB(["table"=>"users","staff_id"=>$this->popupFormID,"schema"=>Info::DB_SYSTEMS]);
				$getStaffSupervisor = $_SESSION['list_supervisor'][$getStaffUser['supervisor']]['display_name'];
				$getStaffSupervisorUnit = $_SESSION['meta']['unit'][$getStaffUser['unit']][0]->meta_value;
			}
			$supervisor = $this->inputGroup(['label'=>'Supervisor','type'=>'text','id'=>'middle_name','name'=>'','placeholder'=>'Staffs Supervisor','value'=>$getStaffSupervisor]);
			$supervisor_designation = $this->inputGroup(['type'=>'text','id'=>'supervisor_designation','name'=>'','placeholder'=>'Department','value'=>$getStaffSupervisorUnit]);
			$avatarBox = "<div class='boxCell boxWrapper avatarBox' style='width:164px'></div>";
			if($this->editable){
				$this->pageAction = "";
				$avatarBox = "<div class='boxCell boxWrapper avatarBox' id='fileUploader' style='width:164px'>
					<form id='fileImport' action='".Info::URL."' class='dropzone hide' method='post' enctype='multipart/form-data'>
						<input type='hidden' name='record_id' id='record_id' value=''><input type='hidden' name='log_set' id='log_set' value=''>
					</form>
					</div>";
				$first_name = $this->inputGroup(['label'=>'Full Name','type'=>'text','id'=>'first_name','name'=>'first_name','placeholder'=>'First Name','value'=>$fieldValue['first_name'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"first_name","id":"'.$this->popupFormID.'"}\'']);
				$middle_name = $this->inputGroup(['type'=>'text','id'=>'middle_name','name'=>'middle_name','placeholder'=>'Middle Name','value'=>$fieldValue['middle_name'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"middle_name","id":"'.$this->popupFormID.'"}\'']);
				$last_name = $this->inputGroup(['type'=>'text','id'=>'last_name','name'=>'last_name','placeholder'=>'Last Name','value'=>$fieldValue['last_name'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"last_name","id":"'.$this->popupFormID.'"}\'']);
				$suffix_name = $this->inputGroup(['type'=>'text','id'=>'suffix_name','name'=>'suffix_name','placeholder'=>'e.g. Jr., III','value'=>$fieldValue['suffix_name'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"suffix_name","id":"'.$this->popupFormID.'"}\'']);
				$gender = $this->inputGroup(['type'=>'select','id'=>'gender','name'=>'gender','meta_key'=>'gender','meta'=>'codebook','placeholder'=>'Gender','value'=>$fieldValue['gender'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"gender","id":"'.$this->popupFormID.'"}\'']);

				$birth_date = $this->inputGroup(['label'=>'Birth Info','type'=>'text','id'=>'birth_date','name'=>'birth_date','placeholder'=>'yyyy-mm-dd','value'=>$fieldValue['birth_date'],'required'=>'data-inputmask="\'mask\': \'9999-99-99\'"','custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"birth_date","id":"'.$this->popupFormID.'"}\'']);
				$bio_id = $this->inputGroup(['type'=>'text','id'=>'bio_id','name'=>'bio_id','placeholder'=>'Biometrics ID','value'=>$fieldValue['bio_id'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"bio_id","id":"'.$this->popupFormID.'"}\'']);
				$contact = $this->inputGroup(['label'=>'Contact Info','type'=>'text','id'=>'contact','name'=>'contact','placeholder'=>'Contact Numbers','value'=>$fieldValue['contact'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"contact","id":"'.$this->popupFormID.'"}\'']);
				$id_number = $this->inputGroup(['label'=>'ID Number','type'=>'text','id'=>'id_number','name'=>'id_number','placeholder'=>'Company ID Number','value'=>$fieldValue['id_number'],'required'=>'data-inputmask="\'mask\': \'99-999999\'"','custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"id_number","id":"'.$this->popupFormID.'"}\'']);
				$email = $this->inputGroup(['type'=>'text','id'=>'email','name'=>'email','placeholder'=>'Email Address','value'=>$fieldValue['email'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"email","id":"'.$this->popupFormID.'"}\'']);

				$remarks = $this->inputGroup(['label'=>'Remarks','type'=>'textarea','id'=>'remarks','name'=>'remarks','title'=>'Remarks','placeholder'=>'Employee addition information','value'=>$fieldValue['remarks'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"remarks","id":"'.$this->popupFormID.'"}\'']);
				$address = $this->inputGroup(['label'=>'Address','type'=>'textarea','id'=>'address','name'=>'address','title'=>'Address','placeholder'=>'Current and Complete Address','value'=>$fieldValue['address'],'custom'=>'onchange="autoSave(this)" params=\'{"table":"staffs","action":"autoSave","field":"address","id":"'.$this->popupFormID.'"}\'']);
				$password = $this->inputGroup(['type'=>'password','id'=>'password','name'=>'password','placeholder'=>'******','value'=>'','custom'=>'onchange="autoSave(this)" params=\'{"table":"users","action":"autoSave","field":"password","id":"'.$this->popupFormID.'"}\'']);
				$this->pageAction = "view";
				$designation = $this->inputGroup(['label'=>'Employment','type'=>'select','id'=>'designation','name'=>'designation','meta_key'=>'designation','meta'=>'codebook','placeholder'=>'Position/Designation','value'=>$fieldValue['designation']]);
				$unit = $this->inputGroup(['type'=>'select','id'=>'unit','name'=>'unit','meta_key'=>'unit','meta'=>'codebook','placeholder'=>'Position/Designation','value'=>$fieldValue['unit']]);
				$username = $this->inputGroup(['label'=>'User Details','type'=>'text','id'=>'username','name'=>'username','placeholder'=>'Username','value'=>$getStaffUserDetails['username']]);
				$formField .= $avatarBox."
				<div class='boxCell elementBox profileBox edit'>
					<div class='half'>
						<div class='form-group no-padding item'>
							<div class='col-md-12 col-sm-12 col-xs-12 no-padding boxFlex'>{$first_name} {$middle_name} {$last_name} {$suffix_name}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$birth_date}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$gender}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$contact}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$email}</div>
						</div>
						<div class='form-group no-padding item col-3'>{$address}</div>
					</div>
					<div class='half'>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$username}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$password}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$id_number}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$bio_id}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$designation}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$unit}</div>
						</div>
						<div class='form-group no-padding item col-3'>{$remarks}</div>
					</div>
				</div>
			</div>
			</div>
			";
			}else{
				$userStaffRole = $getStaffUserDetails['role'];
				$userRole = $this->inputGroup(['type'=>'select','id'=>'role','name'=>'role','meta_key'=>'role','meta'=>'codebook','placeholder'=>'Select Access Role','value'=>$userStaffRole]);
				$username = $this->inputGroup(['label'=>'User Details','type'=>'text','id'=>'username','name'=>'username','placeholder'=>'Username','value'=>$getStaffUserDetails['username']]);
				$formField .= $avatarBox."
				<div class='boxCell elementBox profileBox'>
					<div class='half'>
						<div class='form-group no-padding item'>
							<div class='col-md-12 col-sm-12 col-xs-12 no-padding boxFlex'>{$first_name} {$middle_name} {$last_name} {$suffix_name}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$birth_date}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$gender}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$contact}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$email}</div>
						</div>
						<div class='form-group no-padding item col-3'>{$address}</div>
					</div>
					<div class='half'>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$username}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$userRole}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$designation}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$unit}</div>
						</div>
						<div class='form-group no-padding item'>
							<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$supervisor}</div>
							<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$supervisor_designation}</div>
						</div>
						<div class='form-group no-padding item col-3'>{$remarks}</div>
					</div>
				</div>
			</div>
			</div>
			";
			}

		}else{
			$last_name = $this->inputGroup(['label'=>'Full Name','type'=>'text','id'=>'last_name','name'=>'last_name','placeholder'=>'Last Name','value'=>$fieldValue['last_name']]);//$this->Params['last_name']
			$first_name = $this->inputGroup(['type'=>'text','id'=>'first_name','name'=>'first_name','placeholder'=>'First Name','value'=>$fieldValue['first_name']]);//$this->Params['first_name']
			$middle_name = $this->inputGroup(['label'=>'&nbsp;','type'=>'text','id'=>'middle_name','name'=>'middle_name','placeholder'=>'Middle Name','value'=>$fieldValue['middle_name']]);//$this->Params['middle_name']
			$formField .= "
			<div class='half'>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$last_name}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$first_name}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$middle_name}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$suffix_name}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$birth_date}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$gender}</div>
				</div>
				<div class='form-group no-padding item col-3'>{$address}</div>
			</div>
			<div class='half'>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$designation}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$unit}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$id_number}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$employment_level}</div>
				</div>
				<div class='form-group no-padding item'>
					<div class='col-md-7 col-sm-7 col-xs-12 no-padding'>{$contact}</div>
					<div class='col-md-5 col-sm-5 col-xs-12 no-padding'>{$email}</div>
				</div>
				<div class='form-group no-padding item col-3'>{$remarks}</div>
			</div>
			</div>
			</div>
			";
		}

		$output .= "
            <form id='createRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='schema' id='schema' value=Info::DB_NAME />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='theID' id='theID' value='{$this->Params['value']}' />
				{$formField}
			</form>
        ";
		if(!$this->pageAction) {
			$resultLabelField = [];
			if (false) { //$this->popupFormID
				$getUserDeductions = $this->getValueDB(['table' => 'payroll_adjustment', 'staff_id' => $this->popupFormID]);
				if (EMPTY($getUserDeductions)) { // CREATE DEFAULT DEDUCTIONS
					//$getUserDeductions = [];
					$getFields = ['staff_id', 'first', 'second', 'user', 'date'];
					$postValue[] = $this->popupFormID;
					$postValue[] = '[{"Witholding TAX":"0.00","SSS Contribution":"0.00","HDMF (Pag-Ibig)":"0.00","PhilHealth Insurance":"0.00"}]';
					$postValue[] = '[{"Witholding TAX":"0.00","SSS Contribution":"0.00","HDMF (Pag-Ibig)":"0.00","PhilHealth Insurance":"0.00"}]';
					$postValue[] = $_SESSION['userID'];
					$postValue[] = $this->getDate();
					$stmtElements = ['table' => 'payroll_adjustment', 'fields' => $getFields, 'values' => $postValue];
					$postID = $this->insertDB($stmtElements);
					$getUserDeductions = $this->getValueDB(['table' => 'payroll_adjustment', 'id' => $postID]);
				}
				$deductionMeta = ['first' => '1st PAYDAY', 'second' => '2nd PAYDAY'];
				foreach ($deductionMeta as $meta => $deductionValue) {
					$rowDeduction = self::DEDUCTION_ROW;
					$labelField = $formDeduction = "";
					$userDeduction = json_decode($getUserDeductions[$meta]);

					$fieldValue = [];
					foreach ($userDeduction as $fields => $metaValue) {
						$fieldCnt = 1;
						foreach ($metaValue as $field => $value) {
							$fieldValue['field'][$fieldCnt] = $field;
							$fieldValue['value'][$fieldCnt] = $value;
							$fieldCnt++;
						}
					}

					for ($x = 1; $rowDeduction >= $x; $x++) {
						$thisField = $thisValue = "";
						$label = ($x < 2) ? $deductionValue : "&nbsp;";
						if (isset($fieldValue['field'][$x])) $thisField = $fieldValue['field'][$x];
						if (isset($fieldValue['value'][$x])) $thisValue = $fieldValue['value'][$x];
						$deductionLabel = $this->inputGroup(['label' => $label, 'type' => 'text', 'id' => 'deductionLabel' . $x, 'name' => 'field-' . $x, 'placeholder' => 'e.g. Tax, SSS, Loan', 'value' => $thisField]);
						$deductionField = $this->inputGroup(['type' => 'amount', 'id' => 'deductionField' . $x, 'name' => 'value-' . $x, 'placeholder' => '0.00', 'value' => $thisValue]);
						$labelField .= "<div class='form-group no-padding item'>
						<div class='col-md-9 col-sm-9 col-xs-12 no-padding fieldLabel'>{$deductionLabel}</div>
						<div class='col-md-3 col-sm-3 col-xs-12 no-padding alignRight'>{$deductionField}</div>
					</div>";
					}

					$formDeduction = "
					<form id='{$meta}Deduction' data-toggle='validator' name='{$meta}Deduction' class='form-label-left input_mask' novalidate>
						<input type='hidden' name='action' id='action' value='createObjectRecords' />
						<input type='hidden' name='table' id='table' value='payroll_adjustment' />
						<input type='hidden' name='schema' id='schema' value=Info::DB_NAME />
						<input type='hidden' name='meta' id='meta' value='{$meta}' />
						<input type='hidden' name='theID' id='theID' value='{$getUserDeductions['id']}' />
						{$labelField}
					</form>
					";
					$resultLabelField[$meta] = $formDeduction;
				}

				$output .= "
					<div id='deductionBox' class='x_panel no-padding'>
						<div class='box_title'><h2 class='boxWrapper alignCenter'>Employee Payroll Deductions</h2></div>
						<div class='x_content no-padding'>
							<div class='half'>{$resultLabelField['first']}</div>
							<div class='half'>{$resultLabelField['second']}</div>
						</div>
					</div>
					";
			}
			$output .= "<script>
			$(document).ready(function() {
				webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				$('.select2_single').each(function() {
					$(this).select2({
						placeholder: $(this).attr('placeholder'),
						allowClear: false
					});
				});
				$(':input').inputmask();
			});

			</script>";
		} // END NO PAGE-ACTION

		return (!$this->pageAction) ? $output : $formField;
	}

	function formCharts() { // CONSOLE >> CHARTS : POPUPBOX
		//$output .= $this->Params['meta'].' - '.$this->Params['value'].' : '.$this->Params['userID'];
		$accountingCode = $this->inputGroup(['label'=>'Accounting Code','type'=>'text','id'=>'code','name'=>'code','placeholder'=>'0-00000','value'=>$this->Params['value'],'required'=>'data-inputmask="\'mask\': \'9-99999\'"']);
		$accountingTitle = $this->inputGroup(['label'=>'Accounting Title','type'=>'text','id'=>'title','name'=>'title','placeholder'=>'Accounting Title','value'=>'']);
		$accountingDescription = $this->inputGroup(['label'=>'Account Description','type'=>'textarea','id'=>'description','name'=>'description','placeholder'=>'Field Description','title'=>'Chart of account description','value'=>'']);
		$formField = "<div class='x_panel no-padding'><div class='box_title'><h2 class='left'>Create Accounting Chart</h2></div><div class='x_content no-padding'>";
		$formField .= "<div class='half'><div class='form-group no-padding item'>{$accountingCode}</div><div class='form-group no-padding item'>{$accountingTitle}</div></div>";
		$formField .= "<div class='half'><div class='form-group no-padding item'>{$accountingDescription}</div></div>";
		$formField .= "</div></div>";

		$output = "
            <form id='createRecords' data-toggle='validator' name='{$this->Params['meta']}' class='form-label-left input_mask' novalidate>
				<input type='hidden' name='action' id='action' value='createRecords' />
				<input type='hidden' name='type' id='type' value='{$this->Params['value']}' />
				<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
				<input type='hidden' name='theID' id='theID' value='0' />
				{$formField}
			</form>
        ";
		$output .= "<script>$(':input').inputmask();</script>";
		return $output;
	}
	
	public function journalEntry($accountCharts,$listID){
		$output = "
			<td class='no-padding'><select id='selectAccount-{$listID}' tabindex='{$listID}1' name='journals_entry[{$listID}][charts_id]' class='select2_group form-control' placeholder='Select Account Code/Title'>{$accountCharts}</select></td>
			<td class='no-padding alignRight'><input tabindex='{$listID}2' title='Debit Entry' onchange='computeRules(this)' count={$listID} field='compute' compute='debit' data-number-to-fixed='2' value='' type='text' id='debit-{$listID}' name='journals_entry[{$listID}][debit]' placeholder='0.000' class='form-control journalInput'></td>
			<td class='no-padding alignRight'><input tabindex='{$listID}3' title='Credit Entry' onchange='computeRules(this)' count={$listID} field='compute' compute='credit' data-number-to-fixed='2' value='' type='text' id='credit-{$listID}' name='journals_entry[{$listID}][credit]' placeholder='0.000' class='form-control journalInput'></td>
			";
		
		return $output;
	}
	
	public function optionsBranchUnit($params){
		// OPTION UNIT/BRANCH
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
					//if(isset($params['unit']) && $params['unit'] === $_SESSION['codebook']['unit'][$unitID]->meta_option){
					if(isset($params['unit']) && $params['unit'] === $unitID){
						$areaActive = "selected";
						$active = "selected";
					}
					$optionChild .= "<option {$active} value='{$unitID}' meta='{$_SESSION['codebook']['unit'][$unitID]->meta_option}'>{$_SESSION['codebook']['unit'][$unitID]->meta_value}</option>";
				}
				$parentName = (!EMPTY($_SESSION['codebook']['division'][$areaID]->meta_value)) ? $_SESSION['codebook']['division'][$areaID]->meta_value : "";
				$optionParent .= "<optgroup {$areaActive} label='{$parentName}'>{$optionChild}</optgroup>";
			}
		}
		$output = "<select id='{$params["name"]}' name='{$params["name"]}' class='select2_group form-control option-branch' placeholder='Select Unit/Branch'>{$optionParent}</select>";
		// END OPTION UNIT/BRANCH
		return $output;
	}

	public function pageForm($parseData) { // CONSOLE >> JOURNAL : CREATE JOURNAL ENTRY
		//$output .= $this->Params['meta'].' - '.$this->Params['value'].' : '.$this->Params['userID'];
		switch($parseData['table']){
			case "journals":
				$journalEntry = $output = $inputBranchUnit = "";
				$inputDate = $this->inputGroup(['label'=>'Entry Date','type'=>'date','id'=>'journal_date','name'=>'journals[entry_date]','placeholder'=>'yyyy-mm-dd','value'=>'','title'=>'','custom'=>'readonly="readonly"']);
				$inputRecipient = $this->inputGroup(['label'=>'Recipient Name','type'=>'text','id'=>'journal_recipient','name'=>'journals[recipient]','placeholder'=>'Full Name (Client, Members, Staff and etc)','value'=>'','title'=>'Journal Recipient','custom'=>'']);
				$inputParticulars = $this->inputGroup(['label'=>'Particulars','type'=>'text','id'=>'journal_particulars','name'=>'journals[particulars]','placeholder'=>'Description: Max 500 characters','value'=>'','title'=>'Journal Entry Particulars','custom'=>'']);
				
				if($_SESSION["userrole"] <= 3){ // ONLY FOR AREA MANAGERS/ADMINISTRATOR
					$inputBranchUnit = "<i class='fa fa-sitemap'></i>".$this->optionsBranchUnit(["name"=>"unit"]);
				}
				
				$formField = "<div class='x_panel journalForm'>";
				$formField .= "
						<div class='form-group item no-padding'>
							<div class='col-md-12 col-sm-12 col-xs-12'>{$inputDate}{$inputBranchUnit}</div>
						</div>
						<div class='form-group item no-padding'>
							<div class='col-md-12 col-sm-12 col-xs-12'>{$inputRecipient}</div>
						</div>
						<div class='form-group item no-padding'>
							<div class='col-md-12 col-sm-12 col-xs-12'>{$inputParticulars}</div>
						</div>
					</div>
				";
				//$output = $formField;
				$accountCharts = $this->getOptionLists();
				$maxListID = 5;
				for ($listID = 1; $listID <= $maxListID; $listID++) {
					$journalEntry .= "<tr id='{$listID}' class='journal_entry'>".$this->journalEntry($accountCharts,$listID)."</tr>";
				}
				$journalEntry .= "<tr id='{$listID}'></tr>";
				$output .= "
				<form id='createJournalEntry' data-toggle='validator' name='createJournalEntry' class='form-label-left input_mask' novalidate>
					<input type='hidden' name='action' id='action' value='createJournalEntry' />
					<input type='hidden' name='table' id='table' value='{$parseData['table']}' />
					<input type='hidden' name='theID' id='theID' value='0' />
					<input type='hidden' name='entry' id='entry' value='{$maxListID}' />
					{$formField}
					<div class='x_panel no-padding journalEntry'>
					<table id='table_journal_entry' class='table hasFooter'>
					<thead>
					<tr class='default'>
						<th width='60%'>Account Code/Title</th><th width='20%' class='alignRight'>Debit (Dr)</th><th width='20%' class='alignRight'>Credit (Cr)</th>
					</tr>
					</thead>
					<tbody>{$journalEntry}</tbody>
					<tfoot>
					<tr>
						<td class='alignRight bold no-padding'>
							<button type='button' class='btn capitalize no-margin right' id='saveOptionBtn' onclick='saveOption(\"createJournalEntry\")' name='submitRecords'>CREATE ENTRY</button>
							<button type='button' class='btn no-margin right' id='createClone' onclick='loadStorage(this);' action='getElementData' block='#table_journal_entry tbody' meta='journal_entry' value='6' name='journal_entry'><i class='fa fa-plus'></i></button>
						</td>
						<td class='alignRight total no-padding bold large'>
							<span id='total-debit'>&ctdot;</span>
						</td>
						<td class='alignRight total no-padding bold large'>
							<span id='total-credit'>&ctdot;</span>
						</td>
					</tr>
					</tfoot>
				</table>
				</div>
				</form>
					<script>
					//$(':input').inputmask();
					//webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
					$('.select2_group').each(function() {
						$(this).select2({
							placeholder: $(this).attr('placeholder'),
							allowClear: true
						});
					});
					
					function addTableList(me){
						thisVal = me.value;
						thisName = me.name;
						listNum = parseFloat(thisVal) + 1;
						$('[name='+thisName+']').val(listNum);
						clone = $('.journal_entry:last-of-type').clone();
						clone.appendTo('tbody');
						//alert(clone).replaceAll('5','6');
						$('.journal_entry:last-of-type').attr('id',listNum);
						$('.journal_entry:last-of-type input#meta-code').attr('name','code-'+listNum).attr('element','elementTitle-'+listNum);
						$('.journal_entry:last-of-type input#meta-Dr').attr('name','journal[Dr]['+listNum+']');
						$('.journal_entry:last-of-type input#meta-Cr').attr('name','journal[Cr]['+listNum+']');
						$('.journal_entry:last-of-type span.ellipsis').attr('id','elementTitle-'+listNum);
						$(document).ready(function() {
							//$(':input').inputmask();
							webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
						});
					}
					
						$('input#journal_date').daterangepicker({
							singleDatePicker: true,
							autoUpdateInput: true,
							".(false ? "minDate: moment().subtract(-1, 'days'),maxDate: moment().subtract(-1, 'days')," : "")."
							calender_style: 'picker_4',
							locale: {
								format: 'YYYY/MM/DD'
							}
						}, function (start, end, label) {
							[moment().subtract(29, 'days'), moment()]
							console.log(start.toISOString(), end.toISOString(), label);
						});
				</script>
			";
				break; // END CASE JOURNAL
				
				case "payroll":
				$this->schema = Info::DB_NAME;
				$fieldValue[] = $tableListings = "";
				$defaultDateRanges = "true";
				$getParseValue = $this->getValueDB(["table"=>$parseData['table'],"id"=>$parseData['value']]);
				$getFields = $this->getTableFields(['table'=>$parseData['table'],'exclude'=>['id']]);
				foreach($getFields as $var){
					$fieldValue[$var] = (isset($getParseValue[$var]) && $parseData['value'] != "") ? $getParseValue[$var] : "";
				}
				if($parseData['value']){
					$defaultDateRanges = "false";
					$fieldValue['start'] = date_create($fieldValue['start']);
					$fieldValue['start'] = $startDate = date_format($fieldValue['start'], 'Y/m/d');
					$fieldValue['end'] = date_create($fieldValue['end']);
					$fieldValue['end'] = $endDate = date_format($fieldValue['end'], 'Y/m/d');
					$startDate = "'".$fieldValue['start']."'";
					$endDate = "'".$fieldValue['end']."'";
				}else{
					$startDate = "moment().subtract(12, 'days')";
					$endDate = "moment()";
				}

				$cutOffDate = $this->inputGroup(['label'=>'Cut-Off Date','type'=>'date','id'=>'dateRange','name'=>'cutOffDate','placeholder'=>'Cut-off Date','value'=>$fieldValue['start']." - ".$fieldValue['end']]);
				//$cutOffDate = "<input title='Report Date' value='' type='text' id='inputDate' name='dateAsOf' placeholder='yyyy-mm-dd' class='form-control date-picker'>";

				$title = $this->inputGroup(['label'=>'Payroll Title','type'=>'text','id'=>'title','name'=>'title','placeholder'=>'Payroll Description','value'=>$fieldValue['title']]);//$this->Params['last_name']
				$payroll_set = $this->inputGroup(['label'=>'Payroll Type','type'=>'select','id'=>'payroll_set','name'=>'payroll_set','meta_key'=>'payroll_set','meta'=>'codebook','placeholder'=>'Select Payroll Type','value'=>$fieldValue['payroll_set']]);

				$popUpCreate = ($parseData['value']) ? "
					<button type='button' title='Upload New Logs' value='' id='uploadLogsBtn' name='uploadLogsBtn' handle='createLogRecords' action='showElement' element='fileImport' class='btn add paddingHorizontal' onclick=\"showElement(this)\"><i class='fa fa-upload'></i></button>
					<button type='button' title='Create New Logs' value='{$parseData['value']}' id='{$this->Params['table']}LogsBtn' name='create{$this->Params['table']}Logs' handle='createLogRecords' action='popupBox' table='{$this->Params['table']}_logs' class='btn add paddingHorizontal' onclick=\"getPopup(this,'console_{$this->Params['table']}')\" data-toggle='modal' data-target='.popup-console_{$this->Params['table']}'><i class='fa fa-plus'></i></button>
					"
					: "";

				$formField = "
				<form id='createRecords' data-toggle='validator' name='createPayrollEntry' class='form-label-left input_mask' novalidate>
					<input type='hidden' name='action' id='action' value='createRecords' />
					<input type='hidden' name='table' id='table' value='{$this->Params['table']}' />
					<input type='hidden' name='theID' id='theID' value='{$parseData['value']}' />
			
					<div class='x_panel no-padding borderBox'><div class='box_title'><h2 class='left capitalize'>Create Employee {$this->Params['table']}'s</h2></div><div class='x_content no-padding'>
						<div class='form-group no-padding item'>
							<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$payroll_set}</div>
							<div class='col-md-6 col-sm-6 col-xs-12 no-padding'>{$cutOffDate}</div>
						</div>
						<div class='form-group no-padding item col-4'>{$title}</div>
					</div>
					<div class='form-group bottomSubmit'>
						<div class='col-md-10 col-sm-10 col-xs-12 col-md-offset-2 no-padding'><button type='submit' title='Submit payroll records' value='' id='submitEntry' name='createRecords' class='btn btn-success capitalize paddingHorizontal' onclick='submitData(this)'>".($parseData['value'] ? 'Update' : 'Create')." {$this->Params['table']}</button>{$popUpCreate}</div>
					</div>
					</div>
				</form>
				";

				if(isset($parseData['value']) && $parseData['value'] != ""){ // LIST OF PAYROLL LOGS
					$tbHeader = $tblLists = "";
					$this->tblCol = ['id_number'=>['ID Number','10%'],'bio_id'=>['Bio ID','6%'],'full_name'=>['Employee\'s Full Name',''],'employment_level'=>['Emp. Level','12%'],'gender'=>['Gender','14%'],'unit'=>['Department','18%'],'designation'=>['Position','18%']];

					$colCenter = ['id_number','bio_id'];
					foreach($this->tblCol as $tblKey => $tblValue){
						$alignClass = (in_array($tblKey, $colCenter)) ? "alignCenter" : "paddingLeft";
						$tbHeader .= "<th width='{$tblValue[1]}' class='{$alignClass} no-padding'>{$tblValue[0]}</th>";
					}

					$stmtElements = ['schema'=>Info::DB_NAME,'table'=>$this->Params['table'].'_logs','arguments'=>['record_id'=>$parseData['value']],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'fields'=>['staff_id','id','record_id']];
					$getElements = self::selectDB($stmtElements);
					//var_dump($getElements);
					$cnt = 1;
					//$this->pageAction = ($this->isApprove) ? "view" : "";
					$this->Params['pageName'] = "payroll_logs-page";
					foreach($getElements as $staff_ID => $metaStmt){
						$metaStmt[0]->staff_id = $staff_ID;
						$listValue = $metaStmt;
						$tblLists .= self::getTableListings($listValue);
						$cnt++;
					}

					$tableListings = "
					<div class='x_content'>
						<table id='payroll_logs' width='100%' class='table table-bordered'>
							<thead>
							<tr class='default'>{$tbHeader}<th class='no-padding no-sort actionBtn'>&nbsp;</th></tr>
							</thead>
							<tbody>{$tblLists}</tbody>
						</table>
					</div>
					";
				}

				$output = $formField.$tableListings;
				$output .= "<script>
				$(document).ready(function() {
					$(':input').inputmask();
					$('.select2_single').each(function() {
						$(this).select2({
							placeholder: $(this).attr('placeholder'),
							allowClear: false
						});
					});
					
					$('#payroll_logs').DataTable( {
						dom: 'Bfrtip',
						'searching': true,
						'bPaginate': false,
						'bInfo': false,
						'order': [[ 2, 'asc' ]],
						responsive: true,
						buttons: [
							{
								extend: 'copy',
								className: 'hide fa fa-clipboard'
							},
							{
								extend: 'csv',
								className: 'hide fa fa-table'
							},
							{
								extend: 'print',
								className: 'hide fa fa-print'
							},
								{
							 extend: 'excel',
							 className: 'btn-sm'
							},
							{
							extend: 'pdfHtml5',
							className: 'btn-sm'
							},
						],
					});

					$('input#dateRange.date-picker').daterangepicker({
						singleDatePicker: false,
						opens: 'left',
						startDate: {$startDate},
						endDate:  {$endDate},
						
						calender_style: 'picker_4',
						locale: {
							format: 'YYYY/MM/DD'
						}
					}, function (start, end, label) {
						console.log(start.toISOString(), end.toISOString(), label);
					});
				});
				</script>";
				break;

		}

		return $output;
	}

	public function getOptionLists(){
		$stmtAccounting['fields'] = ['meta_terms.meta_value','charts_meta.type','charts_meta.id','charts.code','charts.title'];
		$stmtAccounting['table'] = 'charts_meta AS charts_meta';
		$stmtAccounting['join'] = '
			JOIN '.Info::PREFIX_SCHEMA.Info::DB_ACCOUNTING.'.charts AS charts ON charts.id = charts_meta.id
			JOIN '.Info::PREFIX_SCHEMA.Info::DB_SYSTEMS.'.meta_terms AS meta_terms ON meta_terms.meta_id = charts_meta.type AND meta_terms.meta_key = "accounting_type"
			';
		$stmtAccounting['extra'] = 'ORDER BY type, code ASC';
		$stmtAccounting['arguments'] = ["charts_meta.status>"=>1];
		$stmtAccounting += ['schema'=>Info::DB_ACCOUNTING,'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC];
		$getAccounting = $this->selectDB($stmtAccounting);
		$optionGroup = "";
		foreach($getAccounting as $actgTitle => $actgCodes) {
			$options = "";
			foreach($actgCodes as $keyID => $objectValue){
				$options .= "<option code='{$objectValue['code']}' value='{$objectValue['id']}'>{$objectValue['code']}: {$objectValue['title']}</option>";
			}
			$optionGroup .= "<optgroup label='{$actgTitle}'>{$options}</optgroup>";
		}

		$output = "<option></option>".$optionGroup;
		//$output .= "<script></script>";

		return $output;
	}
	
	public function logData($params){
		$stmtDataElements['fieldValues'] = ["data_logs"=>$params['values']];
		$stmtDataElements['schema'] = Info::DB_DATA;
		$stmtDataElements['table'] = "data_queue";
		$stmtDataElements['id'] = $params["id"];
		$cntUpdate = $this->updateDB($stmtDataElements);
	}

	public function selectDB($stmtElements){
		//$valueExecute = [];
		$theArguments = $extraArguments = $extraStmts = $joinStatement = "";
		$dbSchema = $this->schema;
		if(isset($stmtElements['extra'])) $extraStmts = " ".$stmtElements['extra'];
		if(isset($stmtElements['join'])) $joinStatement = " ".$stmtElements['join'];
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		if(!isset($stmtElements['table'])) $stmtElements['table'] = $this->Params['table'];
		$pdoFetch = (isset($stmtElements['pdoFetch']))? $stmtElements['pdoFetch'] : PDO::FETCH_ASSOC;
		$stmtFields = (sizeof($stmtElements['arguments']) > 0) ? $stmtElements['arguments'] : "";//['user'=>1,'type'=>1];
		if($stmtFields) {
			foreach ($stmtFields as $key => $value) {
				$valueExecute[] = $value;
				$stmtArgs[] = "{$key}=?";
			}
			$theStatement = implode(",", $stmtElements['fields']);
			$theArguments = implode(' AND ', $stmtArgs);
		}
		if(isset($stmtElements['between'])) $extraArguments = ($stmtFields ? ' AND ' : '').$stmtElements['between'][0]." BETWEEN '".$stmtElements['between'][1]."' AND '".$stmtElements['between'][2]."'";
		//$stmt = $this->db->prepare("SELECT * FROM {$this->schema}.cp_{$stmtElements['table']} WHERE ? = '?' AND ? = '?'"); //".$this->schema.".".$stmtElements['table']
		//$connectDB = dbConnect($this->schema);
		$stmt = $this->db->prepare("SELECT ".$theStatement." FROM ".Info::PREFIX_SCHEMA."{$dbSchema}.{$stmtElements['table']} {$joinStatement} WHERE ".$theArguments.$extraArguments.$extraStmts);
		//$this->test = "SELECT ".$theStatement." FROM ".Info::PREFIX_SCHEMA."{$this->schema}.{$tablePrefix}{$stmtElements['table']} {$joinStatement} WHERE ".$theArguments.$extraArguments.$extraStmts;
		$stmt->execute($valueExecute);
		$result = $stmt->fetchAll($pdoFetch); //FETCH_ASSOC
		//if($stmtElements['table'] == 'charts_meta AS charts_meta') var_dump($stmt);

		return $result;
	}

	public function deleteDB($stmtElements){
		//$deletePostValue = $methods->deleteDB(['table'=>'codebook','id'=>[217,218,219],'schema'=>'clamp_systems']);
		$dbSchema = $this->schema;
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		if(!isset($stmtElements['table'])) $stmtElements['table'] = $this->Params['table'];
		//$connectDB = dbConnect($dbSchema);
		$clause = implode(',', array_fill(0, count($stmtElements['id']), '?'));
		$stmt = $this->db->prepare("DELETE FROM ".Info::PREFIX_SCHEMA."{$dbSchema}.{$stmtElements['table']} WHERE id IN ({$clause})");
		$stmt->execute($stmtElements['id']);
		$result = $stmt->rowCount();
		$stmt = null;
		return $result;
	}

	public function updateDB($stmtElements){
		$dbSchema = $this->schema;
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		if(!isset($stmtElements['table'])) $stmtElements['table'] = $this->Params['table'];
		//$getFieldValues = ['code'=>'00000','title'=>'FOX YEAH'];
		$getFieldValues = $stmtElements['fieldValues'];
		foreach($getFieldValues as $field => $value){
			$setFieldValues[] = $field." = ?";
			$setValues[] = $value;
		}
		$setValues[] = $stmtElements['id'];
		$stmtFieldValues = implode(',',$setFieldValues);
		//$this->db = dbConnect($this->schema);
		$stmt = $this->db->prepare("UPDATE ".Info::PREFIX_SCHEMA."{$dbSchema}.{$stmtElements['table']} SET {$stmtFieldValues} WHERE id = ?");
		$stmt->execute($setValues);
		$result = $stmt->rowCount();
		$stmt = null;
		return $result;
	}

	public function insertDB($stmtElements){
		$dbSchema = $this->schema;
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		if(!isset($stmtElements['table'])) $stmtElements['table'] = $this->Params['table'];
		$clause = implode(',', array_fill(0, count($stmtElements['fields']), '?'));
		$stmtFields = implode(',',$stmtElements['fields']);
		//$connectDB = dbConnect($this->schema);
		$stmt = $this->db->prepare("INSERT INTO ".Info::PREFIX_SCHEMA."{$dbSchema}.{$stmtElements['table']} ({$stmtFields}) VALUES ({$clause})");
		$stmt->execute($stmtElements['values']);
		$result = $this->db->lastInsertId();
		//$result = $this->schema.' - '.$stmtElements['table'].' - '.$stmtElements['fields'].' - '.$clause.' - '.$stmtElements['values'];
		$stmt = null;
		return $result;
	}

	public function getValueDB($stmtElements){
		$dbSchema = $this->schema;
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		if(!isset($stmtElements['table'])) $stmtElements['table'] = $this->Params['table'];
		//$connectDB = dbConnect($dbSchema);
		$stmt = $this->db->prepare("SELECT * FROM ".Info::PREFIX_SCHEMA."{$dbSchema}.{$stmtElements['table']} WHERE ".array_keys($stmtElements)[1]."= ? ORDER BY id DESC");
		$stmt->execute([$stmtElements[array_keys($stmtElements)[1]]]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC); //FETCH_NUM must be used with list
		//$result = $this->schema.' - '.$stmtElements['table'].' - '.$stmtElements['fields'].' - '.$clause.' - '.$stmtElements['values'];
		//list($Dr, $Cr) = $arr;

		return $result;
	}

	public function getTableFields($stmtElements){
		$output = [];
		$dbSchema = $this->schema;
		if(isset($stmtElements['schema'])) $dbSchema = $this->schema = $stmtElements['schema'];
		$pdoFetch = (isset($stmtElements['pdoFetch']))? $stmtElements['pdoFetch'] : PDO::FETCH_COLUMN;
		//$connectDB = dbConnect($this->schema);
		$stmt = $this->db->prepare("DESCRIBE ".Info::PREFIX_SCHEMA.$this->schema.".".$stmtElements['table']); //".$this->schema.".".$stmtElements['table']
		$stmt->execute();
		$tableFields = $stmt->fetchAll($pdoFetch);
		if(isset($stmtElements['pdoFetch'])){ // TO CHECK FIELD METAS
			foreach($tableFields as $fieldMeta){
				if(!in_array($fieldMeta[0], $stmtElements['exclude'])) $output[] =  [$fieldMeta[0] => $fieldMeta[1]];
			}
		}else{
			foreach($tableFields as $fieldMeta){
				if(!in_array($fieldMeta, $stmtElements['exclude'])) $output[] =  $fieldMeta;
			}
			// if(isset($stmtElements['exclude'])){
			// $tableFields = array_diff($tableFields, $stmtElements['exclude']);
			// }
			//$output = $tableFields;
		}
		//$tableFields = array_diff($tableFields, $stmtElements['exclude']);
		return $output;
	}
	
	public function queryURL($exQuery) {
		$arrayParam = explode('&', $_SERVER['QUERY_STRING']);
		$cnt=0;
		$preFix='?';
		$output='';
		$query='';
		foreach ($arrayParam as $paramVar):
			$arrayQuery = explode('=', $paramVar);
			$theQuery = $arrayQuery[0];$valQuery = $arrayQuery[1];
			//if($exQuery!=$theQuery)$query .= $preFix.$theQuery.'='.$valQuery;
			if(!in_array($theQuery, $exQuery))$query .= $preFix.$theQuery.'='.$valQuery;
			$preFix='&';
			$cnt++;
		endforeach;
		$output = $query;
		return $output;
	}

	public function stringLimit($theString,$limit,$type) {
		$excerpt='';
		switch($type):
			case 'char':
				$excerpt = substr($theString, 0, $limit);
				if (strlen($theString)>$limit) {
					$excerpt = $excerpt.'&hellip;';
				}
				break;
			case 'word':
				$excerpt = explode(' ', $theString, $limit);
				if (count($excerpt)>=$limit) {
					array_pop($excerpt);
					$excerpt = implode(" ",$excerpt).'&hellip;';
				} else {
					$excerpt = implode(" ",$excerpt);
				}
				$excerpt = preg_replace('`\[[^\]]*\]`','',$excerpt);
				break;
		endswitch;

		return $excerpt;
	}

	public function setGroupElements($attributes){
		$thisElement = [];
		$this->Params["dataID"] = 0;
		$this->Params["pathID"] = $attributes["pathID"];
		if($attributes["dataID"]) $this->Params["dataID"] = $attributes["dataID"];
		$attrElements = explode(",",$attributes['elements']);

		foreach($attrElements as $element){
			$elementAlias = "";
			$arrayElement = explode(":",$element);
			if($arrayElement[1]) $elementAlias = str_replace($arrayElement[0]."-","",$arrayElement[1]);
//			if($arrayElement[0] == "group"){
//				$thisElement[$arrayElement[0]][$elementAlias] = self::getElements($elementAlias);
//			}elseif($arrayElement[0] == "procedure"){
//
//			}
			if($arrayElement[0] == "group"){
				$thisElement[][$arrayElement[0]][$elementAlias] = ($this->getElements($elementAlias)) ? $this->getElements($elementAlias) : "";
			}else{ // GETTING THE PROCEDURE AS PARENT
				$stmtProcedure = ['schema'=>Info::DB_SYSTEMS,'table'=>'procedure','arguments'=>['alias'=>$elementAlias],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['value']];
				$getProcedure = $this->selectDB($stmtProcedure);
				$thisElement[][$arrayElement[0]][$elementAlias] = $getProcedure[0];
			}

		}
		return $thisElement;//json_encode($thisElement);
	}
	
	public function getAdjectival($metaKey,$score){
		$output = "";
		$stmtAdjectival = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>$metaKey,'id >'=>1],'pdoFetch'=>PDO::FETCH_ASSOC,'extra'=>'ORDER BY id ASC','fields'=>['id','meta_key','meta_id','meta_option']];
		$getExtrasValue = $this->selectDB($stmtAdjectival);

		foreach($getExtrasValue as $theAdjectival){
			$adjectivalOption = $theAdjectival['meta_option'];
			$theAdjectivalRange = str_replace($metaKey.'_', '', $adjectivalOption);
			$thisAdjectivalRange = explode('-', $theAdjectivalRange);
			if(in_array($score, range($thisAdjectivalRange[0],$thisAdjectivalRange[1]))) $output = $theAdjectival['meta_id'];
		}
		return $output;
	}

	public function getElements($alias){
		$output = $dataValue = [];
		$GLOBALS['data']['client_id'] = "";
		$getElements[$alias] = $_SESSION['groups'][$alias];
		$paramsDataID = (isset($this->Params["dataID"]) && $this->Params["dataID"] != "") ? $this->Params["dataID"] : 0;
		foreach($getElements as $elementAlias => $elementDetails){
			$getDataRecords = [];
			if($paramsDataID > 0){
				$getDataFields = array_merge(['data_id'],$this->getTableFields(["table"=>"path_{$this->Params["pathID"]}_{$elementAlias}",'exclude'=>['id','date'],'schema'=>Info::DB_DATA]));
				$stmtDataRecords = ['schema'=>Info::DB_DATA,'table'=>"path_{$this->Params["pathID"]}_{$elementAlias}",'arguments'=>['data_id'=>$paramsDataID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$getDataFields];
				$getDataRecords = $this->selectDB($stmtDataRecords);
				//var_dump($elementAlias);
			}
			$arrayElement = explode(",",$elementDetails->elements);
			//$theElement = str_replace($arrayElement[0]."-","",$arrayElement[1]);
			foreach($arrayElement as $theElement){
				$thisElement = explode(":",$theElement);
				$theField = str_replace($thisElement[0]."-","",$thisElement[1]);
				if($thisElement[0] == "field" || $thisElement[0] == "custom"){ // DISPLAYING PATH DATA
					if($paramsDataID > 0){
						if($thisElement[0] == "custom"){
							$theField = $thisElement[1];
							$dataValue[$theField] = (isset($getDataRecords[$paramsDataID]->$theField)) ? $getDataRecords[$paramsDataID]->$theField : "";//json_encode($getDataRecords,true);
						}else{
							$dataValue[$theField] = $output[$theField] = (isset($getDataRecords[$paramsDataID]->$theField)) ? $getDataRecords[$paramsDataID]->$theField : "";//json_encode($getDataRecords,true);
						}
					}else{
						if($thisElement[0] == "custom"){
							$dataValue[$theField] = "";
						}else{
							$output[$theField] = "";
						}
					}
					
				}elseif($thisElement[0] == "procedure" && EMPTY($this->getElementType)){ // GETTING THE PROCEDURE AS CHILD
					//$output[$thisElement[0]][$theField] = $theField;
					$stmtProcedure = ['schema'=>Info::DB_SYSTEMS,'table'=>'procedure','arguments'=>['alias'=>$theField],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['value']];
					$getProcedure = $this->selectDB($stmtProcedure);
					if($thisElement[0]) $output[$thisElement[0]][$theField] = $getProcedure[0];
				}elseif(!EMPTY($this->getElementType) && $thisElement[0] != "procedure"){
					$output[$theField] = $this->getElements($theField);
				}
			}
			//$dataValue += $output;//array_merge($output,$dataValue);
			//unset($dataValue['loan_application']);
		}
		if($this->globalQueueData->id > 0){
			$this->globalDataValue['post_id'] = $this->globalQueueData->id;
			$this->globalDataValue['data_id'] = $this->Params["dataID"];
			$this->globalDataValue[$alias] = $dataValue;
		}
		
		return $output;
	}
	
	public function elementTree($alias){
		$output = $elements = $value = [];
		$GLOBALS['data']['client_id'] = "";
		$getElements = $_SESSION['groups'][$alias]->elements;
		$elementTree = explode(",",$getElements);
		foreach($elementTree as $element){
			$elementValues = explode(":",$element);
			//$thisElements = [$elementValues[0]=>$elementValues[1]];
			$elementKey = str_replace($elementValues[0]."-","",$elementValues[1]);
			$elementValues[1] = $elementKey;
			if($elementValues[0] == 'group'){
				$elementValue = $this->elementTree($elementKey);
				$elementValue = $elementValue['elements'];
			}else{
				$elementValue = [$elementValues[0]=>$elementKey];
			}
			$value[$elementValues[0]][$elementKey] = $elementValue;
			$elements[$elementKey] = $elementValue;
		}
		//$value['fox'] = $value;
		//$output['group'] = $value['group'];
		$output['elements'] = $elements;
		$output['meta'] = $value;
		//$output = ["elements"=>$elements,"value"=>$value];
		return $output;
	}

	public function elementForm($getPostElements){
		$output = "";
		$panel = "";
		//$arrayPostElements = json_decode($getPostElements);
		foreach($getPostElements as $panelBox => $panelValue){
			foreach($panelValue as $element => $aliasElements){
				if($element == "group"){
					$thisPageAction = $this->pageAction;
					foreach($aliasElements as $groupAlias => $groupInfo){
						//if($groupAlias == "loan_summary" && $this->pageAction == "view") $this->pageAction = ""; // TO EXCLUDE LOAN SUMMARY INPUTS DISABLED
						$fieldBox = $this->fieldBox($groupAlias,$groupInfo);
						$panel .= "<div class='x_panel {$this->pageAction}' id='{$groupAlias}'><div class='x_title'><h2>{$_SESSION['groups'][$groupAlias]->name}<span class='subTitle'>|| {$_SESSION['groups'][$groupAlias]->description}</span></h2><ul class='nav navbar-right panel_toolbox'><li class='right'><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li></ul></div><div class='fieldGroup x_content no-padding'>".$fieldBox."</div></div>";
						$this->pageAction = $thisPageAction; // TO REVERT PAGE ACTION
					}
				}else{
					foreach($aliasElements as $groupAlias => $groupInfo){
						$panel .= $this->setProcedure($aliasElements[$groupAlias]); //EXTRACTING PROCEDURE HERE	$panel .= $aliasElements;
					}
				}
			}

		}
		$output = $panel;
		return $output;
	}
	
	public function fieldBox($groupAlias,$groupInfo, $dataType = ""){
		$output = "";
		
		//$this->pageAction = ""; // INPUT FIELDS EDITABLE
		foreach($groupInfo as $inputAlias => $inputValue){
			if($inputAlias == "procedure"){
				foreach($inputValue as $procedureName => $procedureValue){
					$output .= $this->setProcedure($procedureValue); //EXTRACTING PROCEDURE HERE AS CHILD
				}
			}else{
				$inputAttr = "";
				if($dataType){
					$attrName = $inputAlias;
				}else{
					$attrName = "{$groupAlias}[{$inputAlias}]";//$groupAlias."_".$inputAlias;
				}
				
				self::customPageAction($inputAlias); // SETTING FOR CUSTOM/FORCE FIELD PAGEACTION

				$fieldType = $_SESSION['fields'][$inputAlias]->field_type;
				if($inputAlias == "description") $fieldType = 9;
				$codebookFields = ["field_type","codebook_id"]; //,"tokens","path"
				if(in_array($inputAlias,$codebookFields)) $fieldType = 1;
				$inputCustom = "";
				switch($fieldType){
					case 1: $inpuType = "select"; $inputAttr = ",'meta_key'=>'codebook','meta'=>'{$inputAlias}'"; break; //$attrName = $inputAlias;
					case 4: $inpuType = "date"; break;
					case 8: $inpuType = "number"; break;
					case 9: $inpuType = "textarea"; break;
					case 10: $inpuType = "amount"; break;
					case 12: $inpuType = "file"; $inputCustom = ["group_name"=>$groupAlias]; break;
					case 13: $inpuType = "hidden"; break;
					case 14: $inpuType = "block"; break;
					default: $inpuType = "text"; break;
				}
				$inputLabel = (!EMPTY($_SESSION['fields'][$inputAlias]->name)) ? $_SESSION['fields'][$inputAlias]->name : "Field {$inputAlias}";
				$thisInput = $this->inputGroup(['label'=>$inputLabel,'type'=>$inpuType,'id'=>$inputAlias,'name'=>$attrName,'title'=>$_SESSION['fields'][$inputAlias]->description,'meta_key'=>$inputAlias,'meta'=>'codebook','placeholder'=>$_SESSION['fields'][$inputAlias]->description,'value'=>$inputValue,'custom'=>$inputCustom]);
				### CUSTOM FIELD INSERTED START ###
				// if($inputAlias == "loan_interest_percentage"){
					// $loanAmortizationTypeValue = (!EMPTY($this->globalDataValue['loan_details']['amortization_type'])) ? $this->globalDataValue['loan_details']['amortization_type'] : "";
					// $thisInput .= $this->inputGroup(['type'=>'select','id'=>'amortization_type','name'=>'amortization_type','meta_key'=>'amortization_type','meta'=>'codebook','placeholder'=>'Select amortization type...','value'=>$loanAmortizationTypeValue]);
				// }elseif($inputAlias == "payment_mode"){
					// $loanAmortizationTypeValue = (!EMPTY($this->globalDataValue['loan_details']['interest_type'])) ? $this->globalDataValue['loan_details']['interest_type'] : "";
					// $thisInput .= $this->inputGroup(['type'=>'select','id'=>'interest_type','name'=>'interest_type','meta_key'=>'interest_type','meta'=>'codebook','placeholder'=>'Select interest type...','value'=>$loanAmortizationTypeValue]);
				// }
				### CUSTOM FIELD INSERTED END ###
				$output .= '<div id="'.$groupAlias.'_'.$inputAlias.'" class="form-group half no-padding '.$inpuType.' '.($this->pageAction ? $this->pageAction : "edit").' item">'.$thisInput.'</div>'; // $inputObj
			}
		}
		return $output;
	}

	public function setProcedure($content){
		$output = [];
		ob_start();
		$keyReplace = Info::value("eval_key_strict"); // GET KEY STRICT IN EVAL, INFO::VALUE
		foreach($keyReplace as $key => $value){ // TO REPLACE VALUES WITH STRICT SYNTAX
			$content = str_replace($key, $value, $content);
		}
		
		eval($content);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	// function procedureContent($content){
		// $output = [];
		// ob_start();
		// eval($content);
		// $output = ob_get_contents();
		// ob_end_clean();
		// return $output;
	// }

	// FIELD INPUT
	public function input($attributes){
		$this->attr = $attributes;
		$return = "<input type='{$this->attr['type']}' id='{$this->attr['id']}' name='{$this->attr['name']}' value='{$this->attr['value']}' ".(isset($this->attr['class']) && $this->attr['class'] != '' ? 'class="'.$this->attr['class'].'"' : '')." ".(isset($this->attr['custom']) && $this->attr['custom'] != '' ? $this->attr['custom'] : '').">";
		return $return;
	}

	// FIELD INPUT BOX
	public function inputGroup($attributes){
		$theLabel = $hiddenValue = ''; $getFilterLists = $getStmtMeta = $tokensValues = [];
		$this->attr = $attributes;
		if(isset($this->attr['label']))$theLabel = '<label for="'.$this->attr['id'].'" class="alignRight '.$this->attr['type'].' control-label col-md-4 col-sm-4 col-xs-12">'.$this->attr['label'].'</label>';
		$this->attr['required'] = (isset($this->attr['required'])) ? $this->attr['required'] : '';
		$this->attr['placeholder'] = (isset($this->attr['placeholder']) && $this->attr['placeholder'] != "") ? $this->attr['placeholder'] : ""; 
		switch($this->attr['type']){
			case 'file':
				$this->attr['custom'] = (isset($this->attr['custom']))? $this->attr['custom'] : [];
				$attachmentName = preg_replace('/\s+/', '', $this->attr['label']);
				if($this->pageAction == "view"){
					$getFileAttachment = "";
					if($this->attr['value']){
						$arrayFileAttachment = explode(",",$this->attr['value']);
						$fileLabel = ["Member's Photo/Image","Member's Signature"];
						$cnt = 0;
						foreach($arrayFileAttachment as $fileName){
							$getFileAttachment .=  '<span class="fileAttachment" name="'.$this->attr['name'].'" data-toggle="modal" meta-box="'.$this->attr['id'].'" data-target=".viewattachments" file="'.$fileName.'"><i class="fa fa-search-plus"></i>'.$fileLabel[$cnt].'</span>';
							$cnt++;
						}
					}else{
						$getFileAttachment = "<span class='placeholder'>".self::EMPTY_VAL."</span>";
					}
					$inputBox = $getFileAttachment;
				}else{
					$inputBox = self::attachmentBox($attachmentName,[$this->attr['custom']['group_name'],$this->attr['id']],$this->attr['value']);
				}
				
				$return = $theLabel.'<div class="col-md-'.((isset($this->attr['label']))? '8':'12').' col-xs-12 no-padding type-'.$this->attr['type'].' '.($this->pageAction == "view" ? "view-file" : "edit").'">'.$inputBox.'</div>';
				$return .= '<input type="hidden" name="'.$this->attr['name'].'" id="'.$this->attr['id'].'" value="'.$this->attr['value'].'">';
				if($this->pageAction != "view"){
					$return .= '<script>
						Dropzone.options.'.$attachmentName.' = {
							paramName: "file",
							maxFiles: 2,
							params: {"data_id":'.($this->Params["dataID"] > 0 ? $this->Params["dataID"] : '""').',"member_id":"'.($this->globalDataValue['loan_details']['client_id'] ? $this->globalDataValue['loan_details']['client_id'] : '').'","user":"'.$_SESSION['userID'].'"},
							acceptedFiles: ".jpg,.jpeg,.png",
							createImageThumbnails: true,
							renameFilename: "yeah",
							maxThumbnailFilesize: 10,
							thumbnailWidth: 164,
							thumbnailHeight: 164,
							filesizeBase: 1000,
							dictDefaultMessage: "'.$this->attr['placeholder'].'",
							url: "'.Info::URL.'/storage.php?upload_type='.$this->attr['id'].'",
							maxFilesize: 500,
							init: function() {
								
								this.on("success", function(file, response) {
									fileValues = []; fileName = ""; comma = "";
									profileAttachmentValue = $("[name='.$this->attr['name'].']").val();
									if(profileAttachmentValue != ""){
										comma = ",";
									}
									getResponse = JSON.parse(response);
									fileName = getResponse.file_name;
									fileName = profileAttachmentValue+comma+fileName;
									console.log(getResponse);

									// $("#'.$attachmentName.'.dropzone .dz-complete .dz-details > .dz-filename").each(function() {
									// fileName = $(this).text();
									// fileValues.push(fileName);
									// });
									//console.log("'.$attachmentName.'");
									$("[name='.$this->attr['name'].']").val(fileName);
								});
							}
						}
						</script>';
				} // NOT VIEW
				
				break;
			case 'textarea':
				$this->attr['custom'] = (isset($this->attr['custom']))? $this->attr['custom'] : '';
				$inputBox = ($this->pageAction == "view") ? '<span class="ellipsis text-view" name="'.$this->attr['name'].'">'.($this->attr['value'] ? $this->attr['value'] : "<span class='placeholder'>".self::EMPTY_VAL."</span>").'</span>' : '<textarea '.$this->attr['custom'].' title="'.$this->attr['title'].'" id="'.$this->attr['id'].'" name="'.$this->attr['name'].'" class="form-control '.$this->editable.'" tabindex="-1" '.$this->readonly.' '.$this->attr['required'].' placeholder="'.$this->attr['placeholder'].'">'.$this->attr['value'].'</textarea>';
				$return = $theLabel.'<div class="col-md-'.((isset($this->attr['label']))? '8':'12').' col-xs-12 no-padding type-'.$this->attr['type'].' edit">'.$inputBox.'</div>';
				break;
			case 'select': case 'select2': case 'element':
				$selectType = "single";
				$apiOptions = [];//["loan_types"]; # TO GET ON API OPTION INSTEAD OF CODEBOOK, SHOW IN POSTS
				$this->attr['custom'] = (isset($this->attr['custom']))? $this->attr['custom'] : '';
				$select2Class = $select2Attr = $optionAttr = ""; $isSelect2 = false;
				if($this->attr['type'] == 'select2')$isSelect2 = true;
				$selectOptions = ($this->popupFormID && $this->attr['value'] > 0) ? "" : '<option></option>';

				switch($this->attr['meta']){
					case "charts":
						// GET STAFFS ALREADY SET AS USERS
						$stmtAccounting['fields'] = ['charts_meta.id','charts.code','charts_meta.status','charts.title'];
						$stmtAccounting['table'] = 'charts_meta AS charts_meta';
						$stmtAccounting['join'] = 'JOIN '.Info::PREFIX_SCHEMA.Info::DB_ACCOUNTING.'.charts AS charts ON charts.id = charts_meta.id';
						$stmtAccounting['extra'] = 'ORDER BY type, code ASC';
						$stmtAccounting['arguments'] = ["charts_meta.status"=>1];
						$stmtAccounting += ['schema'=>Info::DB_ACCOUNTING,'pdoFetch'=>PDO::FETCH_ASSOC];
						$getStmtMeta = $this->selectDB($stmtAccounting);

						$optionFields = ['code','id',['code','title'],'status'];
						break;
						
					case 'groups': case 'path': case 'tokens':
						if($this->attr['meta'] == "tokens" && $this->attr['id'] == "tokens") $selectType = "multiple";
						$tokensValues = explode(",",$this->attr['value']);
						$stmtMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->attr['meta'],'arguments'=>['id>'=>1,'status>'=>1],'pdoFetch'=>PDO::FETCH_ASSOC,'extra'=>'ORDER BY alias ASC','fields'=>['id','alias','name']];
						$getStmtMeta = $this->selectDB($stmtMeta);
						$optionFields = ['alias','id','name','status'];
						break;

					case "codebook":
						$stmtMeta['extra'] = "";
						$stmtMeta['pdoFetch'] = ($this->pageAction == "view") ? PDO::FETCH_GROUP | PDO::FETCH_CLASS : PDO::FETCH_ASSOC;
						if($this->attr['id'] == "codemeta"){
							$stmtMeta['arguments'] = ['field_type'=>1];
							$theTable = $this->attr['meta_key'];
							$tableFields = ['id','alias','name'];
							$optionFields = ['alias','id','name'];
						}else{ // IN CODEBOOK
							$stmtMeta['arguments'] = ['meta_key'=>$this->attr['meta_key'],'status>'=>1];
							$theTable = $this->attr['meta'];
							$tableFields = ['meta_id','meta_option','meta_value'];
							$optionFields = ['meta_option','meta_id','meta_value'];
							switch($this->attr['meta_key']){
								case "accounting_code":
									$metaKeyValues = "";
									//$stmtMeta['arguments'] = ['active'=>1];
									if($_SESSION["userrole"] > 3 && $this->globalQueueData->id < 1){ // BRANCH MANAGER AND CLERK ONLY
										if($_SESSION["meta_accounting_code"]){
											$metaKeyValues = implode(",",array_map('strval', array_keys($_SESSION["meta_accounting_code"])));
										}else{
											$stmtMeta['arguments'] = [];
										}
									}elseif($this->globalQueueData->id > 0){ // GET ACCOUNTING KEYS THRU POST DATA UNIT FOR SUPER ADMIN AND ADMIN
										$metaKeyValue = [];
										$codemetaActgCode = $_SESSION["codemeta"]["accounting_code"];
										foreach($codemetaActgCode as $actgValues){
											if($actgValues["meta_parent"] == $this->globalQueueData->unit) $metaKeyValue[] = (int)$actgValues["meta_value"];
										}
										$metaKeyValues = implode(",",$metaKeyValue);
									}
									if($metaKeyValues) $stmtMeta['extra'] = " AND id IN ({$metaKeyValues})";
								break;
							}
						}
						$stmtMeta['extra'] .= 'ORDER BY '.$tableFields[0].' ASC';
						$stmtMeta += ['schema'=>Info::DB_SYSTEMS,'table'=>$theTable,'fields'=>$tableFields];
						$getStmtMeta = $this->selectDB($stmtMeta);

						//var_dump($getStmtMeta);
						break;
					case "users":
						$stmtMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>$this->attr['meta'],'arguments'=>['status'=>1],'pdoFetch'=>PDO::FETCH_ASSOC,'extra'=>'ORDER BY username ASC','fields'=>['id','username','staff_id','status']];
						if($this->Params['table'] == 'users' && $this->attr['name'] == 'supervisor') $stmtMeta['arguments'] += ['role<'=>4]; // FORM USERS
						$getStmtMeta = $this->selectDB($stmtMeta);
						$thisSupervisorLists = array_column($getStmtMeta,'staff_id');
						$supervisorLists = implode(",",$thisSupervisorLists);
						$stmtSupervisorStaffs = ['schema'=>Info::DB_NAME,'table'=>'staffs','arguments'=>['status'=>1],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'extra'=>'and id IN ('.$supervisorLists.')','fields'=>['id','first_name','middle_name','last_name','suffix_name','designation','unit']];
						$getSupervisorStaffs = $this->selectDB($stmtSupervisorStaffs);
						//var_dump($getSupervisorStaffs);
						$optionFields = ['username','id','username','status'];
						break;
				}
				$metaStmtValue = "";
				if(!$this->pageAction){ // FORM EDITABLE
					foreach($getStmtMeta as $optionMeta){
						//$optionAttr = ($this->attr['meta'] != 'codebook' && $this->attr['meta'] != 'tokens' && $optionMeta[$optionFields[3]] < 1) ? "" : "";
						if(is_array($optionFields[2])){
							$optionMetaValue = [];
							foreach($optionFields[2] as $optionfield){
								if($optionMeta[$optionfield]) $optionMetaValue[] = $optionMeta[$optionfield];
							}
							$optionText = implode(", ",$optionMetaValue);
						}else{
							$thisUserID = "";
							if($this->attr['meta'] == 'users' && $this->attr['name'] == 'supervisor' && $this->Params['table'] == 'users' && isset($_SESSION['list_supervisor'])){ // FORM USERS
								$staffUserID = $optionMeta['staff_id'];
								$userStaffDetails = $getSupervisorStaffs[$staffUserID][0];
								$optionText = implode(" ",[$userStaffDetails->first_name,$userStaffDetails->middle_name,$userStaffDetails->last_name,$userStaffDetails->suffix_name]);
							}else{
								$optionText = $optionMeta[$optionFields[2]];
							}

						}
						$optionValue = $optionMeta[$optionFields[1]];
						$selected = "";
						if($selectType == "multiple"){
							if(in_array($optionValue,$tokensValues)){
								$selected = "selected";
							}
						}else{
							$selected = ($optionMeta[$optionFields[1]] == $this->attr['value']) ? "selected" : "";
						}
						
						if($getFilterLists && in_array($optionValue, $getFilterLists) && !$selected) $optionAttr = "disabled='disabled'";
						if($this->attr['id'] == "loan_types"){

						}
						$selectOptions .= "<option {$selected} {$optionAttr} title=\"\" alias=\"{$optionMeta[$optionFields[0]]}\" value=\"{$optionMeta[$optionFields[1]]}\">{$optionText}</option>"; // {$this->attr['placeholder']}	title
					}
				}else{ // READ ONLY
					if($selectType == "multiple"){
						$arrayValue = explode(",", $this->attr['value']);
						$metaValues = $this->array_search_by_key($getStmtMeta, $optionFields[1], $arrayValue, $optionFields[2]);	
						$getStmtMeta[$this->attr['value']][0]->meta_value = implode(", ",$metaValues);//$this->attr['value'];
					}elseif(in_array($this->attr['id'], $apiOptions)){
						$getStmtMeta[$this->attr['value']][0]->meta_value = $this->attr['value'];
						$hiddenValue = '<span '.$this->attr['custom'].' class="hide" name="'.$this->attr['name'].'">'.$this->attr['value'].'</span>';
					}else{
						//$hiddenValue = '<span '.$this->attr['custom'].' class="hide" name="'.$this->attr['name'].'">'.$this->attr['value'].'</span>';
						$hiddenValue = '<input '.$this->attr['custom'].' type="hidden" name="'.$this->attr['name'].'" value="'.$this->attr['value'].'">';
					}
				}
				// title="'.$this->attr['title'].'"
				$inputBox = ($this->pageAction == "view") ? $hiddenValue.'<span class="ellipsis text-view" field="'.$this->attr['name'].'" id="'.$this->attr['id'].'">'.(!EMPTY($getStmtMeta[$this->attr['value']][0]->meta_value) ? $getStmtMeta[$this->attr['value']][0]->meta_value : '<span class="placeholder">---</span>').'</span>' : '<select title="'.$this->attr['placeholder'].'" id="'.$this->attr['id'].'" name="'.$this->attr['name'].'" meta="'.$this->attr['meta'].'" meta_key="'.$this->attr['meta_key'].'" '.$selectType.' class="select2_'.$selectType.' '.$this->attr['name'].$select2Class.' form-control" tabindex="-1" '.$this->attr['required'].$select2Attr.' placeholder="'.$this->attr['placeholder'].'" '.$this->attr['custom'].'>'.$selectOptions.'</select>';
				$return = $theLabel.'<div class="col-md-'.((isset($this->attr['label']))? '8':'12').' col-xs-12 no-padding type-select edit">'.$inputBox.'</div>';

			break;
			default:
				$emptyVal = self::EMPTY_VAL;
				if($this->attr['type'] == 'number' || $this->attr['type'] == 'amount'){
					$emptyVal = 0;
					$inputType = $inputClass = 'number';
					$inputClass .= ' alignRight';
					$toFixed = ($this->attr['type'] == 'amount') ? 'data-number-to-fixed="2"' : '';
					if($this->attr['type'] == 'amount'){
						//$inputClass .= ' alignRight';
						$toFixed = 'data-number-to-fixed="2"';
					}

				}else{
					$inputType = $inputClass = $this->attr['type'];
					if($this->attr['type'] == 'date'){
						$inputType = "text";
						$inputClass = 'date-picker';
						$this->attr['placeholder'] = "YYYY-MM-DD";
					}
					$toFixed = '';
				}
				
				if($this->attr['type'] == 'block'){
					$inputType = "hidden";
					$inputClass .= ' hidden';
					//$this->attr['name'] = $this->attr['custom']['group_name']."_".$this->attr['meta_key'];
				}
				$decimal = 0;
				$this->attr['required'] = (isset($this->attr['required']))? $this->attr['required'] : '';
				$this->attr['title'] = (isset($this->attr['title']))? $this->attr['title'] : '';
				$this->attr['custom'] = (isset($this->attr['custom']))? $this->attr['custom'] : '';
				$inputFieldValue = '"'.$this->attr['value'].'"';
				if($this->attr['name'] == "loan_summary_other_charges"){ //strpos($inputFieldValue, '"')
					$inputFieldValue = "'".$this->attr['value']."'";
				}elseif($this->attr['type'] == 'amount' || $this->attr['type'] == 'number'){
					//if($this->pageAction == "view") $hiddenValue = '<span '.$this->attr['custom'].' id="'.$this->attr['id'].'" class="hide" name="'.$this->attr['name'].'">'.$this->attr['value'].'</span>';
					$decimal = 2;
					if($this->pageAction == "view") $hiddenValue = '<input '.$this->attr['custom'].' id="'.$this->attr['id'].'" type="hidden" name="'.$this->attr['name'].'" value="'.$this->attr['value'].'">';
					if($this->attr["value"]) $this->attr['value'] = ($this->attr['type'] == 'amount') ? number_format($this->attr["value"],2) : round($this->attr["value"],$decimal);//number_format($this->attr['value'],2);
				}elseif($this->pageAction == "view") {
					//$hiddenValue = '<span '.$this->attr['custom'].' id="'.$this->attr['id'].'" class="hide" name="'.$this->attr['name'].'">'.$this->attr['value'].'</span>';
					$hiddenValue = '<input '.$this->attr['custom'].' type="hidden" name="'.$this->attr['name'].'" value="'.$this->attr['value'].'">';
				}
				
				$isArray = json_decode( stripslashes( $inputFieldValue ) );
				if( $isArray === NULL ){
				   $inputFieldValue = str_replace('"', "'", $this->attr['value']);
				}
				//$inputFieldValue = (is_array($isArray)) ? "" : $inputFieldValue;
				$inputCustom = (isset($this->attr['custom']) && (is_array($this->attr['custom']) && sizeof($this->attr['custom']) > 0) || (!is_array($this->attr['custom']) && $this->attr['custom'] != "")) ? $this->attr['custom'] : "";
				
				if($this->pageAction == "view"){
					$inputBox = $hiddenValue.'<span '.$this->attr['custom'].' id="'.$this->attr['id'].'" class="ellipsis text-view '.$inputClass.'">'.($this->attr['value'] ? $this->attr['value'] : "<span class='placeholder'>".$emptyVal."</span>").'</span>';
				}else{
					$inputBox = '<input title="'.$this->attr['title'].'" value='.$inputFieldValue.' type="'.$inputType.'" id="'.$this->attr['id'].'" name="'.$this->attr['name'].'" '.$this->attr['required'].' placeholder="'.$this->attr['placeholder'].'" '.$this->readonly.' '.$toFixed.' class="form-control '.$inputClass.' '.$this->editable.'" '.$inputCustom.'>';
				}
				if($this->attr['type'] == 'block'){
					$inputBox .= "<span class='placeholder'>".$this->attr['placeholder']."...</span>";
				}
				
				$return = $theLabel.'<div class="col-md-'.((isset($this->attr['label']))? '8':'12').' col-xs-12 no-padding type-'.$this->attr['type'].' edit">'.$inputBox.'</div>';
			break;
		}
		return $return;
	}
	// END FIELD INPUT BOX
	
	public function postLogs($getDataLogs,$pathID){
		$output = $logDataHead = $logData = ""; $cnt = 0;
		#### USERS ####
		$stmtUsername = ['schema'=>Info::DB_SYSTEMS,'table'=>'users','arguments'=>['id >'=>0],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','username']];
		$getUsername = $this->selectDB($stmtUsername);
		switch($pathID){
			case "1": // MEMBERS
				$logDataFields = ['date','action','activity',['membership_information','member_id'],['personal_information','first_name'],['contact_information','contact_mobile'],['residential_address','address_city'],'user'];
			break;
			case "2": // SAVINGS
				$logDataFields = ['date','action','activity',['savings_details','balance'],'user'];
			break;
			case "3": // SAVINGS DEPOSITS
				$logDataFields = ['date','action','activity',['deposits_transactions','amount'],'user'];
			break;
			case "4": // SAVINGS WITHDRAWAL
				$logDataFields = ['date','action','activity',['withdrawal_transactions','amount'],'user'];
			break;
			case "5": // CBU
				$logDataFields = ['date','action','activity',['cbu_details','amount'],'user'];
			break;
			case "6": // SHARE CAPITAL
				$logDataFields = ['date','action','activity',['share_capital_details','amount'],'user'];
			break;
			case "7": // LOANS
				#### INDUSTRY SECTION ####
				$stmtIndustrySection = ['schema'=>Info::DB_DATA,'table'=>'loan_industry_section','arguments'=>['id >'=>0],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','title']];
				$getIndustrySection = $this->selectDB($stmtIndustrySection);
				#### INDUSTRY SECTION ####
				$stmtIndustryInternal = ['schema'=>Info::DB_DATA,'table'=>'loan_industry_internal','arguments'=>['id >'=>0],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','title']];
				$getIndustryInternal = $this->selectDB($stmtIndustryInternal);
				$logDataFields = ['date','action','activity',['loans_details','mem_info'],['members_credit_rating','ability_pay'],['loan_summary','loan_granted'],'user'];
			break;
			case "8": // LOANS PAYMENT
				$logDataFields = ['date','action','activity',['loans_payment_transactions','payment_principal'],'user'];
			break;
			case "9": // CLIENTS/SUPPLIER
				$logDataFields = ['date','action','activity',['client_information','client_name'],['address_information','address_city'],['contact_information','contact_mobile'],'user'];
			break;
			case "10": // CASH TRANSACTION
				$logDataFields = ['date','action','activity',['cash_transactions','cash_type'],['cash_details','recipient_info'],'user'];
			break;
		}
		
		foreach($logDataFields as $field){ // TABLE THEAD
			if(is_array($field)){
				$getField = $field;//explode(":",$field[0]); // GETTING THE GROUP NAME AS HEAD TITLE
				$getField = str_replace('_', ' ', $getField[0]);
			}else{
				$getField = $field;
			}
			$logDataHead .= "<th class='log-list'>{$getField}</th>"; 
		}
		$output = "<table id='datatable-logLists' class='table table-striped table-bordered dt-responsive nowrap' width='100%'><thead><tr>{$logDataHead}</tr></thead>";
		foreach($getDataLogs as $logs => $logDetail){
			$getLogDetail = "";
			//foreach($logDetail as $key => $value){
			foreach($logDataFields as $field){
				$getListDetail = "";
				if(is_array($field)){
					$getField = $field;//explode(":",$field[0]); // GETTING THE GROUP NAME AS HEAD TITLE
					$fieldStrGroup = str_replace('_', ' ', $getField[1]);
					if(isset($logDetail[$getField[0]])){
						$fieldGroup = $logDetail[$getField[0]];
						$getListDetail .= "<div class='boxTable'>";
						foreach($fieldGroup as $key => $value){
							$fieldStr = str_replace('_', ' ', $key);
							if($fieldStrGroup != $fieldStr && $key != "data_id"){
								if(is_numeric($value)){
									$decimal = substr($value, -3, 1);
									$value = ($decimal === ".") ? number_format($value, 2) : floatval($value);
								}
								if(isset($_SESSION['codebook'][$key][$value]) && !EMPTY($_SESSION['codebook'][$key][$value])){
									$value = $_SESSION['codebook'][$key][$value]->meta_value;
									switch($key){
										case "loan_industry":
											$value = $getIndustrySection[$value];
										break;
										case "industry_division":
											$value = $getIndustryInternal[$value];
										break;
									}
								}
								$getListDetail .= "<div class='boxWrapper'><div class='boxCell capitalize xsmall'>{$fieldStr}</div><div class='boxCell alignRight mid'>{$value}</div></div>";
							}
						} // END FOREACH
						$getListDetail .= "</div>";
					
						// FIRST DATA/COLLAPSABLE TITLE HERE
						$logValue = $logDetail[$getField[0]][$getField[1]];
						if(is_numeric($logValue)){
							$decimal = substr($logValue, -3, 1);
							if($decimal === ".") $logValue = number_format($logValue, 2);
						}
						$getLogDetail .= "
						<td class='no-padding log-list' role='tab' id='heading{$getField[1]}' data-toggle='collapse' data-parent='#accordion' href='#collapse{$getField[1]}{$cnt}' aria-expanded='false' aria-controls='collapse{$getField[1]}{$cnt}'>
							<div class='accordion' id='accordion' role='tablist' aria-multiselectable='true'>
								<div class='panel'>
									<div class='boxTable'>
										<div class='boxWrapper panel-heading no-padding'><div class='boxCell capitalize xsmall'>{$fieldStrGroup}</div><div class='boxCell alignRight mid'>{$logValue}</div></div>
									</div>
									<div id='collapse{$getField[1]}{$cnt}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='heading{$getField[1]}' aria-expanded='false' style=''>{$getListDetail}</div>
								</div>
							</div>
						</td>"; 
					}else{ // END IF ISSET
						$getLogDetail .= "<td>".Info::EMPTY_VAL."</td>";
					}
					
				}else{
					if(isset($logDetail[$field])){
						$logDetailValue = $dataSort = $logDetail[$field];
						if($field == "date"){
							$logDetailValue = "<span class='uppercase paddingHorizontal'>".$this->timeDateFormat($logDetailValue,'dateTime2')."</span>";
							$dataSort = substr($this->cleanString($dataSort), 0, 16);
						}elseif($field == "activity"){
							$activityName = $this->array_search_by_key($_SESSION["path_activity"][$pathID], 'id', $logDetailValue, 'name');
							$activityName = array_values($activityName);
							$logDetailValue = $activityName[0];//"<span class='ellipsis length-mid'>".$_SESSION['activity'][$logDetailValue]->name."</span>";
						}elseif($field == "action"){
							$logDetailValue = "<span class='uppercase'>{$logDetailValue}</span>";
						}elseif($field == "user"){
							$logDetailValue = $getUsername[$logDetailValue];
						}
						$getLogDetail .= "<td class='xmid no-padding alignCenter' data-sort='{$dataSort}'>{$logDetailValue}</td>"; 
					}else{
						$getLogDetail .= "<td>".Info::EMPTY_VAL."</td>"; 
					}					
				}
			}
			$logData .= "<tr>{$getLogDetail}</tr>";
			$cnt++;
		} // END FOREACH GETDATALOGS

		$output .= "<tbody>{$logData}</tbody></table>";
		return $output;
	}

	public function getUserBySupervisor($listUserWithSupervisor,$personnelUserID) {
		$output = [];

		$getUserWithSupervisor = array_intersect($listUserWithSupervisor,[$personnelUserID]);
		//$output += $getUserWithSupervisor;
		//if(sizeof($getUserWithSupervisor) > 0){
		foreach($getUserWithSupervisor as $personnelID => $supervisorID){
			//if($isSupervisor <= 4){
			$getUserWithSupervisor = array_intersect($listUserWithSupervisor,[$personnelID]);
			$thisListUsers = $getUserWithSupervisor;
			$output[$personnelID] = (sizeof($thisListUsers) > 0) ? $thisListUsers : $personnelID; //array_keys($thisListUsers)

		}

		return $output;
	}
	
	public function getEncrypt($theString) {
		$output = '';
		switch($theString) {
			// case 'check':
				// $checkArray=getMetaValue('options',array('option_meta'=>'info','option_name'=>'url'),'id');
				// $toCheck = getMetaValue('options',array('id'=>$checkArray),'option_meta').getMetaValue('options',array('id'=>$checkArray),'option_name').getMetaValue('options',array('id'=>$checkArray),'option_value');$theCheck =  getEncrypt($toCheck);$output = checkEncrypt($theCheck);
				// break;
			default: $output = md5($theString); break;
		}
		return $output;
	}
	
	public function computeTime($start,$end,$format){ // COMPUTE NUMBER OF TIME, DAYS/MONTHS BY DATE
		$startTime  = new DateTime($start);
		$endTime = new DateTime($end);

		$diff = $startTime->diff($endTime);
		switch($format){
			case 'months':
				$year = $diff->format( '%y' );// -> YEAR
				$month = $diff->format( '%m' );// -> MONTH
				if($year > 0){ // EXCEEDS A YEAR
					$yearMonth = $year * 12;
					$output = $yearMonth + $month;
				}else{
					$output = $month;
				}
			break;
			case 'month':
				$output = $diff->format( '%m' );// -> MONTH
			break;
			case 'days2': // NEGATIVE IF LAPSE
				$output = $diff->format( '%R%a' );// -> 00:25:25
			break;
			case 'days':
				$output = $diff->format( '%a' );// -> 00:25:25
			break;
			case 'hrsMins':
				$output = $diff->format( '%H:%I' );// -> 00:25:25
			break;
			case 'mins':
				$output = $diff->format( '%I' );// -> 00:25:25
			break;
			case 'digits':
				$output = $diff->format( '%H.%I:%S' );// -> 00:25:25
				$output = round(date('H.i', strtotime($output)),2);
			break;
			default:
				$output = $diff->format( '%H:%I:%S' );// -> 00:25:25
			break;
		}
		return $output;
	}
	
	public function getDate($format = 'dateTime'){
		switch($format) {
			case 'date':
				$output = date('Y-m-d');
				break;
			case 'dateTimeAlpha':
				$output = date('F j, Y - g:ia');
				break;
			case 'dateAlpha':
				$output = date('F j, Y');
				break;
			case 'time':
				$output = date('H:i:s');
				break;
			case 'mos':
				$output = date('m');
				break;
			case 'dateTime':
				$output = date('Y-m-d H:i:s');
				break;
		}
		return $output;
	}
	
	public function timeDateFormat($theDateTime,$theView) {
		$date = date_create($theDateTime);
		switch($theView):
			case 'dateTime':
				$newFormat = date_format($date, 'F j, Y - g:ia');
				break;
			case 'dateTime2':
				$newFormat = date_format($date, 'd M Y - g:ia');
				break;
			case 'date':
				$newFormat = date_format($date, 'd M Y');
				break;
			case 'date2':
				$newFormat = date_format($date, 'D : d M Y');
				break;
			case 'date3':
				$newFormat = date_format($date, 'd F Y');
				break;
			case 'dayMonth':
				$newFormat = '<span>'.date_format($date, 'd').'</span><span>'.date_format($date, 'F').'</span>';
				break;
			case 'dayMonthYear':
				$newFormat = '<span class="mos">'.date_format($date, 'M').'</span><span class="day">'.date_format($date, 'd').'</span><span class="year">'.date_format($date, 'Y').'</span>';
				break;
			case 'dateMonthDay':
				$newFormat = '<span class="day">'.date_format($date, 'D').'</span><span class="month">'.date_format($date, 'F o').'</span><span class="date">'.date_format($date, 'd').'</span><span class="year">'.date_format($date, 'l').'</span>';
				break;
			case 'month':
				$newFormat = date_format($date, 'F');
				break;
			case 'mos':
				$newFormat = date_format($date, 'm');
				break;
			case 'day':
				$newFormat = date_format($date, 'd');
				break;
			case 'year':
				$newFormat = date_format($date, 'Y');
				break;
			case 'dateField':
				$newFormat = date_format($date, 'Y-m-d');
				break;
			case 'timeField':
				$newFormat = date_format($date, 'H:i:s');
				break;
			case 'hrsMins':
				$newFormat = date_format($date, 'H:i');
				break;
			case 'dateTimeField':
				$newFormat = date_format($date, 'Y-m-d H:i:s');
				break;
			case 'validDate':
				$newFormat = date_format($date, 'm-d-Y');
				break;
			default:
				$newFormat = date_format($date, 'F j, Y');
				break;
		endswitch;
		return $newFormat;
	}
	
	public function getTime($dateTime) {
		switch($dateTime) {
			case 'date':
				$output = date('Y-m-d');
			break;
			case 'dateTimeAlpha':
				$output = date('F j, Y - g:ia');
			break;
			case 'time':
				$output = date('H:i:s');
			break;
			case 'datetime':
				$output = date('Y-m-d H:i:s');
			break;
			case 'min_sec':
				$output = date('is');
			break;
		}
		return $output;

	}
	
	public function convertNumToWords($num){ 
		ob_start();
		$output = "";
		//$num= floatval($num);
		$ones = [1 => "one", 2 => "two", 3 => "three", 4 => "four", 5 => "five", 6 => "six", 7 => "seven", 8 => "eight", 9 => "nine", 10 => "ten", 11 => "eleven", 12 => "twelve", 13 => "thirteen", 14 => "fourteen", 15 => "fifteen", 16 => "sixteen", 17 => "seventeen", 18 => "eighteen", 19 => "nineteen"]; 
		$tens = [1 => "ten",2 => "twenty", 3 => "thirty", 4 => "forty", 5 => "fifty", 6 => "sixty", 7 => "seventy", 8 => "eighty", 9 => "ninety"];
		$hundreds = ["hundred", "thousand", "million", "billion", "trillion", "quadrillion"]; //limit t quadrillion 
		$num = number_format($num,2,".",",");//(is_numeric($num)) ? $num : number_format($num,2,".",","); 
		$num_arr = explode(".",$num); 
		$wholenum = $num_arr[0]; 
		$decnum = $num_arr[1]; 
		$whole_arr = array_reverse(explode(",",$wholenum)); 
		krsort($whole_arr); 
		foreach($whole_arr as $key => $i){ 
			if($i < 20){ 
				if(!EMPTY($ones[$i])) $output .= $ones[$i]; 
			}elseif($i < 100){ 
				if(!EMPTY($tens[substr($i,0,1)])) $output .= $tens[substr($i,0,1)]; 
				if(!EMPTY($ones[substr($i,1,1)])) $output .= " ".$ones[substr($i,1,1)]; 
			}else{ 
				if(!EMPTY($ones[substr($i,0,1)])) $output .= $ones[substr($i,0,1)]." ".$hundreds[0]; 
				if(!EMPTY($tens[substr($i,1,1)])) $output .= " ".$tens[substr($i,1,1)]; 
				if(!EMPTY($ones[substr($i,2,1)])) $output .= " ".$ones[substr($i,2,1)]; 
			} 
			if($key > 0){ 
				if(!EMPTY($hundreds[$key])) $output .= " ".$hundreds[$key]." "; 
			} 
		} 
		if($decnum > 0){ 
			$output .= " and "; 
			if($decnum < 20){ 
				if(!EMPTY($ones[$decnum])) $output .= $ones[$decnum]; 
			}elseif($decnum < 100){ 
				if(!EMPTY($tens[substr($decnum,0,1)])) $output .= $tens[substr($decnum,0,1)]; 
				if(!EMPTY($ones[substr($decnum,1,1)])) $output .= " ".$ones[substr($decnum,1,1)]; 
			} 
		} 
		ob_end_clean();
		return $output; 
	}
	
	public function computeTotalJournal($chartArray, $getJournals, $beginningJournals){ # TO COMPUTE CURRENT, PREVIOUS AND VARIANCE BY PARENT CHART ID
		$total = [];
		$totalCurrent = $totalPrevious = $totalVariance = 0;
		foreach($chartArray as $chart_id){
			$journalDebit = (isset($getJournals[$chart_id]->debit)) ? $getJournals[$chart_id]->debit : 0;
			$journalCredit = (isset($getJournals[$chart_id]->credit)) ? $getJournals[$chart_id]->credit : 0;
			$currentAmount = $journalDebit - $journalCredit;
			$previousAmount = (isset($beginningJournals[$chart_id])) ? $beginningJournals[$chart_id] : 0;
			$totalCurrent = $totalCurrent + $currentAmount;
			$totalPrevious = $totalPrevious + $previousAmount;
			$totalVariance = $totalCurrent - $totalPrevious;
		}
		$total["variance"] = $totalVariance;
		$total["previous"] = $totalPrevious;
		$total["current"] = $totalCurrent;
		return $total;
	}
	
	public function computeAcntChartValues($chartArray, $chartValues, $type){
		$result = $amount = 0;
		foreach($chartArray as $chartKey => $chartID){
			if(is_array($chartID)){
				if($type == "current"){
					$debitAmount = (isset($chartValues[$chartKey]->debit)) ? $chartValues[$chartKey]->debit : 0;
					$creditAmount = (isset($chartValues[$chartKey]->credit)) ? $chartValues[$chartKey]->credit : 0;
					$isAmount = $debitAmount - $creditAmount; // IF HAS CURRENT VALUE
				}else{
					$isAmount = (isset($chartValues[$chartKey])) ? $chartValues[$chartKey] : 0; // IF HAS CURRENT VALUE
				}
				$amount = $isAmount + $this->computeAcntChartValues($chartID, $chartValues, $type);
			}else{
				if($type == "current"){
					$debitAmount = (isset($chartValues[$chartID]->debit)) ? $chartValues[$chartID]->debit : 0;
					$creditAmount = (isset($chartValues[$chartID]->credit)) ? $chartValues[$chartID]->credit : 0;
					$amount = $debitAmount - $creditAmount;
				}else{
					$amount = (isset($chartValues[$chartID])) ? $chartValues[$chartID] : 0;
				}
			}
			
			$result = $result + $amount;
		}
		return $result;
	}
	
	public function setArrayTree($getAccounting){
		$actgTree = [];
		foreach($getAccounting as $actgID => $actgDetails){
			$getParent = $this->array_search_by_key($getAccounting, 'parent', $actgID, 'meta_id');
			if($getParent){
				foreach($getParent as $parentID => $id){
					//$getParent = $this->array_search_by_key($getAccounting, 'parent', $parentID, 'meta_id');
					$getSubParent = $this->array_search_by_key($getAccounting, 'parent', $id, 'meta_id');
					if($getSubParent){
						$actgTree[$actgID][$parentID] = $getSubParent;
					}else{
						$actgTree[$actgID][$parentID] = (int)$id;
					}
					
				}
			}else{
				$actgTree[$actgID] = $actgID;
			}
			//$array_column($getParent, 'parent', 'id');
		}
		$resultArrayTree = $actgTree;
		$inArrayTree = [];
		$result = $output = [];
		foreach($resultArrayTree as $keyID => $valueID){
			if(!in_array($keyID, $inArrayTree)){
				if(is_array($valueID)){
					foreach($valueID as $key => $value){
						if(is_array($valueID)){
							if(is_array($value)){
								$setKey = "{$key},".implode(",",$value);
								$arrayKey = explode(",",$setKey);
								foreach($arrayKey as $theKey){
									$inArrayTree[] = (int)$theKey;
								}
							}else{
								$inArrayTree[] = $key;
							}
							// $output[$keyID][$key]["text"] = $keyID;
							// $output[$keyID][$key]["nodes"] = $actgTree[$key];
							$result[$keyID][$key] = $actgTree[$key];
						}else{ // NO EFFECT SO FAR
							//$output[$keyID]["text"] = $value;
							$result[$keyID] = $value;
						}
					}
				}else{
					//$output[$valueID]["text"] = $valueID;
					$result[$valueID] = $valueID;
				}
			}
			
		}
		//$this->test = $inArrayTree;
		return $result;
	}
	
	public function jsonConvertArrayTree($actgTree){
		$output = [];
		foreach($actgTree as $key => $value){
			$result = [];
			if(is_array($value)){
				$text = $GLOBALS["account_codes"][$key]['code']." - ".$GLOBALS["account_codes"][$key]['title'];
				$result["text"] = $text;//(string)$key;
				$result["nodes"] = $this->jsValueTree($value);
			}else{
				$text = $GLOBALS["account_codes"][$key]['code']." - ".$GLOBALS["account_codes"][$key]['title'];
				$result["text"] = $text;//(string)$key;
			}
			$output[] = $result;
		}
		return $output;
	}
	
	private function jsValueTree($treeValue){
		$output = [];
		foreach($treeValue as $key => $value){
			$result = [];
			if(is_array($value)){
				$text = $GLOBALS["account_codes"][$key]['code']." - ".$GLOBALS["account_codes"][$key]['title'];
				$result["text"] = $text;//$key;
				$result["nodes"] = $this->jsValueTree($value);
			}else{
				$text = $GLOBALS["account_codes"][$value]['code']." - ".$GLOBALS["account_codes"][$value]['title'];
				$result["text"] = $text;//(string)$value;
			}
			$output[] = $result;
		}
		return $output;
	}
	
	public function array_search_by_key($array, $key, $value, $output) {
		if(!is_array($array)) {
			return [];
		}
		$results = [];
		foreach($array as $element) {
			if(isset($element[$key]) && $element[$key] == $value) {
				$results[$element[$output]] = $element[$output];
			}elseif(is_array($value) && in_array($element[$key], $value)){ // CHECK ALL ARRAYS
				$results[$element[$key]] = $element[$output];
			}
		}
		return $results;
	}

	public function array_recursive_search_key_map($needle, $haystack) {
		foreach($haystack as $first_level_key=>$value) {
			if ($needle === $value) {
				return array($first_level_key);
			} elseif (is_array($value)) {
				$callback = self::array_recursive_search_key_map($needle, $value);
				if ($callback) {
					return array_merge(array($first_level_key), $callback);
				}
			}
		}
		return false;
	}

	public function cleanString($string) {
		$output = preg_replace('/-+/', '-', $string);
		$output = preg_replace('/[^A-Za-z0-9\-]/','', $output); // Removes special chars.
		return $output;
	}
	
	public function generateJournalEntries($params){ # CREATING JOURNAL ENTRIES
		/*
		# SAMPLE PARAMS FOR JOURNAL ENTRIES
		$journalParams = [];
		$journalParams["table"] = "journals";
		$journalParams["unit"] = "3";
		$journalParams["theID"] = "0";
		$journalParams["entry"] = "3";
		$journalParams["journals"] = ["entry_date" => "2020/12/09", "particulars" => "Auto Journal Entry"];
		$journalParams["journals_entry"] = [
				1 => ["charts_id" => "40", "debit" => "5,000.00", "credit" => "0.00"],
				2 => ["charts_id" => "78", "debit" => "0.00", "credit" => "5,500.00"],
				3 => ["charts_id" => "110", "debit" => "500.00", "credit" => "0.00"]
			];
		*/
		$output = [];
		$record_type = "create";
		$this->Params = $params;
		$journalEntryTable = $this->Params['table']."_entry";
		$postFieldsJournal = ["entry_date","recipient","particulars"];
		
		if($this->Params["theID"] > 0){ // UPDATE
			$record_type = "update";
			foreach($postFieldsJournal as $journalField){
				$stmtElements['fieldValues'][$journalField] = $this->Params[$this->Params['table']][$journalField];
			}
			$stmtElements['fieldValues']['date_updated'] = $this->getDate('dateTime');
			$stmtElements['schema'] = Info::DB_ACCOUNTING;
			$stmtElements['table'] = $this->Params['table'];
			$stmtElements['id'] = $this->Params['theID'];
			$cntStmtElements = $this->updateDB($stmtElements);
			$postID = $this->Params['theID'];
			
			// CHECKING TO REMOVE JOURNAL ENTRIES IF HAS EXISTING SCHEDULE START
			$stmtJournalEntries = ['schema'=>Info::DB_ACCOUNTING,'table'=>$journalEntryTable,'arguments'=>['journals_id'=>$postID],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['id']];
			$getJournalEntryID = $this->selectDB($stmtJournalEntries);
			if(count($getJournalEntryID) > 0){
				$deleteJournalEntries = $this->deleteDB(['table'=>$journalEntryTable,'id'=>$getJournalEntryID,'schema'=>Info::DB_ACCOUNTING]);
			}
			// CHECKING TO REMOVE JOURNAL ENTRIES IF HAS EXISTING SCHEDULE END
			
		}else{ # CREATE
			# INSERT TO JOURNALS
			$getFieldsJournal = array_merge($postFieldsJournal,["unit","user"]);
			foreach($postFieldsJournal as $journalField){
				$postValue[] = $this->Params[$this->Params['table']][$journalField];
			}
			$postValue[] = (isset($this->Params['unit']) && $this->Params['unit']) ? $this->Params['unit'] : $_SESSION["unit"];
			$postValue[] = $_SESSION["userID"];
			$stmtElements = ['schema'=>Info::DB_ACCOUNTING,'table'=>$this->Params['table'],'fields'=>$getFieldsJournal,'values'=>$postValue];
			$postID = $this->insertDB($stmtElements);
		}
		
		# INSERT TO JOURNALS ENTRY
		$dataPostJournalEntry = [];
		$entries = (isset($this->Params['entry']) && $this->Params['entry'] != "") ? $this->Params['entry'] : count($this->Params[$journalEntryTable] );
		$amountFields = ["debit","credit"];
		$postFieldsJournalEntry = ["charts_id","debit","credit"];
		$getFieldsJournalEntry = array_merge($postFieldsJournalEntry, ["entry_date","journals_id","unit","user"]);
		for($x=1; $x<=$entries; $x++){
			$postJournalEntry = $stmtElementEntry = [];
			foreach($postFieldsJournalEntry as $journalEntryField){
				$postParamValue = "";
				$postParamValue = $this->Params[$journalEntryTable][$x][$journalEntryField];
				if(in_array($journalEntryField, $amountFields)){
					$postParamValue = str_replace(',', '', $postParamValue);
					$postParamValue = round($postParamValue,3);
				}
				$postJournalEntry[] = $postParamValue;
			}
			# ADDITIONAL FIELDS FOR JOURNAL ENTRIES
			$postJournalEntry[] = $this->Params[$this->Params['table']]['entry_date'];
			$postJournalEntry[] = $postID;
			$postJournalEntry[] = (isset($this->Params['unit']) && $this->Params['unit']) ? $this->Params['unit'] : $_SESSION["unit"];
			$postJournalEntry[] = $_SESSION["userID"];
			if($postJournalEntry[0] && ($postJournalEntry[1] > 0 || $postJournalEntry[2] > 0)){ # CHECK IF HAS CHART_ID AND HAS DEBIT OR CREDIT VALUE
				$stmtElementEntry = ['schema'=>Info::DB_ACCOUNTING,'table'=>$journalEntryTable,'fields'=>$getFieldsJournalEntry,'values'=>$postJournalEntry];
				$journalPostID = $this->insertDB($stmtElementEntry);
				$dataPostJournalEntry[] = $journalPostID;
			}
			# END ADDITIONAL FIELDS FOR JOURNAL ENTRIES
		}
		# END INSERT TO JOURNALS ENTRY
		$output['record_type'] = $record_type;
		$output['post_id'] = $postID;
		return $output;
	}
	
	public function resize($width, $height, $filename, $pathFolder){
		$fileExtention = strtolower(pathinfo($_FILES[$filename]['name'], PATHINFO_EXTENSION));
		/* Get original image x y*/
		list($w, $h) = getimagesize($_FILES[$filename]['tmp_name']);
		/* new file name */
		$generateFileName = $_POST['username'];
		$path = $pathFolder.'/'.$width.'x'.$height."_".$generateFileName.".".$fileExtention;
		if($w>$width || $h>$height){
			$origHeight=$h*0.5;
			$iWidth = $w;
			$iHeight = $h;
			/* calculate new image size with ratio */
			$ratio = max($width/$w, $height/$h);
			$w = ceil($width / $ratio);
			$h = ceil($height / $ratio);
			$x = ($width * $ratio);
			$y = ($height * $ratio);
		
			$xCeil = $yCeil = 0;
			if($iWidth > $iHeight){
				$xCeil = $x;
				$yCeil = 0;
			}else{
				$xCeil = 0;
				$yCeil = $y;
			}
			/* read binary data from image file */
			$imgString = file_get_contents($_FILES[$filename]['tmp_name']);
			/* create image from string */
			$image = imagecreatefromstring($imgString);
			$tmp = imagecreatetruecolor($width, $height);
			imagecopyresampled($tmp, $image,
				0, 0,
				$xCeil, $yCeil,
				$width, $height,
				$w, $h);
		}else{
			move_uploaded_file($_FILES[$filename]['tmp_name'], $path);
		}
		/* Save image */
		switch ($_FILES[$filename]['type']) {
			case 'image/jpeg':
				imagejpeg($tmp, $path, 100);
				break;
			case 'image/png':
				($pathFolder=='images')?move_uploaded_file($_FILES[$filename]['tmp_name'], $path):imagepng($tmp, $path, 0);
				break;
			case 'image/gif':
				imagegif($tmp, $path);
				break;
			default:
				exit;
				break;
		}
		return $path;
		/* cleanup memory */
		imagedestroy($image);
		imagedestroy($tmp);
	}

	public function errorPage($errorType = 404){
		switch($errorType){
			case 401:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Unauthorized Page! User Authentication Required.</h2><p>User failed to provide a valid user name/password required for access to a file/directory. To access, please contact your System Administrator.</p></div></div>";
				break;
			case 403:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Page Restricted/Forbidden! User Authentication Required.</h2><p>Your access role/level is not authorized to access this page. To access, please contact your System Administrator.</p></div></div>";
				break;
			case 404:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Page Not Found! Requested page was not found.</h2><p>Error on page content, please check and try again. To access, please contact your System Administrator.</p></div></div>";
				break;
			case 408:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Request Time-Out! Unable to request page successfully.</h2><p>Error on page content, please check and try again. To access, please contact your System Administrator.</p></div></div>";
				break;
			case 405:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Page Authentication Error! Method Not Allowed.</h2><p>Please check URL and try again. Or contact your System Administrator.</p></div></div>";
				break;
			default:
				$output = "<div class='col-middle'><div class='text-center text-center'><h1 class='error-number'>{$errorType}</h1><h2>Page Error! Page Not Found.</h2><p>Please check URL and try again. Or contact your System Administrator.</p></div></div>";
				break;
		}

		print $output;
	}
	
	static function headerJS(){
		$output = '<link href="'.Info::URL.'/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="'.Info::URL.'/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet"><!-- Font Awesome -->
		<link href="'.Info::URL.'/vendors/nprogress/nprogress.css" rel="stylesheet"><!-- NProgress PreLoader -->
		<link href="'.Info::URL.'/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" rel="stylesheet"/>
		<link href="'.Info::URL.'/vendors/iCheck/skins/flat/green.css" rel="stylesheet"><!-- iCheck -->
		<link href="'.Info::URL.'/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet"><!-- bootstrap-progressbar -->
		<link href="'.Info::URL.'/vendors/switchery/dist/switchery.min.css" rel="stylesheet">
		<link href="'.Info::URL.'/vendors/select2/dist/css/select2.min.css" rel="stylesheet">
		<link href="'.Info::URL.'/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		<link href="'.Info::URL.'/css/custom.min.css" rel="stylesheet"><!-- Custom Theme Style -->
		<link href="'.Info::URL.'/css/style.css" rel="stylesheet"><!-- Custom Theme Style -->
		
		<!-- PNotify -->
		<link href="'.Info::URL.'/vendors/pnotify/dist/pnotify.css" rel="stylesheet">
		<link href="'.Info::URL.'/vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
		<link href="'.Info::URL.'/vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">

		<script src="'.Info::URL.'/vendors/jquery/dist/jquery.min.js"></script>
		<script src="'.Info::URL.'/vendors/select2/dist/js/select2.full.min.js"></script>

		';
		return $output;
	}
	
	static function footerJS(){
		$output = '<!-- jQuery -->

	<!-- Bootstrap -->
		<script src="'.Info::URL.'/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
		<!-- FastClick -->
		<script src="'.Info::URL.'/vendors/fastclick/lib/fastclick.js"></script>
		<!--Remove Click Delays-->
		<!-- NProgress -->
		<script src="'.Info::URL.'/vendors/nprogress/nprogress.js"></script>
		<!-- bootstrap-progressbar -->
		<script src="'.Info::URL.'/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>

		<!-- bootstrap-wysiwyg -->
		<script src="'.Info::URL.'/vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>
		<script src="'.Info::URL.'/vendors/jquery.hotkeys/jquery.hotkeys.js"></script>
		<script src="'.Info::URL.'/vendors/google-code-prettify/src/prettify.js"></script>

		<!-- bootstrap-daterangepicker -->
		<script src="'.Info::URL.'/js/moment/moment.min.js"></script>
		<script src="'.Info::URL.'/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>
		<!-- Custom Theme Scripts -->
		
		<script src="'.Info::URL.'/js/custom.min.js"></script>
		<script src="'.Info::URL.'/vendors/switchery/dist/switchery.min.js"></script>
		<script src="'.Info::URL.'/vendors/iCheck/icheck.min.js"></script>
		<!-- PNotify -->
		<script src="'.Info::URL.'/vendors/pnotify/dist/pnotify.js"></script>
		<script src="'.Info::URL.'/vendors/pnotify/dist/pnotify.buttons.js"></script>
		<script src="'.Info::URL.'/vendors/pnotify/dist/pnotify.nonblock.js"></script>
		
		<script src="'.Info::URL.'/vendors/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
		<script>function emptyModal(){modalDefault=\'<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button><h4 class="modal-title" id="myModalLabel">Modal title</h4></div><div class="modal-body"></div><div class="modal-footer"><div id="success"></div><button type="button" class="btn btn-default" id="closeBtn" data-dismiss="modal">Close</button> <button type="button" class="btn btn-primary" id="saveBtn">Save changes</button></div>\';$(".modal.viewReservations .modal-content").html(modalDefault);}</script>
		';
		return $output;
	}
	
	public function sessionAuth($userID){
		$output = [];
		$_SESSION["module_type"] = $this->Systems("module_type");
		$getSystemInfo = $this->Systems("info");
		$usersFields = $this->getTableFields(['table'=>'users','exclude'=>['user','date'],'schema'=>Info::DB_SYSTEMS]);
		$stmtUsers = ['schema'=>Info::DB_SYSTEMS,'table'=>'users','arguments'=>['id'=>$userID],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>$usersFields]; //,'option_name'=>$meta[$optionMeta]
		$getUsers = $this->selectDB($stmtUsers);
		if(!EMPTY($getUsers)){
			$userDetails = $getUsers[$userID];
			//$this->userInfo = $userInfo;
			$infoKey = "";
			foreach($userDetails as $key => $value){
				 $_SESSION[$key] = $value;
			}
			$_SESSION["tokens"] = explode(',',$_SESSION["tokens"]);
			$_SESSION["server_host"] = Info::URL;
			$_SESSION["displayname"] = $_SESSION["firstname"]." ".$_SESSION["lastname"];
			
			$getSystemRole = $this->Systems("userrole");
	
			$_SESSION["roleValue"] = $getSystemRole[$_SESSION["role"]];
			$systemInfoFields = ["companyname","theurl","emailaddress","logo","companySlogan","api_sync"];
			foreach($systemInfoFields as $systemField){
				$_SESSION["info"][$systemField] = $getSystemInfo[$systemField];
			}
			$getFields = array_merge(['alias'],$this->getTableFields(['table'=>'fields','exclude'=>['id','alias','status','user','date'],'schema'=>Info::DB_SYSTEMS]));
			$stmtFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'fields','arguments'=>['field_type >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$getFields];
			$_SESSION["fields"] = $this->selectDB($stmtFields);

			$getCodebookValue = ['admin','accounting_type','designation','unit','gender','civil_status','role','area_code','division','account_type','membership_type','membership_status','reports','product_savings','savings_status','share_capital_type','loan_types','client_type','cash_type','cash_option','payment_form','payment_mode','amortization_type','interest_type']; //,'loan_industry','industry_division'
			foreach($getCodebookValue as $codeKey){
				$getStmtCodebook = ['schema'=>Info::DB_SYSTEMS,'arguments'=>['meta_key'=>$codeKey,'status'=>1],'table'=>'codebook','pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['meta_id','id','meta_option','meta_value']];
				$codebookValues[$codeKey] = $this->selectDB($getStmtCodebook);
			}
			$_SESSION["codebook"] = $codebookValues;
			
			$stmtCodeMetaValues = ['schema'=>Info::DB_SYSTEMS,'table'=>'codemeta','arguments'=>['active >'=>0],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC,'fields'=>['key_value','meta_value','id','meta_parent','key_parent']];
			$getCodeMetaValues = $this->selectDB($stmtCodeMetaValues);
			$_SESSION["codemeta"] = $getCodeMetaValues;
			
			$codeMetaUnit = $getCodeMetaValues['unit'];
			$metaValueUnit = $areaUnits = [];
			$sessionCodebookUnit = json_decode(json_encode($codebookValues["unit"]), true);
			
			$getUnitMetaID = $sessionCodebookUnit[$_SESSION['unit']]['meta_option'];
			foreach($codeMetaUnit as $metaValue){
				$metaID = $metaValue['meta_value'];
				$codebookMeta = $this->array_recursive_search_key_map((string)$metaID, $sessionCodebookUnit);
				$metaValue['codebook_id'] = $metaID;
				unset($metaValue['meta_value']);
				$metaValueUnit[$codebookMeta[0]] = json_decode(json_encode($metaValue));
				
				if($_SESSION['userrole'] == 3 && $metaValue['meta_parent'] == $getUnitMetaID) $areaUnits[] = $codebookMeta[0];// ONLY FOR AREA MANAGERS / GET UNITS ON AREA

			}
			if($_SESSION["userrole"] == 3){ // GET UNITS ON AREA FOR AREA MANAGERS
				$_SESSION["unit_code"] = $_SESSION["unit"];
				$_SESSION["unit"] = implode(",",$areaUnits);
			}  
			$_SESSION["unit_area"] = $metaValueUnit;
			
			// $stmtActgCodeUnit = ['schema'=>Info::DB_SYSTEMS,'table'=>'codemeta','arguments'=>['key_value'=>'accounting_code','meta_parent'=>$_SESSION["unit"]],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['meta_value','id','meta_parent','key_parent']];
			// $getActgCodeUnit = $this->selectDB($stmtActgCodeUnit);
			
			$codeMetaActgCode = $getCodeMetaValues['accounting_code'];
			$metaValueActgCode = [];
			foreach($codeMetaActgCode as $metaValue){
				if($metaValue['meta_parent'] == $_SESSION["unit"]) $metaValueActgCode[$metaValue['meta_value']] = json_decode(json_encode($metaValue));
			}

			$_SESSION["meta_accounting_code"] = $metaValueActgCode;

			switch($_SESSION["userrole"]){
				case 1: case 2: // SUPER ADMIN AND ADMIN

				break;
				case 3: // AREA MANAGER
					$areaCode = $_SESSION['codebook']["unit"][$_SESSION["unit_code"]]->meta_option;
					$_SESSION["area"] = $areaCode;
					
					//$unitArea = $codebookValues["area_code"][$areaCode]->meta_option;
					$stmtCodeMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>'codemeta','arguments'=>['key_value'=>'unit','key_parent'=>'division','meta_parent'=>$areaCode],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['meta_value']];
					$getCodeMeta = $this->selectDB($stmtCodeMeta);
					//$_SESSION["unit"] = implode(",",$getCodeMeta);
					break;
				case 4:case 5: // MANAGER AND CLERK
					$stmtCodeMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>'codemeta','arguments'=>['key_value'=>'unit','key_parent'=>'division','meta_value'=>$_SESSION["codebook"]["unit"][$_SESSION["unit"]]->id],'pdoFetch'=>PDO::FETCH_COLUMN,'fields'=>['meta_parent']];
					$getCodeMeta = $this->selectDB($stmtCodeMeta);
					$_SESSION["area"] = $getCodeMeta[0];
					$_SESSION["unit_code"] = str_pad($_SESSION["area"], 2, '0', STR_PAD_LEFT)."-".str_pad($_SESSION["unit"], 2, '0', STR_PAD_LEFT);
				break;
			}
			##### PATH #####
			$pathFields = $this->getTableFields(['table'=>'path','exclude'=>['status','user','date'],'schema'=>Info::DB_SYSTEMS]);
			$stmtPath = ['schema'=>Info::DB_SYSTEMS,'table'=>'path','arguments'=>['status>'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$pathFields];
			$_SESSION["path"] = $this->selectDB($stmtPath);
			##### ACTIVITY #####
			
			$stmtPathActivities = ['schema'=>Info::DB_SYSTEMS,'table'=>'activity','arguments'=>['status'=>1],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_ASSOC,'extra'=>'ORDER BY id ASC','fields'=>["path","id","alias","name","tokens","description"]];
			$getPathActivities = $this->selectDB($stmtPathActivities);
			$_SESSION["path_activity"] = $getPathActivities;

			##### START GETTING THE NOTIFICATIONS #####
			$getNotificationActivity = Info::NOTIFICATION_ACTIVITIES; // SET ACTIVITIES ON NOTIFICATIONS

			$notificationArguments = "AND id IN ({$getNotificationActivity}) ";
			$stmtTokensActivity = ['schema'=>Info::DB_SYSTEMS,'table'=>'activity','arguments'=>['status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC,'extra'=>$notificationArguments.'ORDER BY id ASC','fields'=>["id","tokens","id","name"]];
			$getTokensActivity = $this->selectDB($stmtTokensActivity);

			$arrayTokenActivity = array_intersect(array_column($getTokensActivity,"tokens"),array_values($_SESSION["tokens"]));
			$arrayNotificationActivity = array_intersect_key(array_values($getTokensActivity),$arrayTokenActivity);
			$notificationActivity = array_column($arrayNotificationActivity,"id");
			$thisNotificationActivity = implode(",",$notificationActivity);

			if(count(array_intersect([7,8],$_SESSION["tokens"])) > 0){ // IF OM AND GM LOAN APPLICATION TOKEN:7,8 
				$extraArguments = "AND dataQueue.activity_id IN ({$thisNotificationActivity})";
			}else{
				$extraArguments = "AND dataQueue.activity_id IN ({$thisNotificationActivity}) AND dataQueue.unit IN ({$_SESSION['unit']})";
			}
			// $queueNotification = [
				// 'schema'=>Info::DB_DATA,
				// 'table'=>'data_queue AS dataQueue',
				// 'arguments'=>['dataQueue.data_id>'=>1],
				// 'join'=>'
					// LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loanDetails ON dataQueue.data_id = loanDetails.data_id
					// LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_10_cash_transactions AS cashTransactions ON dataQueue.data_id = cashTransactions.data_id
					
					// ',
				// 'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>$extraArguments.' ORDER BY dataQueue.id DESC','fields'=>['dataQueue.data_id','dataQueue.id','dataQueue.date_created','dataQueue.path_id','dataQueue.activity_id','dataQueue.unit','loanDetails.client_name','dataQueue.user']];
			// $getQueueNotification = $this->selectDB($queueNotification);

			$stmtCheckMember['fields'] = [
				'queue.id',
				'queue.path_id',
				'queue.data_id',
				'queue.date_created',
				'queue.activity_id',
				'queue.unit',
				'CASE WHEN queue.path_id = 7
					THEN loans_details.mem_info
					ELSE cash_details.recipient_info
				END AS client_name',
				'queue.user'
			];
			$stmtCheckMember['table'] = 'data_queue as queue';
			$stmtCheckMember['join'] = 'LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_7_loans_details AS loans_details ON (loans_details.data_id = queue.data_id)';
			$stmtCheckMember['join'] .= 'LEFT JOIN '.Info::PREFIX_SCHEMA.Info::DB_DATA.'.path_10_cash_details AS cash_details ON (cash_details.data_id = queue.data_id)';
			
			$stmtCheckMember['extra'] = 'AND queue.path_id = 7 AND queue.activity_id IN (15)';
			$stmtCheckMember['extra'] .= 'OR queue.path_id = 10 AND queue.activity_id IN (22)';
			$stmtCheckMember['extra'] .= 'ORDER BY queue.id DESC';
			$stmtCheckMember['arguments']["queue.unit"] = $_SESSION["unit"];
			$stmtCheckMember += ['schema'=>Info::DB_DATA,'pdoFetch'=>PDO::FETCH_CLASS];
			$getQueueNotification = $this->selectDB($stmtCheckMember);

			$notifications = [];
			foreach($getQueueNotification as $queueID => $queueData){
				$getName = explode(":",$queueData->client_name);
				$clientName = $getName[0];
				$notifications[$queueData->id] = [
					"path_title"=>$_SESSION["path"][$queueData->path_id]->name,
					"path_alias"=>$_SESSION["path"][$queueData->path_id]->alias,
					"date"=>date_format(date_create($queueData->date_created), 'd M Y'),
					"data_id"=>$queueData->data_id,
					"trans_id"=>$this->formatValue(['prefix'=>$queueData->path_id,'id'=>$queueData->data_id],"app_id"),
					"title"=>$queueData->activity_id,
					"unit"=>$_SESSION["codebook"]["unit"][$queueData->unit]->meta_value,
					"client"=>$clientName,
					"status"=>0
				];
			}
			$_SESSION["notifications"] = $notifications;
			##### END GETTING THE NOTIFICATIONS #####

			$groupsFields = array_merge(['alias'],$this->getTableFields(['table'=>'groups','exclude'=>['alias','status','user','date'],'schema'=>Info::DB_SYSTEMS]));
			$stmtGroups = ['schema'=>Info::DB_SYSTEMS,'table'=>'groups','arguments'=>['status>'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$groupsFields];
			$_SESSION["groups"] = $this->selectDB($stmtGroups);
			
			$_SESSION["module_charts"] = $this->methodSettings(['meta_key'=>'module_policy','name'=>'module_charts']);
			
			if(PAGE_TYPE == "login"){
				//$_SESSION["member_masterlist"] = "fox";
			}
	
		}else{
			unset($_SESSION['userID']);
			echo "<script type='text/javascript'>location.reload(true);</script>";
		}
	}
	
	function detectClientId($token) {

	}

	function __destruct() {

	}

}

class formTree
{
	public $params;
	public $schema = Info::DB_SYSTEMS;
	public $table;
	public $groupTblName = 'groups';
	public $getGroup;
	public $treeType;
	public $groupAlias;

	function __construct($params) {
		$this->params = $params;
		if(isset($this->params['schema']))$this->schema = $this->params['schema'];
		$this->table = $this->params['table'];
		$this->getGroup = new getMetaValue(['schema'=>Info::DB_SYSTEMS,'table'=>$this->groupTblName]);
		$this->getField = new getMetaValue(['schema'=>Info::DB_SYSTEMS,'table'=>'fields']);
		$this->getProcedure = new getMetaValue(['schema'=>Info::DB_SYSTEMS,'table'=>'procedure']);
		$this->getCodeBook = new getMetaValue(['schema'=>Info::DB_SYSTEMS,'table'=>'codebook']);
		$this->inputField = new fieldGroup;
		if(isset($this->params['type']))$this->treeType = $this->params['type'];
	}

	public function formBox($groupID){
		$output = '';
		$thisGroup = $this->getGroup->listings(['id'=>$groupID],getTableFields($this->groupTblName,['date','user']));
		$elements = $thisGroup[0]['elements'];
		$output = $this->elements($elements);

		return $output;
	}

	public function elements($elements){
		$output = ''; $groupFields = [];
		$arrayElements = explode(',',$elements);
		foreach($arrayElements as $theElement){
			$objectElement = explode(':',$theElement);
			$objName = $objectElement[0];
			$objAlias = str_replace($objName."-","",$objectElement[1]);
			$output .= $this->$objName($objAlias);
			//if($this->treeType != 'treeView' && $objName == 'group') $groupFields[] = $objAlias;
		}
		//if(count($groupFields) > 0) $output .= '<input type="hidden" name="group_fields" id="group_fields" value="'.implode(',',$groupFields).'" />'; // IF VIEW FORM PAGE
		return $output;
	}

	public function group($objAlias){
		$thisGroup = $this->getGroup->listings(['alias'=>$objAlias],getTableFields('groups',['date','user'],Info::DB_SYSTEMS));
		$getElements = $thisGroup[0]['elements'];
		$this->groupAlias = $thisGroup[0]['alias'];
		switch($this->treeType){
			case 'treeView':
				$output = "{
                text: '".$thisGroup[0]['name']."',
                href: '#".$thisGroup[0]['alias']."',
				object: 'group',
				id: '".$thisGroup[0]['id']."',
				name: '".$thisGroup[0]['alias']."',
                tags: ['2'],
                nodes: [
                  ".$this->elements($getElements)."
                ]
              },";
				break;
			default:
				$output = '<div class="x_panel" id="'.$thisGroup[0]['alias'].'"><div class="x_title"><h2>'.$thisGroup[0]['name'].'<span class="subTitle">|| '.$thisGroup[0]['description'].'</span></h2><ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul></div><div class="fieldGroup x_content no-padding">'.$this->elements($getElements).'</div></div>';
				break;
		}

		return $output;
	}

	public function procedure($objAlias){
		$output = [];
		$thisProcedure = $this->getProcedure->listings(['alias'=>$objAlias],getTableFields('procedure',['date','user'],Info::DB_SYSTEMS));
		$getElements = $thisProcedure[0]['value'];
		switch($this->treeType){
			case 'treeView': // JS Bootstrap Tree
				$output = "{
                    text: '".$thisProcedure[0]['name']."',
                    href: '#".$thisProcedure[0]['alias']."',
					object: 'procedure',
					id: '".$thisProcedure[0]['id']."',
					name: '".$thisProcedure[0]['alias']."',
                    tags: ['0']
                  },";
				break;
			default:
				//$output = '<div class="form-group half no-padding item">'.$thisProcedure[0]['value'].'</div>';
				//$output = '<div class="form-group half no-padding item">'.eval($thisProcedure[0]['value']).'</div>';

				$output = procedureContent($thisProcedure[0]['value']);
				break;
		}

		return $output;
	}

	public function field($objAlias){
		$thisField = $this->getField->listings(['alias'=>$objAlias],getTableFields('fields',['date','user'],Info::DB_SYSTEMS));

		$codebookValue = $this->getCodeBook->listings(['meta_key'=>'field_type','meta_id'=>$thisField[0]['field_type']],['id','meta_id','meta_option']);
		switch($codebookValue[0]['meta_option']){
			default:
				$inputObj = $this->inputField->inputGroup(['label'=>$thisField[0]['name'],'type'=>$codebookValue[0]['meta_option'],'id'=>$thisField[0]['alias'],'title'=>$thisField[0]['name'],'attr'=>'','meta'=>'','name'=>$this->groupAlias."_".$thisField[0]['alias'],'placeholder'=>$thisField[0]['description'],'value'=>'']);
				break;
			case 'select': case 'element':
			$inputObj = $this->inputField->inputGroup(['label'=>$thisField[0]['name'],'type'=>$codebookValue[0]['meta_option'],'id'=>$codebookValue[0]['meta_option'],'title'=>$thisField[0]['name'],'meta'=>'codebook','meta_key'=>$thisField[0]['alias'],'name'=>$this->groupAlias."_".$thisField[0]['alias'],'placeholder'=>$thisField[0]['description'],'value'=>'']);
			break;
		}

		switch($this->treeType){
			case 'treeView': // JS Bootstrap Tree
				$output = "{
                    text: '".$thisField[0]['name']."',
                    href: '#".$thisField[0]['alias']."',
					object: 'field',
					field_type: '".$codebookValue[0]['meta_option']."',
					id: '".$thisField[0]['id']."',
					name: '".$thisField[0]['alias']."',
                    tags: ['0']
                  },";
				break;
			default:
				$output = '<div id="'.$this->groupAlias."_".$thisField[0]['alias'].'" class="form-group half no-padding edit item">'.$inputObj.'</div>';
				break;
		}
		return $output;
	}
	
}



##### OUTSIDE FUNCTION-METHODS #####

function setMetaArray($fieldsValue,$metaName){
	$x=1;
	$metaValue='[{';
	$comma='';
	foreach($fieldsValue as $terms => $value){
		//$value = cleanString($value,'');
		$terms = str_replace('"', '', $terms);
		$value = str_replace('"', '', $value);
		if($metaName=='identity_information')$terms = cleanString($terms,'_');
		$metaValue.=$comma.'"'.$terms.'":"'.$value.'"';
		$comma=',';$x++;
	}
	$metaValue.='}]';
	return $metaValue;
}