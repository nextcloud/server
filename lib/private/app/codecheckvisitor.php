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

class CodeCheckVisitor extends NodeVisitorAbstract {

	public function __construct($blackListedClassNames) {
		$this->blackListedClassNames = array_map('strtolower', $blackListedClassNames);
	}

	public $errors = [];

	public function enterNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			if (!is_null($node->extends)) {
				$this->checkBlackList($node->extends->toString(), CodeChecker::CLASS_EXTENDS_NOT_ALLOWED, $node);
			}
			foreach ($node->implements as $implements) {
				$this->checkBlackList($implements->toString(), CodeChecker::CLASS_IMPLEMENTS_NOT_ALLOWED, $node);
			}
		}
		if ($node instanceof Node\Expr\StaticCall) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::STATIC_CALL_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = $i::call();
					 */
				}
			}
		}
		if ($node instanceof Node\Expr\ClassConstFetch) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::CLASS_CONST_FETCH_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = $i::ADMIN_AUTH;
					 */
				}
			}
		}
		if ($node instanceof Node\Expr\New_) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::CLASS_NEW_FETCH_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = new $i;
					 */
				}
			}
		}
	}

	private function checkBlackList($name, $errorCode, Node $node) {
		if (in_array(strtolower($name), $this->blackListedClassNames)) {
			$this->errors[]= [
				'disallowedToken' => $name,
				'errorCode' => $errorCode,
				'line' => $node->getLine(),
				'reason' => $this->buildReason($name, $errorCode)
			];
		}
	}

	private function buildReason($name, $errorCode) {
		static $errorMessages= [
			CodeChecker::CLASS_EXTENDS_NOT_ALLOWED => "used as base class",
			CodeChecker::CLASS_IMPLEMENTS_NOT_ALLOWED => "used as interface",
			CodeChecker::STATIC_CALL_NOT_ALLOWED => "static method call on private class",
			CodeChecker::CLASS_CONST_FETCH_NOT_ALLOWED => "used to fetch a const from",
			CodeChecker::CLASS_NEW_FETCH_NOT_ALLOWED => "is instanciated",
		];

		if (isset($errorMessages[$errorCode])) {
			return $errorMessages[$errorCode];
		}

		return "$name usage not allowed - error: $errorCode";
	}
}
