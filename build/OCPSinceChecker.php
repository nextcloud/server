<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
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


require_once(dirname(__DIR__) . '/3rdparty/autoload.php');

/**
 * Class SinceTagCheckVisitor
 *
 * this class checks all methods for the presence of the @since tag
 */
class SinceTagCheckVisitor extends \PhpParser\NodeVisitorAbstract {

	/** @var string */
	protected $namespace = '';
	/** @var string */
	protected $className = '';
	/** @var bool */
	protected $deprecatedClass = false;

	/** @var array */
	protected $errors = [];

	public function enterNode(\PhpParser\Node $node) {
		if($this->deprecatedClass) {
			return;
		}

		if($node instanceof \PhpParser\Node\Stmt\Namespace_) {
			$this->namespace = $node->name;
		}

		if($node instanceof \PhpParser\Node\Stmt\Interface_ or
			$node instanceof \PhpParser\Node\Stmt\Class_) {
			$this->className = $node->name;

			/** @var \PhpParser\Comment\Doc[] $comments */
			$comments = $node->getAttribute('comments');

			if(count($comments) === 0) {
				$this->errors[] = 'PHPDoc is needed for ' . $this->namespace . '\\' . $this->className . '::' . $node->name;
				return;
			}

			$comment = $comments[count($comments) - 1];
			$text = $comment->getText();
			if(strpos($text, '@deprecated') !== false) {
				$this->deprecatedClass = true;
			}

			if($this->deprecatedClass === false && strpos($text, '@since') === false && strpos($text, '@deprecated') === false) {
				$type = $node instanceof \PhpParser\Node\Stmt\Interface_ ? 'interface' : 'class';
				$this->errors[] = '@since or @deprecated tag is needed in PHPDoc for ' . $type . ' ' . $this->namespace . '\\' . $this->className;
				return;
			}
		}

		if($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
			/** @var \PhpParser\Node\Stmt\ClassMethod $node */
			/** @var \PhpParser\Comment\Doc[] $comments */
			$comments = $node->getAttribute('comments');

			if(count($comments) === 0) {
				$this->errors[] = 'PHPDoc is needed for ' . $this->namespace . '\\' . $this->className . '::' . $node->name;
				return;
			}
			$comment = $comments[count($comments) - 1];
			$text = $comment->getText();
			if(strpos($text, '@since') === false && strpos($text, '@deprecated') === false) {
				$this->errors[] = '@since or @deprecated tag is needed in PHPDoc for ' . $this->namespace . '\\' . $this->className . '::' . $node->name;
				return;
			}
		}
	}

	public function getErrors() {
		return $this->errors;
	}
}

echo 'Parsing all files in lib/public for the presence of @since or @deprecated on each method...' . PHP_EOL . PHP_EOL;


$parser = new PhpParser\Parser(new PhpParser\Lexer);

/* iterate over all .php files in lib/public */
$Directory = new RecursiveDirectoryIterator(dirname(__DIR__) . '/lib/public');
$Iterator = new RecursiveIteratorIterator($Directory);
$Regex = new RegexIterator($Iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$errors = [];

foreach($Regex as $file) {
	$stmts = $parser->parse(file_get_contents($file[0]));

	$visitor = new SinceTagCheckVisitor();
	$traverser = new \PhpParser\NodeTraverser();
	$traverser->addVisitor($visitor);
	$traverser->traverse($stmts);

	$errors = array_merge($errors, $visitor->getErrors());
}

if(count($errors)) {
	echo join(PHP_EOL, $errors) . PHP_EOL . PHP_EOL;
	exit(1);
}
