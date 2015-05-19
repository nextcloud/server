<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\App;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

class CodeCheckVisitor extends NodeVisitorAbstract {

	public function __construct($blackListedClassNames) {
		$this->blackListedClassNames = array_map('strtolower', $blackListedClassNames);
	}

	public $errors = [];

	public function enterNode(Node $node) {
		if ($node instanceof Node\Expr\BinaryOp\Equal) {
			$this->errors[]= [
				'disallowedToken' => '==',
				'errorCode' => CodeChecker::OP_OPERATOR_USAGE_DISCOURAGED,
				'line' => $node->getLine(),
				'reason' => $this->buildReason('==', CodeChecker::OP_OPERATOR_USAGE_DISCOURAGED)
			];
		}
		if ($node instanceof Node\Expr\BinaryOp\NotEqual) {
			$this->errors[]= [
				'disallowedToken' => '!=',
				'errorCode' => CodeChecker::OP_OPERATOR_USAGE_DISCOURAGED,
				'line' => $node->getLine(),
				'reason' => $this->buildReason('!=', CodeChecker::OP_OPERATOR_USAGE_DISCOURAGED)
			];
		}
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
			CodeChecker::OP_OPERATOR_USAGE_DISCOURAGED => "is discouraged"
		];

		if (isset($errorMessages[$errorCode])) {
			return $errorMessages[$errorCode];
		}

		return "$name usage not allowed - error: $errorCode";
	}
}
