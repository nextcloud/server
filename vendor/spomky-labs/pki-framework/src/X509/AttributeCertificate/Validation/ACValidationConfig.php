<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate\Validation;

use DateTimeImmutable;
use SpomkyLabs\Pki\X509\Certificate\Extension\Target\Target;
use SpomkyLabs\Pki\X509\CertificationPath\CertificationPath;

/**
 * Provides configuration context for the attribute certificate validation.
 */
final class ACValidationConfig
{
    /**
     * Evaluation reference time.
     */
    private DateTimeImmutable $evalTime;

    /**
     * Permitted targets.
     *
     * @var Target[]
     */
    private array $targets;

    /**
     * @param CertificationPath $holderPath Certification path of the AC holder
     * @param CertificationPath $issuerPath Certification path of the AC issuer
     */
    private function __construct(
        private readonly CertificationPath $holderPath,
        private readonly CertificationPath $issuerPath
    ) {
        $this->evalTime = new DateTimeImmutable();
        $this->targets = [];
    }

    public static function create(CertificationPath $holderPath, CertificationPath $issuerPath): self
    {
        return new self($holderPath, $issuerPath);
    }

    /**
     * Get certification path of the AC's holder.
     */
    public function holderPath(): CertificationPath
    {
        return $this->holderPath;
    }

    /**
     * Get certification path of the AC's issuer.
     */
    public function issuerPath(): CertificationPath
    {
        return $this->issuerPath;
    }

    /**
     * Get self with given evaluation reference time.
     */
    public function withEvaluationTime(DateTimeImmutable $dt): self
    {
        $obj = clone $this;
        $obj->evalTime = $dt;
        return $obj;
    }

    /**
     * Get the evaluation reference time.
     */
    public function evaluationTime(): DateTimeImmutable
    {
        return $this->evalTime;
    }

    /**
     * Get self with permitted targets.
     */
    public function withTargets(Target ...$targets): self
    {
        $obj = clone $this;
        $obj->targets = $targets;
        return $obj;
    }

    /**
     * Get array of permitted targets.
     *
     * @return Target[]
     */
    public function targets(): array
    {
        return $this->targets;
    }
}
