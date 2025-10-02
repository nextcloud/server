<?php

namespace ownCloud\TarStreamer;

use ownCloud\TarStreamer\TarHeader;

class TarStreamer {
	public const REGTYPE = 0;
	public const DIRTYPE = 5;
	public const XHDTYPE = 'x';
	public const LONGNAMETYPE = 'L';

	/**
	 * Process in 1 MB chunks
	 */
	protected $blockSize = 1048576;
	protected $outStream;
	protected $needHeaders = false;
	protected $longNameHeaderType;

	/**
	 * Create a new TarStreamer object.
	 *
	 * @param array $options
	 */
	public function __construct($options = []) {
		if (isset($options['outstream'])) {
			$this->outStream = $options['outstream'];
		} else {
			$this->outStream = fopen('php://output', 'w');
			// turn off output buffering
			while (ob_get_level() > 0) {
				ob_end_flush();
			}
		}
		if (isset($options['longnames'])) {
			$this->longNameHeaderType = $options['longnames'];
		} else {
			$this->longNameHeaderType = self::LONGNAMETYPE;
		}
	}

	/**
	 * Send appropriate http headers before streaming the tar file and disable output buffering.
	 * This method, if used, has to be called before adding anything to the tar file.
	 *
	 * @param string $archiveName Filename of archive to be created (optional, default 'archive.tar')
	 * @param string $contentType Content mime type to be set (optional, default 'application/x-tar')
	 * @throws \Exception
	 */
	public function sendHeaders($archiveName = 'archive.tar', $contentType = 'application/x-tar') {
		$encodedArchiveName = rawurlencode($archiveName);
		if (headers_sent($headerFile, $headerLine)) {
			throw new \Exception("Unable to send file $encodedArchiveName. HTML Headers have already been sent from $headerFile in line $headerLine");
		}
		$buffer = ob_get_contents();
		if (!empty($buffer)) {
			throw new \Exception("Unable to send file $encodedArchiveName. Output buffer already contains text (typically warnings or errors).");
		}

		$headers = [
			'Pragma' => 'public',
			'Last-Modified' => gmdate('D, d M Y H:i:s T'),
			'Expires' => '0',
			'Accept-Ranges' => 'bytes',
			'Connection' => 'Keep-Alive',
			'Content-Type' => $contentType,
			'Cache-Control' => 'public, must-revalidate',
			'Content-Transfer-Encoding' => 'binary',
		];

		// Use UTF-8 filenames when not using Internet Explorer
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') > 0) {
			header('Content-Disposition: attachment; filename="' . rawurlencode($archiveName) . '"');
		} else {
			header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($archiveName)
					. '; filename="' . rawurlencode($archiveName) . '"');
		}

		foreach ($headers as $key => $value) {
			header("$key: $value");
		}
	}

	/**
	 * Add a file to the archive at the specified location and file name.
	 *
	 * @param resource $stream Stream to read data from
	 * @param string $filePath Filepath and name to be used in the archive.
	 * @param int $size
	 * @param array $options Optional, additional options
	 *                   Valid options are:
	 *                      * int timestamp: timestamp for the file (default: current time)
	 * @return bool $success
	 */
	public function addFileFromStream($stream, $filePath, $size, $options = []) {
		if (!\is_resource($stream) || get_resource_type($stream) != 'stream') {
			return false;
		}

		$this->initFileStreamTransfer($filePath, self::REGTYPE, $size, $options);

		// send file blocks
		while ($data = fread($stream, $this->blockSize)) {
			// send data
			$this->streamFilePart($data);
		}

		// complete the file stream
		$this->completeFileStream($size);

		return true;
	}

	/**
	 * Explicitly adds a directory to the tar (necessary for empty directories)
	 *
	 * @param  string $name Name (path) of the directory
	 * @param  array  $opt  Additional options to set
	 *                   Valid options are:
	 *                      * int timestamp: timestamp for the file (default: current time)
	 * @return void
	 */
	public function addEmptyDir($name, $opt = []) {
		$opt['type'] = self::DIRTYPE;

		// send header
		$this->initFileStreamTransfer($name, self::DIRTYPE, 0, $opt);

		// complete the file stream
		$this->completeFileStream(0);
	}

	/**
	 * Close the archive.
	 * A closed archive can no longer have new files added to it. After
	 * closing, the file is completely written to the output stream.
	 * @return bool $success */
	public function finalize() {
		// tar requires the end of the file have two 512 byte null blocks
		$this->send(pack('a1024', ''));

		// flush the data to the output
		fflush($this->outStream);
		return true;
	}

	/**
	 * Initialize a file stream
	 *
	 * @param string $name file path or just name
	 * @param int|string $type type of the item
	 * @param int $size size in bytes of the file
	 * @param array $opt array  (optional)
	 *                   Valid options are:
	 *                      * int timestamp: timestamp for the file (default: current time)
	 */
	protected function initFileStreamTransfer($name, $type, $size, $opt = []) {
		$dirName = (\dirname($name) == '.') ? '' : \dirname($name);
		$fileName = ($type == self::DIRTYPE) ? basename($name) . '/' : basename($name);

		// handle long file names
		if (\strlen($fileName) > 99 || \strlen($dirName) > 154) {
			$this->writeLongName($fileName, $dirName);
		}

		// process optional arguments
		$time = isset($opt['timestamp']) ? $opt['timestamp'] : time();

		$tarHeader = new TarHeader();
		$header = $tarHeader->setName($fileName)
				->setSize($size)
				->setMtime($time)
				->setTypeflag($type)
				->setPrefix($dirName)
				->getHeader()
		;
		// print header
		$this->send($header);
	}
	
	protected function writeLongName($fileName, $dirName) {
		$internalPath = trim($dirName . '/' . $fileName, '/');
		if ($this->longNameHeaderType === self::XHDTYPE) {
			// Long names via PAX
			$pax = $this->paxGenerate([ 'path' => $internalPath]);
			$paxSize = \strlen($pax);
			$this->initFileStreamTransfer('', self::XHDTYPE, $paxSize);
			$this->streamFilePart($pax);
			$this->completeFileStream($paxSize);
		} else {
			// long names via 'L' header
			$pathSize = \strlen($internalPath);
			$tarHeader = new TarHeader();
			$header = $tarHeader->setName('././@LongLink')
					->setSize($pathSize)
					->setTypeflag(self::LONGNAMETYPE)
					->getHeader()
			;
			$this->send($header);
			$this->streamFilePart($internalPath);
			$this->completeFileStream($pathSize);
		}
	}

	/**
	 * Stream the next part of the current file stream.
	 *
	 * @param string $data raw data to send
	 */
	protected function streamFilePart($data) {
		// send data
		$this->send($data);

		// flush the data to the output
		fflush($this->outStream);
	}

	/**
	 * Complete the current file stream
	 * @param $size
	 */
	protected function completeFileStream($size) {
		// ensure we pad the last block so that it is 512 bytes
		if (($mod = ($size % 512)) > 0) {
			$this->send(pack('a' . (512 - $mod), ''));
		}

		// flush the data to the output
		fflush($this->outStream);
	}

	/**
	 * Send string, sending HTTP headers if necessary.
	 *
	 * @param string $data data to send
	 */
	protected function send($data) {
		if ($this->needHeaders) {
			$this->sendHeaders();
		}
		$this->needHeaders = false;

		fwrite($this->outStream, $data);
	}

	/**
	 * Generate a PAX string
	 *
	 * @param array $fields key value mapping
	 * @return string PAX formatted string
	 * @link http://www.freebsd.org/cgi/man.cgi?query=tar&sektion=5&manpath=FreeBSD+8-current tar / PAX spec
	 */
	protected function paxGenerate($fields) {
		$lines = '';
		foreach ($fields as $name => $value) {
			// build the line and the size
			$line = ' ' . $name . '=' . $value . "\n";
			$size = \strlen((string) \strlen($line)) + \strlen($line);

			// add the line
			$lines .= $size . $line;
		}

		return $lines;
	}
}
