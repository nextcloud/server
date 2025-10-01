<?php

declare(strict_types=1);

namespace Webauthn\Counter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Webauthn\Exception\CounterException;
use Webauthn\MetadataService\CanLogData;
use Webauthn\PublicKeyCredentialSource;

final class ThrowExceptionIfInvalid implements CounterChecker, CanLogData
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function check(PublicKeyCredentialSource $publicKeyCredentialSource, int $currentCounter): void
    {
        try {
            $currentCounter > $publicKeyCredentialSource->counter || throw CounterException::create(
                $currentCounter,
                $publicKeyCredentialSource->counter,
                'Invalid counter.'
            );
        } catch (CounterException $throwable) {
            $this->logger->error('The counter is invalid', [
                'current' => $currentCounter,
                'new' => $publicKeyCredentialSource->counter,
            ]);
            throw $throwable;
        }
    }
}
