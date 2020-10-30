<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tokenizer;

use PhpCsFixer\Utils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Collection of Transformer classes.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class Transformers
{
    /**
     * The registered transformers.
     *
     * @var TransformerInterface[]
     */
    private $items = [];

    /**
     * Register built in Transformers.
     */
    private function __construct()
    {
        $this->registerBuiltInTransformers();

        usort($this->items, static function (TransformerInterface $a, TransformerInterface $b) {
            return Utils::cmpInt($b->getPriority(), $a->getPriority());
        });
    }

    /**
     * @return Transformers
     */
    public static function create()
    {
        static $instance = null;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Transform given Tokens collection through all Transformer classes.
     *
     * @param Tokens $tokens Tokens collection
     */
    public function transform(Tokens $tokens)
    {
        foreach ($this->items as $transformer) {
            foreach ($tokens as $index => $token) {
                $transformer->process($tokens, $token, $index);
            }
        }
    }

    /**
     * @param TransformerInterface $transformer Transformer
     */
    private function registerTransformer(TransformerInterface $transformer)
    {
        if (\PHP_VERSION_ID >= $transformer->getRequiredPhpVersionId()) {
            $this->items[] = $transformer;
        }
    }

    private function registerBuiltInTransformers()
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        foreach ($this->findBuiltInTransformers() as $transformer) {
            $this->registerTransformer($transformer);
        }
    }

    /**
     * @return \Generator|TransformerInterface[]
     */
    private function findBuiltInTransformers()
    {
        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in(__DIR__.'/Transformer') as $file) {
            $relativeNamespace = $file->getRelativePath();
            $class = __NAMESPACE__.'\\Transformer\\'.($relativeNamespace ? $relativeNamespace.'\\' : '').$file->getBasename('.php');

            yield new $class();
        }
    }
}
