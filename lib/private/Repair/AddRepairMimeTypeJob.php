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

use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddRepairMimeTypeJob implements IRepairStep {
	protected $config;
	protected $connection;
	protected $jobList;
	public function __construct(IConfig $config,
								IDBConnection $connection, IJobList $jobList) {
		$this->config = $config;
		$this->connection = $connection;
		$this->jobList = $jobList;
	}

	public function getName() {
		return 'Repair mime types through scheduling background jobs';
	}

	private function scheduleMimeTypeUpdateJob(array $updatedMimetypes): int {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select('numeric_id')
			->from('storages')
			->execute();

		$jobCount = 0;
		while ($row = $result->fetch()) {
			$this->jobList->add(RepairMimeTypeJob::class, [
				'storageId' => $row['numeric_id'],
				'mimetypes' => $updatedMimetypes,
			]);
			$jobCount++;
		}

		return $jobCount;
	}

	private function introduceAsciidocType() {
		$updatedMimetypes = [
			'adoc' => 'text/asciidoc',
			'asciidoc' => 'text/asciidoc',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceImageTypes() {
		$updatedMimetypes = [
			'jp2' => 'image/jp2',
			'webp' => 'image/webp',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceWindowsProgramTypes() {
		$updatedMimetypes = [
			'htaccess' => 'text/plain',
			'bat' => 'application/x-msdos-program',
			'cmd' => 'application/cmd',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceLocationTypes() {
		$updatedMimetypes = [
			'gpx' => 'application/gpx+xml',
			'kml' => 'application/vnd.google-earth.kml+xml',
			'kmz' => 'application/vnd.google-earth.kmz',
			'tcx' => 'application/vnd.garmin.tcx+xml',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceInternetShortcutTypes() {
		$updatedMimetypes = [
			'url' => 'application/internet-shortcut',
			'webloc' => 'application/internet-shortcut'
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceStreamingTypes() {
		$updatedMimetypes = [
			'm3u' => 'audio/mpegurl',
			'm3u8' => 'audio/mpegurl',
			'pls' => 'audio/x-scpls'
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceVisioTypes() {
		$updatedMimetypes = [
			'vsdm' => 'application/vnd.visio',
			'vsdx' => 'application/vnd.visio',
			'vssm' => 'application/vnd.visio',
			'vssx' => 'application/vnd.visio',
			'vstm' => 'application/vnd.visio',
			'vstx' => 'application/vnd.visio',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceComicbookTypes() {
		$updatedMimetypes = [
			'cb7' => 'application/comicbook+7z',
			'cba' => 'application/comicbook+ace',
			'cbr' => 'application/comicbook+rar',
			'cbt' => 'application/comicbook+tar',
			'cbtc' => 'application/comicbook+truecrypt',
			'cbz' => 'application/comicbook+zip',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceOpenDocumentTemplates() {
		$updatedMimetypes = [
			'ott' => 'application/vnd.oasis.opendocument.text-template',
			'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
			'otp' => 'application/vnd.oasis.opendocument.presentation-template',
			'otg' => 'application/vnd.oasis.opendocument.graphics-template',
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceFlatOpenDocumentType() {
		$updatedMimetypes = [
			"fodt" => "application/vnd.oasis.opendocument.text-flat-xml",
			"fods" => "application/vnd.oasis.opendocument.spreadsheet-flat-xml",
			"fodg" => "application/vnd.oasis.opendocument.graphics-flat-xml",
			"fodp" => "application/vnd.oasis.opendocument.presentation-flat-xml",
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceOrgModeType() {
		$updatedMimetypes = [
			'org' => 'text/org'
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}

	private function introduceOnlyofficeFormType() {
		$updatedMimetypes = [
			"oform" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document.oform",
			"docxf" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document.docxf",
		];

		return $this->scheduleMimeTypeUpdateJob($updatedMimetypes);
	}


	/**
	 * Fix mime types
	 */
	public function run(IOutput $out) {
		$ocVersionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0');

		// NOTE TO DEVELOPERS: when adding new mime types, please make sure to
		// add a version comparison to avoid doing it every time

		if (version_compare($ocVersionFromBeforeUpdate, '12.0.0.14', '<') && $this->introduceImageTypes()) {
			$out->info('Fixed image mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '12.0.0.13', '<') && $this->introduceWindowsProgramTypes()) {
			$out->info('Fixed windows program mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '13.0.0.0', '<') && $this->introduceLocationTypes()) {
			$out->info('Fixed geospatial mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '13.0.0.3', '<') && $this->introduceInternetShortcutTypes()) {
			$out->info('Fixed internet-shortcut mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '13.0.0.6', '<') && $this->introduceStreamingTypes()) {
			$out->info('Fixed streaming mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '14.0.0.8', '<') && $this->introduceVisioTypes()) {
			$out->info('Fixed visio mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '14.0.0.10', '<') && $this->introduceComicbookTypes()) {
			$out->info('Fixed comicbook mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '20.0.0.5', '<') && $this->introduceOpenDocumentTemplates()) {
			$out->info('Fixed OpenDocument template mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '21.0.0.7', '<') && $this->introduceOrgModeType()) {
			$out->info('Fixed orgmode mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '23.0.0.2', '<') && $this->introduceFlatOpenDocumentType()) {
			$out->info('Fixed Flat OpenDocument mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '25.0.0.2', '<') && $this->introduceOnlyofficeFormType()) {
			$out->info('Fixed ONLYOFFICE Forms OpenXML mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '26.0.0.1', '<') && $this->introduceAsciidocType()) {
			$out->info('Fixed AsciiDoc mime types');
		}
	}
}
