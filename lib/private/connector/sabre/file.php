<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class OC_Connector_Sabre_File extends OC_Connector_Sabre_Node implements Sabre_DAV_IFile {

	/**
	 * Updates the data
	 *
	 * The data argument is a readable stream resource.
	 *
	 * After a successful put operation, you may choose to return an ETag. The
	 * etag must always be surrounded by double-quotes. These quotes must
	 * appear in the actual string you're returning.
	 *
	 * Clients may use the ETag from a PUT request to later on make sure that
	 * when they update the file, the contents haven't changed in the mean
	 * time.
	 *
	 * If you don't plan to store the file byte-by-byte, and you return a
	 * different object on a subsequent GET you are strongly recommended to not
	 * return an ETag, and just return null.
	 *
	 * @param resource $data
	 * @throws Sabre_DAV_Exception_Forbidden
	 * @throws OC_Connector_Sabre_Exception_UnsupportedMediaType
	 * @throws Sabre_DAV_Exception_BadRequest
	 * @throws Sabre_DAV_Exception
	 * @throws OC_Connector_Sabre_Exception_EntityTooLarge
	 * @throws Sabre_DAV_Exception_ServiceUnavailable
	 * @return string|null
	 */
	public function put($data) {
		if ($this->info && $this->fileView->file_exists($this->path) &&
			!$this->info->isUpdateable()) {
			throw new \Sabre_DAV_Exception_Forbidden();
		}

		// throw an exception if encryption was disabled but the files are still encrypted
		if (\OC_Util::encryptedFiles()) {
			throw new \Sabre_DAV_Exception_ServiceUnavailable();
		}

		$fileName = basename($this->path);
		if (!\OCP\Util::isValidFileName($fileName)) {
			throw new \Sabre_DAV_Exception_BadRequest();
		}

		// chunked handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			return $this->createFileChunked($data);
		}

		// mark file as partial while uploading (ignored by the scanner)
		$partpath = $this->path . '.ocTransferId' . rand() . '.part';

		// if file is located in /Shared we write the part file to the users
		// root folder because we can't create new files in /shared
		// we extend the name with a random number to avoid overwriting a existing file
		if (dirname($partpath) === 'Shared') {
			$partpath = pathinfo($partpath, PATHINFO_FILENAME) . rand() . '.part';
		}

		try {
			$putOkay = $this->fileView->file_put_contents($partpath, $data);
			if ($putOkay === false) {
				\OC_Log::write('webdav', '\OC\Files\Filesystem::file_put_contents() failed', \OC_Log::ERROR);
				$this->fileView->unlink($partpath);
				// because we have no clue about the cause we can only throw back a 500/Internal Server Error
				throw new Sabre_DAV_Exception('Could not write file contents');
			}
		} catch (\OCP\Files\NotPermittedException $e) {
			// a more general case - due to whatever reason the content could not be written
			throw new Sabre_DAV_Exception_Forbidden($e->getMessage());

		} catch (\OCP\Files\EntityTooLargeException $e) {
			// the file is too big to be stored
			throw new OC_Connector_Sabre_Exception_EntityTooLarge($e->getMessage());

		} catch (\OCP\Files\InvalidContentException $e) {
			// the file content is not permitted
			throw new OC_Connector_Sabre_Exception_UnsupportedMediaType($e->getMessage());

		} catch (\OCP\Files\InvalidPathException $e) {
			// the path for the file was not valid
			// TODO: find proper http status code for this case
			throw new Sabre_DAV_Exception_Forbidden($e->getMessage());
		}

		// rename to correct path
		$renameOkay = $this->fileView->rename($partpath, $this->path);
		$fileExists = $this->fileView->file_exists($this->path);
		if ($renameOkay === false || $fileExists === false) {
			\OC_Log::write('webdav', '\OC\Files\Filesystem::rename() failed', \OC_Log::ERROR);
			$this->fileView->unlink($partpath);
			throw new Sabre_DAV_Exception('Could not rename part file to final file');
		}

		// allow sync clients to send the mtime along in a header
		$mtime = OC_Request::hasModificationTime();
		if ($mtime !== false) {
			if($this->fileView->touch($this->path, $mtime)) {
				header('X-OC-MTime: accepted');
			}
		}
		$this->refreshInfo();

		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Returns the data
	 *
	 * @return string | resource
	 */
	public function get() {

		//throw exception if encryption is disabled but files are still encrypted
		if (\OC_Util::encryptedFiles()) {
			throw new \Sabre_DAV_Exception_ServiceUnavailable();
		} else {
			return $this->fileView->fopen($this->path, 'rb');
		}

	}

	/**
	 * Delete the current file
	 *
	 * @return void
	 * @throws Sabre_DAV_Exception_Forbidden
	 */
	public function delete() {
		if ($this->path === 'Shared') {
			throw new \Sabre_DAV_Exception_Forbidden();
		}

		if (!$this->info->isDeletable()) {
			throw new \Sabre_DAV_Exception_Forbidden();
		}
		$this->fileView->unlink($this->path);

		// remove properties
		$this->removeProperties();

	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->info->getSize();
	}

	/**
	 * Returns the ETag for a file
	 *
	 * An ETag is a unique identifier representing the current version of the
	 * file. If the file changes, the ETag MUST change.  The ETag is an
	 * arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined
	 *
	 * @return mixed
	 */
	public function getETag() {
		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {
		$mimeType = $this->info->getMimetype();

		return \OC_Helper::getSecureMimeType($mimeType);
	}

	/**
	 * @param resource $data
	 * @return null|string
	 */
	private function createFileChunked($data)
	{
		list($path, $name) = \Sabre_DAV_URLUtil::splitPath($this->path);

		$info = OC_FileChunking::decodeName($name);
		if (empty($info)) {
			throw new Sabre_DAV_Exception_NotImplemented();
		}
		$chunk_handler = new OC_FileChunking($info);
		$bytesWritten = $chunk_handler->store($info['index'], $data);

		//detect aborted upload
		if (isset ($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$expected = $_SERVER['CONTENT_LENGTH'];
				if ($bytesWritten != $expected) {
					$chunk_handler->remove($info['index']);
					throw new Sabre_DAV_Exception_BadRequest(
						'expected filesize ' . $expected . ' got ' . $bytesWritten);
				}
			}
		}

		if ($chunk_handler->isComplete()) {

			// we first assembly the target file as a part file
			$partFile = $path . '/' . $info['name'] . '.ocTransferId' . $info['transferid'] . '.part';
			$chunk_handler->file_assemble($partFile);

			// here is the final atomic rename
			$targetPath = $path . '/' . $info['name'];
			$renameOkay = $this->fileView->rename($partFile, $targetPath);
			$fileExists = $this->fileView->file_exists($targetPath);
			if ($renameOkay === false || $fileExists === false) {
				\OC_Log::write('webdav', '\OC\Files\Filesystem::rename() failed', \OC_Log::ERROR);
				// only delete if an error occurred and the target file was already created
				if ($fileExists) {
					$this->fileView->unlink($targetPath);
				}
				throw new Sabre_DAV_Exception('Could not rename part file assembled from chunks');
			}

			// allow sync clients to send the mtime along in a header
			$mtime = OC_Request::hasModificationTime();
			if ($mtime !== false) {
				if($this->fileView->touch($targetPath, $mtime)) {
					header('X-OC-MTime: accepted');
				}
			}

			$info = $this->fileView->getFileInfo($targetPath);
			return $info->getEtag();
		}

		return null;
	}

}
