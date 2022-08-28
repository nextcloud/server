<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license GNU AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

/**
 * Shared configuration between ConnectionTrait and ObjectTrait to ensure both to be in sync
 */
trait S3ConfigTrait {
	protected array $params;

	protected string $bucket;

	/** Maximum number of concurrent multipart uploads */
	protected int $concurrency;

	protected int $timeout;

	protected string $proxy;

	protected string $storageClass;

	/** @var int Part size in bytes (float is added for 32bit support) */
	protected int|float $uploadPartSize;

	/** @var int Limit on PUT in bytes (float is added for 32bit support) */
	private int|float $putSizeLimit;

	/** @var int Limit on COPY in bytes (float is added for 32bit support) */
	private int|float $copySizeLimit;

	private bool $useMultipartCopy = true;
}
