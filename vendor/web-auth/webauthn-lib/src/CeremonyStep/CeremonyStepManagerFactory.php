<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\Counter\CounterChecker;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;

final class CeremonyStepManagerFactory
{
    private CounterChecker $counterChecker;

    private Manager $algorithmManager;

    private null|MetadataStatementRepository $metadataStatementRepository = null;

    private null|StatusReportRepository $statusReportRepository = null;

    private null|CertificateChainValidator $certificateChainValidator = null;

    private null|TopOriginValidator $topOriginValidator = null;

    /**
     * @var string[]
     */
    private null|array $securedRelyingPartyId = null;

    private AttestationStatementSupportManager $attestationStatementSupportManager;

    private ExtensionOutputCheckerHandler $extensionOutputCheckerHandler;

    public function __construct()
    {
        $this->counterChecker = new ThrowExceptionIfInvalid();
        $this->algorithmManager = Manager::create()->add(ES256::create(), RS256::create());
        $this->attestationStatementSupportManager = new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
        ]);
        $this->extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();
    }

    public function setCounterChecker(CounterChecker $counterChecker): void
    {
        $this->counterChecker = $counterChecker;
    }

    /**
     * @param string[] $securedRelyingPartyId
     */
    public function setSecuredRelyingPartyId(array $securedRelyingPartyId): void
    {
        $this->securedRelyingPartyId = $securedRelyingPartyId;
    }

    public function setExtensionOutputCheckerHandler(ExtensionOutputCheckerHandler $extensionOutputCheckerHandler): void
    {
        $this->extensionOutputCheckerHandler = $extensionOutputCheckerHandler;
    }

    public function setAttestationStatementSupportManager(
        AttestationStatementSupportManager $attestationStatementSupportManager
    ): void {
        $this->attestationStatementSupportManager = $attestationStatementSupportManager;
    }

    public function setAlgorithmManager(Manager $algorithmManager): void
    {
        $this->algorithmManager = $algorithmManager;
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

    public function enableTopOriginValidator(TopOriginValidator $topOriginValidator): void
    {
        $this->topOriginValidator = $topOriginValidator;
    }

    /**
     * @param null|string[] $securedRelyingPartyId
     */
    public function creationCeremony(null|array $securedRelyingPartyId = null): CeremonyStepManager
    {
        $metadataStatementChecker = new CheckMetadataStatement();
        if ($this->certificateChainValidator !== null) {
            $metadataStatementChecker->enableCertificateChainValidator($this->certificateChainValidator);
        }
        if ($this->metadataStatementRepository !== null && $this->statusReportRepository !== null && $this->certificateChainValidator !== null) {
            $metadataStatementChecker->enableMetadataStatementSupport(
                $this->metadataStatementRepository,
                $this->statusReportRepository,
                $this->certificateChainValidator,
            );
        }

        /* @see https://www.w3.org/TR/webauthn-3/#sctn-registering-a-new-credential */
        return new CeremonyStepManager([
            new CheckClientDataCollectorType(),
            new CheckChallenge(),
            new CheckOrigin($this->securedRelyingPartyId ?? $securedRelyingPartyId ?? []),
            new CheckTopOrigin($this->topOriginValidator),
            new CheckRelyingPartyIdIdHash(),
            new CheckUserWasPresent(),
            new CheckUserVerification(),
            new CheckBackupBitsAreConsistent(),
            new CheckAlgorithm(),
            new CheckExtensions($this->extensionOutputCheckerHandler),
            new CheckAttestationFormatIsKnownAndValid($this->attestationStatementSupportManager),
            new CheckHasAttestedCredentialData(),
            $metadataStatementChecker,
            new CheckCredentialId(),
        ]);
    }

    /**
     * @param null|string[] $securedRelyingPartyId
     */
    public function requestCeremony(null|array $securedRelyingPartyId = null): CeremonyStepManager
    {
        /* @see https://www.w3.org/TR/webauthn-3/#sctn-verifying-assertion */
        return new CeremonyStepManager([
            new CheckAllowedCredentialList(),
            new CheckUserHandle(),
            new CheckClientDataCollectorType(),
            new CheckChallenge(),
            new CheckOrigin($this->securedRelyingPartyId ?? $securedRelyingPartyId ?? []),
            new CheckTopOrigin(null),
            new CheckRelyingPartyIdIdHash(),
            new CheckUserWasPresent(),
            new CheckUserVerification(),
            new CheckBackupBitsAreConsistent(),
            new CheckExtensions($this->extensionOutputCheckerHandler),
            new CheckSignature($this->algorithmManager),
            new CheckCounter($this->counterChecker),
        ]);
    }
}
