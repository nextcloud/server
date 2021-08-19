<?php


namespace Stecman\Component\Symfony\Console\BashCompletion;

class EnvironmentCompletionContext extends CompletionContext
{
    /**
     * Set up completion context from the environment variables set by the parent shell
     */
    public function __construct()
    {
        $this->commandLine = getenv('CMDLINE_CONTENTS');
        $this->charIndex = intval(getenv('CMDLINE_CURSOR_INDEX'));

        if ($this->commandLine === false) {
            $message = 'Failed to configure from environment; Environment var CMDLINE_CONTENTS not set.';

            if (getenv('COMP_LINE')) {
                $message .= "\n\nYou appear to be attempting completion using an out-dated hook. If you've just updated,"
                            . " you probably need to reinitialise the completion shell hook by reloading your shell"
                            . " profile or starting a new shell session. If you are using a hard-coded (rather than generated)"
                            . " hook, you will need to update that function with the new environment variable names."
                            . "\n\nSee here for details: https://github.com/stecman/symfony-console-completion/issues/31";
            }

            throw new \RuntimeException($message);
        }
    }

    /**
     * Use the word break characters set by the parent shell.
     *
     * @throws \RuntimeException
     */
    public function useWordBreaksFromEnvironment()
    {
        $breaks = getenv('CMDLINE_WORDBREAKS');

        if (!$breaks) {
            throw new \RuntimeException('Failed to read word breaks from environment; Environment var CMDLINE_WORDBREAKS not set');
        }

        $this->wordBreaks = $breaks;
    }
}
