<?php
namespace Psalm\Internal\PluginManager\Command;

use function assert;
use const DIRECTORY_SEPARATOR;
use function getcwd;
use InvalidArgumentException;
use function is_string;
use Psalm\Internal\PluginManager\PluginListFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class DisableCommand extends Command
{
    /** @var PluginListFactory */
    private $plugin_list_factory;

    public function __construct(PluginListFactory $plugin_list_factory)
    {
        $this->plugin_list_factory = $plugin_list_factory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('disable')
            ->setDescription('Disables a named plugin')
            ->addArgument(
                'pluginName',
                InputArgument::REQUIRED,
                'Plugin name (fully qualified class name or composer package name)'
            )
            ->addUsage('vendor/plugin-package-name [-c path/to/psalm.xml]');
        $this->addUsage('\'Plugin\Class\Name\' [-c path/to/psalm.xml]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        $config_file_path = $input->getOption('config');
        if ($config_file_path !== null && !is_string($config_file_path)) {
            throw new \UnexpectedValueException('Config file path should be a string');
        }

        $plugin_list = ($this->plugin_list_factory)($current_dir, $config_file_path);

        $plugin_name = $input->getArgument('pluginName');

        assert(is_string($plugin_name));

        try {
            $plugin_class = $plugin_list->resolvePluginClass($plugin_name);
        } catch (InvalidArgumentException $e) {
            $io->error('Unknown plugin class ' . $plugin_name);

            return 2;
        }

        if (!$plugin_list->isEnabled($plugin_class)) {
            $io->note('Plugin already disabled');

            return 3;
        }

        $plugin_list->disable($plugin_class);
        $io->success('Plugin disabled');

        return 0;
    }
}
