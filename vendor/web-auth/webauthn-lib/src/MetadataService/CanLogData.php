<?php

declare(strict_types=1);

namespace Webauthn\MetadataService;

use Psr\Log\LoggerInterface;

interface CanLogData
{
    public function setLogger(LoggerInterface $logger): void;
}
