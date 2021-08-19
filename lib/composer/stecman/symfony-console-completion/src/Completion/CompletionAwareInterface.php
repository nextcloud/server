<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Completion;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

interface CompletionAwareInterface
{

    /**
     * Return possible values for the named option
     *
     * @param string $optionName
     * @param CompletionContext $context
     * @return array
     */
    public function completeOptionValues($optionName, CompletionContext $context);

    /**
     * Return possible values for the named argument
     *
     * @param string $argumentName
     * @param CompletionContext $context
     * @return array
     */
    public function completeArgumentValues($argumentName, CompletionContext $context);
}
