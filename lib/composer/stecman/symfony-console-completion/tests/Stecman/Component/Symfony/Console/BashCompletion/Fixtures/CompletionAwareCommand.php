<?php


use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CompletionAwareCommand extends Command implements CompletionAwareInterface
{
    protected function configure()
    {
        $this->setName('completion-aware')
            ->addOption('option-with-suggestions', null, InputOption::VALUE_REQUIRED)
            ->addOption('option-without-suggestions', null, InputOption::VALUE_REQUIRED)
            ->addArgument('argument-without-suggestions')
            ->addArgument('argument-with-suggestions')
            ->addArgument('array-argument-with-suggestions', InputArgument::IS_ARRAY)
        ;
    }

    /**
     * Returns possible option values.
     *
     * @param string            $optionName Option name.
     * @param CompletionContext $context    Completion context.
     *
     * @return array
     */
    public function completeOptionValues($optionName, CompletionContext $context)
    {
        if ($optionName === 'option-with-suggestions') {
            $suggestions = array('one-opt', 'two-opt');

            if ('one' === $context->getCurrentWord()) {
                $suggestions[] = 'one-opt-context';
            }

            return $suggestions;
        }

        return array();
    }

    /**
     * Returns possible argument values.
     *
     * @param string            $argumentName Argument name.
     * @param CompletionContext $context      Completion context.
     *
     * @return array
     */
    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if (in_array($argumentName, array('argument-with-suggestions', 'array-argument-with-suggestions'))) {
            $suggestions = array('one-arg', 'two-arg');

            if ('one' === $context->getCurrentWord()) {
                $suggestions[] = 'one-arg-context';
            }

            return $suggestions;
        }

        return array();
    }

}
