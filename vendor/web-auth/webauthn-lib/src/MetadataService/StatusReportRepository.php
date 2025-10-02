<?php

declare(strict_types=1);

namespace Webauthn\MetadataService;

use Webauthn\MetadataService\Statement\StatusReport;

interface StatusReportRepository
{
    /**
     * @return StatusReport[]
     */
    public function findStatusReportsByAAGUID(string $aaguid): array;
}
