<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\PublicPrivateKeyPairs\Exceptions;

/**
 * conflict between public and private key pair
 *
 * @since 30.0.0
 */
class KeyPairConflictException extends KeyPairException {
}
