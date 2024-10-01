<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Type;

use OCP\Files\IMimeTypeDetector;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Class Detection
 *
 * Mimetype detection
 *
 * @package OC\Files\Type
 */
class Detection implements IMimeTypeDetector {
	private const CUSTOM_MIMETYPEMAPPING = 'mimetypemapping.json';
	private const CUSTOM_MIMETYPEALIASES = 'mimetypealiases.json';

	protected array $mimetypes = [];
	protected array $secureMimeTypes = [];

	protected array $mimetypeIcons = [];
	/** @var array<string,string> */
	protected array $mimeTypeAlias = [];

	public function __construct(
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private string $customConfigDir,
		private string $defaultConfigDir,
	) {
	}

	/**
	 * Add an extension -> mimetype mapping
	 *
	 * $mimetype is the assumed correct mime type
	 * The optional $secureMimeType is an alternative to send to send
	 * to avoid potential XSS.
	 *
	 * @param string $extension
	 * @param string $mimetype
	 * @param string|null $secureMimeType
	 */
	public function registerType(string $extension,
		string $mimetype,
		?string $secureMimeType = null): void {
		$this->mimetypes[$extension] = [$mimetype, $secureMimeType];
		$this->secureMimeTypes[$mimetype] = $secureMimeType ?: $mimetype;
	}

	/**
	 * Add an array of extension -> mimetype mappings
	 *
	 * The mimetype value is in itself an array where the first index is
	 * the assumed correct mimetype and the second is either a secure alternative
	 * or null if the correct is considered secure.
	 *
	 * @param array $types
	 */
	public function registerTypeArray(array $types): void {
		$this->mimetypes = array_merge($this->mimetypes, $types);

		// Update the alternative mimetypes to avoid having to look them up each time.
		foreach ($this->mimetypes as $extension => $mimeType) {
			if (str_starts_with($extension, '_comment')) {
				continue;
			}
			$this->secureMimeTypes[$mimeType[0]] = $mimeType[1] ?? $mimeType[0];
			if (isset($mimeType[1])) {
				$this->secureMimeTypes[$mimeType[1]] = $mimeType[1];
			}
		}
	}

	private function loadCustomDefinitions(string $fileName, array $definitions): array {
		if (file_exists($this->customConfigDir . '/' . $fileName)) {
			$custom = json_decode(file_get_contents($this->customConfigDir . '/' . $fileName), true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$definitions = array_merge($definitions, $custom);
			} else {
				$this->logger->warning('Failed to parse ' . $fileName . ': ' . json_last_error_msg());
			}
		}
		return $definitions;
	}

	/**
	 * Add the mimetype aliases if they are not yet present
	 */
	private function loadAliases(): void {
		if (!empty($this->mimeTypeAlias)) {
			return;
		}

		$this->mimeTypeAlias = json_decode(file_get_contents($this->defaultConfigDir . '/mimetypealiases.dist.json'), true);
		$this->mimeTypeAlias = $this->loadCustomDefinitions(self::CUSTOM_MIMETYPEALIASES, $this->mimeTypeAlias);
	}

	/**
	 * @return array<string,string>
	 */
	public function getAllAliases(): array {
		$this->loadAliases();
		return $this->mimeTypeAlias;
	}

	public function getOnlyDefaultAliases(): array {
		$this->loadMappings();
		$this->mimeTypeAlias = json_decode(file_get_contents($this->defaultConfigDir . '/mimetypealiases.dist.json'), true);
		return $this->mimeTypeAlias;
	}

	/**
	 * Add mimetype mappings if they are not yet present
	 */
	private function loadMappings(): void {
		if (!empty($this->mimetypes)) {
			return;
		}

		$mimetypeMapping = json_decode(file_get_contents($this->defaultConfigDir . '/mimetypemapping.dist.json'), true);
		$mimetypeMapping = $this->loadCustomDefinitions(self::CUSTOM_MIMETYPEMAPPING, $mimetypeMapping);

		$this->registerTypeArray($mimetypeMapping);
	}

	/**
	 * @return array
	 */
	public function getAllMappings(): array {
		$this->loadMappings();
		return $this->mimetypes;
	}

	/**
	 * detect mimetype only based on filename, content of file is not used
	 *
	 * @param string $path
	 * @return string
	 */
	public function detectPath($path): string {
		$this->loadMappings();

		$fileName = basename($path);

		// remove leading dot on hidden files with a file extension
		$fileName = ltrim($fileName, '.');

		// note: leading dot doesn't qualify as extension
		if (strpos($fileName, '.') > 0) {
			// remove versioning extension: name.v1508946057 and transfer extension: name.ocTransferId2057600214.part
			$fileName = preg_replace('!((\.v\d+)|((\.ocTransferId\d+)?\.part))$!', '', $fileName);

			//try to guess the type by the file extension
			$extension = strrchr($fileName, '.');
			if ($extension !== false) {
				$extension = strtolower($extension);
				$extension = substr($extension, 1); //remove leading .
				return $this->mimetypes[$extension][0] ?? 'application/octet-stream';
			}
		}

		return 'application/octet-stream';
	}

