<?php

namespace OpenStack\Integration;

use OpenStack\Common\Resource\Deletable;
use Psr\Log\LoggerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase implements TestInterface
{
    protected $logger;
    private $startPoint;
    private $lastPoint;
    private $sampleManager;
    private $namePrefix = 'phptest_';

    public function __construct(LoggerInterface $logger, SampleManagerInterface $sampleManager)
    {
        $this->logger = $logger;
        $this->sampleManager = $sampleManager;
    }

    public function teardown()
    {
        $this->sampleManager->deletePaths();
    }

    public function runOneTest($name)
    {
        if (!method_exists($this, $name)) {
            throw new \InvalidArgumentException(sprintf("%s method does not exist", $name));
        }

        $this->startTimer();
        $this->$name();
        $this->outputTimeTaken();
    }

    protected function startTimer()
    {
        $this->startPoint = $this->lastPoint = microtime(true);
    }

    private function wrapColor($message, $colorPrefix)
    {
        return sprintf("%s%s", $colorPrefix, $message) . "\033[0m\033[1;0m";
    }

    protected function logStep($message, array $context = [])
    {
        $duration = microtime(true) - $this->lastPoint;

        $stepTimeTaken = sprintf('(%s)', $this->formatSecDifference($duration));

        if ($duration >= 10) {
            $color = "\033[0m\033[1;31m"; // red
        } elseif ($duration >= 2) {
            $color = "\033[0m\033[1;33m"; // yellow
        } else {
            $color = "\033[0m\033[1;32m"; // green
        }

        $message = '{timeTaken} ' . $message;
        $context['{timeTaken}'] = $this->wrapColor($stepTimeTaken, $color);

        $this->logger->info($message, $context);

        $this->lastPoint = microtime(true);
    }

    protected function randomStr($length = 5)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsLen = strlen($chars);

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[rand(0, $charsLen - 1)];
        }

        return $this->namePrefix . $randomString;
    }

    private function formatMinDifference($duration)
    {
        $output = '';

        if (($minutes = floor($duration / 60)) > 0) {
            $output .= $minutes . 'min' . (($minutes > 1) ? 's' : '');
        }

        if (($seconds = number_format(fmod($duration, 60), 2)) > 0) {
            if ($minutes > 0) {
                $output .= ' ';
            }
            $output .= $seconds . 's';
        }

        return $output;
    }

    private function formatSecDifference($duration)
    {
        return number_format($duration, 2) . 's';
    }

    protected function outputTimeTaken()
    {
        $output = $this->formatMinDifference(microtime(true) - $this->startPoint);

        $this->logger->info('Finished all tests! Time taken: {output}.', ['{output}' => $output]);
    }

    protected function sampleFile(array $replacements, $path)
    {
        return $this->sampleManager->write($path, $replacements);
    }

    protected function getBaseClient()
    {
        return eval($this->sampleManager->getConnectionStr());
    }

    protected function deleteItems(\Generator $items)
    {
        foreach ($items as $item) {
            if ($item instanceof Deletable
                && property_exists($item, 'name')
                && strpos($item->name, $this->namePrefix) === 0
            ) {
                $item->delete();
            }
        }
    }
}
