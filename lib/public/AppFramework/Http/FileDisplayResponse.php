<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class FileDisplayResponse
 *
 * @since 11.0.0
 * @template S of int
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class FileDisplayResponse extends Response implements ICallbackResponse {
	/** @var File|ISimpleFile */
	private $file;

	/**
	 * FileDisplayResponse constructor.
	 *
	 * @param File|ISimpleFile $file
	 * @param S $statusCode
	 * @param H $headers
	 * @since 11.0.0
	 */
	public function __construct(File|ISimpleFile $file, int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->file = $file;
		$this->addHeader('Content-Disposition', 'inline; filename="' . rawurldecode($file->getName()) . '"');

		$this->setETag($file->getEtag());
		$lastModified = new \DateTime();
		$lastModified->setTimestamp($file->getMTime());
		$this->setLastModified($lastModified);
	}

	/**
	 * @param IOutput $output
	 * @since 11.0.0
	 */
	public function callback(IOutput $output) {
		if ($output->getHttpResponseCode() !== Http::STATUS_NOT_MODIFIED) {
			$output->setHeader('Content-Length: ' . $this->file->getSize());
			$output->setOutput($this->file->getContent());
		}
	}
}
