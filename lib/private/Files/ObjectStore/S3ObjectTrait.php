<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Florent <florent@coppint.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use Aws\S3\Exception\S3MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Icewind\Streams\CallbackWrapper;
use OC\Files\Stream\SeekableHttpStream;
use OC\Files\Stream\HashWrapper;

trait S3ObjectTrait {
	/**
	 * Returns the connection
	 *
	 * @return S3Client connected client
	 * @throws \Exception if connection could not be made
	 */
	abstract protected function getConnection();

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function readObject($urn) {
		return SeekableHttpStream::open(function ($range) use ($urn) {
			$command = $this->getConnection()->getCommand('GetObject', [
				'Bucket' => $this->bucket,
				'Key' => $urn,
				'Range' => 'bytes=' . $range,
			]);
			$request = \Aws\serialize($command);
			$headers = [];
			foreach ($request->getHeaders() as $key => $values) {
				foreach ($values as $value) {
					$headers[] = "$key: $value";
				}
			}
			$opts = [
				'http' => [
					'protocol_version' => 1.1,
					'header' => $headers,
				],
			];

			$context = stream_context_create($opts);
			return fopen($request->getUri(), 'r', false, $context);
		});
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function writeObject($urn, $stream) {
		$count = 0;
		$countStream = CallbackWrapper::wrap($stream, function ($read) use (&$count) {
			$count += $read;
		});
		$tempFile = $this->copyFile($stream);
		$exifData = $this->extractExifData($tempFile,$urn);
		$this->checksum = $this->generateSha256($tempFile);
		$checksumLocal = $this->checksum;
		unlink($tempFile);
		$uploader = new MultipartUploader($this->getConnection(), $countStream, [
			'bucket' => $this->bucket,
			'key' => $urn,
			'part_size' => $this->uploadPartSize,
			'before_initiate' => function (\Aws\Command $command) use ($checksumLocal, $exifData) {
				if (empty($command['Metadata'])) {
					$command['Metadata'] = [];
				}
				$command['Metadata']['nextcloud-sha256'] = $checksumLocal;
				$command['Metadata']['nextcloud-exif'] = $exifData;
			}
		]);

		try {
			$uploader->upload();
		} catch (S3MultipartUploadException $e) {
			// This is an empty file so just touch it then
			if ($count === 0 && feof($countStream)) {
				$uploader = new ObjectUploader($this->getConnection(), $this->bucket, $urn, '');
				$uploader->upload();
			} else {
				throw $e;
			}
		}
	
		fclose($countStream);
	}
	
	private function copyFile($stream): string {
		$tempFile = tempnam('/tmp','nextcloud');
		$tempStream = fopen($tempFile,'w+');
		$count = stream_copy_to_stream($stream, $tempStream);
		fclose($tempStream);
		fseek($stream,0);
		return $tempFile;
	}

	private function generateSha256($tempFile): string {
		$tempStream = fopen($tempFile,'r');

		$obtainedHash = "";
		$callback = function ($hash) use (&$obtainedHash) {
			$obtainedHash = $hash;
		};

		$tempStream = HashWrapper::wrap($tempStream, "sha256", $callback);

		while (feof($tempStream) === false) {
			fread($tempStream, 200);
		}
		fclose($tempStream);
		return $obtainedHash;
	}
	
	private function extractExifData($tempFile,$fileName): string {
		$mimeType = \OC::$server->getMimeTypeDetector()->detectPath($fileName);
		if (in_array($mimeType, ['image/jpeg'])) {
			$tempStream = fopen($tempFile,'r');
			$metadata = exif_read_data($tempStream);
			fclose($tempStream);
			$metadata = [
				'DateTimeOriginal' => $metadata['DateTimeOriginal'],
				'GPSLatitude' => implode('-',$metadata['GPSLatitude']),
				'GPSLongitude' => implode('-',$metadata['GPSLongitude']),
				'GPSLatitudeRef' => $metadata['GPSLatitudeRef'],
				'GPSLongitudeRef' => $metadata['GPSLongitudeRef']
			];

			$result = json_encode($metadata);
			return $result;
		}
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function deleteObject($urn) {
		$this->getConnection()->deleteObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		]);
	}

	public function objectExists($urn) {
		return $this->getConnection()->doesObjectExist($this->bucket, $urn);
	}
}
