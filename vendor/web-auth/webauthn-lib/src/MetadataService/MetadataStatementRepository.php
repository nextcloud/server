<?php

declare(strict_types=1);

namespace Webauthn\MetadataService;

use Webauthn\MetadataService\Statement\MetadataStatement;

interface MetadataStatementRepository
{
    public function findOneByAAGUID(string $aaguid): ?MetadataStatement;
}
