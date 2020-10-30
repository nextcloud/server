<?php

namespace Amp;

class InvalidYieldError extends \Error
{
    /**
     * @param \Generator      $generator
     * @param string          $prefix
     * @param \Throwable|null $previous
     */
    public function __construct(\Generator $generator, string $prefix, \Throwable $previous = null)
    {
        $yielded = $generator->current();
        $prefix .= \sprintf(
            "; %s yielded at key %s",
            \is_object($yielded) ? \get_class($yielded) : \gettype($yielded),
            \var_export($generator->key(), true)
        );

        if (!$generator->valid()) {
            parent::__construct($prefix, 0, $previous);
            return;
        }

        $reflGen = new \ReflectionGenerator($generator);
        $exeGen = $reflGen->getExecutingGenerator();
        if ($isSubgenerator = ($exeGen !== $generator)) {
            $reflGen = new \ReflectionGenerator($exeGen);
        }

        parent::__construct(\sprintf(
            "%s on line %s in %s",
            $prefix,
            $reflGen->getExecutingLine(),
            $reflGen->getExecutingFile()
        ), 0, $previous);
    }
}
