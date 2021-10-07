<?php

namespace Bamarni\Composer\Bin;

use Composer\Console\Application as ComposerApplication;
use Composer\Factory;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;

class BinCommand extends BaseCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('bin')
            ->setDescription('Run a command inside a bin namespace')
            ->setDefinition([
                new InputArgument('namespace', InputArgument::REQUIRED),
                new InputArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY),
            ])
            ->ignoreValidationErrors()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = new Config($this->getComposer());
        $this->resetComposers($application = $this->getApplication());
        /** @var ComposerApplication $application */

        if ($config->binLinksAreEnabled()) {
            putenv('COMPOSER_BIN_DIR='.$this->createConfig()->get('bin-dir'));
        }

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument('namespace');
        $input = new StringInput(preg_replace(
            sprintf('/bin\s+(--ansi\s)?%s(\s.+)/', preg_quote($namespace, '/')),
            '$1$2',
            (string) $input,
            1
        ));

        return ('all' !== $namespace)
            ? $this->executeInNamespace($application, $vendorRoot.'/'.$namespace, $input, $output)
            : $this->executeAllNamespaces($application, $vendorRoot, $input, $output)
        ;
    }

    /**
     * @param ComposerApplication $application
     * @param string              $binVendorRoot
     * @param InputInterface      $input
     * @param OutputInterface     $output
     *
     * @return int Exit code
     */
    private function executeAllNamespaces(ComposerApplication $application, $binVendorRoot, InputInterface $input, OutputInterface $output)
    {
        $binRoots = glob($binVendorRoot.'/*', GLOB_ONLYDIR);
        if (empty($binRoots)) {
            $this->getIO()->writeError('<warning>Couldn\'t find any bin namespace.</warning>');

            return 0;   // Is a valid scenario: the user may not have setup any bin namespace yet
        }

        $originalWorkingDir = getcwd();
        $exitCode = 0;
        foreach ($binRoots as $binRoot) {
            $exitCode += $this->executeInNamespace($application, $binRoot, $input, $output);

            chdir($originalWorkingDir);
            $this->resetComposers($application);
        }

        return min($exitCode, 255);
    }

    /**
     * @param ComposerApplication $application
     * @param string              $namespace
     * @param InputInterface      $input
     * @param OutputInterface     $output
     *
     * @return int Exit code
     */
    private function executeInNamespace(ComposerApplication $application, $namespace, InputInterface $input, OutputInterface $output)
    {
        if (!file_exists($namespace)) {
            mkdir($namespace, 0777, true);
        }

        $this->chdir($namespace);

        // some plugins require access to composer file e.g. Symfony Flex
        if (!file_exists(Factory::getComposerFile())) {
            file_put_contents(Factory::getComposerFile(), '{}');
        }
        
        $input = new StringInput((string) $input . ' --working-dir=.');

        $this->getIO()->writeError('<info>Run with <comment>' . $input->__toString() . '</comment></info>', true, IOInterface::VERBOSE);
        
        return $application->doRun($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    public function isProxyCommand()
    {
        return true;
    }

    /**
     * Resets all Composer references in the application.
     *
     * @param ComposerApplication $application
     */
    private function resetComposers(ComposerApplication $application)
    {
        $application->resetComposer();
        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof BaseCommand) {
                $command->resetComposer();
            }
        }
    }

    private function chdir($dir)
    {
        chdir($dir);
        $this->getIO()->writeError('<info>Changed current directory to ' . $dir . '</info>', true, IOInterface::VERBOSE);
    }

    private function createConfig()
    {
        $config = Factory::createConfig();

        $file = new JsonFile(Factory::getComposerFile());
        if (!$file->exists()) {
            return $config;
        }
        $file->validateSchema(JsonFile::LAX_SCHEMA);

        $config->merge($file->read());

        return $config;
    }
}
