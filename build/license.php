<?php
/**
 * @author Thomas Müller
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
class Licenses
{
	protected $paths = [];
	protected $mailMap = [];
	public $authors = [];

	public function __construct() {
		$this->licenseText = <<<EOD
/**
@AUTHORS@
 *
 * @copyright Copyright (c) @YEAR@, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
EOD;
		$this->licenseText = str_replace('@YEAR@', date("Y"), $this->licenseText);
	}

	/**
	 * @param string|string[] $folder
	 * @param string|bool $gitRoot
	 */
	function exec($folder, $gitRoot = false) {

		if (is_array($folder)) {
			foreach($folder as $f) {
				$this->exec($f, $gitRoot);
			}
			return;
		}

		if ($gitRoot !== false && substr($gitRoot, -1) !== '/') {
			$gitRoot .= '/';
		}

		if (is_file($folder)) {
			$this->handleFile($folder, $gitRoot);
			return;
		}

		$excludes = array_map(function($item) use ($folder) {
			return $folder . '/' . $item;
		}, ['vendor', '3rdparty', '.git', 'l10n', 'templates']);

		$iterator = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveCallbackFilterIterator($iterator, function($item) use ($folder, $excludes){
			/** @var SplFileInfo $item */
			foreach($excludes as $exclude) {
				if (substr($item->getPath(), 0, strlen($exclude)) === $exclude) {
					return false;
				}
			}
			return true;
		});
		$iterator = new RecursiveIteratorIterator($iterator);
		$iterator = new RegexIterator($iterator, '/^.+\.php$/i');

		foreach ($iterator as $file) {
			/** @var SplFileInfo $file */
			$this->handleFile($file, $gitRoot);
		}
	}

	function writeAuthorsFile() {
		ksort($this->authors);
		$template = "ownCloud is written by:
@AUTHORS@

With help from many libraries and frameworks including:
	Open Collaboration Services
	SabreDAV
	jQuery
	…
";
		$authors = implode(PHP_EOL, array_map(function($author){
			return " - ".$author;
		}, $this->authors));
		$template = str_replace('@AUTHORS@', $authors, $template);
		file_put_contents(__DIR__.'/../AUTHORS', $template);
	}

	function handleFile($path, $gitRoot) {
		$source = file_get_contents($path);
		if ($this->isMITLicensed($source)) {
			echo "MIT licensed file: $path" . PHP_EOL;
			return;
		}
		$source = $this->eatOldLicense($source);
		$authors = $this->getAuthors($path, $gitRoot);
		$license = str_replace('@AUTHORS@', $authors, $this->licenseText);

		$source = "<?php" . PHP_EOL . $license . PHP_EOL . $source;
		file_put_contents($path,$source);
		echo "License updated: $path" . PHP_EOL;
	}

	/**
	 * @param string $source
	 */
	private function isMITLicensed($source) {
		$lines = explode(PHP_EOL, $source);
		while(!empty($lines)) {
			$line = $lines[0];
			array_shift($lines);
			if (strpos($line, 'The MIT License') !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $source
	 * @return string
	 */
	private function eatOldLicense($source) {
		$lines = explode(PHP_EOL, $source);
		while(!empty($lines)) {
			$line = $lines[0];
			if (strpos($line, '<?php') !== false) {
				array_shift($lines);
				continue;
			}
			if (strpos($line, '/**') !== false) {
				array_shift($lines);
				continue;
			}
			if (strpos($line, '*/') !== false ) {
				array_shift($lines);
				break;
			}
			if (strpos($line, '*') !== false) {
				array_shift($lines);
				continue;
			}
			if (trim($line) === '') {
				array_shift($lines);
				continue;
			}
			break;
		}

		return implode(PHP_EOL, $lines);
	}

	private function getAuthors($file, $gitRoot) {
		// only add authors that changed code and not the license header
		$licenseHeaderEndsAtLine = trim(shell_exec("grep -n '*/' $file | head -n 1 | cut -d ':' -f 1"));
		$buildDir = getcwd();
		if ($gitRoot) {
			chdir($gitRoot);
			$file = substr($file, strlen($gitRoot));
		}
		$out = shell_exec("git blame --line-porcelain -L $licenseHeaderEndsAtLine, $file | sed -n 's/^author //p;s/^author-mail //p' | sed 'N;s/\\n/ /' | sort -f | uniq");
		if ($gitRoot) {
			chdir($buildDir);
		}
		$authors = explode(PHP_EOL, $out);

		$authors = array_filter($authors, function($author) {
			return !in_array($author, [
				'',
				'Not Committed Yet <not.committed.yet>',
				'Jenkins for ownCloud <owncloud-bot@tmit.eu>',
				'Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>',
			]);
		});

		if ($gitRoot) {
			$authors = array_map([$this, 'checkCoreMailMap'], $authors);
			$authors = array_unique($authors);
		}

		$authors = array_map(function($author){
			$this->authors[$author] = $author;
			return " * @author $author";
		}, $authors);
		return implode(PHP_EOL, $authors);
	}

	private function checkCoreMailMap($author) {
		if (empty($this->mailMap)) {
			$content = file_get_contents(__DIR__ . '/../.mailmap');
			$entries = explode("\n", $content);
			foreach ($entries as $entry) {
				if (strpos($entry, '> ') === false) {
					$this->mailMap[$entry] = $entry;
				} else {
					list($use, $actual) = explode('> ', $entry);
					$this->mailMap[$actual] = $use . '>';
				}
			}
		}

		if (isset($this->mailMap[$author])) {
			return $this->mailMap[$author];
		}
		return $author;
	}
}

$licenses = new Licenses;
if (isset($argv[1])) {
	$licenses->exec($argv[1], isset($argv[2]) ? $argv[1] : false);
} else {
	$licenses->exec([
		'../apps/comments',
		'../apps/dav',
		'../apps/encryption',
		'../apps/federatedfilesharing',
		'../apps/federation',
		'../apps/files',
		'../apps/files_external',
		'../apps/files_sharing',
		'../apps/files_trashbin',
		'../apps/files_versions',
		'../apps/provisioning_api',
		'../apps/systemtags',
		'../apps/testing',
		'../apps/updatenotification',
		'../apps/user_ldap',
		'../core',
		'../lib',
		'../ocs',
		'../settings',
		'../console.php',
		'../cron.php',
		'../index.php',
		'../public.php',
		'../remote.php',
		'../status.php',
		'../version.php',
	]);
	$licenses->writeAuthorsFile();
}
