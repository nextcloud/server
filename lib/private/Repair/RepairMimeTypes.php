<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair;

use OC\Migration\NullOutput;
use OCP\DB\Exception;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairMimeTypes implements IRepairStep {
	private bool $dryRun = false;
	private int $changeCount = 0;

	/** @var int */
	protected int $folderMimeTypeId;

	public function __construct(
		protected IConfig $config,
		protected IAppConfig $appConfig,
		protected IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Repair mime types';
	}

	/**
	 * @throws Exception
	 */
	private function updateMimetypes($updatedMimetypes): IResult|int|null {
		if ($this->dryRun) {
			$this->changeCount += count($updatedMimetypes);
			return null;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('mimetypes')
			->where($query->expr()->eq('mimetype', $query->createParameter('mimetype'), IQueryBuilder::PARAM_INT));
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('mimetypes')
			->setValue('mimetype', $insert->createParameter('mimetype'));

		if (empty($this->folderMimeTypeId)) {
			$query->setParameter('mimetype', 'httpd/unix-directory');
			$result = $query->executeQuery();
			$this->folderMimeTypeId = (int)$result->fetchOne();
			$result->closeCursor();
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('filecache')
			->runAcrossAllShards()
			->set('mimetype', $update->createParameter('mimetype'))
			->where($update->expr()->neq('mimetype', $update->createParameter('mimetype'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->neq('mimetype', $update->createParameter('folder'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->iLike('name', $update->createParameter('name')))
			->setParameter('folder', $this->folderMimeTypeId);

		$count = 0;
		foreach ($updatedMimetypes as $extension => $mimetype) {
			// get target mimetype id
			$query->setParameter('mimetype', $mimetype);
			$result = $query->executeQuery();
			$mimetypeId = (int)$result->fetchOne();
			$result->closeCursor();

			if (!$mimetypeId) {
				// insert mimetype
				$insert->setParameter('mimetype', $mimetype);
				$insert->executeStatement();
				$mimetypeId = $insert->getLastInsertId();
			}

			// change mimetype for files with x extension
			$update->setParameter('mimetype', $mimetypeId)
				->setParameter('name', '%' . $this->connection->escapeLikeParameter('.' . $extension));
			$count += $update->executeStatement();
		}

		return $count;
	}

	/**
	 * @throws Exception
	 * @since 12.0.0.14
	 */
	private function introduceImageTypes(): IResult|int|null {
		$updatedMimetypes = [
			'jp2' => 'image/jp2',
			'webp' => 'image/webp',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 12.0.0.13
	 */
	private function introduceWindowsProgramTypes(): IResult|int|null {
		$updatedMimetypes = [
			'htaccess' => 'text/plain',
			'bat' => 'application/x-msdos-program',
			'cmd' => 'application/cmd',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 13.0.0.0
	 */
	private function introduceLocationTypes(): IResult|int|null {
		$updatedMimetypes = [
			'gpx' => 'application/gpx+xml',
			'kml' => 'application/vnd.google-earth.kml+xml',
			'kmz' => 'application/vnd.google-earth.kmz',
			'tcx' => 'application/vnd.garmin.tcx+xml',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 13.0.0.3
	 */
	private function introduceInternetShortcutTypes(): IResult|int|null {
		$updatedMimetypes = [
			'url' => 'application/internet-shortcut',
			'webloc' => 'application/internet-shortcut'
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 13.0.0.6
	 */
	private function introduceStreamingTypes(): IResult|int|null {
		$updatedMimetypes = [
			'm3u' => 'audio/mpegurl',
			'm3u8' => 'audio/mpegurl',
			'pls' => 'audio/x-scpls'
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 14.0.0.8
	 */
	private function introduceVisioTypes(): IResult|int|null {
		$updatedMimetypes = [
			'vsdm' => 'application/vnd.visio',
			'vsdx' => 'application/vnd.visio',
			'vssm' => 'application/vnd.visio',
			'vssx' => 'application/vnd.visio',
			'vstm' => 'application/vnd.visio',
			'vstx' => 'application/vnd.visio',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 14.0.0.10
	 */
	private function introduceComicbookTypes(): IResult|int|null {
		$updatedMimetypes = [
			'cb7' => 'application/comicbook+7z',
			'cba' => 'application/comicbook+ace',
			'cbr' => 'application/comicbook+rar',
			'cbt' => 'application/comicbook+tar',
			'cbtc' => 'application/comicbook+truecrypt',
			'cbz' => 'application/comicbook+zip',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 20.0.0.5
	 */
	private function introduceOpenDocumentTemplates(): IResult|int|null {
		$updatedMimetypes = [
			'ott' => 'application/vnd.oasis.opendocument.text-template',
			'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
			'otp' => 'application/vnd.oasis.opendocument.presentation-template',
			'otg' => 'application/vnd.oasis.opendocument.graphics-template',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 21.0.0.7
	 */
	private function introduceOrgModeType(): IResult|int|null {
		$updatedMimetypes = [
			'org' => 'text/org'
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 23.0.0.2
	 */
	private function introduceFlatOpenDocumentType(): IResult|int|null {
		$updatedMimetypes = [
			'fodt' => 'application/vnd.oasis.opendocument.text-flat-xml',
			'fods' => 'application/vnd.oasis.opendocument.spreadsheet-flat-xml',
			'fodg' => 'application/vnd.oasis.opendocument.graphics-flat-xml',
			'fodp' => 'application/vnd.oasis.opendocument.presentation-flat-xml',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 25.0.0.2
	 */
	private function introduceOnlyofficeFormType(): IResult|int|null {
		$updatedMimetypes = [
			'oform' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.oform',
			'docxf' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.docxf',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 26.0.0.1
	 */
	private function introduceAsciidocType(): IResult|int|null {
		$updatedMimetypes = [
			'adoc' => 'text/asciidoc',
			'asciidoc' => 'text/asciidoc',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 28.0.0.5
	 */
	private function introduceEnhancedMetafileFormatType(): IResult|int|null {
		$updatedMimetypes = [
			'emf' => 'image/emf',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 29.0.0.2
	 */
	private function introduceEmlAndMsgFormatType(): IResult|int|null {
		$updatedMimetypes = [
			'eml' => 'message/rfc822',
			'msg' => 'application/vnd.ms-outlook',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 29.0.0.6
	 */
	private function introduceAacAudioType(): IResult|int|null {
		$updatedMimetypes = [
			'aac' => 'audio/aac',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 29.0.10
	 */
	private function introduceReStructuredTextFormatType(): IResult|int|null {
		$updatedMimetypes = [
			'rst' => 'text/x-rst',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 30.0.0
	 */
	private function introduceExcalidrawType(): IResult|int|null {
		$updatedMimetypes = [
			'excalidraw' => 'application/vnd.excalidraw+json',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}


	/**
	 * @throws Exception
	 * @since 31.0.0
	 */
	private function introduceZstType(): IResult|int|null {
		$updatedMimetypes = [
			'zst' => 'application/zstd',
			'nfo' => 'text/x-nfo',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 32.0.0
	 */
	private function introduceMusicxmlType(): IResult|int|null {
		$updatedMimetypes = [
			'mxl' => 'application/vnd.recordare.musicxml',
			'musicxml' => 'application/vnd.recordare.musicxml+xml',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 * @since 32.0.0
	 */
	private function introduceTextType(): IResult|int|null {
		$updatedMimetypes = [
			'text' => 'text/plain',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}



	/**
	 * Check if there are any migrations available
	 *
	 * @throws Exception
	 */
	public function migrationsAvailable(): bool {
		$this->dryRun = true;
		$this->run(new NullOutput());
		$this->dryRun = false;
		return $this->changeCount > 0;
	}

	/**
	 * Get the current mimetype version
	 */
	private function getMimeTypeVersion(): string {
		$serverVersion = $this->config->getSystemValueString('version', '0.0.0');
		// 29.0.0.10 is the last version with a mimetype migration before it was moved to a separate version number
		if (version_compare($serverVersion, '29.0.0.10', '>')) {
			return $this->appConfig->getValueString('files', 'mimetype_version', '29.0.0.10');
		}

		return $serverVersion;
	}

	/**
	 * Fix mime types
	 *
	 * @throws Exception
	 */
	public function run(IOutput $output): void {
		$serverVersion = $this->config->getSystemValueString('version', '0.0.0');
		$mimeTypeVersion = $this->getMimeTypeVersion();

		// NOTE TO DEVELOPERS: when adding new mime types, please make sure to
		// add a version comparison to avoid doing it every time
		// PLEASE ALSO KEEP THE LIST SORTED BY VERSION NUMBER

		if (version_compare($mimeTypeVersion, '12.0.0.14', '<') && $this->introduceImageTypes()) {
			$output->info('Fixed image mime types');
		}

		if (version_compare($mimeTypeVersion, '12.0.0.13', '<') && $this->introduceWindowsProgramTypes()) {
			$output->info('Fixed windows program mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.0', '<') && $this->introduceLocationTypes()) {
			$output->info('Fixed geospatial mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.3', '<') && $this->introduceInternetShortcutTypes()) {
			$output->info('Fixed internet-shortcut mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.6', '<') && $this->introduceStreamingTypes()) {
			$output->info('Fixed streaming mime types');
		}

		if (version_compare($mimeTypeVersion, '14.0.0.8', '<') && $this->introduceVisioTypes()) {
			$output->info('Fixed visio mime types');
		}

		if (version_compare($mimeTypeVersion, '14.0.0.10', '<') && $this->introduceComicbookTypes()) {
			$output->info('Fixed comicbook mime types');
		}

		if (version_compare($mimeTypeVersion, '20.0.0.5', '<') && $this->introduceOpenDocumentTemplates()) {
			$output->info('Fixed OpenDocument template mime types');
		}

		if (version_compare($mimeTypeVersion, '21.0.0.7', '<') && $this->introduceOrgModeType()) {
			$output->info('Fixed orgmode mime types');
		}

		if (version_compare($mimeTypeVersion, '23.0.0.2', '<') && $this->introduceFlatOpenDocumentType()) {
			$output->info('Fixed Flat OpenDocument mime types');
		}

		if (version_compare($mimeTypeVersion, '25.0.0.2', '<') && $this->introduceOnlyofficeFormType()) {
			$output->info('Fixed ONLYOFFICE Forms OpenXML mime types');
		}

		if (version_compare($mimeTypeVersion, '26.0.0.1', '<') && $this->introduceAsciidocType()) {
			$output->info('Fixed AsciiDoc mime types');
		}

		if (version_compare($mimeTypeVersion, '28.0.0.5', '<') && $this->introduceEnhancedMetafileFormatType()) {
			$output->info('Fixed Enhanced Metafile Format mime types');
		}

		if (version_compare($mimeTypeVersion, '29.0.0.2', '<') && $this->introduceEmlAndMsgFormatType()) {
			$output->info('Fixed eml and msg mime type');
		}

		if (version_compare($mimeTypeVersion, '29.0.0.6', '<') && $this->introduceAacAudioType()) {
			$output->info('Fixed aac mime type');
		}

		if (version_compare($mimeTypeVersion, '29.0.0.10', '<') && $this->introduceReStructuredTextFormatType()) {
			$output->info('Fixed ReStructured Text mime type');
		}

		if (version_compare($mimeTypeVersion, '30.0.0.0', '<') && $this->introduceExcalidrawType()) {
			$output->info('Fixed Excalidraw mime type');
		}

		if (version_compare($mimeTypeVersion, '31.0.0.0', '<') && $this->introduceZstType()) {
			$output->info('Fixed zst mime type');
		}

		if (version_compare($mimeTypeVersion, '32.0.0.0', '<') && $this->introduceMusicxmlType()) {
			$output->info('Fixed musicxml mime type');
		}

		if (version_compare($mimeTypeVersion, '32.0.0.0', '<') && $this->introduceTextType()) {
			$output->info('Fixed text mime type');
		}

		if (!$this->dryRun) {
			$this->appConfig->setValueString('files', 'mimetype_version', $serverVersion);
		}
	}
}
