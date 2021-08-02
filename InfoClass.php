<?php
class Info{
    const URL = 'http://localhost/cbase';
	
    const PREFIX_SCHEMA = "mantoolph_cbase_";
    const DB_NAME = "systems";
	const DB_SYSTEMS = "systems";
	const DB_DATA = "data";
	const DB_ACCOUNTING = "accounting";
	const DB_HOST = "localhost";
    const DB_USER = "root";
    const DB_PASS = "5wdsaf48esdsfs78ew";
	
	const APPROVE_ACTIVITIES = "4";//"2,3,4,5";
	const NOTIFICATION_ACTIVITIES = "15,22";
	const DEDUCTION_ROW = 6;
	const EMPTY_VAL = "&ctdot;";
	const API_DIR = "/ecapp_api/request_api.php?type=";
	const COMPLETED_ACTIVITIES = "2,4,6,8,10,12,16,18,20,23";
	
    public $Params;
	public $method;

    function __construct($params) {
        $this->Params = $params;
    }
	
	private static $apiAddress = [ // API IP ADDRESSES ON VPN/RADMIN
		1 => "10.72.1.88:16167",
        2 => "10.72.1.88:16167",//"localhost", //10.72.1.21:8081		26.22.226.194
		9 => "122.3.175.200:16167",
		11 => "122.3.175.200:16167",
        14 => "10.72.1.141", //26.22.226.194
		41 => "10.72.1.21:8081",
        32 => "192.168.1.141", // FOX
		16 => "192.168.1.146"
    ];
	
	static function getAPIAddress($branchCode) {
		//return self::$apiAddress[$branchCode];
        return "10.72.1.88:16167";
    }
	
	private static $apiDefaultValue = [ // API IP ADDRESSES ON VPN/RADMIN
        "LoanDD_FLAG" => 1, // EVERY 15/30 OF THE MONTH AND ETC
        "LoanSTATUS" => 11, // CURRENT 11, PAST DUE 12
		"LoanSLC_CODE" => 12 // LOAN RECEIVABLE
    ];
	
	static function apiDefaultValue($field) {
        return self::$apiDefaultValue[$field];
    }
	
	static function paymentsTerms($loanDetails) {
		$numPayments = $paymentCount = $monthlyCount = 1;
		$paymentCountReset = $numDays = 0;
		$paymentMode = $loanDetails['mode'];
		$paymentTerms = $loanDetails['terms']; // BY MONTHLY
		switch($paymentMode){
			case 1: // DAILY
				$numDays = 1;
				$paymentCount = $paymentCountReset = 30;
				$numPayments = $paymentTerms * $paymentCount;
				$monthlyCount = $monthlyCount / $paymentCount;
			break;
			case 2: // WEEKLY
				$numDays = 7;
				$paymentCount = $paymentCountReset = 4;
				$numPayments = $paymentTerms * $paymentCount;
				$monthlyCount = $monthlyCount / $paymentCount;
			break;
			case 3: // SEMI-MONTHLY
				$numDays = 15;
				$paymentCount = $paymentCountReset = 2;
				$numPayments = $paymentTerms * $paymentCount;
				$monthlyCount = $monthlyCount / $paymentCount;
			break;
			case 4: // MONTHLY
				$numDays = 30;
				$paymentCount = $paymentCountReset = 1;
				$numPayments = $paymentTerms;
				$monthlyCount = $monthlyCount / $paymentCount;
			break;
			case 5: // QUARTERLY
				$numDays = 90;
				$paymentCount = $monthlyCount = 3;
				$paymentCountReset = 12 / $paymentCount;
				$numPayments = $paymentTerms / $paymentCount;
			break;
			case 6: // SEMESTRAL
				$numDays = 180;
				$paymentCount = $monthlyCount = 6;
				$paymentCountReset = 12 / $paymentCount;
				$numPayments = $paymentTerms / $paymentCount;
			break;
			case 7: // YEARLY
				$numDays = 360;
				$paymentCount = $monthlyCount = 12;
				$paymentCountReset = 12 / $paymentCount;
				$numPayments = $paymentTerms / $paymentCount;
			break;
			case 9: // LUMPSUM
				$numDays = 30;
				$numPayments = $paymentTerms;
			break;
		}
		$output['number_days'] = $numDays;
		$output['count_reset'] = $paymentCountReset;
		$output['count_payment'] = $paymentCount;
		$output['monthly_terms'] = $monthlyCount;
		$output['number_payment'] = $numPayments;
		
        return $output;
    }
	
