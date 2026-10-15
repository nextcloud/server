<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * @psalm-suppress MissingConstructor ORM based hydration
 */
#[Entity(name: 'oauth2_clients')]
final class Client {
	#[Id, Column(name: 'id', type: ColumnType::Integer)]
	public int $id;

	#[Column(name: 'name', type: ColumnType::String, length: 64)]
	public string $name;

	#[Column(name: 'redirect_uri', type: ColumnType::String, length: 2000)]
	public string $redirectUri;

	#[Column(name: 'client_identifier', type: ColumnType::String, length: 64)]
	public string $clientIdentifier;

	#[Column(name: 'secret', type: ColumnType::String, length: 512)]
	public string $secret;
}
