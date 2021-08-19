<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn;

use Assert\Assertion;
use Cose\Algorithm\Algorithm;
use Cose\Algorithm\ManagerFactory;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\Counter\CounterChecker;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Webauthn\TokenBinding\TokenBindingHandler;

class Server
{
    /**
     * @var int
     */
    public $timeout = 60000;

    /**
     * @var int
     */
    public $challengeSize = 32;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    private $rpEntity;

    /**
     * @var ManagerFactory
     */
    private $coseAlgorithmManagerFactory;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSourceRepository;

    /**
     * @var TokenBindingHandler
     */
    private $tokenBindingHandler;

    /**
     * @var ExtensionOutputCheckerHandler
     */
    private $extensionOutputCheckerHandler;

    /**
     * @var string[]
     */
    private $selectedAlgorithms;

    /**
     * @var MetadataStatementRepository|null
     */
    private $metadataStatementRepository;

    /**
     * @var ClientInterface|null
     */
    private $httpClient;

    /**
     * @var string|null
     */
    private $googleApiKey;

    /**
     * @var RequestFactoryInterface|null
     */
    private $requestFactory;

    /**
     * @var CounterChecker|null
     */
    private $counterChecker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $securedRelyingPartyId = [];

    public function __construct(PublicKeyCredentialRpEntity $relyingParty, PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository, ?MetadataStatementRepository $metadataStatementRepository = null)
    {
        if (null !== $metadataStatementRepository) {
            @trigger_error('The argument "metadataStatementRepository" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setMetadataStatementRepository".', E_USER_DEPRECATED);
        }
        $this->rpEntity = $relyingParty;
        $this->logger = new NullLogger();

        $this->coseAlgorithmManagerFactory = new ManagerFactory();
        $this->coseAlgorithmManagerFactory->add('RS1', new RSA\RS1());
        $this->coseAlgorithmManagerFactory->add('RS256', new RSA\RS256());
        $this->coseAlgorithmManagerFactory->add('RS384', new RSA\RS384());
        $this->coseAlgorithmManagerFactory->add('RS512', new RSA\RS512());
        $this->coseAlgorithmManagerFactory->add('PS256', new RSA\PS256());
        $this->coseAlgorithmManagerFactory->add('PS384', new RSA\PS384());
        $this->coseAlgorithmManagerFactory->add('PS512', new RSA\PS512());
        $this->coseAlgorithmManagerFactory->add('ES256', new ECDSA\ES256());
        $this->coseAlgorithmManagerFactory->add('ES256K', new ECDSA\ES256K());
        $this->coseAlgorithmManagerFactory->add('ES384', new ECDSA\ES384());
        $this->coseAlgorithmManagerFactory->add('ES512', new ECDSA\ES512());
        $this->coseAlgorithmManagerFactory->add('Ed25519', new EdDSA\Ed25519());

        $this->selectedAlgorithms = ['RS256', 'RS512', 'PS256', 'PS512', 'ES256', 'ES512', 'Ed25519'];
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;
        $this->tokenBindingHandler = new IgnoreTokenBindingHandler();
        $this->extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();
        $this->metadataStatementRepository = $metadataStatementRepository;
    }

    public function setMetadataStatementRepository(MetadataStatementRepository $metadataStatementRepository): self
    {
        $this->metadataStatementRepository = $metadataStatementRepository;

        return $this;
    }

    /**
     * @param string[] $selectedAlgorithms
     */
    public function setSelectedAlgorithms(array $selectedAlgorithms): self
    {
        $this->selectedAlgorithms = $selectedAlgorithms;

        return $this;
    }

    public function setTokenBindingHandler(TokenBindingHandler $tokenBindingHandler): self
    {
        $this->tokenBindingHandler = $tokenBindingHandler;

        return $this;
    }

    public function addAlgorithm(string $alias, Algorithm $algorithm): self
    {
        $this->coseAlgorithmManagerFactory->add($alias, $algorithm);
        $this->selectedAlgorithms[] = $alias;
        $this->selectedAlgorithms = array_unique($this->selectedAlgorithms);

        return $this;
    }

    public function setExtensionOutputCheckerHandler(ExtensionOutputCheckerHandler $extensionOutputCheckerHandler): self
    {
        $this->extensionOutputCheckerHandler = $extensionOutputCheckerHandler;

        return $this;
    }

    /**
     * @param string[] $securedRelyingPartyId
     */
    public function setSecuredRelyingPartyId(array $securedRelyingPartyId): self
    {
        Assertion::allString($securedRelyingPartyId, 'Invalid list. Shall be a list of strings');
        $this->securedRelyingPartyId = $securedRelyingPartyId;

        return $this;
    }

