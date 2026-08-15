<?php

/**
 * Invariant tests for order-status coverage.
 *
 * The API reports more statuses than a payment simply succeeding or failing:
 * partial and late payments, and the refund lifecycle. Every status it can send
 * must be understood here, otherwise the callback answers "Unknown order
 * status" and SpectroCoin records a failed delivery and retries forever.
 *
 * Statuses are classified three ways:
 *   - a completed or terminal outcome, which moves the order;
 *   - a cancellation, which ends the order without payment;
 *   - informational, which is recorded and leaves the order untouched, because
 *     transitioning automatically would either fulfil an order that was not
 *     paid in full or reverse one the merchant may already have settled.
 *
 * Standalone by design: this client ships no PHPUnit setup.
 *
 * Run:  php tests/check-order-status-coverage.php
 */

require_once __DIR__ . '/../SCMerchantClient/data/OrderStatusEnum.php';

/** Every status the API can put on the wire, by its numeric code. */
const WIRE_STATUSES = [
    'New' => 1, 'Pending' => 2, 'Paid' => 3, 'Failed' => 4, 'Expired' => 5,
    'Test' => 6,
    'LateCryptoPayment' => 10, 'PartialPayment' => 11, 'Underpaid' => 12,
    'Cancelled' => 13, 'InvalidPayment' => 14, 'TestPaid' => 15,
    'TestExpired' => 16, 'ProcessingRefund' => 17, 'Refunded' => 18,
    'RejectedRefund' => 19, 'PendingLateCryptoPayment' => 20, 'Rejected' => 21,
];

const CANCELLATIONS = ['Failed', 'Cancelled', 'Rejected', 'InvalidPayment'];
const INFORMATIONAL = ['PartialPayment', 'Underpaid', 'LateCryptoPayment',
                        'PendingLateCryptoPayment', 'ProcessingRefund',
                        'Refunded', 'RejectedRefund',
                        'Test', 'TestPaid', 'TestExpired'];

class TestRunner
{
    private $failures = [];
    private $passed = 0;
    private $failed = 0;

    public function assertTrue($cond, $message)
    {
        if (!$cond) { $this->failures[] = $message; }
    }

    public function assertSame($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            $this->failures[] = $message . ' (expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true) . ')';
        }
    }

    public function run($name, callable $test)
    {
        $this->failures = [];
        try { $test($this); }
        catch (\Throwable $e) { $this->failures[] = 'threw ' . get_class($e) . ': ' . $e->getMessage(); }
        if (empty($this->failures)) { $this->passed++; echo "  PASS  {$name}\n"; }
        else {
            $this->failed++;
            echo "  FAIL  {$name}\n";
            foreach ($this->failures as $f) { echo "          {$f}\n"; }
        }
    }

    public function summary()
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$callbackSource = file_get_contents(__DIR__ . '/../callback.php');

$t = new TestRunner();
echo "SpectroCoin Merchant PHP — order-status coverage\n\n";

$t->run('every status the API can send is defined on the enum', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $t->assertSame($code, OrderStatusEnum::${$name},
            "OrderStatusEnum::\${$name} must equal the wire code {$code}");
    }
});

$t->run('cancellations are classified exactly', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $expected = in_array($name, CANCELLATIONS, true);
        $t->assertSame($expected, OrderStatusEnum::isCancellation($code),
            "{$name}: isCancellation() classification");
    }
});

$t->run('informational statuses are classified exactly', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $expected = in_array($name, INFORMATIONAL, true);
        $t->assertSame($expected, OrderStatusEnum::isInformational($code),
            "{$name}: isInformational() classification");
    }
});

$t->run('no status is both a cancellation and informational', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $t->assertTrue(!(OrderStatusEnum::isCancellation($code) && OrderStatusEnum::isInformational($code)),
            "{$name} must not be classified both ways");
    }
});

$t->run('a status outside the contract is still rejected', function ($t) {
    foreach ([999, -1, 0] as $bogus) {
        $t->assertTrue(!OrderStatusEnum::isCancellation($bogus) && !OrderStatusEnum::isInformational($bogus),
            "status code '{$bogus}' must not be classified as cancellation or informational");
    }
});

$t->run('the callback consults the informational classification', function ($t) use ($callbackSource) {
    $t->assertTrue(strpos($callbackSource, 'isInformational(') !== false,
        'the callback must skip shop-side changes for informational statuses');
});

$t->run('the callback routes every cancellation status', function ($t) use ($callbackSource) {
    foreach (CANCELLATIONS as $name) {
        $t->assertTrue(strpos($callbackSource, 'OrderStatusEnum::$' . $name) !== false,
            "the callback must handle the {$name} status");
    }
});

$t->run('every non-informational status the API sends is explicitly routed', function ($t) use ($callbackSource) {
    foreach (WIRE_STATUSES as $name => $code) {
        if (in_array($name, INFORMATIONAL, true)) {
            continue; // acknowledged via isInformational(), never reaches the switch
        }
        $t->assertTrue(strpos($callbackSource, 'OrderStatusEnum::$' . $name) !== false,
            "the callback must not fall through to 'Unknown order status' for {$name}");
    }
});

exit($t->summary());
