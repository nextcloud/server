<?php
namespace Aws\Retry;

use Aws\Exception\AwsException;
use Aws\ResultInterface;

/**
 * @internal
 */
class QuotaManager
{
    private $availableCapacity;
    private $capacityAmount;
    private $initialRetryTokens;
    private $maxCapacity;
    private $noRetryIncrement;
    private $retryCost;
    private $timeoutRetryCost;

    public function __construct($config = [])
    {
        $this->initialRetryTokens = isset($config['initial_retry_tokens'])
            ? $config['initial_retry_tokens']
            : 500;
        $this->noRetryIncrement = isset($config['no_retry_increment'])
            ? $config['no_retry_increment']
            : 1;
        $this->retryCost = isset($config['retry_cost'])
            ? $config['retry_cost']
            : 5;
        $this->timeoutRetryCost = isset($config['timeout_retry_cost'])
            ? $config['timeout_retry_cost']
            : 10;
        $this->maxCapacity = $this->initialRetryTokens;
        $this->availableCapacity = $this->initialRetryTokens;
    }

    public function hasRetryQuota($result)
    {
        if ($result instanceof AwsException && $result->isConnectionError()) {
            $this->capacityAmount = $this->timeoutRetryCost;
        } else {
            $this->capacityAmount = $this->retryCost;
        }

        if ($this->capacityAmount > $this->availableCapacity) {
            return false;
        }

        $this->availableCapacity -= $this->capacityAmount;
        return true;
    }

    public function releaseToQuota($result)
    {
        if ($result instanceof AwsException) {
            $statusCode = (int) $result->getStatusCode();
        } elseif ($result instanceof ResultInterface) {
            $statusCode = isset($result['@metadata']['statusCode'])
                ? (int) $result['@metadata']['statusCode']
                : null;
        }

        if (!empty($statusCode) && $statusCode >= 200 && $statusCode < 300) {
            if (isset($this->capacityAmount)) {
                $amount = $this->capacityAmount;
                $this->availableCapacity += $amount;
                unset($this->capacityAmount);
            } else {
                $amount = $this->noRetryIncrement;
                $this->availableCapacity += $amount;
            }
            $this->availableCapacity = min(
                $this->availableCapacity,
                $this->maxCapacity
            );
        }

        return (isset($amount) ? $amount : 0);
    }

    public function getAvailableCapacity()
    {
        return $this->availableCapacity;
    }
}
