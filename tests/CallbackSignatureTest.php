<?php

/**
 * Signature-verification tests for the SpectroCoin merchant PHP client.
 *
 * callback.php is an unauthenticated server-to-server webhook, so
 * SCMerchantClient's RSA signature check is the only thing separating a genuine
 * SpectroCoin notification from an attacker-supplied one. These tests assert
 * that forged, tampered, wrong-merchant and unverifiable payloads are rejected,
 * and that a correctly signed payload is accepted.
 *
 * Run standalone (no test framework required):
 *
 *   php tests/CallbackSignatureTest.php
 */

require_once __DIR__ . '/../SCMerchantClient/SCMerchantClient.php';

class CallbackSignatureTest
{
    /** @var string */
    private $keyDir;
    /** @var string */
    private $publicCertPath;
    /** @var string */
    private $privateKeyPem;

    const MERCHANT_ID = '1387551';
    const API_ID = '105548';

    public function setUp()
    {
        $this->keyDir = sys_get_temp_dir() . '/sc-php-callback-test-' . getmypid();
        if (!is_dir($this->keyDir)) {
            mkdir($this->keyDir, 0700, true);
        }

        // A throwaway key pair stands in for SpectroCoin's signing key.
        $res = openssl_pkey_new(array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        openssl_pkey_export($res, $this->privateKeyPem);
        $details = openssl_pkey_get_details($res);

        $this->publicCertPath = $this->keyDir . '/merchant.public.pem';
        file_put_contents($this->publicCertPath, $details['key']);
    }

    public function tearDown()
    {
        if (is_file($this->publicCertPath)) {
            unlink($this->publicCertPath);
        }
        if (is_dir($this->keyDir)) {
            rmdir($this->keyDir);
        }
    }

    private function client($certPath = null)
    {
        $client = new SCMerchantClient('https://spectrocoin.com/api/merchant/1', self::MERCHANT_ID, self::API_ID);
        // Pin the public certificate locally so the test never touches the network.
        $client->setPublicSpectroCoinCertLocation($certPath === null ? $this->publicCertPath : $certPath);
        return $client;
    }

    private function rawPayload(array $overrides = array())
    {
        return array_merge(array(
            'userId' => self::MERCHANT_ID,
            'merchantApiId' => self::API_ID,
            'merchantId' => self::MERCHANT_ID,
            'apiId' => self::API_ID,
            'orderId' => '1001',
            'payCurrency' => 'BTC',
            'payAmount' => '0.01',
            'receiveCurrency' => 'EUR',
            'receiveAmount' => '100.00',
            'receivedAmount' => '100.00',
            'description' => 'Order #1001',
            'orderRequestId' => '12345',
            'status' => '3',
            'payerName' => 'Test',
            'payerSurname' => 'Payer',
            'payerEmail' => 'payer@example.com',
        ), $overrides);
    }

    /**
     * Serialises a payload the way validateCreateOrderCallback() does - through
     * OrderCallback's getters, so amount formatting matches exactly.
     */
    private function canonicalData(array $p)
    {
        $c = new OrderCallback(
            $p['userId'], $p['merchantApiId'], $p['merchantId'], $p['apiId'], $p['orderId'],
            $p['payCurrency'], $p['payAmount'], $p['receiveCurrency'], $p['receiveAmount'],
            $p['receivedAmount'], $p['description'], $p['orderRequestId'], $p['status'],
            'unused', $p['payerName'], $p['payerSurname'], $p['payerEmail']
        );

        $formHandler = new \Httpful\Handlers\FormHandler();

        return $formHandler->serialize(array(
            'merchantId' => $c->getMerchantId(),
            'apiId' => $c->getApiId(),
            'orderId' => $c->getOrderId(),
            'payCurrency' => $c->getPayCurrency(),
            'payAmount' => $c->getPayAmount(),
            'receiveCurrency' => $c->getReceiveCurrency(),
            'receiveAmount' => $c->getReceiveAmount(),
            'receivedAmount' => $c->getReceivedAmount(),
            'description' => $c->getDescription(),
            'orderRequestId' => $c->getOrderRequestId(),
            'status' => $c->getStatus(),
        ));
    }

    private function signedPayload(array $overrides = array())
    {
        $payload = $this->rawPayload($overrides);

        openssl_sign(
            $this->canonicalData($payload),
            $signature,
            openssl_pkey_get_private($this->privateKeyPem),
            OPENSSL_ALGO_SHA1
        );
        $payload['sign'] = base64_encode($signature);

        return $payload;
    }

    public function testValidCallbackIsAccepted()
    {
        $client = $this->client();
        $callback = $client->parseCreateOrderCallback($this->signedPayload());

        $this->assertTrue($callback !== null, 'a complete payload should parse');
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === true,
            'a correctly signed callback must be accepted'
        );
    }

