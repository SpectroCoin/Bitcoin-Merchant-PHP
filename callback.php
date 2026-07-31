<?php

include_once('constants.php');
include_once('SCMerchantClient/SCMerchantClient.php');

// The callback is an unauthenticated server-to-server webhook: the payload
// signature is the only authenticator, so it has to be verified before any
// order is acted on, and the payload must be read from the POST body only.
if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
	http_response_code(405);
	exit('Invalid request method.');
}

$scMerchantClient = new SCMerchantClient(SC_API_URL, SC_MERCHANT_ID, SC_MERCHANT_API_ID);
$callback = $scMerchantClient->parseCreateOrderCallback($_POST);

if ($callback != null && $scMerchantClient->validateCreateOrderCallback($callback)){

	switch ($callback->getStatus()) {
		case OrderStatusEnum::$Test:
			processTestCallback($callback);
			break;
		case OrderStatusEnum::$New:
			processNewCallback($callback);
			break;
		case OrderStatusEnum::$Pending:
			processPendingCallback($callback);
			break;
		case OrderStatusEnum::$Expired:
			processExpiredCallback($callback);
			break;
		case OrderStatusEnum::$Failed:
			processFailedCallback($callback);
			break;
		case OrderStatusEnum::$Paid:
			processPaidCallback($callback);
			break;
		default:
			echo 'Unknown order status: '.$callback->getStatus();
			break;
	}

//	exit(print_r($callback, true));
	echo '*ok*';

} else {
	http_response_code(400);
	echo 'Invalid callback!';
}

function processTestCallback(OrderCallback $callback) {
	// process
}
function processNewCallback(OrderCallback $callback) {
	// process
}
function processPendingCallback(OrderCallback $callback) {
	// process
}
function processExpiredCallback(OrderCallback $callback) {
	// process
}
function processFailedCallback(OrderCallback $callback) {
	// process
}
function processPaidCallback(OrderCallback $callback) {
	// process
}