    /**
     * @param PublicKeyCredentialDescriptor[] $excludedPublicKeyDescriptors
     */
    public function generatePublicKeyCredentialCreationOptions(PublicKeyCredentialUserEntity $userEntity, ?string $attestationMode = null, array $excludedPublicKeyDescriptors = [], ?AuthenticatorSelectionCriteria $criteria = null, ?AuthenticationExtensionsClientInputs $extensions = null): PublicKeyCredentialCreationOptions
    {
        $coseAlgorithmManager = $this->coseAlgorithmManagerFactory->create($this->selectedAlgorithms);
        $publicKeyCredentialParametersList = [];
        foreach ($coseAlgorithmManager->all() as $algorithm) {
            $publicKeyCredentialParametersList[] = new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm::identifier()
            );
        }
        $criteria = $criteria ?? new AuthenticatorSelectionCriteria();
        $extensions = $extensions ?? new AuthenticationExtensionsClientInputs();
        $challenge = random_bytes($this->challengeSize);

        return PublicKeyCredentialCreationOptions
            ::create(
                $this->rpEntity,
                $userEntity,
                $challenge,
                $publicKeyCredentialParametersList
            )
                ->excludeCredentials($excludedPublicKeyDescriptors)
                ->setAuthenticatorSelection($criteria)
                ->setAttestation($attestationMode ?? PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE)
                ->setExtensions($extensions)
                ->setTimeout($this->timeout)
        ;
    }

    /**
     * @param PublicKeyCredentialDescriptor[] $allowedPublicKeyDescriptors
     */
    public function generatePublicKeyCredentialRequestOptions(?string $userVerification = null, array $allowedPublicKeyDescriptors = [], ?AuthenticationExtensionsClientInputs $extensions = null): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions
            ::create(random_bytes($this->challengeSize))
                ->setRpId($this->rpEntity->getId())
                ->setUserVerification($userVerification ?? PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED)
                ->allowCredentials($allowedPublicKeyDescriptors)
                ->setTimeout($this->timeout)
                ->setExtensions($extensions ?? new AuthenticationExtensionsClientInputs())
        ;
    }

    public function loadAndCheckAttestationResponse(string $data, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager();
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager)
            ->setLogger($this->logger)
        ;
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader)
            ->setLogger($this->logger)
        ;

        $publicKeyCredential = $publicKeyCredentialLoader->load($data);
        $authenticatorResponse = $publicKeyCredential->getResponse();
        Assertion::isInstanceOf($authenticatorResponse, AuthenticatorAttestationResponse::class, 'Not an authenticator attestation response');

        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->publicKeyCredentialSourceRepository,
            $this->tokenBindingHandler,
            $this->extensionOutputCheckerHandler,
            $this->metadataStatementRepository
        );
        $authenticatorAttestationResponseValidator->setLogger($this->logger);

        return $authenticatorAttestationResponseValidator->check($authenticatorResponse, $publicKeyCredentialCreationOptions, $serverRequest, $this->securedRelyingPartyId);
    }

    public function loadAndCheckAssertionResponse(string $data, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, ?PublicKeyCredentialUserEntity $userEntity, ServerRequestInterface $serverRequest): PublicKeyCredentialSource
    {
        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager();
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager)
            ->setLogger($this->logger)
        ;
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader)
            ->setLogger($this->logger)
        ;

        $publicKeyCredential = $publicKeyCredentialLoader->load($data);
        $authenticatorResponse = $publicKeyCredential->getResponse();
        Assertion::isInstanceOf($authenticatorResponse, AuthenticatorAssertionResponse::class, 'Not an authenticator assertion response');

        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $this->publicKeyCredentialSourceRepository,
            $this->tokenBindingHandler,
            $this->extensionOutputCheckerHandler,
            $this->coseAlgorithmManagerFactory->create($this->selectedAlgorithms),
            $this->counterChecker
        );
        $authenticatorAssertionResponseValidator->setLogger($this->logger);

        return $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $authenticatorResponse,
            $publicKeyCredentialRequestOptions,
            $serverRequest,
            null !== $userEntity ? $userEntity->getId() : null,
            $this->securedRelyingPartyId
        );
    }

    public function setCounterChecker(CounterChecker $counterChecker): self
    {
        $this->counterChecker = $counterChecker;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function enforceAndroidSafetyNetVerification(ClientInterface $client, string $apiKey, RequestFactoryInterface $requestFactory): self
    {
        $this->httpClient = $client;
        $this->googleApiKey = $apiKey;
        $this->requestFactory = $requestFactory;

        return $this;
    }

    private function getAttestationStatementSupportManager(): AttestationStatementSupportManager
    {
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport());
        if (class_exists(RS256::class) && class_exists(JWKFactory::class)) {
            $androidSafetyNetAttestationStatementSupport = new AndroidSafetyNetAttestationStatementSupport();
            if (null !== $this->httpClient && null !== $this->googleApiKey && null !== $this->requestFactory) {
                $androidSafetyNetAttestationStatementSupport
                    ->enableApiVerification($this->httpClient, $this->googleApiKey, $this->requestFactory)
                    ->setLeeway(2000)
                    ->setMaxAge(60000)
                ;
            }
            $attestationStatementSupportManager->add($androidSafetyNetAttestationStatementSupport);
        }
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport());
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());
        $coseAlgorithmManager = $this->coseAlgorithmManagerFactory->create($this->selectedAlgorithms);
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($coseAlgorithmManager));

        return $attestationStatementSupportManager;
    }
}
