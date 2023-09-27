<?php

class CreateOrderRequest
{
	private $orderId;
	private $payCurrency;
	private $payAmount;
	private $receiveCurrency;
	private $receiveAmount;
	private $description;
	private $culture;
	private $callbackUrl;
	private $successUrl;
	private $failureUrl;
	private $payerName;
	private $payerSurname;
	private $payerEmail;

	/**
	 * @param $orderId
	 * @param $payCurrency - Customer pay amount calculation currency
	 * @param $payAmount - Customer pay amount in calculation currency
	 * @param $receiveCurrency - Merchant receive amount calculation currency
	 * @param $receiveAmount - Merchant receive amount in calculation currency
	 * @param $description
	 * @param $culture
	 * @param $callbackUrl
	 * @param $successUrl
	 * @param $failureUrl
	 * @param $payerName
	 * @param $payerSurname
	 * @param $payerEmail
	 */
	function __construct($orderId, $payCurrency, $payAmount, $receiveCurrency, $receiveAmount, $description, $culture, $callbackUrl, $successUrl, $failureUrl, $payerName = null, $payerSurname = null, $payerEmail = null)
	{
		$this->orderId = $orderId;
		$this->payCurrency = $payCurrency;
		$this->payAmount = $payAmount;
		$this->receiveCurrency = $receiveCurrency;
		$this->receiveAmount = $receiveAmount;
		$this->description = $description;
		$this->culture = $culture;
		$this->callbackUrl = $callbackUrl;
		$this->successUrl = $successUrl;
		$this->failureUrl = $failureUrl;
		$this->payerName = $payerName;
		$this->payerSurname = $payerSurname;
		$this->payerEmail = $payerEmail;
	}

	/**
	 * @return string
	 */
	public function getPayAmount()
	{
		return FormattingUtil::formatCurrency($this->payAmount == null ? 0.0 : $this->payAmount);
	}

	/**
	 * @return string
	 */
	public function getPayCurrency()
	{
		return $this->payCurrency;
	}

	/**
	 * @return string
	 */
	public function getOrderId()
	{
		return $this->orderId == null ? '' : $this->orderId;
	}

	/**
	 * @return string
	 */
	public function getReceiveAmount()
	{
		return FormattingUtil::formatCurrency($this->receiveAmount == null ? 0.0 : $this->receiveAmount);
	}

	/**
	 * @return string
	 */
	public function getReceiveCurrency()
	{
		return $this->receiveCurrency;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description == null ? '' : $this->description;
	}

	/**
	 * @return string
	 */
	public function getCulture()
	{
		return $this->culture == null ? '' : $this->culture;
	}
	
	/**
	 * @return string
	 */
	public function getCallbackUrl()
	{
		return $this->callbackUrl == null ? '' : $this->callbackUrl;
	}

	/**
	 * @return string
	 */
	public function getSuccessUrl()
	{
		return $this->successUrl == null ? '' : $this->successUrl;
	}

	/**
	 * @return string
	 */
	public function getFailureUrl()
	{
		return $this->failureUrl == null ? '' : $this->failureUrl;
	}

	/**
	 * @return string
	 */
	public function getPayerName()
	{
		return $this->payerName == null ? '' : $this->payerName;
	}

	/**
	 * @return string
	 */
	public function getPayerSurname()
	{
		return $this->payerSurname == null ? '' : $this->payerSurname;
	}

	/**
	 * @return string
	 */
	public function getPayerEmail()
	{
		return $this->payerEmail == null ? '' : $this->payerEmail;
	}
}