	/**
	 * detect mimetype only based on the content of file
	 * @param string $path
	 * @return string
	 * @since 18.0.0
	 */
	public function detectContent(string $path): string {
		$this->loadMappings();

		if (@is_dir($path)) {
			// directories are easy
			return 'httpd/unix-directory';
		}

		if (function_exists('finfo_open')
			&& function_exists('finfo_file')
			&& $finfo = finfo_open(FILEINFO_MIME)) {
			$info = @finfo_file($finfo, $path);
			finfo_close($finfo);
			if ($info) {
				$info = strtolower($info);
				$mimeType = str_contains($info, ';') ? substr($info, 0, strpos($info, ';')) : $info;
				$mimeType = $this->getSecureMimeType($mimeType);
				if ($mimeType !== 'application/octet-stream') {
					return $mimeType;
				}
			}
		}

		if (str_starts_with($path, 'file://')) {
			// Is the file wrapped in a stream?
			return 'application/octet-stream';
		}

		if (function_exists('mime_content_type')) {
			// use mime magic extension if available
			$mimeType = mime_content_type($path);
			if ($mimeType !== false) {
				$mimeType = $this->getSecureMimeType($mimeType);
				if ($mimeType !== 'application/octet-stream') {
					return $mimeType;
				}
			}
		}

		if (\OC_Helper::canExecute('file')) {
			// it looks like we have a 'file' command,
			// lets see if it does have mime support
			$path = escapeshellarg($path);
			$fp = popen("test -f $path && file -b --mime-type $path", 'r');
			$mimeType = fgets($fp);
			pclose($fp);

			if ($mimeType !== false) {
				//trim the newline
				$mimeType = trim($mimeType);
				$mimeType = $this->getSecureMimeType($mimeType);
				if ($mimeType !== 'application/octet-stream') {
					return $mimeType;
				}
			}
		}
		return 'application/octet-stream';
	}

	/**
	 * detect mimetype based on both filename and content
	 *
	 * @param string $path
	 * @return string
	 */
	public function detect($path): string {
		$mimeType = $this->detectPath($path);

		if ($mimeType !== 'application/octet-stream') {
			return $mimeType;
		}

		return $this->detectContent($path);
	}

	/**
	 * detect mimetype based on the content of a string
	 *
	 * @param string $data
	 * @return string
	 */
	public function detectString($data): string {
		if (function_exists('finfo_open') && function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$info = finfo_buffer($finfo, $data);
			return str_contains($info, ';') ? substr($info, 0, strpos($info, ';')) : $info;
		}

		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile();
		$fh = fopen($tmpFile, 'wb');
		fwrite($fh, $data, 8024);
		fclose($fh);
		$mime = $this->detect($tmpFile);
		unset($tmpFile);
		return $mime;
	}

	/**
	 * Get a secure mimetype that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 */
	public function getSecureMimeType($mimeType): string {
		$this->loadMappings();

		return $this->secureMimeTypes[$mimeType] ?? 'application/octet-stream';
	}

	/**
	 * Get path to the icon of a file type
	 * @param string $mimetype the MIME type
	 * @return string the url
	 */
	public function mimeTypeIcon($mimetype): string {
		$this->loadAliases();

		while (isset($this->mimeTypeAlias[$mimetype])) {
			$mimetype = $this->mimeTypeAlias[$mimetype];
		}
		if (isset($this->mimetypeIcons[$mimetype])) {
			return $this->mimetypeIcons[$mimetype];
		}

		// Replace slash and backslash with a minus
		$icon = str_replace(['/', '\\'], '-', $mimetype);

		// Is it a dir?
		if ($mimetype === 'dir') {
			$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/folder.svg');
			return $this->mimetypeIcons[$mimetype];
		}
		if ($mimetype === 'dir-shared') {
			$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/folder-shared.svg');
			return $this->mimetypeIcons[$mimetype];
		}
		if ($mimetype === 'dir-external') {
			$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/folder-external.svg');
			return $this->mimetypeIcons[$mimetype];
		}

		// Icon exists?
		try {
			$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/' . $icon . '.svg');
			return $this->mimetypeIcons[$mimetype];
		} catch (\RuntimeException $e) {
			// Specified image not found
		}

		// Try only the first part of the filetype

		if (strpos($icon, '-')) {
			$mimePart = substr($icon, 0, strpos($icon, '-'));
			try {
				$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/' . $mimePart . '.svg');
				return $this->mimetypeIcons[$mimetype];
			} catch (\RuntimeException $e) {
				// Image for the first part of the mimetype not found
			}
		}

		$this->mimetypeIcons[$mimetype] = $this->urlGenerator->imagePath('core', 'filetypes/file.svg');
		return $this->mimetypeIcons[$mimetype];
	}
}
