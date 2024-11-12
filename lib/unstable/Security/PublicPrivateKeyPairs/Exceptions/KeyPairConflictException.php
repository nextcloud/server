<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\PublicPrivateKeyPairs\Exceptions;

/**
 * conflict between public and private key pair
 *
 * @experimental 31.0.0
 * @since 31.0.0
 */
class KeyPairConflictException extends KeyPairException {
}
