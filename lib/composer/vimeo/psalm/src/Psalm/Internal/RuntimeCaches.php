<?php

namespace Psalm\Internal;

abstract class RuntimeCaches
{
    public static function clearAll(): void
    {
        \Psalm\IssueBuffer::clearCache();
        \Psalm\Internal\Codebase\Reflection::clearCache();
        \Psalm\Internal\Codebase\Functions::clearCache();
        \Psalm\Internal\Type\TypeTokenizer::clearCache();
        \Psalm\Internal\Provider\FileReferenceProvider::clearCache();
        \Psalm\Internal\FileManipulation\FileManipulationBuffer::clearCache();
        \Psalm\Internal\FileManipulation\ClassDocblockManipulator::clearCache();
        \Psalm\Internal\FileManipulation\FunctionDocblockManipulator::clearCache();
        \Psalm\Internal\FileManipulation\PropertyDocblockManipulator::clearCache();
        \Psalm\Internal\Analyzer\FileAnalyzer::clearCache();
        \Psalm\Internal\Analyzer\FunctionLikeAnalyzer::clearCache();
        \Psalm\Internal\Provider\ClassLikeStorageProvider::deleteAll();
        \Psalm\Internal\Provider\FileStorageProvider::deleteAll();
        \Psalm\Internal\Provider\StatementsProvider::clearLexer();
        \Psalm\Internal\Provider\StatementsProvider::clearParser();
        \Psalm\Internal\Scanner\ParsedDocblock::resetNewlineBetweenAnnotations();
    }
}