	static function staticValue($type) {
		$values = [
			"num_format" => [
				"id" => 9,
				"app_id" => 8,
				"ref_id" => 8,
				"reference_id" => 10,
				"savings_id" => 8,
				"client_id" => 6
			]
		];
		$output = $values[$type];
		return json_decode(json_encode($output, JSON_FORCE_OBJECT));
    }
	
	static function value($type) {
		$values = [
			"eval_key_strict" => [
				"[script]" => "<script>",
				"[/script]" => "</script>",
				"thisclick" => "onclick"
			],
			"systems" => [
				"name" => "eC'APPS",
				"title" => "Electronically-Centralized Accounting Platform and Portfolio Management System",
				"version" => "v3.2.1.1",
				"login" => "eC'APPS - ManTools",
				"maintenance" => "Under Maintenance",
				"owner_name" => "MANTOOL",
				"owner_title" => "Management Tools Creation Co.",
				"copyright" => "2020"
			],
			"willingness_value" => [
				1 => ['less_1_year'=>1,'1_year'=>3,'2_years'=>5,'3_years'=>7,'4_years'=>9,'5_years'=>11,'6_years'=>13,'greater_6_years'=>15],
				2 => ['completed_pmes'=>3,'paid_dues'=>4,'paid_social_services'=>4,'membership_recruitment'=>6,'monthly_meetings'=>6,'cbu'=>7,'savings_deposit'=>7,'no_delayed_payments'=>8],
				3 => ['deposit_less_800'=>5,'deposit_801_3600'=>7,'deposit_3601_5000'=>9,'deposit_5001_6500'=>11,'deposit_6501_10000'=>13,'deposit_greater_10000'=>15],
				4 => ['capital_less_2500'=>5,'capital_2501_5000'=>9,'capital_5001_15000'=>13,'capital_15001_25000'=>17,'capital_25001_35000'=>21,'capital_greater_35000'=>25]
			],
			"tax_type" => [
				1 => ['witholdingTax','Witholding Tax (12%)',12],
				2 => ['salesTax','Sales Tax (5%)',5],
				3 => ['contingencyHousehold','15% Household Contingency',15],
				4 => ['contingencyBusiness','15% Business Contingency',15]
			],
			"rating_quality" => [
				1 => ['chattelQuality','Appraiser Rating of Quality'],
				2 => ['chattelQuality','Appraiser Rating of Quality'],
				3 => ['realestateQuality','Appraiser Rating of Quality'],
				4 => ['realestateQuality','Appraiser Rating of Quality'],
				5 => ['appraiserRisk','Appraiser Risk Rating'],
				6 => ['appraiserRisk','Appraiser Risk Rating']
			],
			"null_alert" => "--- Data Record beyond this point is null and void. ---"
		];
        return $values[$type];
    }

    private static $errorTypes = array(
        1 => "Server response error",
        2 => "Bad request",
        3 => "Auth error",
        4 => "Data not correct",
        5 => "Limit for request sms code per day",
        6 => "Token error",
        7 => "Stop by session activity"
    );

    static function Error($type) {
        return self::$errorTypes[$type];
    }
	
    static function Systems($type) {
       
		$thisMethod = new Method("");
		//$output["test"] =  ;
		
		return $thisMethod->Systems($type);
    }

    public function Details($type){
        switch($type){
            case "title":
                $output = ($this->Params['view'] == "reports") ? str_replace("_"," ",$this->Params['type']) : str_replace("_"," ",$this->Params['view']);
				//$output = "Performance Management System";
                break;
            case "subTitle":
				//$output = "|| Surigao Economic Development and  Microfinance Foundation, Inc.";
				$output = "|| ".self::Systems('companyname');
                break;
        }
        return $output;
    }

//    public function Details(){
//
//        //$this->pageName['title'] = str_replace("_"," ",$this->type);
//        $this->pageName['title'] = $this->pageView;
//        $this->pageName['subTitle'] = "|| Results: Transaction Listings";
//    }

    static function DBConnection(){
		//$this->schema = $schema;
        try{
            $db = new PDO('mysql:host='.Info::DB_HOST.';dbname='.Info::PREFIX_SCHEMA.Info::DB_NAME, Info::DB_USER, Info::DB_PASS);
            return $db;
        }catch (Exception $e){
            return 'Database Error.';
        }
    }
}