<?php


namespace Stecman\Component\Symfony\Console\BashCompletion\Completion;

/**
 * Shell Path Completion
 *
 * Defers completion to the calling shell's built-in path completion functionality.
 */
class ShellPathCompletion implements CompletionInterface
{
    /**
     * Exit code set up to trigger path completion in the completion hooks
     * @see Stecman\Component\Symfony\Console\BashCompletion\HookFactory
     */
    const PATH_COMPLETION_EXIT_CODE = 200;

    protected $type;

    protected $commandName;

    protected $targetName;

    public function __construct($commandName, $targetName, $type)
    {
        $this->commandName = $commandName;
        $this->targetName = $targetName;
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getCommandName()
    {
        return $this->commandName;
    }

    /**
     * @inheritdoc
     */
    public function getTargetName()
    {
        return $this->targetName;
    }

    /**
     * Exit with a status code configured to defer completion to the shell
     *
     * @see \Stecman\Component\Symfony\Console\BashCompletion\HookFactory::$hooks
     */
    public function run()
    {
        exit(self::PATH_COMPLETION_EXIT_CODE);
    }
}
