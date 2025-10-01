<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\CanLogData;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\CertificateTrustPath;
use function count;
use function in_array;

final class CheckMetadataStatement implements CeremonyStep, CanLogData
{
    private LoggerInterface $logger;

    private null|MetadataStatementRepository $metadataStatementRepository = null;

    private null|StatusReportRepository $statusReportRepository = null;

    private null|CertificateChainValidator $certificateChainValidator = null;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function enableMetadataStatementSupport(
        MetadataStatementRepository $metadataStatementRepository,
        StatusReportRepository $statusReportRepository,
        CertificateChainValidator $certificateChainValidator
    ): void {
        $this->metadataStatementRepository = $metadataStatementRepository;
        $this->statusReportRepository = $statusReportRepository;
        $this->certificateChainValidator = $certificateChainValidator;
    }

    public function enableCertificateChainValidator(CertificateChainValidator $certificateChainValidator): void
    {
        $this->certificateChainValidator = $certificateChainValidator;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        if (
            ! $publicKeyCredentialOptions instanceof PublicKeyCredentialCreationOptions
            || ! $authenticatorResponse instanceof AuthenticatorAttestationResponse
        ) {
            return;
        }

        $attestationStatement = $authenticatorResponse->attestationObject->attStmt;
        $attestedCredentialData = $authenticatorResponse->attestationObject->authData
            ->attestedCredentialData;
        $attestedCredentialData !== null || throw AuthenticatorResponseVerificationException::create(
            'No attested credential data found'
        );
        $aaguid = $attestedCredentialData->aaguid
            ->__toString();
        if ($publicKeyCredentialOptions->attestation === null || $publicKeyCredentialOptions->attestation === PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE) {
            $this->logger->debug('No attestation is asked.');
            if ($aaguid === '00000000-0000-0000-0000-000000000000' && in_array(
                $attestationStatement->type,
                [AttestationStatement::TYPE_NONE, AttestationStatement::TYPE_SELF],
                true
            )) {
                $this->logger->debug('The Attestation Statement is anonymous.');
                $this->checkCertificateChain($attestationStatement, null);
                return;
            }
            return;
        }
        // If no Attestation Statement has been returned or if null AAGUID (=00000000-0000-0000-0000-000000000000)
        // => nothing to check
        if ($attestationStatement->type === AttestationStatement::TYPE_NONE) {
            $this->logger->debug('No attestation returned.');
            //No attestation is returned. We shall ensure that the AAGUID is a null one.
            //if ($aaguid !== '00000000-0000-0000-0000-000000000000') {
            //$this->logger->debug('Anonymization required. AAGUID and Attestation Statement changed.', [
            //    'aaguid' => $aaguid,
            //    'AttestationStatement' => $attestationStatement,
            //]);
            //$attestedCredentialData->aaguid = Uuid::fromString('00000000-0000-0000-0000-000000000000');
            //    return;
            //}
            return;
        }
        if ($aaguid === '00000000-0000-0000-0000-000000000000') {
            //No need to continue if the AAGUID is null.
            // This could be the case e.g. with AnonCA type
            return;
        }
        //The MDS Repository is mandatory here
        $this->metadataStatementRepository !== null || throw AuthenticatorResponseVerificationException::create(
            'The Metadata Statement Repository is mandatory when requesting attestation objects.'
        );
        $metadataStatement = $this->metadataStatementRepository->findOneByAAGUID($aaguid);
        // At this point, the Metadata Statement is mandatory
        $metadataStatement !== null || throw AuthenticatorResponseVerificationException::create(
            sprintf('The Metadata Statement for the AAGUID "%s" is missing', $aaguid)
        );
        // We check the last status report
        $this->checkStatusReport($aaguid);
        // We check the certificate chain (if any)
        $this->checkCertificateChain($attestationStatement, $metadataStatement);
        // Check Attestation Type is allowed
        if (count($metadataStatement->attestationTypes) !== 0) {
            $type = $this->getAttestationType($attestationStatement);
            in_array(
                $type,
                $metadataStatement->attestationTypes,
                true
            ) || throw AuthenticatorResponseVerificationException::create(
                sprintf(
                    'Invalid attestation statement. The attestation type "%s" is not allowed for this authenticator.',
                    $type
                )
            );
        }
    }

    private function getAttestationType(AttestationStatement $attestationStatement): string
    {
        return match ($attestationStatement->type) {
            AttestationStatement::TYPE_BASIC => MetadataStatement::ATTESTATION_BASIC_FULL,
            AttestationStatement::TYPE_SELF => MetadataStatement::ATTESTATION_BASIC_SURROGATE,
            AttestationStatement::TYPE_ATTCA => MetadataStatement::ATTESTATION_ATTCA,
            AttestationStatement::TYPE_ECDAA => MetadataStatement::ATTESTATION_ECDAA,
            AttestationStatement::TYPE_ANONCA => MetadataStatement::ATTESTATION_ANONCA,
            default => throw AuthenticatorResponseVerificationException::create('Invalid attestation type'),
        };
    }

    private function checkStatusReport(string $aaguid): void
    {
        $statusReports = $this->statusReportRepository === null ? [] : $this->statusReportRepository->findStatusReportsByAAGUID(
            $aaguid
        );
        if (count($statusReports) !== 0) {
            $lastStatusReport = end($statusReports);
            if ($lastStatusReport->isCompromised()) {
                throw AuthenticatorResponseVerificationException::create(
                    'The authenticator is compromised and cannot be used'
                );
            }
        }
    }

    private function checkCertificateChain(
        AttestationStatement $attestationStatement,
        ?MetadataStatement $metadataStatement
    ): void {
        $trustPath = $attestationStatement->trustPath;
        if (! $trustPath instanceof CertificateTrustPath) {
            return;
        }
        $authenticatorCertificates = $trustPath->certificates;
        if ($metadataStatement === null) {
            $this->certificateChainValidator?->check($authenticatorCertificates, []);
            return;
        }
        $trustedCertificates = CertificateToolbox::fixPEMStructures(
            $metadataStatement->attestationRootCertificates
        );
        $this->certificateChainValidator?->check($authenticatorCertificates, $trustedCertificates);
    }
}
