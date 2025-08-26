<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OC\Streamer;
use OCP\AppFramework\Http;
use OCP\IDateTimeZone;
use OCP\IRequest;

/**
 * Public library to send several files in one zip archive.
 *
 * @since 15.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
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

		$zip = new Streamer($this->request, $size, $files, \OCP\Server::get(IDateTimeZone::class));
		$zip->sendHeaders($this->name);

		foreach ($this->resources as $resource) {
			$zip->addFileFromStream($resource['resource'], $resource['internalName'], $resource['size'], $resource['time']);
		}

		$zip->finalize();
	}
}
