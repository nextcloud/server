<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author nik gaffney <nik@fo.am>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Rello <Rello@users.noreply.github.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Ebert <thomas.ebert@usability.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Repair;

use OC\Migration\NullOutput;
use OCP\DB\Exception;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
		protected IDBConnection $connection
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
			$result = $query->execute();
			$this->folderMimeTypeId = (int)$result->fetchOne();
			$result->closeCursor();
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('filecache')
			->set('mimetype', $update->createParameter('mimetype'))
			->where($update->expr()->neq('mimetype', $update->createParameter('mimetype'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->neq('mimetype', $update->createParameter('folder'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->iLike('name', $update->createParameter('name')))
			->setParameter('folder', $this->folderMimeTypeId);

		$count = 0;
		foreach ($updatedMimetypes as $extension => $mimetype) {
			// get target mimetype id
			$query->setParameter('mimetype', $mimetype);
			$result = $query->execute();
			$mimetypeId = (int)$result->fetchOne();
			$result->closeCursor();

			if (!$mimetypeId) {
				// insert mimetype
				$insert->setParameter('mimetype', $mimetype);
				$insert->execute();
				$mimetypeId = $insert->getLastInsertId();
			}

			// change mimetype for files with x extension
			$update->setParameter('mimetype', $mimetypeId)
				->setParameter('name', '%' . $this->connection->escapeLikeParameter('.' . $extension));
			$count += $update->execute();
		}

		return $count;
	}

	/**
	 * @throws Exception
	 */
	private function introduceExcalidrawType(): IResult|int|null {
		$updatedMimetypes = [
			'excalidraw' => 'application/vnd.excalidraw+json',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
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
	 */
	private function introduceFlatOpenDocumentType(): IResult|int|null {
		$updatedMimetypes = [
			"fodt" => "application/vnd.oasis.opendocument.text-flat-xml",
			"fods" => "application/vnd.oasis.opendocument.spreadsheet-flat-xml",
			"fodg" => "application/vnd.oasis.opendocument.graphics-flat-xml",
			"fodp" => "application/vnd.oasis.opendocument.presentation-flat-xml",
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 */
	private function introduceOrgModeType(): IResult|int|null {
		$updatedMimetypes = [
			'org' => 'text/org'
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 */
	private function introduceOnlyofficeFormType(): IResult|int|null {
		$updatedMimetypes = [
			"oform" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document.oform",
			"docxf" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document.docxf",
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
	 */
	private function introduceEnhancedMetafileFormatType(): IResult|int|null {
		$updatedMimetypes = [
			'emf' => 'image/emf',
		];

		return $this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * @throws Exception
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
	 */
	public function migrationsAvailable(): bool {
		$this->dryRun = true;
		$this->run(new NullOutput());
		$this->dryRun = false;
		return $this->changeCount > 0;
	}

	private function getMimeTypeVersion(): string {
		$mimeVersion = $this->config->getAppValue('files', 'mimetype_version', '');
		if ($mimeVersion) {
			return $mimeVersion;
		}
		return $this->config->getSystemValueString('version', '0.0.0');
	}

	/**
	 * Fix mime types
	 *
	 * @throws Exception
	 */
	public function run(IOutput $out): void {
		$serverVersion = $this->config->getSystemValueString('version', '0.0.0');
		$mimeTypeVersion = $this->getMimeTypeVersion();

		// NOTE TO DEVELOPERS: when adding new mime types, please make sure to
		// add a version comparison to avoid doing it every time

		if (version_compare($mimeTypeVersion, '12.0.0.14', '<') && $this->introduceImageTypes()) {
			$out->info('Fixed image mime types');
		}

		if (version_compare($mimeTypeVersion, '12.0.0.13', '<') && $this->introduceWindowsProgramTypes()) {
			$out->info('Fixed windows program mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.0', '<') && $this->introduceLocationTypes()) {
			$out->info('Fixed geospatial mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.3', '<') && $this->introduceInternetShortcutTypes()) {
			$out->info('Fixed internet-shortcut mime types');
		}

		if (version_compare($mimeTypeVersion, '13.0.0.6', '<') && $this->introduceStreamingTypes()) {
			$out->info('Fixed streaming mime types');
		}

		if (version_compare($mimeTypeVersion, '14.0.0.8', '<') && $this->introduceVisioTypes()) {
			$out->info('Fixed visio mime types');
		}

		if (version_compare($mimeTypeVersion, '14.0.0.10', '<') && $this->introduceComicbookTypes()) {
			$out->info('Fixed comicbook mime types');
		}

		if (version_compare($mimeTypeVersion, '20.0.0.5', '<') && $this->introduceOpenDocumentTemplates()) {
			$out->info('Fixed OpenDocument template mime types');
		}

		if (version_compare($mimeTypeVersion, '21.0.0.7', '<') && $this->introduceOrgModeType()) {
			$out->info('Fixed orgmode mime types');
		}

		if (version_compare($mimeTypeVersion, '23.0.0.2', '<') && $this->introduceFlatOpenDocumentType()) {
			$out->info('Fixed Flat OpenDocument mime types');
		}

		if (version_compare($mimeTypeVersion, '25.0.0.2', '<') && $this->introduceOnlyofficeFormType()) {
			$out->info('Fixed ONLYOFFICE Forms OpenXML mime types');
		}

		if (version_compare($mimeTypeVersion, '26.0.0.1', '<') && $this->introduceAsciidocType()) {
			$out->info('Fixed AsciiDoc mime types');
		}

		if (version_compare($mimeTypeVersion, '28.0.0.5', '<') && $this->introduceEnhancedMetafileFormatType()) {
			$out->info('Fixed Enhanced Metafile Format mime types');
		}

		if (version_compare($mimeTypeVersion, '28.0.0.8', '<') && $this->introduceEmlAndMsgFormatType()) {
			$out->info('Fixed eml and msg mime type');
		}

		if (version_compare($mimeTypeVersion, '28.0.9.0', '<') && $this->introduceExcalidrawType()) {
			$out->info('Fixed Excalidraw mime type');
		}

		if (!$this->dryRun) {
			$this->config->setAppValue('files', 'mimetype_version', $serverVersion);
		}
	}
}
