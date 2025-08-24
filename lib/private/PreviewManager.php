<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Preview\Generator;
use OC\Preview\GeneratorHelper;
use OC\Preview\IMagickSupport;
use OCP\AppFramework\QueryException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IPreview;
use OCP\Preview\IProviderV2;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

class PreviewManager implements IPreview {
	protected IConfig $config;
	protected IRootFolder $rootFolder;
	protected IAppData $appData;
	protected IEventDispatcher $eventDispatcher;
	private ?Generator $generator = null;
	private GeneratorHelper $helper;
	protected bool $providerListDirty = false;
	protected bool $registeredCoreProviders = false;
	protected array $providers = [];

	/** @var array mime type => support status */
	protected array $mimeTypeSupportMap = [];
	protected ?array $defaultProviders = null;
	protected ?string $userId;
	private Coordinator $bootstrapCoordinator;

	/**
	 * Hash map (without value) of loaded bootstrap providers
	 * @psalm-var array<string, null>
	 */
	private array $loadedBootstrapProviders = [];
	private ContainerInterface $container;
	private IBinaryFinder $binaryFinder;
	private IMagickSupport $imagickSupport;
	private bool $enablePreviews;

	public function __construct(
		IConfig $config,
		IRootFolder $rootFolder,
		IAppData $appData,
		IEventDispatcher $eventDispatcher,
		GeneratorHelper $helper,
		?string $userId,
		Coordinator $bootstrapCoordinator,
		ContainerInterface $container,
		IBinaryFinder $binaryFinder,
		IMagickSupport $imagickSupport,
	) {
		$this->config = $config;
		$this->rootFolder = $rootFolder;
		$this->appData = $appData;
		$this->eventDispatcher = $eventDispatcher;
		$this->helper = $helper;
		$this->userId = $userId;
		$this->bootstrapCoordinator = $bootstrapCoordinator;
		$this->container = $container;
		$this->binaryFinder = $binaryFinder;
		$this->imagickSupport = $imagickSupport;
		$this->enablePreviews = $config->getSystemValueBool('enable_previews', true);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be
	 * called in case preview providers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Preview\IProvider or \OCP\Preview\IProviderV2
	 *
	 * @param string $mimeTypeRegex Regex with the mime types that are supported by this provider
	 * @param \Closure $callable
	 * @return void
	 */
	public function registerProvider($mimeTypeRegex, \Closure $callable): void {
		if (!$this->enablePreviews) {
			return;
		}

		if (!isset($this->providers[$mimeTypeRegex])) {
			$this->providers[$mimeTypeRegex] = [];
		}
		$this->providers[$mimeTypeRegex][] = $callable;
		$this->providerListDirty = true;
	}

	/**
	 * Get all providers
	 */
	public function getProviders(): array {
		if (!$this->enablePreviews) {
			return [];
		}

		$this->registerCoreProviders();
		$this->registerBootstrapProviders();
		if ($this->providerListDirty) {
			$keys = array_map('strlen', array_keys($this->providers));
			array_multisort($keys, SORT_DESC, $this->providers);
			$this->providerListDirty = false;
		}

		return $this->providers;
	}

	/**
	 * Does the manager have any providers
	 */
	public function hasProviders(): bool {
		$this->registerCoreProviders();
		return !empty($this->providers);
	}

	private function getGenerator(): Generator {
		if ($this->generator === null) {
			$this->generator = new Generator(
				$this->config,
				$this,
				$this->appData,
				new GeneratorHelper(
					$this->rootFolder,
					$this->config
				),
				$this->eventDispatcher,
				$this->container->get(LoggerInterface::class),
			);
		}
		return $this->generator;
	}

	public function getPreview(
		File $file,
		$width = -1,
		$height = -1,
		$crop = false,
		$mode = IPreview::MODE_FILL,
		$mimeType = null,
		bool $cacheResult = true,
	): ISimpleFile {
		$this->throwIfPreviewsDisabled($file, $mimeType);
		$previewConcurrency = $this->getGenerator()->getNumConcurrentPreviews('preview_concurrency_all');
		$sem = Generator::guardWithSemaphore(Generator::SEMAPHORE_ID_ALL, $previewConcurrency);
		try {
			$preview = $this->getGenerator()->getPreview($file, $width, $height, $crop, $mode, $mimeType, $cacheResult);
		} finally {
			Generator::unguardWithSemaphore($sem);
		}

		return $preview;
	}

	/**
	 * Generates previews of a file
	 *
	 * @param File $file
	 * @param array $specifications
	 * @param string $mimeType
	 * @return ISimpleFile the last preview that was generated
	 * @throws NotFoundException
	 * @throws \InvalidArgumentException if the preview would be invalid (in case the original image is invalid)
	 * @since 19.0.0
	 */
	public function generatePreviews(File $file, array $specifications, $mimeType = null) {
		$this->throwIfPreviewsDisabled($file, $mimeType);
		return $this->getGenerator()->generatePreviews($file, $specifications, $mimeType);
	}

	/**
	 * returns true if the passed mime type is supported
	 *
	 * @param string $mimeType
	 * @return boolean
	 */
	public function isMimeSupported($mimeType = '*') {
		if (!$this->enablePreviews) {
			return false;
		}

		if (isset($this->mimeTypeSupportMap[$mimeType])) {
			return $this->mimeTypeSupportMap[$mimeType];
		}

		$this->registerCoreProviders();
		$this->registerBootstrapProviders();
		$providerMimeTypes = array_keys($this->providers);
		foreach ($providerMimeTypes as $supportedMimeType) {
			if (preg_match($supportedMimeType, $mimeType)) {
				$this->mimeTypeSupportMap[$mimeType] = true;
				return true;
			}
		}
		$this->mimeTypeSupportMap[$mimeType] = false;
		return false;
	}

	/**
	 * Check if a preview can be generated for a file
	 */
	public function isAvailable(\OCP\Files\FileInfo $file, ?string $mimeType = null): bool {
		if (!$this->enablePreviews) {
			return false;
		}

		$fileMimeType = $mimeType ?? $file->getMimeType();

		$this->registerCoreProviders();
		if (!$this->isMimeSupported($fileMimeType)) {
			return false;
		}

		$mount = $file->getMountPoint();
		if ($mount and !$mount->getOption('previews', true)) {
			return false;
		}

		foreach ($this->providers as $supportedMimeType => $providers) {
			if (preg_match($supportedMimeType, $fileMimeType)) {
				foreach ($providers as $providerClosure) {
					$provider = $this->helper->getProvider($providerClosure);
					if (!($provider instanceof IProviderV2)) {
						continue;
					}

					if ($provider->isAvailable($file)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * List of enabled default providers
	 *
	 * The following providers are enabled by default:
	 *  - OC\Preview\PNG
	 *  - OC\Preview\JPEG
	 *  - OC\Preview\GIF
	 *  - OC\Preview\BMP
	 *  - OC\Preview\XBitmap
	 *  - OC\Preview\MarkDown
	 *  - OC\Preview\MP3
	 *  - OC\Preview\TXT
	 *
	 * The following providers are disabled by default due to performance or privacy concerns:
	 *  - OC\Preview\Font
	 *  - OC\Preview\HEIC
	 *  - OC\Preview\Illustrator
	 *  - OC\Preview\Movie
	 *  - OC\Preview\MSOfficeDoc
	 *  - OC\Preview\MSOffice2003
	 *  - OC\Preview\MSOffice2007
	 *  - OC\Preview\OpenDocument
	 *  - OC\Preview\PDF
	 *  - OC\Preview\Photoshop
	 *  - OC\Preview\Postscript
	 *  - OC\Preview\StarOffice
	 *  - OC\Preview\SVG
	 *  - OC\Preview\TIFF
	 *
	 * @return array
	 */
	protected function getEnabledDefaultProvider() {
		if ($this->defaultProviders !== null) {
			return $this->defaultProviders;
		}

		$imageProviders = [
			Preview\PNG::class,
			Preview\JPEG::class,
			Preview\GIF::class,
			Preview\BMP::class,
			Preview\XBitmap::class,
			Preview\Krita::class,
			Preview\WebP::class,
		];

		$this->defaultProviders = $this->config->getSystemValue('enabledPreviewProviders', array_merge([
			Preview\MarkDown::class,
			Preview\MP3::class,
			Preview\TXT::class,
			Preview\OpenDocument::class,
		], $imageProviders));

		if (in_array(Preview\Image::class, $this->defaultProviders)) {
			$this->defaultProviders = array_merge($this->defaultProviders, $imageProviders);
		}
		$this->defaultProviders = array_unique($this->defaultProviders);
		return $this->defaultProviders;
	}

	/**
	 * Register the default providers (if enabled)
	 *
	 * @param string $class
	 * @param string $mimeType
	 */
	protected function registerCoreProvider($class, $mimeType, $options = []) {
		if (in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
			$this->registerProvider($mimeType, function () use ($class, $options) {
				return new $class($options);
			});
		}
	}

	/**
	 * Register the default providers (if enabled)
	 */
	protected function registerCoreProviders() {
		if ($this->registeredCoreProviders) {
			return;
		}
		$this->registeredCoreProviders = true;

		$this->registerCoreProvider(Preview\TXT::class, '/text\/plain/');
		$this->registerCoreProvider(Preview\MarkDown::class, '/text\/(x-)?markdown/');
		$this->registerCoreProvider(Preview\PNG::class, '/image\/png/');
		$this->registerCoreProvider(Preview\JPEG::class, '/image\/jpeg/');
		$this->registerCoreProvider(Preview\GIF::class, '/image\/gif/');
		$this->registerCoreProvider(Preview\BMP::class, '/image\/bmp/');
		$this->registerCoreProvider(Preview\XBitmap::class, '/image\/x-xbitmap/');
		$this->registerCoreProvider(Preview\WebP::class, '/image\/webp/');
		$this->registerCoreProvider(Preview\Krita::class, '/application\/x-krita/');
		$this->registerCoreProvider(Preview\MP3::class, '/audio\/mpeg$/');
		$this->registerCoreProvider(Preview\OpenDocument::class, '/application\/vnd.oasis.opendocument.*/');
		$this->registerCoreProvider(Preview\Imaginary::class, Preview\Imaginary::supportedMimeTypes());
		$this->registerCoreProvider(Preview\ImaginaryPDF::class, Preview\ImaginaryPDF::supportedMimeTypes());

		// SVG and Bitmap require imagick
		if ($this->imagickSupport->hasExtension()) {
			$imagickProviders = [
				'SVG' => ['mimetype' => '/image\/svg\+xml/', 'class' => Preview\SVG::class],
				'TIFF' => ['mimetype' => '/image\/tiff/', 'class' => Preview\TIFF::class],
				'PDF' => ['mimetype' => '/application\/pdf/', 'class' => Preview\PDF::class],
				'AI' => ['mimetype' => '/application\/illustrator/', 'class' => Preview\Illustrator::class],
				'PSD' => ['mimetype' => '/application\/x-photoshop/', 'class' => Preview\Photoshop::class],
				'EPS' => ['mimetype' => '/application\/postscript/', 'class' => Preview\Postscript::class],
				'TTF' => ['mimetype' => '/application\/(?:font-sfnt|x-font$)/', 'class' => Preview\Font::class],
				'HEIC' => ['mimetype' => '/image\/(x-)?hei(f|c)/', 'class' => Preview\HEIC::class],
				'TGA' => ['mimetype' => '/image\/(x-)?t(ar)?ga/', 'class' => Preview\TGA::class],
				'SGI' => ['mimetype' => '/image\/(x-)?sgi/', 'class' => Preview\SGI::class],
			];

			foreach ($imagickProviders as $queryFormat => $provider) {
				$class = $provider['class'];
				if (!in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
					continue;
				}

				if ($this->imagickSupport->supportsFormat($queryFormat)) {
					$this->registerCoreProvider($class, $provider['mimetype']);
				}
			}
		}

		$this->registerCoreProvidersOffice();

		// Video requires avconv or ffmpeg
		if (in_array(Preview\Movie::class, $this->getEnabledDefaultProvider())) {
			$movieBinary = $this->config->getSystemValue('preview_ffmpeg_path', null);
			if (!is_string($movieBinary)) {
				$movieBinary = $this->binaryFinder->findBinaryPath('avconv');
				if (!is_string($movieBinary)) {
					$movieBinary = $this->binaryFinder->findBinaryPath('ffmpeg');
				}
			}


			if (is_string($movieBinary)) {
				$this->registerCoreProvider(Preview\Movie::class, '/video\/.*/', ['movieBinary' => $movieBinary]);
			}
		}
	}

	private function registerCoreProvidersOffice(): void {
		$officeProviders = [
			['mimetype' => '/application\/msword/', 'class' => Preview\MSOfficeDoc::class],
			['mimetype' => '/application\/vnd.ms-.*/', 'class' => Preview\MSOffice2003::class],
			['mimetype' => '/application\/vnd.openxmlformats-officedocument.*/', 'class' => Preview\MSOffice2007::class],
			['mimetype' => '/application\/vnd.oasis.opendocument.*/', 'class' => Preview\OpenDocument::class],
			['mimetype' => '/application\/vnd.sun.xml.*/', 'class' => Preview\StarOffice::class],
			['mimetype' => '/image\/emf/', 'class' => Preview\EMF::class],
		];

		$findBinary = true;
		$officeBinary = false;

		foreach ($officeProviders as $provider) {
			$class = $provider['class'];
			if (!in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
				continue;
			}

			if ($findBinary) {
				// Office requires openoffice or libreoffice
				$officeBinary = $this->config->getSystemValue('preview_libreoffice_path', false);
				if ($officeBinary === false) {
					$officeBinary = $this->binaryFinder->findBinaryPath('libreoffice');
				}
				if ($officeBinary === false) {
					$officeBinary = $this->binaryFinder->findBinaryPath('openoffice');
				}
				$findBinary = false;
			}

			if ($officeBinary) {
				$this->registerCoreProvider($class, $provider['mimetype'], ['officeBinary' => $officeBinary]);
			}
		}
	}

	private function registerBootstrapProviders(): void {
		$context = $this->bootstrapCoordinator->getRegistrationContext();

		if ($context === null) {
			// Just ignore for now
			return;
		}

		$providers = $context->getPreviewProviders();
		foreach ($providers as $provider) {
			$key = $provider->getMimeTypeRegex() . '-' . $provider->getService();
			if (array_key_exists($key, $this->loadedBootstrapProviders)) {
				// Do not load the provider more than once
				continue;
			}
			$this->loadedBootstrapProviders[$key] = null;

			$this->registerProvider($provider->getMimeTypeRegex(), function () use ($provider) {
				try {
					return $this->container->get($provider->getService());
				} catch (QueryException $e) {
					return null;
				}
			});
		}
	}

	/**
	 * @throws NotFoundException if preview generation is disabled
	 */
	private function throwIfPreviewsDisabled(File $file, ?string $mimeType = null): void {
		if (!$this->isAvailable($file, $mimeType)) {
			throw new NotFoundException('Previews disabled');
		}
	}
}
