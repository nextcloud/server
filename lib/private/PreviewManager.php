<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use Closure;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Preview\BMP;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\EMF;
use OC\Preview\Font;
use OC\Preview\Generator;
use OC\Preview\GeneratorHelper;
use OC\Preview\GIF;
use OC\Preview\HEIC;
use OC\Preview\Illustrator;
use OC\Preview\Image;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\ImaginaryPDF;
use OC\Preview\JPEG;
use OC\Preview\Krita;
use OC\Preview\MarkDown;
use OC\Preview\Movie;
use OC\Preview\MP3;
use OC\Preview\MSOffice2003;
use OC\Preview\MSOffice2007;
use OC\Preview\MSOfficeDoc;
use OC\Preview\OpenDocument;
use OC\Preview\PDF;
use OC\Preview\Photoshop;
use OC\Preview\PNG;
use OC\Preview\Postscript;
use OC\Preview\SGI;
use OC\Preview\StarOffice;
use OC\Preview\Storage\StorageFactory;
use OC\Preview\SVG;
use OC\Preview\TGA;
use OC\Preview\TIFF;
use OC\Preview\TXT;
use OC\Preview\WebP;
use OC\Preview\XBitmap;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IPreview;
use OCP\Preview\IProviderV2;
use OCP\Snowflake\IGenerator;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

/**
 * @psalm-import-type ProviderClosure from IPreview
 */
class PreviewManager implements IPreview {
	private ?Generator $generator = null;
	protected bool $providerListDirty = false;
	protected bool $registeredCoreProviders = false;
	/**
	 * @var array<string, list<ProviderClosure>> $providers
	 */
	protected array $providers = [];

	/** @var array mime type => support status */
	protected array $mimeTypeSupportMap = [];
	/** @var ?list<class-string<IProviderV2>> $defaultProviders */
	protected ?array $defaultProviders = null;

	/**
	 * Hash map (without value) of loaded bootstrap providers
	 * @psalm-var array<string, null>
	 */
	private array $loadedBootstrapProviders = [];
	private bool $enablePreviews;

