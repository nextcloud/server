<?php

declare(strict_types=1);

namespace Webauthn;

/**
 * @deprecated
 * @infection-ignore-all
 */
interface PublicKeyCredentialSourceRepository
{
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource;

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array;

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void;
}
