<?php

declare(strict_types=1);

namespace Webauthn;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Event\AuthenticatorAttestationResponseValidationFailedEvent;
use Webauthn\Event\AuthenticatorAttestationResponseValidationSucceededEvent;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\CanLogData;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\TokenBinding\TokenBindingHandler;
use function is_string;

class AuthenticatorAttestationResponseValidator implements CanLogData, CanDispatchEvents
{
    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private readonly CeremonyStepManagerFactory $ceremonyStepManagerFactory;

    public function __construct(
        null|AttestationStatementSupportManager $attestationStatementSupportManager = null,
        private readonly null|PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository = null,
        private readonly null|TokenBindingHandler $tokenBindingHandler = null,
        null|ExtensionOutputCheckerHandler $extensionOutputCheckerHandler = null,
        null|EventDispatcherInterface $eventDispatcher = null,
        private null|CeremonyStepManager $ceremonyStepManager = null
    ) {
        if ($this->publicKeyCredentialSourceRepository !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.6.0',
                'The parameter "$publicKeyCredentialSourceRepository" is deprecated since 4.6.0 and will be removed in 5.0.0. Please set "null" instead.'
            );
        }
        if ($this->tokenBindingHandler !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.3.0',
                'The parameter "$tokenBindingHandler" is deprecated since 4.3.0 and will be removed in 5.0.0. Please set "null" instead.'
            );
        }
        if ($extensionOutputCheckerHandler !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$extensionOutputCheckerHandler" is deprecated since 4.8.0 and will be removed in 5.0.0. Please set "null" instead and inject a CheckExtensions object into the CeremonyStepManager.'
            );
        }
        $this->eventDispatcher = $eventDispatcher ?? new NullEventDispatcher();
        if ($eventDispatcher !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                'The parameter "$eventDispatcher" is deprecated since 4.5.0 will be removed in 5.0.0. Please use `setEventDispatcher` instead.'
            );
        }
        if ($this->ceremonyStepManager === null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$ceremonyStepManager" will mandatory in 5.0.0. Please set a CeremonyStepManager object instead and set null for $attestationStatementSupportManager and $extensionOutputCheckerHandler.'
            );
        }
        $this->logger = new NullLogger();
        $this->ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        if ($attestationStatementSupportManager !== null) {
            $this->ceremonyStepManagerFactory->setAttestationStatementSupportManager(
                $attestationStatementSupportManager
            );
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$attestationStatementSupportManager" is deprecated since 4.8.0 will be removed in 5.0.0. Please set a CheckAttestationFormatIsKnownAndValid object into CeremonyStepManager object instead.'
            );
        }
        if ($extensionOutputCheckerHandler !== null) {
            $this->ceremonyStepManagerFactory->setExtensionOutputCheckerHandler($extensionOutputCheckerHandler);
        }
    }

    /**
     * @private Will become private in 5.0.0
     */
    public static function create(
        null|AttestationStatementSupportManager $attestationStatementSupportManager = null,
        null|PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository = null,
        null|TokenBindingHandler $tokenBindingHandler = null,
        null|ExtensionOutputCheckerHandler $extensionOutputCheckerHandler = null,
        null|EventDispatcherInterface $eventDispatcher = null,
        null|CeremonyStepManager $ceremonyStepManager = null,
    ): self {
        return new self(
            $attestationStatementSupportManager,
            $publicKeyCredentialSourceRepository,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler,
            $eventDispatcher,
            $ceremonyStepManager
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @deprecated since 4.8.0 and will be removed in 5.0.0. Please use the CheckMetadataStatement object from the CeremonyStepManager instead.
     */
    public function setCertificateChainValidator(CertificateChainValidator $certificateChainValidator): self
    {
        $this->ceremonyStepManagerFactory->enableCertificateChainValidator($certificateChainValidator);
        return $this;
    }

    /**
     * @deprecated since 4.8.0 and will be removed in 5.0.0. Please use the CheckMetadataStatement object from the CeremonyStepManager instead.
     */
    public function enableMetadataStatementSupport(
        MetadataStatementRepository $metadataStatementRepository,
        StatusReportRepository $statusReportRepository,
        CertificateChainValidator $certificateChainValidator
    ): self {
        $this->ceremonyStepManagerFactory->enableMetadataStatementSupport(
            $metadataStatementRepository,
            $statusReportRepository,
            $certificateChainValidator
        );
        return $this;
    }

    /**
     * @param string[] $securedRelyingPartyId
     *
     * @see https://www.w3.org/TR/webauthn/#registering-a-new-credential
     */
    public function check(
        AuthenticatorAttestationResponse $authenticatorAttestationResponse,
        PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
        ServerRequestInterface|string $request,
        null|array $securedRelyingPartyId = null,
    ): PublicKeyCredentialSource {
        if ($request instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the method `check` of the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
        if ($securedRelyingPartyId !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                sprintf(
                    'Passing a list or secured relying party IDs to the method `check` of the class "%s" is deprecated since 4.8.0 and will be removed in 5.0.0. Please inject the list instead.',
                    self::class
                )
            );
        }
        $host = is_string($request) ? $request : $request->getUri()
            ->getHost();
        try {
            $this->logger->info('Checking the authenticator attestation response', [
                'authenticatorAttestationResponse' => $authenticatorAttestationResponse,
                'publicKeyCredentialCreationOptions' => $publicKeyCredentialCreationOptions,
                'host' => $host,
            ]);
            if ($this->ceremonyStepManager === null) {
                $this->ceremonyStepManager = $this->ceremonyStepManagerFactory->creationCeremony(
                    $securedRelyingPartyId
                );
            }

            $publicKeyCredentialSource = $this->createPublicKeyCredentialSource(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions
            );

            $this->ceremonyStepManager->process(
                $publicKeyCredentialSource,
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                $publicKeyCredentialCreationOptions->user->id,
                $host
            );

            $publicKeyCredentialSource->counter = $authenticatorAttestationResponse->attestationObject->authData->signCount;
            $publicKeyCredentialSource->backupEligible = $authenticatorAttestationResponse->attestationObject->authData->isBackupEligible();
            $publicKeyCredentialSource->backupStatus = $authenticatorAttestationResponse->attestationObject->authData->isBackedUp();
            $publicKeyCredentialSource->uvInitialized = $authenticatorAttestationResponse->attestationObject->authData->isUserVerified();

            $this->logger->info('The attestation is valid');
            $this->logger->debug('Public Key Credential Source', [
                'publicKeyCredentialSource' => $publicKeyCredentialSource,
            ]);
            $this->eventDispatcher->dispatch(
                $this->createAuthenticatorAttestationResponseValidationSucceededEvent(
                    $authenticatorAttestationResponse,
                    $publicKeyCredentialCreationOptions,
                    $host,
                    $publicKeyCredentialSource
                )
            );
            return $publicKeyCredentialSource;
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            $this->eventDispatcher->dispatch(
                $this->createAuthenticatorAttestationResponseValidationFailedEvent(
                    $authenticatorAttestationResponse,
                    $publicKeyCredentialCreationOptions,
                    $host,
                    $throwable
                )
            );
            throw $throwable;
        }
    }

    protected function createAuthenticatorAttestationResponseValidationSucceededEvent(
        AuthenticatorAttestationResponse $authenticatorAttestationResponse,
        PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
        ServerRequestInterface|string $host,
        PublicKeyCredentialSource $publicKeyCredentialSource
    ): AuthenticatorAttestationResponseValidationSucceededEvent {
        if ($host instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the method `createAuthenticatorAttestationResponseValidationSucceededEvent` of the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
        return new AuthenticatorAttestationResponseValidationSucceededEvent(
            $authenticatorAttestationResponse,
            $publicKeyCredentialCreationOptions,
            $host,
            $publicKeyCredentialSource
        );
    }

    protected function createAuthenticatorAttestationResponseValidationFailedEvent(
        AuthenticatorAttestationResponse $authenticatorAttestationResponse,
        PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
        ServerRequestInterface|string $host,
        Throwable $throwable
    ): AuthenticatorAttestationResponseValidationFailedEvent {
        if ($host instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the method `createAuthenticatorAttestationResponseValidationFailedEvent` of the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
        return new AuthenticatorAttestationResponseValidationFailedEvent(
            $authenticatorAttestationResponse,
            $publicKeyCredentialCreationOptions,
            $host,
            $throwable
        );
    }

    private function createPublicKeyCredentialSource(
        AuthenticatorAttestationResponse $authenticatorAttestationResponse,
        PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
    ): PublicKeyCredentialSource {
        $attestationObject = $authenticatorAttestationResponse->attestationObject;
        $attestedCredentialData = $attestationObject->authData->attestedCredentialData;
        $attestedCredentialData !== null || throw AuthenticatorResponseVerificationException::create(
            'Not attested credential data'
        );
        $credentialId = $attestedCredentialData->credentialId;
        $credentialPublicKey = $attestedCredentialData->credentialPublicKey;
        $credentialPublicKey !== null || throw AuthenticatorResponseVerificationException::create(
            'Not credential public key available in the attested credential data'
        );
        $userHandle = $publicKeyCredentialCreationOptions->user->id;
        $transports = $authenticatorAttestationResponse->transports;

        return PublicKeyCredentialSource::create(
            $credentialId,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $transports,
            $attestationObject->attStmt
                ->type,
            $attestationObject->attStmt
                ->trustPath,
            $attestedCredentialData->aaguid,
            $credentialPublicKey,
            $userHandle,
            $attestationObject->authData
                ->signCount,
        );
    }
}
