<?php

declare(strict_types=1);

namespace Webauthn;

use Cose\Algorithm\Manager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Counter\CounterChecker;
use Webauthn\Event\AuthenticatorAssertionResponseValidationFailedEvent;
use Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\CanLogData;
use Webauthn\TokenBinding\TokenBindingHandler;
use function is_string;

class AuthenticatorAssertionResponseValidator implements CanLogData, CanDispatchEvents
{
    private LoggerInterface $logger;

    private readonly CeremonyStepManagerFactory $ceremonyStepManagerFactory;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        private readonly null|PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository = null,
        private readonly null|TokenBindingHandler $tokenBindingHandler = null,
        null|ExtensionOutputCheckerHandler $extensionOutputCheckerHandler = null,
        null|Manager $algorithmManager = null,
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
        if ($algorithmManager !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$algorithmManager" is deprecated since 4.8.0 and will be removed in 5.0.0. Please set "null" instead and inject a CheckSignature object into the CeremonyStepManager.'
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
                'The parameter "$ceremonyStepManager" will mandatory in 5.0.0. Please set a CeremonyStepManager object instead and set null for $algorithmManager and $extensionOutputCheckerHandler.'
            );
        }
        $this->logger = new NullLogger();

        $this->ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        if ($extensionOutputCheckerHandler !== null) {
            $this->ceremonyStepManagerFactory->setExtensionOutputCheckerHandler($extensionOutputCheckerHandler);
        }
        if ($algorithmManager !== null) {
            $this->ceremonyStepManagerFactory->setAlgorithmManager($algorithmManager);
        }
    }

    public static function create(
        null|PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository = null,
        null|TokenBindingHandler $tokenBindingHandler = null,
        null|ExtensionOutputCheckerHandler $extensionOutputCheckerHandler = null,
        null|Manager $algorithmManager = null,
        null|EventDispatcherInterface $eventDispatcher = null,
        null|CeremonyStepManager $ceremonyStepManager = null
    ): self {
        return new self(
            $publicKeyCredentialSourceRepository,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler,
            $algorithmManager,
            $eventDispatcher,
            $ceremonyStepManager
        );
    }

    /**
     * @param string[] $securedRelyingPartyId
     *
     * @see https://www.w3.org/TR/webauthn/#verifying-assertion
     */
    public function check(
        string|PublicKeyCredentialSource $credentialId,
        AuthenticatorAssertionResponse $authenticatorAssertionResponse,
        PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        ServerRequestInterface|string $request,
        ?string $userHandle,
        null|array $securedRelyingPartyId = null
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
        if (is_string($credentialId)) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.6.0',
                sprintf(
                    'Passing a string as first to the method `check` of the class "%s" is deprecated since 4.6.0. Please inject a %s object instead.',
                    self::class,
                    PublicKeyCredentialSource::class
                )
            );
        }
        if ($securedRelyingPartyId !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                sprintf(
                    'Passing a list or secured relying party IDs to the method `check` of the class "%s" is deprecated since 4.8.0 and will be removed in 5.0.0. Please inject a CheckOrigin into the CeremonyStepManager instead.',
                    self::class
                )
            );
        }

        if ($credentialId instanceof PublicKeyCredentialSource) {
            $publicKeyCredentialSource = $credentialId;
        } else {
            $this->publicKeyCredentialSourceRepository instanceof PublicKeyCredentialSourceRepository || throw AuthenticatorResponseVerificationException::create(
                'Please pass the Public Key Credential Source to the method "check".'
            );
            $publicKeyCredentialSource = $this->publicKeyCredentialSourceRepository->findOneByCredentialId(
                $credentialId
            );
        }
        $publicKeyCredentialSource !== null || throw AuthenticatorResponseVerificationException::create(
            'The credential ID is invalid.'
        );
        $host = is_string($request) ? $request : $request->getUri()
            ->getHost();

        if ($this->ceremonyStepManager === null) {
            $this->ceremonyStepManager = $this->ceremonyStepManagerFactory->requestCeremony($securedRelyingPartyId);
        }

        try {
            $this->logger->info('Checking the authenticator assertion response', [
                'credentialId' => $credentialId,
                'publicKeyCredentialSource' => $publicKeyCredentialSource,
                'authenticatorAssertionResponse' => $authenticatorAssertionResponse,
                'publicKeyCredentialRequestOptions' => $publicKeyCredentialRequestOptions,
                'host' => $host,
                'userHandle' => $userHandle,
            ]);

            $this->ceremonyStepManager->process(
                $publicKeyCredentialSource,
                $authenticatorAssertionResponse,
                $publicKeyCredentialRequestOptions,
                $userHandle,
                $host
            );

            $publicKeyCredentialSource->counter = $authenticatorAssertionResponse->authenticatorData->signCount; //26.1.
            $publicKeyCredentialSource->backupEligible = $authenticatorAssertionResponse->authenticatorData->isBackupEligible(); //26.2.
            $publicKeyCredentialSource->backupStatus = $authenticatorAssertionResponse->authenticatorData->isBackedUp(); //26.2.
            if ($publicKeyCredentialSource->uvInitialized === false) {
                $publicKeyCredentialSource->uvInitialized = $authenticatorAssertionResponse->authenticatorData->isUserVerified(); //26.3.
            }
            /*
             * 26.3.
             * OPTIONALLY, if response.attestationObject is present, update credentialRecord.attestationObject to the value of response.attestationObject and update credentialRecord.attestationClientDataJSON to the value of response.clientDataJSON.
             */

            if (is_string(
                $credentialId
            ) && ($this->publicKeyCredentialSourceRepository instanceof PublicKeyCredentialSourceRepository)) {
                $this->publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
            }
            //All good. We can continue.
            $this->logger->info('The assertion is valid');
            $this->logger->debug('Public Key Credential Source', [
                'publicKeyCredentialSource' => $publicKeyCredentialSource,
            ]);
            $this->eventDispatcher->dispatch(
                $this->createAuthenticatorAssertionResponseValidationSucceededEvent(
                    null,
                    $authenticatorAssertionResponse,
                    $publicKeyCredentialRequestOptions,
                    $host,
                    $userHandle,
                    $publicKeyCredentialSource
                )
            );
            // 27.
            return $publicKeyCredentialSource;
        } catch (AuthenticatorResponseVerificationException $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            $this->eventDispatcher->dispatch(
                $this->createAuthenticatorAssertionResponseValidationFailedEvent(
                    $publicKeyCredentialSource,
                    $authenticatorAssertionResponse,
                    $publicKeyCredentialRequestOptions,
                    $host,
                    $userHandle,
                    $throwable
                )
            );
            throw $throwable;
        }
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
     * @deprecated since 4.8.0 and will be removed in 5.0.0. Please inject a CheckCounter object into a CeremonyStepManager instead.
     */
    public function setCounterChecker(CounterChecker $counterChecker): self
    {
        $this->ceremonyStepManagerFactory->setCounterChecker($counterChecker);
        return $this;
    }

    protected function createAuthenticatorAssertionResponseValidationSucceededEvent(
        null|string $credentialId,
        AuthenticatorAssertionResponse $authenticatorAssertionResponse,
        PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        ServerRequestInterface|string $host,
        ?string $userHandle,
        PublicKeyCredentialSource $publicKeyCredentialSource
    ): AuthenticatorAssertionResponseValidationSucceededEvent {
        if ($host instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the method `createAuthenticatorAssertionResponseValidationSucceededEvent` of the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
        return new AuthenticatorAssertionResponseValidationSucceededEvent(
            $credentialId,
            $authenticatorAssertionResponse,
            $publicKeyCredentialRequestOptions,
            $host,
            $userHandle,
            $publicKeyCredentialSource
        );
    }

    protected function createAuthenticatorAssertionResponseValidationFailedEvent(
        string|PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse $authenticatorAssertionResponse,
        PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        ServerRequestInterface|string $host,
        ?string $userHandle,
        Throwable $throwable
    ): AuthenticatorAssertionResponseValidationFailedEvent {
        if ($host instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the method `createAuthenticatorAssertionResponseValidationFailedEvent` of the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
        return new AuthenticatorAssertionResponseValidationFailedEvent(
            $publicKeyCredentialSource,
            $authenticatorAssertionResponse,
            $publicKeyCredentialRequestOptions,
            $host,
            $userHandle,
            $throwable
        );
    }
}
