<?php

/**
 * Copyright (c) 2020 Lukas Reschke <lukas@statuscode.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Type\TaintKindGroup;

class AppFrameworkTainter implements AfterFunctionLikeAnalysisInterface {
	public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool {
		if ($event->getStatementsSource()->getFQCLN() === null) {
			return null;
		}
		if (!$event->getCodebase()->classExtendsOrImplements($event->getStatementsSource()->getFQCLN(), \OCP\AppFramework\Controller::class)) {
			return null;
		}
		if (!($event->getStmt() instanceof PhpParser\Node\Stmt\ClassMethod)) {
			return null;
		}
		if (!$event->getStmt()->isPublic() || $event->getStmt()->isMagic()) {
			return null;
		}
		foreach ($event->getStmt()->params as $i => $param) {
			$expr_type = new Psalm\Type\Union([new Psalm\Type\Atomic\TString()]);
			$expr_identifier = (strtolower($event->getStatementsSource()->getFQCLN()) . '::' . strtolower($event->getFunctionlikeStorage()->cased_name) . '#' . ($i + 1));
			$event->getCodebase()->addTaintSource(
				$expr_type,
				$expr_identifier,
				TaintKindGroup::ALL_INPUT,
				new CodeLocation($event->getStatementsSource(), $param)
			);
		}

		return null;
	}
}