    public function testForgedSignatureIsRejected()
    {
        $client = $this->client();
        $payload = $this->signedPayload();
        $payload['sign'] = base64_encode('this-is-not-a-signature');

        $callback = $client->parseCreateOrderCallback($payload);
        $this->assertTrue($callback !== null, 'a complete payload should parse');
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a forged signature must be rejected'
        );
    }

    public function testNonBase64SignatureIsRejected()
    {
        $client = $this->client();
        $payload = $this->signedPayload();
        $payload['sign'] = '!!!not base64!!!';

        $callback = $client->parseCreateOrderCallback($payload);
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a non-base64 signature must be rejected'
        );
    }

    public function testTamperedStatusIsRejected()
    {
        $client = $this->client();

        // Signed as status 2 (pending), then flipped to 3 (paid) in transit:
        // exactly the payment bypass an attacker would attempt.
        $payload = $this->signedPayload(array('status' => '2'));
        $payload['status'] = '3';

        $callback = $client->parseCreateOrderCallback($payload);
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a callback whose status was changed after signing must be rejected'
        );
    }

    public function testTamperedAmountIsRejected()
    {
        $client = $this->client();

        $payload = $this->signedPayload(array('receivedAmount' => '1.00'));
        $payload['receivedAmount'] = '100.00';

        $callback = $client->parseCreateOrderCallback($payload);
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a callback whose amount was changed after signing must be rejected'
        );
    }

    public function testWrongMerchantIsRejected()
    {
        $client = $this->client();

        // Correctly signed, but issued for a different merchant account.
        $payload = $this->signedPayload(array('userId' => '999999'));

        $callback = $client->parseCreateOrderCallback($payload);
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a callback for another merchant must be rejected'
        );
    }

    public function testUnreadableCertificateDoesNotFailOpen()
    {
        // openssl_verify() returns -1 on error; treating that as truthy used to
        // make an unreachable or corrupt certificate fail open.
        $client = $this->client($this->keyDir . '/does-not-exist.pem');

        $callback = $client->parseCreateOrderCallback($this->signedPayload());
        $this->assertTrue(
            $client->validateCreateOrderCallback($callback) === false,
            'a missing public certificate must not be treated as a valid signature'
        );
    }

    public function testCallbackEndpointVerifiesAndIsPostOnly()
    {
        $source = file_get_contents(__DIR__ . '/../callback.php');

        $this->assertTrue(
            strpos($source, 'validateCreateOrderCallback') !== false,
            'callback.php must call validateCreateOrderCallback()'
        );
        $this->assertTrue(
            strpos($source, '$_REQUEST') === false,
            'callback.php must not read payload fields from the merged request array'
        );
        $this->assertTrue(
            strpos($source, "REQUEST_METHOD']) !== 'POST'") !== false,
            'callback.php must accept POST only'
        );
    }

    public function testOutboundApiCallsVerifyTls()
    {
        $source = file_get_contents(__DIR__ . '/../SCMerchantClient/SCMerchantClient.php');

        // Httpful defaults to disabling peer/host verification.
        $sends = substr_count($source, '->send();');
        $strict = substr_count($source, 'strictSSL(true)');

        $this->assertTrue($sends > 0, 'expected at least one outbound request');
        $this->assertTrue(
            $strict >= $sends,
            'every outbound request must enable strict SSL verification'
        );
    }

    // --- minimal assertion + runner so the file works without PHPUnit ---

    private $failures = array();
    private $assertions = 0;

    private function assertTrue($condition, $message)
    {
        $this->assertions++;
        if ($condition !== true) {
            $this->failures[] = $message;
        }
    }

    public function run()
    {
        $tests = array();
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'test') === 0) {
                $tests[] = $method;
            }
        }

        $failed = 0;
        foreach ($tests as $test) {
            $this->failures = array();
            $this->setUp();
            try {
                $this->$test();
            } catch (\Throwable $e) {
                $this->failures[] = 'threw ' . get_class($e) . ': ' . $e->getMessage();
            }
            $this->tearDown();

            if (empty($this->failures)) {
                echo "PASS  {$test}\n";
            } else {
                $failed++;
                echo "FAIL  {$test}\n";
                foreach ($this->failures as $failure) {
                    echo "        {$failure}\n";
                }
            }
        }

        $total = count($tests);
        echo "\n" . ($total - $failed) . "/{$total} passed, {$this->assertions} assertions\n";

        return $failed === 0 ? 0 : 1;
    }
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $runner = new CallbackSignatureTest();
    exit($runner->run());
}