	public function __construct(
		protected IConfig $config,
		protected IRootFolder $rootFolder,
		protected IEventDispatcher $eventDispatcher,
		private GeneratorHelper $helper,
		protected ?string $userId,
		private Coordinator $bootstrapCoordinator,
		private ContainerInterface $container,
		private IBinaryFinder $binaryFinder,
		private IMagickSupport $imagickSupport,
	) {
		$this->enablePreviews = $this->config->getSystemValueBool('enable_previews', true);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be
	 * called in case preview providers are actually requested
	 *
	 * @param string $mimeTypeRegex Regex with the mime types that are supported by this provider
	 * @param ProviderClosure $callable
	 */
	public function registerProvider(string $mimeTypeRegex, Closure $callable): void {
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
				new GeneratorHelper(),
				$this->eventDispatcher,
				$this->container->get(LoggerInterface::class),
				$this->container->get(PreviewMapper::class),
				$this->container->get(StorageFactory::class),
				$this->container->get(IGenerator::class),
			);
		}
		return $this->generator;
	}

	public function getPreview(
		File $file,
		int $width = -1,
		int $height = -1,
		bool $crop = false,
		string $mode = IPreview::MODE_FILL,
		?string $mimeType = null,
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
	 * @param array $specifications
	 * @return ISimpleFile the last preview that was generated
	 * @throws NotFoundException
	 * @throws \InvalidArgumentException if the preview would be invalid (in case the original image is invalid)
	 * @since 19.0.0
	 */
	public function generatePreviews(File $file, array $specifications, ?string $mimeType = null): ISimpleFile {
		$this->throwIfPreviewsDisabled($file, $mimeType);
		return $this->getGenerator()->generatePreviews($file, $specifications, $mimeType);
	}

	public function isMimeSupported(string $mimeType = '*'): bool {
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

	public function isAvailable(FileInfo $file, ?string $mimeType = null): bool {
		if (!$this->enablePreviews) {
			return false;
		}

		$fileMimeType = $mimeType ?? $file->getMimeType();

		$this->registerCoreProviders();
		if (!$this->isMimeSupported($fileMimeType)) {
			return false;
		}

		$mount = $file->getMountPoint();
		if ($mount && !$mount->getOption('previews', true)) {
			return false;
		}

		foreach ($this->providers as $supportedMimeType => $providers) {
			if (preg_match($supportedMimeType, $fileMimeType)) {
				foreach ($providers as $providerClosure) {
					$provider = $this->helper->getProvider($providerClosure);
					if (!$provider) {
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
	 * @return list<class-string<IProviderV2>>
	 */
	protected function getEnabledDefaultProvider(): array {
		if ($this->defaultProviders !== null) {
			return $this->defaultProviders;
		}

		$imageProviders = [
			PNG::class,
			JPEG::class,
			GIF::class,
			BMP::class,
			XBitmap::class,
			Krita::class,
			WebP::class,
		];

		$this->defaultProviders = $this->config->getSystemValue('enabledPreviewProviders', array_merge([
			MarkDown::class,
			TXT::class,
			OpenDocument::class,
		], $imageProviders));

		if (in_array(Image::class, $this->defaultProviders)) {
			$this->defaultProviders = array_merge($this->defaultProviders, $imageProviders);
		}
		$this->defaultProviders = array_values(array_unique($this->defaultProviders));
		/** @var list<class-string<IProviderV2>> $providers */
		$providers = $this->defaultProviders;
		return $providers;
	}

	/**
	 * Register the default providers (if enabled)
	 */
	protected function registerCoreProvider(string $class, string $mimeType, array $options = []): void {
		if (in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
			$this->registerProvider($mimeType, function () use ($class, $options) {
				return new $class($options);
			});
		}
	}

	/**
	 * Register the default providers (if enabled)
	 */
	protected function registerCoreProviders(): void {
		if ($this->registeredCoreProviders) {
			return;
		}
		$this->registeredCoreProviders = true;

		$this->registerCoreProvider(TXT::class, '/text\/plain/');
		$this->registerCoreProvider(MarkDown::class, '/text\/(x-)?markdown/');
		$this->registerCoreProvider(PNG::class, '/image\/png/');
		$this->registerCoreProvider(JPEG::class, '/image\/jpeg/');
		$this->registerCoreProvider(GIF::class, '/image\/gif/');
		$this->registerCoreProvider(BMP::class, '/image\/bmp/');
		$this->registerCoreProvider(XBitmap::class, '/image\/x-xbitmap/');
		$this->registerCoreProvider(WebP::class, '/image\/webp/');
		$this->registerCoreProvider(Krita::class, '/application\/x-krita/');
		$this->registerCoreProvider(MP3::class, '/audio\/mpeg$/');
		$this->registerCoreProvider(OpenDocument::class, '/application\/vnd.oasis.opendocument.*/');
		$this->registerCoreProvider(Imaginary::class, Imaginary::supportedMimeTypes());
		$this->registerCoreProvider(ImaginaryPDF::class, ImaginaryPDF::supportedMimeTypes());

		// SVG and Bitmap require imagick
		if ($this->imagickSupport->hasExtension()) {
			$imagickProviders = [
				'SVG' => ['mimetype' => '/image\/svg\+xml/', 'class' => SVG::class],
				'TIFF' => ['mimetype' => '/image\/tiff/', 'class' => TIFF::class],
				'PDF' => ['mimetype' => '/application\/pdf/', 'class' => PDF::class],
				'AI' => ['mimetype' => '/application\/illustrator/', 'class' => Illustrator::class],
				'PSD' => ['mimetype' => '/application\/x-photoshop/', 'class' => Photoshop::class],
				'EPS' => ['mimetype' => '/application\/postscript/', 'class' => Postscript::class],
				'TTF' => ['mimetype' => '/application\/(?:font-sfnt|x-font$)/', 'class' => Font::class],
				'HEIC' => ['mimetype' => '/image\/(x-)?hei(f|c)/', 'class' => HEIC::class],
				'TGA' => ['mimetype' => '/image\/(x-)?t(ar)?ga/', 'class' => TGA::class],
				'SGI' => ['mimetype' => '/image\/(x-)?sgi/', 'class' => SGI::class],
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

		// Video requires ffmpeg
		if (in_array(Movie::class, $this->getEnabledDefaultProvider())) {
			$movieBinary = $this->config->getSystemValue('preview_ffmpeg_path', null);
			if (!is_string($movieBinary)) {
				$movieBinary = $this->binaryFinder->findBinaryPath('ffmpeg');
			}


			if (is_string($movieBinary)) {
				$this->registerCoreProvider(Movie::class, '/video\/.*/', ['movieBinary' => $movieBinary]);
			}
		}
	}

	private function registerCoreProvidersOffice(): void {
		$officeProviders = [
			['mimetype' => '/application\/msword/', 'class' => MSOfficeDoc::class],
			['mimetype' => '/application\/vnd.ms-.*/', 'class' => MSOffice2003::class],
			['mimetype' => '/application\/vnd.openxmlformats-officedocument.*/', 'class' => MSOffice2007::class],
			['mimetype' => '/application\/vnd.oasis.opendocument.*/', 'class' => OpenDocument::class],
			['mimetype' => '/application\/vnd.sun.xml.*/', 'class' => StarOffice::class],
			['mimetype' => '/image\/emf/', 'class' => EMF::class],
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

			$this->registerProvider($provider->getMimeTypeRegex(), function () use ($provider): IProviderV2|false {
				try {
					return $this->container->get($provider->getService());
				} catch (NotFoundExceptionInterface) {
					return false;
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
