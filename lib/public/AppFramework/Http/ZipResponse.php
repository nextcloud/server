<?php
declare(strict_types=1);

namespace OCP\AppFramework\Http;

use OC\Streamer;

class ZipResponse extends Response implements ICallbackResponse {
	/** @var resource[] */
	private $resources;

	/** @var string */
	private $name;

	public function __construct(string $name = 'output') {
		$this->name = $name;
	}

	public function addResource($r, string $internalName, int $size, int $time = -1) {
		if (!\is_resource($r)) {
			return;
		}

		$this->resources[] = [
			'resource' => $r,
			'internalName' => $internalName,
			'size' => $size,
			'time' => $time,
		];
	}

	public function callback (IOutput $output) {
		$zip = new Streamer();
		$zip->sendHeaders($this->name);

		foreach ($this->resources as $resource) {
			$zip->addFileFromStream($resource['resource'], $resource['internalName'], $resource['size'], $resource['time']);
		}

		$zip->finalize();
	}

}
