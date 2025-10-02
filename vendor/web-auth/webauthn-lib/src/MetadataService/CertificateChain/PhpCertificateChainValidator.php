<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\CertificateChain;

use DateTimeZone;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\CertificationPath\CertificationPath;
use SpomkyLabs\Pki\X509\CertificationPath\PathValidation\PathValidationConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use Webauthn\Event\BeforeCertificateChainValidation;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\CertificateChainValidationFailed;
use Webauthn\Event\CertificateChainValidationSucceeded;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\CertificateChainException;
use Webauthn\Exception\CertificateRevocationListException;
use Webauthn\Exception\InvalidCertificateException;
use function count;
use function in_array;
use function parse_url;
use const PHP_EOL;
use const PHP_URL_SCHEME;

class PhpCertificateChainValidator implements CertificateChainValidator, CanDispatchEvents
{
    private const MAX_VALIDATION_LENGTH = 5;

    private readonly Clock|ClockInterface $clock;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        private readonly ClientInterface|HttpClientInterface $client,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        null|Clock|ClockInterface $clock = null,
        private readonly bool $allowFailures = true
    ) {
        if ($clock === null) {
            trigger_deprecation(
                'web-auth/metadata-service',
                '4.5.0',
                'The parameter "$clock" will become mandatory in 5.0.0. Please set a valid PSR Clock implementation instead of "null".'
            );
            $clock = new SystemClock(new DateTimeZone('UTC'));
        }
        if ($requestFactory !== null && ! $client instanceof HttpClientInterface) {
            trigger_deprecation(
                'web-auth/metadata-service',
                '4.7.0',
                'The parameter "$requestFactory" will be removed in 5.0.0. Please set it to null and set an Symfony\Contracts\HttpClient\HttpClientInterface as "$client" argument.'
            );
        }
        $this->clock = $clock;
        $this->dispatcher = new NullEventDispatcher();
    }

    public static function create(
        HttpClientInterface $client,
        null|Clock|ClockInterface $clock = null,
        bool $allowFailures = true
    ): self {
        return new self($client, null, $clock, $allowFailures);
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * @param string[] $untrustedCertificates
     * @param string[] $trustedCertificates
     */
    public function check(array $untrustedCertificates, array $trustedCertificates): void
    {
        foreach ($trustedCertificates as $trustedCertificate) {
            $this->dispatcher->dispatch(
                BeforeCertificateChainValidation::create($untrustedCertificates, $trustedCertificate)
            );
            try {
                if ($this->validateChain($untrustedCertificates, $trustedCertificate)) {
                    $this->dispatcher->dispatch(
                        CertificateChainValidationSucceeded::create($untrustedCertificates, $trustedCertificate)
                    );
                    return;
                }
            } catch (Throwable $exception) {
                $this->dispatcher->dispatch(
                    CertificateChainValidationFailed::create($untrustedCertificates, $trustedCertificate)
                );
                throw $exception;
            }
        }

        throw CertificateChainException::create($untrustedCertificates, $trustedCertificates);
    }

    /**
     * @param string[] $untrustedCertificates
     */
    private function validateChain(array $untrustedCertificates, string $trustedCertificate): bool
    {
        $untrustedCertificates = array_map(
            static fn (string $cert): Certificate => Certificate::fromPEM(PEM::fromString($cert)),
            array_reverse($untrustedCertificates)
        );
        $trustedCertificate = Certificate::fromPEM(PEM::fromString($trustedCertificate));

        // The trust path and the authenticator certificate are the same
        if (count(
            $untrustedCertificates
        ) === 1 && $untrustedCertificates[0]->toPEM()->string() === $trustedCertificate->toPEM()->string()) {
            return true;
        }
        $uniqueCertificates = array_map(
            static fn (Certificate $cert): string => $cert->toPEM()
                ->string(),
            [...$untrustedCertificates, $trustedCertificate]
        );
        count(array_unique($uniqueCertificates)) === count(
            $uniqueCertificates
        ) || throw CertificateChainException::create(
            $untrustedCertificates,
            [$trustedCertificate],
            'Invalid certificate chain with duplicated certificates.'
        );

        if (! $this->validateCertificates($trustedCertificate, ...$untrustedCertificates)) {
            return false;
        }

        $certificates = [$trustedCertificate, ...$untrustedCertificates];
        $numCerts = count($certificates);
        for ($i = 1; $i < $numCerts; $i++) {
            if ($this->isRevoked($certificates[$i])) {
                throw CertificateChainException::create(
                    $untrustedCertificates,
                    [$trustedCertificate],
                    'Unable to validate the certificate chain. Revoked certificate found.'
                );
            }
        }

        return true;
    }

    private function isRevoked(Certificate $subject): bool
    {
        try {
            $csn = $subject->tbsCertificate()
                ->serialNumber();
        } catch (Throwable $e) {
            throw InvalidCertificateException::create(
                $subject->toPEM()
                    ->string(),
                sprintf('Failed to parse certificate: %s', $e->getMessage()),
                $e
            );
        }

        try {
            $urls = $this->getCrlUrlList($subject);
        } catch (Throwable $e) {
            if ($this->allowFailures) {
                return false;
            }
            throw InvalidCertificateException::create(
                $subject->toPEM()
                    ->string(),
                'Failed to get CRL distribution points: ' . $e->getMessage(),
                $e
            );
        }

        foreach ($urls as $url) {
            try {
                $revokedCertificates = $this->retrieveRevokedSerialNumbers($url);

                if (in_array($csn, $revokedCertificates, true)) {
                    return true;
                }
            } catch (Throwable $e) {
                if ($this->allowFailures) {
                    return false;
                }
                throw CertificateRevocationListException::create($url, sprintf(
                    'Failed to retrieve the CRL:' . PHP_EOL . '%s',
                    $e->getMessage()
                ), $e);
            }
        }
        return false;
    }

    private function validateCertificates(Certificate ...$certificates): bool
    {
        try {
            $config = PathValidationConfig::create($this->clock->now(), self::MAX_VALIDATION_LENGTH);
            CertificationPath::create(...$certificates)->validate($config);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    private function retrieveRevokedSerialNumbers(string $url): array
    {
        try {
            if ($this->client instanceof HttpClientInterface) {
                $crlData = $this->client->request('GET', $url)
                    ->getContent();
            } else {
                $crlData = $this->sendPsrRequest($url);
            }
            $crl = UnspecifiedType::fromDER($crlData)->asSequence();
            count($crl) === 3 || throw CertificateRevocationListException::create($url);
            $tbsCertList = $crl->at(0)
                ->asSequence();
            count($tbsCertList) >= 6 || throw CertificateRevocationListException::create($url);
            $list = $tbsCertList->at(5)
                ->asSequence();

            return array_map(static function (UnspecifiedType $r) use ($url): string {
                $sequence = $r->asSequence();
                count($sequence) >= 1 || throw CertificateRevocationListException::create($url);
                return $sequence->at(0)
                    ->asInteger()
                    ->number();
            }, $list->elements());
        } catch (Throwable $e) {
            throw CertificateRevocationListException::create($url, 'Failed to download the CRL', $e);
        }
    }

    /**
     * @return string[]
     */
    private function getCrlUrlList(Certificate $subject): array
    {
        try {
            $urls = [];

            $extensions = $subject->tbsCertificate()
                ->extensions();
            if ($extensions->hasCRLDistributionPoints()) {
                $crlDists = $extensions->crlDistributionPoints();
                foreach ($crlDists->distributionPoints() as $dist) {
                    $url = $dist->fullName()
                        ->names()
                        ->firstURI();
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    if (! in_array($scheme, ['http', 'https'], true)) {
                        continue;
                    }
                    $urls[] = $url;
                }
            }
            return $urls;
        } catch (Throwable $e) {
            throw InvalidCertificateException::create(
                $subject->toPEM()
                    ->string(),
                'Failed to get CRL distribution points from certificate: ' . $e->getMessage(),
                $e
            );
        }
    }

    private function sendPsrRequest(string $url): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw CertificateRevocationListException::create($url, 'Failed to download the CRL');
        }

        return $response->getBody()
            ->getContents();
    }
}
