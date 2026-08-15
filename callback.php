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

	// These statuses report on a payment already under way (partial/late
	// payments, refunds, the TEST_* dry-run traffic) and carry no shop-side
	// transition. They must be acknowledged, not routed through the switch
	// below, because moving the order here would either fulfil an order that
	// was not paid in full or reverse one the merchant may already have
	// settled by hand.
	if (OrderStatusEnum::isInformational($callback->getStatus())) {
		processInformationalCallback($callback);
		echo '*ok*';
		exit;
	}

	switch ($callback->getStatus()) {
		case OrderStatusEnum::$New:
			processNewCallback($callback);
			break;
		case OrderStatusEnum::$Pending:
			processPendingCallback($callback);
			break;
		case OrderStatusEnum::$Paid:
			processPaidCallback($callback);
			break;
		case OrderStatusEnum::$Expired:
			processExpiredCallback($callback);
			break;
		case OrderStatusEnum::$Failed:
		case OrderStatusEnum::$Cancelled:
		case OrderStatusEnum::$Rejected:
		case OrderStatusEnum::$InvalidPayment:
			processFailedCallback($callback);
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

function processInformationalCallback(OrderCallback $callback) {
	// process, must not change the order's status
}
function processNewCallback(OrderCallback $callback) {
	// process
}
function processPendingCallback(OrderCallback $callback) {
	// process
}
function processPaidCallback(OrderCallback $callback) {
	// process
}
function processExpiredCallback(OrderCallback $callback) {
	// process
}
function processFailedCallback(OrderCallback $callback) {
	// process
}