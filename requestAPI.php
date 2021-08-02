<?php

class Request {

   private $paramsAPI;

    function __construct() { //$requestParams
        //$this->params = $requestParams;
    }

    function getAPI($getURL,$paramsAPI) {
        $apiURL = $getURL . "?" . $paramsAPI;
 		$options = [
			CURLOPT_URL => $apiURL,
			CURLOPT_POST => false,
			CURLOPT_USERAGENT => "CLAMP_API",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_TIMEOUT => 132
		];
		// Initiates the cURL object
		$curl = curl_init();
		// Assigns our options
		curl_setopt_array($curl, $options);
		// Executes the cURL POST
		$results = curl_exec($curl);
		if(curl_errno($curl)){
			$results = 'ERROR_API_GET';
		}
		// Be kind, tidy up!
		curl_close($curl);
        return $results;
    }
	
	function sendAPI($apiURL,$postData){
		// Sets our options array so we can assign them all at once
		$options = [
			CURLOPT_URL => $apiURL,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		];
		// Initiates the cURL object
		$curl = curl_init();
		// Assigns our options
		curl_setopt_array($curl, $options);
			// Executes the cURL POST
		$results = curl_exec($curl);
		if(curl_errno($curl)){
			$results = 'ERROR_API_SEND';
		}
		// Be kind, tidy up!
		curl_close($curl);
        return $results;
	}

    // function setRequestType($type) {
        // $request->type = $type;
    // }

}
