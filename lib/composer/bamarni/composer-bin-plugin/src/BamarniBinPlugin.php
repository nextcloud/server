<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Bamarni\Composer\Bin\Command\BinCommand;
use Bamarni\Composer\Bin\CommandProvider as BamarniCommandProvider;
use Bamarni\Composer\Bin\Config\Config;
use Bamarni\Composer\Bin\Input\BinInputFactory;
use Composer\Composer;
use Composer\Console\Application;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Console\Command\Command;
use Composer\Plugin\Capability\CommandProvider as ComposerPluginCommandProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function count;
use function in_array;
use function sprintf;

/**
 * @final Will be final in 2.x.
 */
class BamarniBinPlugin implements PluginInterface, Capable, EventSubscriberInterface
{
    private const FORWARDED_COMMANDS = ['install', 'update'];

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Logger
     */
    private $logger;

    private $forwarded = false;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->logger = new Logger($io);
    }

    public function getCapabilities(): array
    {
        return [
            ComposerPluginCommandProvider::class => BamarniCommandProvider::class,
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::COMMAND => 'onCommandEvent',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
        ];
    }

    public function onPostAutoloadDump(Event $event): void
    {
        $this->logger->logDebug('Calling onPostAutoloadDump().');

        $eventIO = $event->getIO();

        if (!($eventIO instanceof ConsoleIO)) {
            return;
        }

        // This is a bit convoluted but Event does not expose the input unlike
        // CommandEvent.
        $publicIO = PublicIO::fromConsoleIO($eventIO);
        $eventInput = $publicIO->getInput();

        $this->onEvent(
            $eventInput->getArgument('command'),
            $eventInput,
            $publicIO->getOutput()
        );
    }

    public function onCommandEvent(CommandEvent $event): bool
    {
        $this->logger->logDebug('Calling onCommandEvent().');

        return $this->onEvent(
            $event->getCommandName(),
            $event->getInput(),
            $event->getOutput()
        );
    }

    private function onEvent(
        string $commandName,
        InputInterface $input,
        OutputInterface $output
    ): bool {
        $config = Config::fromComposer($this->composer);

        $deprecations = $config->getDeprecations();

        if (count($deprecations) > 0) {
            foreach ($deprecations as $deprecation) {
                $this->logger->logStandard($deprecation);
            }
        }

        if ($config->isCommandForwarded()
            && in_array($commandName, self::FORWARDED_COMMANDS, true)
        ) {
            return $this->onForwardedCommand($input, $output);
        }

        return true;
    }

    protected function onForwardedCommand(
        InputInterface $input,
        OutputInterface $output
    ): bool {
        if ($this->forwarded) {
            $this->logger->logDebug('Command already forwarded within the process: skipping.');

            return true;
        }

        $this->forwarded = true;

        $this->logger->logStandard('The command is being forwarded.');
        $this->logger->logDebug(
            sprintf(
                'Original input: <comment>%s</comment>.',
                $input->__toString()
            )
        );

        // Note that the input & output of $io should be the same as the event
        // input & output.
        $io = $this->io;

        $application = new Application();

        $command = new BinCommand();
        $command->setComposer($this->composer);
        $command->setApplication($application);
        $command->setIO($io);

        $forwardedCommandInput = BinInputFactory::createForwardedCommandInput($input);

        try {
            return Command::SUCCESS === $command->run(
                $forwardedCommandInput,
                $output
            );
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
