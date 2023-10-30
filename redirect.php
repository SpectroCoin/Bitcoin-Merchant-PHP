<?php

//redirect.php

include_once('constants.php');
include_once('SCMerchantClient/SCMerchantClient.php');

$orderId = null;// "Order005";
$payCurrency = 'BTC'; // Customer pay amount calculation currency
$payAmount = null;//0.00025; // Customer pay amount in calculation currency
$receiveCurrency = 'GBP'; // Merchant receive amount calculation currency
$receiveAmount = 9.99; // Merchant receive amount in calculation currency
$description = "Order 'Order001' at www.merchant.com"; // Description of the order.
$culture = "en"; // Language or culture setting (e.g., English).
$payerName = "Name"; // OPTIONAL - First name of the payer/customer.
$payerSurname = "Surname"; //OPTIONAL - Last name of the payer/customer.
$payerEmail = "your-email@gmail.com"; // OPTIONAL - Email address of the payer/customer.

$scMerchantClient = new SCMerchantClient(SC_API_URL, SC_MERCHANT_ID, SC_MERCHANT_API_ID);
$createOrderRequest = new CreateOrderRequest($orderId, $payCurrency, $payAmount, $receiveCurrency, $receiveAmount, $description, $culture, SC_MERCHANT_ORDER_CALLBACK_URL, SC_MERCHANT_ORDER_SUCCESS_URL, SC_MERCHANT_ORDER_FAILURE_URL);
$createOrderResponse = $scMerchantClient->createOrder($createOrderRequest);

if ($createOrderResponse instanceof ApiError) {
	echo 'Error occurred. ' . $createOrderResponse->getCode() . ': ' . $createOrderResponse->getMessage();
} else if ($createOrderResponse instanceof CreateOrderResponse) {
	header('Location: '.$createOrderResponse->getRedirectUrl());
	exit();
} else {
	echo 'error';
}

?>
