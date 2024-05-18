<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
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
namespace OCP\AppFramework\Http;

use OC\Streamer;
use OCP\AppFramework\Http;
use OCP\IRequest;

/**
 * Public library to send several files in one zip archive.
 *
 * @since 15.0.0
 * @template S of int
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class ZipResponse extends Response implements ICallbackResponse {
	/** @var array{internalName: string, resource: resource, size: int, time: int}[] Files to be added to the zip response */
	private array $resources = [];
	/** @var string Filename that the zip file should have */
	private string $name;
	private IRequest $request;

	/**
	 * @param S $status
	 * @param H $headers
	 * @since 15.0.0
	 */
	public function __construct(IRequest $request, string $name = 'output', int $status = Http::STATUS_OK, array $headers = []) {
		parent::__construct($status, $headers);

		$this->name = $name;
		$this->request = $request;
	}

	/**
	 * @since 15.0.0
	 */
	public function addResource($r, string $internalName, int $size, int $time = -1) {
		if (!\is_resource($r)) {
			throw new \InvalidArgumentException('No resource provided');
		}

		$this->resources[] = [
			'resource' => $r,
			'internalName' => $internalName,
			'size' => $size,
			'time' => $time,
		];
	}

	/**
	 * @since 15.0.0
	 */
	public function callback(IOutput $output) {
		$size = 0;
		$files = count($this->resources);

		foreach ($this->resources as $resource) {
			$size += $resource['size'];
		}

		$zip = new Streamer($this->request, $size, $files);
		$zip->sendHeaders($this->name);

		foreach ($this->resources as $resource) {
			$zip->addFileFromStream($resource['resource'], $resource['internalName'], $resource['size'], $resource['time']);
		}

		$zip->finalize();
	}
}
