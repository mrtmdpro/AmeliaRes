<?php

// Get requested method name
preg_match('/^.*\/api\/([a-zA-Z]+).*$/', $_SERVER['REQUEST_URI'], $matches);
if (count($matches) != 2) {
	http_response_code(404);
	exit();
}

$method_name = $matches[1];

// Check that we have this method
$methods = array(
	'GetPSSPAXSummaries' => 'Document',
	'GetPSSFlightsSchedule' => 'Document',
	'GetPSSCheckInData' => 'Document',
	'GetPSSRevenueByAgencies' => 'Document',	
	'GetPSSRevenueByChargeType'  => 'Document',
	'GetPSSRevenueHistorical' => 'Document',
	'GetPSSUsers' => 'Catalog',
	'GetCounterParties' => 'Catalog',
	'GetAirports' => 'Catalog',
	'GetFareClasses' => 'Catalog',
	'GetPaymentMethods' => 'Catalog',
	'GetRawFlights' => 'Catalog',
	'GetRevenueTypes' => 'Catalog',
	'GetSectors' => 'Catalog'
);

if (!array_key_exists($method_name, $methods)) {
	http_response_code(403);
	exit('Method name is not found');
}

// Define common variables
$callback_url = 'http://localhost/1cPublic/hs/CallBackService';
$user_password = 'nguzui:222';
$sleep_time = 10;

// Get array of headers from request
$headers = array();
$header_str = '';
foreach($_SERVER as $key => $value) {
	if (substr($key, 0, 5) <> 'HTTP_') {
		continue;
	}
	$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
	$headers[$header] = $value;
	$header_str .= $header . ':' . $value . '|';
}

// TODO: upgrade if needed
//$_POST = '[]';

$rest_json = file_get_contents("php://input");
$data = json_decode($rest_json, true);

$prm_batchId = $headers['Batchid'];
//$prm_begDate =  '2020-01-01T00:00:00';
//$prm_endDate = '2020-01-01T23:59:59';

//$prm_begDate =  $data['begDate'];
//$prm_endDate = $data['endDate'];

$function_type = $methods[$method_name];
if (empty($data['begDate'])) {
	$prm_begDate =  '2020-01-01T00:00:00';
	//$function_type = 'Catalog';
} else {
	//$function_type = 'Document';
	$prm_begDate =  $data['begDate'];
}

if (empty($data['endDate'])) {
	$prm_endDate = '2020-01-01T23:59:59';
	//$function_type = 'Catalog'
} else {
	//$function_type = 'Document'
	$prm_endDate = $data['endDate'];
}

//$default_file = $method_name . '.json';

// Check basic parameters
if (empty($prm_batchId)) {
	http_response_code(403);
	//exit('Empty batchId header parameter');
	exit('Test |' . $header_str);
} else {
	if (strlen($rest_json) == 0 || json_last_error() <> JSON_ERROR_NONE) {
		http_response_code(403);
		exit('Invalid request body');
	}
}

if ($function_type == 'Document') {
	//include 'document.php'; >>>

	// Overkill, but meh, maybe it will be useful someday
	if (!empty($default_file)) {
		$file_path = $default_file;
	} else {
		$data_date = substr($prm_begDate, 0, 10);
		$file_path = $method_name . '_on_' . $data_date . '.json';
	}
	
	// Search for file with requested data to send it back later
	$request_body = file_get_contents($file_path);
	if ($request_body === false) {
		http_response_code(500);
		echo 'I need a file with data for that function for that day '.$file_path;

	} else {
		// Answer on request but continue php execution
		ob_start();                              // Buffer all upcoming output
		echo '';                                 // Send response body

		$size = ob_get_length();                 // Get output size
		http_response_code(200);                 // Set response code
		header('Content-Encoding: none');        // Disable compression (in case content length is compressed)
		header('Content-Length: 0');             // Set the content length of the response
		header('Connection: close');             // Close the connection
		ob_end_flush(); ob_flush(); flush();     // Flush all output
		if (session_id()) session_write_close(); // Close current session (if it exists)

		// Emulate thinking process
		sleep($sleep_time);

		// Send request to the 1C-Service
		$authentication = base64_encode($user_password);
		$curl = curl_init($callback_url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,          // return web page
			CURLOPT_HEADER         => false,         // don't return headers
			CURLOPT_FOLLOWLOCATION => true,          // follow redirects
			//CURLOPT_ENCODING       => "utf-8",     // handle all encodings
			CURLOPT_AUTOREFERER    => true,          // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 20,            // timeout on connect
			CURLOPT_TIMEOUT        => 20,            // timeout on response
			CURLOPT_POST           => 1,             // i am sending post data
			CURLOPT_POSTFIELDS     => $request_body, // send this
			CURLOPT_SSL_VERIFYHOST => 0,             // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false,         // don't verify ssl
			CURLOPT_VERBOSE        => 1,
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Basic ' . $authentication,
				'Content-Type: application/json; charset=utf-8',
				'function: ' . $method_name,
				'batchId: ' . $prm_batchId,
				'begDate: ' . $prm_begDate,
				'endDate: ' . $prm_endDate
			)
		);
		curl_setopt_array($curl, $options);
		curl_exec($curl);
		curl_close($curl);
	}

} elseif ($function_type == 'Catalog') {
	// TODO: upgrade if needed

	// Overkill, but meh, maybe it will be useful someday
	if (!empty($default_file)) {
		$file_path = $default_file;
	} else {
		$data_date = substr($prm_begDate, 0, 10);
		$file_path = $method_name . '_on_' . $data_date . '.json';
	}

	// Search for file with requested data to send it back later
	$request_body = file_get_contents($file_path);

	http_response_code(200);
	exit($request_body);
	//http_response_code(403);
	//exit('Method is under development');

} else {
	// Huh? How so?
	http_response_code(500);
	exit($function_type);

}