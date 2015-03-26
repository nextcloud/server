<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\App;

use OC\Hooks\BasicEmitter;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class CodeChecker extends BasicEmitter {

	const CLASS_EXTENDS_NOT_ALLOWED = 1000;
	const CLASS_IMPLEMENTS_NOT_ALLOWED = 1001;
	const STATIC_CALL_NOT_ALLOWED = 1002;
	const CLASS_CONST_FETCH_NOT_ALLOWED = 1003;
	const CLASS_NEW_FETCH_NOT_ALLOWED =  1004;

	/** @var Parser */
	private $parser;

	/** @var string[] */
	private $blackListedClassNames;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
		$this->blackListedClassNames = [
			// classes replaced by the public api
			'OC_API',
			'OC_App',
			'OC_AppConfig',
			'OC_Avatar',
			'OC_BackgroundJob',
			'OC_Config',
			'OC_DB',
			'OC_Files',
			'OC_Helper',
			'OC_Hook',
			'OC_Image',
			'OC_JSON',
			'OC_L10N',
			'OC_Log',
			'OC_Mail',
			'OC_Preferences',
			'OC_Request',
			'OC_Response',
			'OC_Template',
			'OC_User',
			'OC_Util',
		];
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function analyse($appId) {
		$appPath = \OC_App::getAppPath($appId);
		if ($appPath === false) {
			throw new \RuntimeException("No app with given id <$appId> known.");
		}

		return $this->analyseFolder($appPath);
	}

	/**
	 * @param string $folder
	 * @return array
	 */
	public function analyseFolder($folder) {
		$errors = [];

		$excludes = array_map(function($item) use ($folder) {
			return $folder . '/' . $item;
		}, ['vendor', '3rdparty', '.git', 'l10n']);

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
			$this->emit('CodeChecker', 'analyseFileBegin', [$file->getPathname()]);
			$fileErrors = $this->analyseFile($file);
			$this->emit('CodeChecker', 'analyseFileFinished', [$fileErrors]);
			$errors = array_merge($fileErrors, $errors);
		}

		return $errors;
	}


	/**
	 * @param string $file
	 * @return array
	 */
	public function analyseFile($file) {
		$code = file_get_contents($file);
		$statements = $this->parser->parse($code);

		$visitor = new CodeCheckVisitor($this->blackListedClassNames);
		$traverser = new NodeTraverser;
		$traverser->addVisitor($visitor);

		$traverser->traverse($statements);

		return $visitor->errors;
	}
}
