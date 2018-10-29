<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

use Phan\PluginV2;
use Phan\PluginV2\AnalyzeNodeCapability;
use Phan\PluginV2\PluginAwareAnalysisVisitor;

class SqlInjectionCheckerPlugin extends PluginV2  implements AnalyzeNodeCapability{
	public static function getAnalyzeNodeVisitorClassName() : string {
		return SqlInjectionCheckerVisitor::class;
	}
}

class SqlInjectionCheckerVisitor extends PluginAwareAnalysisVisitor  {

	private function throwError(string $hint) {
		$this->emit(
			'SqlInjectionChecker',
			'Potential SQL injection detected - ' . $hint,
			[],
			\Phan\Issue::SEVERITY_CRITICAL
		);
	}

	/**
	 * Checks whether the query builder functions are using prepared statements
	 *
	 * @param \ast\Node $node
	 */
	private function checkQueryBuilderParameters(\ast\Node $node) {
		$dangerousFunctions = [
			'eq',
			'neq',
			'lt',
			'lte',
			'gt',
			'gte',
			'like',
			'iLike',
			'notLike',
		];

		$safeFunctions = [
			'createNamedParameter',
			'createPositionalParameter',
			'createParameter',
			'createFunction',
			'func',
		];

		$functionsToSearch = [
			'set',
			'setValue',
		];

		$expandedNode = \Phan\Language\UnionType::fromNode($this->context, $this->code_base, $node);
		$expandedNodeType = (string)$expandedNode->asExpandedTypes($this->code_base);

		if($expandedNodeType === '\OCP\DB\QueryBuilder\IQueryBuilder') {
			/** @var \ast\Node $child */
			foreach($node->children as $child) {
				if(isset($child->kind) && $child->kind === 128) {
					if(isset($child->children)) {
						/** @var \ast\Node $subChild */
						foreach ($child->children as $subChild) {
							// For set actions
							if(isset($node->children['method']) && in_array($node->children['method'], $functionsToSearch, true) && !is_string($subChild)) {
								if(!isset($subChild->children['method']) || !in_array($subChild->children['method'], $safeFunctions, true)) {
									$this->throwError('method: ' . ($subChild->children['method'] ?? 'no child method'));
								}
							}

							if(isset($subChild->children['method'])) {
								// For all "eq" etc. actions
								$method = $subChild->children['method'];
								if(!in_array($method, $dangerousFunctions, true)) {
									return;
								}

								/** @var \ast\Node $functionNode */
								$functionNode = $subChild->children['args'];

								/** @var \ast\Node $secondParameterNode */
								$secondParameterNode = $functionNode->children[1];
								$expandedNode = \Phan\Language\UnionType::fromNode($this->context, $this->code_base, $secondParameterNode);

								// For literals with a plain string or integer inside
								if(isset($secondParameterNode->children['method']) && $secondParameterNode->children['method'] === 'literal') {
									/** @var \ast\Node $functionNode */
									$functionNode = $secondParameterNode->children['args'];

									$expandedNode = \Phan\Language\UnionType::fromNode($this->context, $this->code_base, $functionNode);
									if(isset($functionNode->children[0]) && (is_string($functionNode->children[0]) || is_int($functionNode->children[0]))) {
										return;
									}
								}

								// If it is an IParameter or a pure string no error is thrown
								if((string)$expandedNode !== '\OCP\DB\QueryBuilder\IParameter' && !is_string($secondParameterNode)) {
									$this->throwError('neither a parameter nor a string');
								}
							}
						}
					}
				}
			}
		}
	}

	public function visitMethodCall(\ast\Node $node) {
		$this->checkQueryBuilderParameters($node);
	}

}

return new SqlInjectionCheckerPlugin();
