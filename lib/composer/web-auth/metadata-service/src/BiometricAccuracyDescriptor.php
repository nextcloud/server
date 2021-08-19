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

namespace Webauthn\MetadataService;

use Assert\Assertion;

class BiometricAccuracyDescriptor extends AbstractDescriptor
{
    /**
     * @var float|null
     */
    private $FAR;

    /**
     * @var float|null
     */
    private $FRR;

    /**
     * @var float|null
     */
    private $EER;

    /**
     * @var float|null
     */
    private $FAAR;

    /**
     * @var int|null
     */
    private $maxReferenceDataSets;

    public function __construct(?float $FAR, ?float $FRR, ?float $EER, ?float $FAAR, ?int $maxReferenceDataSets, ?int $maxRetries = null, ?int $blockSlowdown = null)
    {
        Assertion::greaterOrEqualThan($maxReferenceDataSets, 0, Utils::logicException('Invalid data. The value of "maxReferenceDataSets" must be a positive integer'));
        $this->FRR = $FRR;
        $this->FAR = $FAR;
        $this->EER = $EER;
        $this->FAAR = $FAAR;
        $this->maxReferenceDataSets = $maxReferenceDataSets;
        parent::__construct($maxRetries, $blockSlowdown);
    }

    public function getFAR(): ?float
    {
        return $this->FAR;
    }

    public function getFRR(): ?float
    {
        return $this->FRR;
    }

    public function getEER(): ?float
    {
        return $this->EER;
    }

    public function getFAAR(): ?float
    {
        return $this->FAAR;
    }

    public function getMaxReferenceDataSets(): ?int
    {
        return $this->maxReferenceDataSets;
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            $data['FAR'] ?? null,
            $data['FRR'] ?? null,
            $data['EER'] ?? null,
            $data['FAAR'] ?? null,
            $data['maxReferenceDataSets'] ?? null,
            $data['maxRetries'] ?? null,
            $data['blockSlowdown'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'FAR' => $this->FAR,
            'FRR' => $this->FRR,
            'EER' => $this->EER,
            'FAAR' => $this->FAAR,
            'maxReferenceDataSets' => $this->maxReferenceDataSets,
            'maxRetries' => $this->getMaxRetries(),
            'blockSlowdown' => $this->getBlockSlowdown(),
        ];

        return Utils::filterNullValues($data);
    }
}
