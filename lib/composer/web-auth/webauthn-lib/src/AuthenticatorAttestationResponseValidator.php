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
use function count;
use function in_array;
use InvalidArgumentException;
use function is_string;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use function Safe\parse_url;
use function Safe\sprintf;
use Throwable;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientOutputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\CertificateChainChecker\CertificateChainChecker;
use Webauthn\MetadataService\MetadataStatement;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReport;
use Webauthn\TokenBinding\TokenBindingHandler;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;

class AuthenticatorAttestationResponseValidator
{
    /**
     * @var AttestationStatementSupportManager
     */
    private $attestationStatementSupportManager;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSource;

    /**
     * @var TokenBindingHandler
     */
    private $tokenBindingHandler;

    /**
     * @var ExtensionOutputCheckerHandler
     */
    private $extensionOutputCheckerHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MetadataStatementRepository|null
     */
    private $metadataStatementRepository;

    /**
     * @var CertificateChainChecker|null
     */
    private $certificateChainChecker;

    public function __construct(AttestationStatementSupportManager $attestationStatementSupportManager, PublicKeyCredentialSourceRepository $publicKeyCredentialSource, TokenBindingHandler $tokenBindingHandler, ExtensionOutputCheckerHandler $extensionOutputCheckerHandler, ?MetadataStatementRepository $metadataStatementRepository = null, ?LoggerInterface $logger = null)
    {
        if (null !== $logger) {
            @trigger_error('The argument "logger" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setLogger".', E_USER_DEPRECATED);
        }
        if (null !== $metadataStatementRepository) {
            @trigger_error('The argument "metadataStatementRepository" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setMetadataStatementRepository".', E_USER_DEPRECATED);
        }
        $this->attestationStatementSupportManager = $attestationStatementSupportManager;
        $this->publicKeyCredentialSource = $publicKeyCredentialSource;
        $this->tokenBindingHandler = $tokenBindingHandler;
        $this->extensionOutputCheckerHandler = $extensionOutputCheckerHandler;
        $this->metadataStatementRepository = $metadataStatementRepository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setCertificateChainChecker(CertificateChainChecker $certificateChainChecker): self
    {
        $this->certificateChainChecker = $certificateChainChecker;

        return $this;
    }

    public function setMetadataStatementRepository(MetadataStatementRepository $metadataStatementRepository): self
    {
        $this->metadataStatementRepository = $metadataStatementRepository;

        return $this;
    }

    /**
     * @see https://www.w3.org/TR/webauthn/#registering-a-new-credential
     */
    public function check(AuthenticatorAttestationResponse $authenticatorAttestationResponse, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $request, array $securedRelyingPartyId = []): PublicKeyCredentialSource
    {
        try {
            $this->logger->info('Checking the authenticator attestation response', [
                'authenticatorAttestationResponse' => $authenticatorAttestationResponse,
                'publicKeyCredentialCreationOptions' => $publicKeyCredentialCreationOptions,
                'host' => $request->getUri()->getHost(),
            ]);
            /** @see 7.1.1 */
            //Nothing to do

            /** @see 7.1.2 */
            $C = $authenticatorAttestationResponse->getClientDataJSON();

            /* @see 7.1.3 */
            Assertion::eq('webauthn.create', $C->getType(), 'The client data type is not "webauthn.create".');

            /* @see 7.1.4 */
            Assertion::true(hash_equals($publicKeyCredentialCreationOptions->getChallenge(), $C->getChallenge()), 'Invalid challenge.');

            /** @see 7.1.5 */
            $rpId = $publicKeyCredentialCreationOptions->getRp()->getId() ?? $request->getUri()->getHost();
            $facetId = $this->getFacetId($rpId, $publicKeyCredentialCreationOptions->getExtensions(), $authenticatorAttestationResponse->getAttestationObject()->getAuthData()->getExtensions());

            $parsedRelyingPartyId = parse_url($C->getOrigin());
            Assertion::isArray($parsedRelyingPartyId, sprintf('The origin URI "%s" is not valid', $C->getOrigin()));
            Assertion::keyExists($parsedRelyingPartyId, 'scheme', 'Invalid origin rpId.');
            $clientDataRpId = $parsedRelyingPartyId['host'] ?? '';
            Assertion::notEmpty($clientDataRpId, 'Invalid origin rpId.');
            $rpIdLength = mb_strlen($facetId);
            Assertion::eq(mb_substr('.'.$clientDataRpId, -($rpIdLength + 1)), '.'.$facetId, 'rpId mismatch.');

            if (!in_array($facetId, $securedRelyingPartyId, true)) {
                $scheme = $parsedRelyingPartyId['scheme'] ?? '';
                Assertion::eq('https', $scheme, 'Invalid scheme. HTTPS required.');
            }

            /* @see 7.1.6 */
            if (null !== $C->getTokenBinding()) {
                $this->tokenBindingHandler->check($C->getTokenBinding(), $request);
            }

            /** @see 7.1.7 */
            $clientDataJSONHash = hash('sha256', $authenticatorAttestationResponse->getClientDataJSON()->getRawData(), true);

            /** @see 7.1.8 */
            $attestationObject = $authenticatorAttestationResponse->getAttestationObject();

            /** @see 7.1.9 */
            $rpIdHash = hash('sha256', $facetId, true);
            Assertion::true(hash_equals($rpIdHash, $attestationObject->getAuthData()->getRpIdHash()), 'rpId hash mismatch.');

            /* @see 7.1.10 */
            Assertion::true($attestationObject->getAuthData()->isUserPresent(), 'User was not present');
            /* @see 7.1.11 */
            if (AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED === $publicKeyCredentialCreationOptions->getAuthenticatorSelection()->getUserVerification()) {
                Assertion::true($attestationObject->getAuthData()->isUserVerified(), 'User authentication required.');
            }

            /* @see 7.1.12 */
            $extensionsClientOutputs = $attestationObject->getAuthData()->getExtensions();
            if (null !== $extensionsClientOutputs) {
                $this->extensionOutputCheckerHandler->check(
                    $publicKeyCredentialCreationOptions->getExtensions(),
                    $extensionsClientOutputs
                );
            }

            /* @see 7.1.13 */
            $this->checkMetadataStatement($publicKeyCredentialCreationOptions, $attestationObject);
            $fmt = $attestationObject->getAttStmt()->getFmt();
            Assertion::true($this->attestationStatementSupportManager->has($fmt), 'Unsupported attestation statement format.');

            /* @see 7.1.14 */
            $attestationStatementSupport = $this->attestationStatementSupportManager->get($fmt);
            Assertion::true($attestationStatementSupport->isValid($clientDataJSONHash, $attestationObject->getAttStmt(), $attestationObject->getAuthData()), 'Invalid attestation statement.');

            /* @see 7.1.15 */
            /* @see 7.1.16 */
            /* @see 7.1.17 */
            Assertion::true($attestationObject->getAuthData()->hasAttestedCredentialData(), 'There is no attested credential data.');
            $attestedCredentialData = $attestationObject->getAuthData()->getAttestedCredentialData();
            Assertion::notNull($attestedCredentialData, 'There is no attested credential data.');
            $credentialId = $attestedCredentialData->getCredentialId();
            Assertion::null($this->publicKeyCredentialSource->findOneByCredentialId($credentialId), 'The credential ID already exists.');

            /* @see 7.1.18 */
            /* @see 7.1.19 */
            $publicKeyCredentialSource = $this->createPublicKeyCredentialSource(
                $credentialId,
                $attestedCredentialData,
                $attestationObject,
                $publicKeyCredentialCreationOptions->getUser()->getId()
            );
            $this->logger->info('The attestation is valid');
            $this->logger->debug('Public Key Credential Source', ['publicKeyCredentialSource' => $publicKeyCredentialSource]);

            return $publicKeyCredentialSource;
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            throw $throwable;
        }
    }

    private function checkCertificateChain(AttestationStatement $attestationStatement, ?MetadataStatement $metadataStatement): void
    {
        $trustPath = $attestationStatement->getTrustPath();
        if (!$trustPath instanceof CertificateTrustPath) {
            return;
        }
        $authenticatorCertificates = $trustPath->getCertificates();

        if (null === $metadataStatement) {
            // @phpstan-ignore-next-line
            null === $this->certificateChainChecker ? CertificateToolbox::checkChain($authenticatorCertificates) : $this->certificateChainChecker->check($authenticatorCertificates, [], null);

            return;
        }

        $metadataStatementCertificates = $metadataStatement->getAttestationRootCertificates();
        $rootStatementCertificates = $metadataStatement->getRootCertificates();
        foreach ($metadataStatementCertificates as $key => $metadataStatementCertificate) {
            $metadataStatementCertificates[$key] = CertificateToolbox::fixPEMStructure($metadataStatementCertificate);
        }
        $trustedCertificates = array_merge(
            $metadataStatementCertificates,
            $rootStatementCertificates
        );

        // @phpstan-ignore-next-line
        null === $this->certificateChainChecker ? CertificateToolbox::checkChain($authenticatorCertificates, $trustedCertificates) : $this->certificateChainChecker->check($authenticatorCertificates, $trustedCertificates);
    }

    private function checkMetadataStatement(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, AttestationObject $attestationObject): void
    {
        $attestationStatement = $attestationObject->getAttStmt();
        $attestedCredentialData = $attestationObject->getAuthData()->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential data found');
        $aaguid = $attestedCredentialData->getAaguid()->toString();
        if (PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE === $publicKeyCredentialCreationOptions->getAttestation()) {
            $this->logger->debug('No attestation is asked.');
            //No attestation is asked. We shall ensure that the data is anonymous.
            if (
                '00000000-0000-0000-0000-000000000000' === $aaguid
                && (AttestationStatement::TYPE_NONE === $attestationStatement->getType() || AttestationStatement::TYPE_SELF === $attestationStatement->getType())) {
                $this->logger->debug('The Attestation Statement is anonymous.');
                $this->checkCertificateChain($attestationStatement, null);

                return;
            }
            $this->logger->debug('Anonymization required. AAGUID and Attestation Statement changed.', [
                'aaguid' => $aaguid,
                'AttestationStatement' => $attestationStatement,
            ]);
            $attestedCredentialData->setAaguid(
                Uuid::fromString('00000000-0000-0000-0000-000000000000')
            );
            $attestationObject->setAttStmt(AttestationStatement::createNone('none', [], new EmptyTrustPath()));

            return;
        }
        if (AttestationStatement::TYPE_NONE === $attestationStatement->getType()) {
            $this->logger->debug('No attestation returned.');
            //No attestation is returned. We shall ensure that the AAGUID is a null one.
            if ('00000000-0000-0000-0000-000000000000' !== $aaguid) {
                $this->logger->debug('Anonymization required. AAGUID and Attestation Statement changed.', [
                    'aaguid' => $aaguid,
                    'AttestationStatement' => $attestationStatement,
                ]);
                $attestedCredentialData->setAaguid(
                    Uuid::fromString('00000000-0000-0000-0000-000000000000')
                );

                return;
            }

            return;
        }

        //The MDS Repository is mandatory here
        Assertion::notNull($this->metadataStatementRepository, 'The Metadata Statement Repository is mandatory when requesting attestation objects.');
        $metadataStatement = $this->metadataStatementRepository->findOneByAAGUID($aaguid);

        // We check the last status report
        $this->checkStatusReport(null === $metadataStatement ? [] : $metadataStatement->getStatusReports());

        // We check the certificate chain (if any)
        $this->checkCertificateChain($attestationStatement, $metadataStatement);

        // If no Attestation Statement has been returned or if null AAGUID (=00000000-0000-0000-0000-000000000000)
        // => nothing to check
        if ('00000000-0000-0000-0000-000000000000' === $aaguid || AttestationStatement::TYPE_NONE === $attestationStatement->getType()) {
            return;
        }

        // At this point, the Metadata Statement is mandatory
        Assertion::notNull($metadataStatement, sprintf('The Metadata Statement for the AAGUID "%s" is missing', $aaguid));

        // Check Attestation Type is allowed
        if (0 !== count($metadataStatement->getAttestationTypes())) {
            $type = $this->getAttestationType($attestationStatement);
            Assertion::inArray($type, $metadataStatement->getAttestationTypes(), 'Invalid attestation statement. The attestation type is not allowed for this authenticator');
        }
    }

    /**
     * @param StatusReport[] $statusReports
     */
    private function checkStatusReport(array $statusReports): void
    {
        if (0 !== count($statusReports)) {
            $lastStatusReport = end($statusReports);
            if ($lastStatusReport->isCompromised()) {
                throw new LogicException('The authenticator is compromised and cannot be used');
            }
        }
    }

    private function createPublicKeyCredentialSource(string $credentialId, AttestedCredentialData $attestedCredentialData, AttestationObject $attestationObject, string $userHandle): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $credentialId,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            [],
            $attestationObject->getAttStmt()->getType(),
            $attestationObject->getAttStmt()->getTrustPath(),
            $attestedCredentialData->getAaguid(),
            $attestedCredentialData->getCredentialPublicKey(),
            $userHandle,
            $attestationObject->getAuthData()->getSignCount()
        );
    }

    private function getAttestationType(AttestationStatement $attestationStatement): int
    {
        switch ($attestationStatement->getType()) {
            case AttestationStatement::TYPE_BASIC:
                return MetadataStatement::ATTESTATION_BASIC_FULL;
            case AttestationStatement::TYPE_SELF:
                return MetadataStatement::ATTESTATION_BASIC_SURROGATE;
            case AttestationStatement::TYPE_ATTCA:
                return MetadataStatement::ATTESTATION_ATTCA;
            case AttestationStatement::TYPE_ECDAA:
                return MetadataStatement::ATTESTATION_ECDAA;
            default:
                throw new InvalidArgumentException('Invalid attestation type');
        }
    }

    private function getFacetId(string $rpId, AuthenticationExtensionsClientInputs $authenticationExtensionsClientInputs, ?AuthenticationExtensionsClientOutputs $authenticationExtensionsClientOutputs): string
    {
        if (null === $authenticationExtensionsClientOutputs || !$authenticationExtensionsClientInputs->has('appid') || !$authenticationExtensionsClientOutputs->has('appid')) {
            return $rpId;
        }
        $appId = $authenticationExtensionsClientInputs->get('appid')->value();
        $wasUsed = $authenticationExtensionsClientOutputs->get('appid')->value();
        if (!is_string($appId) || true !== $wasUsed) {
            return $rpId;
        }

        return $appId;
    }
}
