<?php
namespace Psalm\Examples\Template;

use PhpParser;
use Psalm;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Storage\MethodStorage;
use Psalm\Type;

class TemplateAnalyzer extends Psalm\Internal\Analyzer\FileAnalyzer
{
    const VIEW_CLASS = 'Your\\View\\Class';

    public function analyze(?Context $file_context = null, bool $preserve_analyzers = false, ?Context $global_context = null): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $stmts = $codebase->getStatementsForFile($this->file_path);

        if ($stmts === []) {
            return;
        }

        $first_stmt = $stmts[0];

        $this_params = null;

        if (($first_stmt instanceof PhpParser\Node\Stmt\Nop) && ($doc_comment = $first_stmt->getDocComment())) {
            $comment_block = DocComment::parsePreservingLength($doc_comment);

            if (isset($comment_block->tags['variablesfrom'])) {
                $variables_from = trim($comment_block->tags['variablesfrom'][0]);

                $first_line_regex = '/([A-Za-z\\\0-9]+::[a-z_A-Z]+)(\s+weak)?/';

                $matches = [];

                if (!preg_match($first_line_regex, $variables_from, $matches)) {
                    throw new \InvalidArgumentException('Could not interpret doc comment correctly');
                }

                /** @psalm-suppress ArgumentTypeCoercion */
                $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $matches[1]));

                $this_params = $this->checkMethod($method_id, $first_stmt, $codebase);

                if ($this_params === false) {
                    return;
                }

                $this_params->vars_in_scope['$this'] = new Type\Union([
                    new Type\Atomic\TNamedObject(self::VIEW_CLASS),
                ]);
            }
        }

        if (!$this_params) {
            $this_params = new Context();
            $this_params->check_variables = false;
            $this_params->self = self::VIEW_CLASS;
            $this_params->vars_in_scope['$this'] = new Type\Union([
                new Type\Atomic\TNamedObject(self::VIEW_CLASS),
            ]);
        }

        $this->checkWithViewClass($this_params, $stmts);
    }

    /**
     * @return Context|false
     */
    private function checkMethod(\Psalm\Internal\MethodIdentifier $method_id, PhpParser\Node $stmt, Codebase $codebase)
    {
        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $this,
            $method_id->fq_class_name,
            new CodeLocation($this, $stmt),
            null,
            null,
            [],
            true
        ) === false
        ) {
            return false;
        }

        $this_context = new Context();
        $this_context->self = $method_id->fq_class_name;

        $class_storage = $codebase->classlike_storage_provider->get($method_id->fq_class_name);

        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic\TNamedObject($class_storage->name)]);

        $this->project_analyzer->getMethodMutations(
            new \Psalm\Internal\MethodIdentifier($method_id->fq_class_name, '__construct'),
            $this_context,
            $this->getRootFilePath(),
            $this->getRootFileName()
        );

        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic\TNamedObject($class_storage->name)]);

        // check the actual method
        $this->project_analyzer->getMethodMutations(
            $method_id,
            $this_context,
            $this->getRootFilePath(),
            $this->getRootFileName()
        );

        $view_context = new Context();
        $view_context->self = strtolower(self::VIEW_CLASS);

        // add all $this-> vars to scope
        foreach ($this_context->vars_possibly_in_scope as $var => $_) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = Type::getMixed();
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = $type;
        }

        return $view_context;
    }

    /**
     * @param  array<PhpParser\Node\Stmt> $stmts
     *
     */
    protected function checkWithViewClass(Context $context, array $stmts): void
    {
        $pseudo_method_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } else {
                $pseudo_method_stmts[] = $stmt;
            }
        }

        $pseudo_method_name = preg_replace('/[^a-zA-Z0-9_]+/', '_', $this->file_name);

        $class_method = new PhpParser\Node\Stmt\ClassMethod($pseudo_method_name, ['stmts' => []]);

        $class = new PhpParser\Node\Stmt\Class_(self::VIEW_CLASS);

        $class_analyzer = new ClassAnalyzer($class, $this, self::VIEW_CLASS);

        $view_method_analyzer = new MethodAnalyzer($class_method, $class_analyzer, new MethodStorage());

        if (!$context->check_variables) {
            $view_method_analyzer->addSuppressedIssue('UndefinedVariable');
        }

        $statements_source = new StatementsAnalyzer(
            $view_method_analyzer,
            new \Psalm\Internal\Provider\NodeDataProvider()
        );

        $statements_source->analyze($pseudo_method_stmts, $context);
    }
}
