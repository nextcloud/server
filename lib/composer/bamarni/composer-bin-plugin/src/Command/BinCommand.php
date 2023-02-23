<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Command;

use Bamarni\Composer\Bin\Config\Config;
use Bamarni\Composer\Bin\Config\ConfigFactory;
use Bamarni\Composer\Bin\ApplicationFactory\FreshInstanceApplicationFactory;
use Bamarni\Composer\Bin\Input\BinInputFactory;
use Bamarni\Composer\Bin\Logger;
use Bamarni\Composer\Bin\ApplicationFactory\NamespaceApplicationFactory;
use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function chdir;
use function count;
use function file_exists;
use function file_put_contents;
use function getcwd;
use function glob;
use function method_exists;
use function min;
use function mkdir;
use function putenv;
use function sprintf;
use const GLOB_ONLYDIR;

/**
 * @final Will be final in 2.x.
 */
class BinCommand extends BaseCommand
{
    private const ALL_NAMESPACES = 'all';

    private const NAMESPACE_ARG = 'namespace';

    /**
     * @var NamespaceApplicationFactory
     */
    private $applicationFactory;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ?NamespaceApplicationFactory $applicationFactory = null,
        ?Logger $logger = null
    ) {
        parent::__construct('bin');

        $this->applicationFactory = $applicationFactory ?? new FreshInstanceApplicationFactory();
        $this->logger = $logger ?? new Logger(new NullIO());
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run a command inside a bin namespace')
            ->addArgument(
                self::NAMESPACE_ARG,
                InputArgument::REQUIRED
            )
            ->ignoreValidationErrors();
    }

    public function setIO(IOInterface $io): void
    {
        parent::setIO($io);

        $this->logger = new Logger($io);
    }

    public function getIO(): IOInterface
    {
        $io = parent::getIO();

        $this->logger = new Logger($io);

        return $io;
    }

    public function isProxyCommand(): bool
    {
        return true;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Switch to requireComposer() once Composer 2.3 is set as the minimum
        $composer = method_exists($this, 'requireComposer')
            ? $this->requireComposer()
            : $this->getComposer();

        $config = Config::fromComposer($composer);
        $currentWorkingDir = getcwd();

        $this->logger->logDebug(
            sprintf(
                'Current working directory: <comment>%s</comment>',
                $currentWorkingDir
            )
        );

        // Ensures Composer is reset – we are setting some environment variables
        // & co. so a fresh Composer instance is required.
        $this->resetComposers();

        $this->configureBinLinksDir($config);

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument(self::NAMESPACE_ARG);

        $binInput = BinInputFactory::createInput(
            $namespace,
            $input
        );

        return (self::ALL_NAMESPACES !== $namespace)
            ? $this->executeInNamespace(
                $currentWorkingDir,
                $vendorRoot.'/'.$namespace,
                $binInput,
                $output
            )
            : $this->executeAllNamespaces(
                $currentWorkingDir,
                $vendorRoot,
                $binInput,
                $output
            );
    }

    /**
     * @return list<string>
     */
    private static function getBinNamespaces(string $binVendorRoot): array
    {
        return glob($binVendorRoot.'/*', GLOB_ONLYDIR);
    }

    private function executeAllNamespaces(
        string $originalWorkingDir,
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $namespaces = self::getBinNamespaces($binVendorRoot);

        if (count($namespaces) === 0) {
            $this->logger->logStandard('<warning>Could not find any bin namespace.</warning>');

            // Is a valid scenario: the user may not have set up any bin
            // namespace yet
            return 0;
        }

        $exitCode = 0;

        foreach ($namespaces as $namespace) {
            $exitCode += $this->executeInNamespace(
                $originalWorkingDir,
                $namespace,
                $input,
                $output
            );
        }

        return min($exitCode, 255);
    }

    private function executeInNamespace(
        string $originalWorkingDir,
        string $namespace,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->logger->logStandard(
            sprintf(
                'Checking namespace <comment>%s</comment>',
                $namespace
            )
        );

        try {
            self::createNamespaceDirIfDoesNotExist($namespace);
        } catch (CouldNotCreateNamespaceDir $exception) {
            $this->logger->logStandard(
                sprintf(
                    '<warning>%s</warning>',
                    $exception->getMessage()
                )
            );

            return 1;
        }

        // Use a new application: this avoids a variety of issues:
        // - A command may be added in a namespace which may cause side effects
        //   when executed in another namespace afterwards (since it is the same
        //   process).
        // - Different plugins may be registered in the namespace in which case
        //   an already executed application will not pick that up.
        $namespaceApplication = $this->applicationFactory->create(
            $this->getApplication()
        );

        // It is important to clean up the state either for follow-up plugins
        // or for example the execution in the next namespace.
        $cleanUp = function () use ($originalWorkingDir): void {
            $this->chdir($originalWorkingDir);
            $this->resetComposers();
        };

        $this->chdir($namespace);

        $this->ensureComposerFileExists();

        $namespaceInput = BinInputFactory::createNamespaceInput($input);

        $this->logger->logDebug(
            sprintf(
                'Running <info>`@composer %s`</info>.',
                $namespaceInput->__toString()
            )
        );

        try {
            $exitCode = $namespaceApplication->doRun($namespaceInput, $output);
        } catch (Throwable $executionFailed) {
            // Ensure we do the cleanup even in case of failure
            $cleanUp();

            throw $executionFailed;
        }

        $cleanUp();

        return $exitCode;
    }

    /**
     * @throws CouldNotCreateNamespaceDir
     */
    private static function createNamespaceDirIfDoesNotExist(string $namespace): void
    {
        if (file_exists($namespace)) {
            return;
        }

        $mkdirResult = mkdir($namespace, 0777, true);

        if (!$mkdirResult && !is_dir($namespace)) {
            throw CouldNotCreateNamespaceDir::forNamespace($namespace);
        }
    }

    private function configureBinLinksDir(Config $config): void
    {
        if (!$config->binLinksAreEnabled()) {
            return;
        }

        $binDir = ConfigFactory::createConfig()->get('bin-dir');

        putenv(
            sprintf(
                'COMPOSER_BIN_DIR=%s',
                $binDir
            )
        );

        $this->logger->logDebug(
            sprintf(
                'Configuring bin directory to <comment>%s</comment>.',
                $binDir
            )
        );
    }

    private function ensureComposerFileExists(): void
    {
        // Some plugins require access to the Composer file e.g. Symfony Flex
        $namespaceComposerFile = Factory::getComposerFile();

        if (file_exists($namespaceComposerFile)) {
            return;
        }

        file_put_contents($namespaceComposerFile, '{}');

        $this->logger->logDebug(
            sprintf(
                'Created the file <comment>%s</comment>.',
                $namespaceComposerFile
            )
        );
    }

    private function resetComposers(): void
    {
        $this->getApplication()->resetComposer();

        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof BaseCommand) {
                $command->resetComposer();
            }
        }
    }

    private function chdir(string $dir): void
    {
        chdir($dir);

        $this->logger->logDebug(
            sprintf(
                'Changed current directory to <comment>%s</comment>.',
                $dir
            )
        );
    }
}
