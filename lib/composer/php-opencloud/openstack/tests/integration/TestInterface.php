<?php

namespace OpenStack\Integration;

use Psr\Log\LoggerInterface;

interface TestInterface
{
    public function __construct(LoggerInterface $logger, SampleManagerInterface $sampleManager);

    public function runTests();

    public function runOneTest($name);

    public function teardown();
}
