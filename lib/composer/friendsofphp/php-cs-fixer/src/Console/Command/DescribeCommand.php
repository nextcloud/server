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

namespace PhpCsFixer\Console\Command;

use PhpCsFixer\Differ\DiffConsoleFormatter;
use PhpCsFixer\Differ\FullDiffer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\AliasedFixerOption;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\DeprecatedFixerOption;
use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use PhpCsFixer\FixerDefinition\FileSpecificCodeSampleInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSampleInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\Preg;
use PhpCsFixer\RuleSet;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
use PhpCsFixer\WordMatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @internal
 */
final class DescribeCommand extends Command
{
    protected static $defaultName = 'describe';

    /**
     * @var string[]
     */
    private $setNames;

    /**
     * @var FixerFactory
     */
    private $fixerFactory;

    /**
     * @var array<string, FixerInterface>
     */
    private $fixers;

    public function __construct(FixerFactory $fixerFactory = null)
    {
        parent::__construct();

        if (null === $fixerFactory) {
            $fixerFactory = new FixerFactory();
            $fixerFactory->registerBuiltInFixers();
        }

        $this->fixerFactory = $fixerFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(
                [
                    new InputArgument('name', InputArgument::REQUIRED, 'Name of rule / set.'),
                ]
            )
            ->setDescription('Describe rule / ruleset.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            if ('@' === $name[0]) {
                $this->describeSet($output, $name);

                return 0;
            }

            $this->describeRule($output, $name);
        } catch (DescribeNameNotFoundException $e) {
            $matcher = new WordMatcher(
                'set' === $e->getType() ? $this->getSetNames() : array_keys($this->getFixers())
            );

            $alternative = $matcher->match($name);

            $this->describeList($output, $e->getType());

            throw new \InvalidArgumentException(sprintf(
                '%s "%s" not found.%s',
                ucfirst($e->getType()),
                $name,
                null === $alternative ? '' : ' Did you mean "'.$alternative.'"?'
            ));
        }

        return 0;
    }

    /**
     * @param string $name
     */
    private function describeRule(OutputInterface $output, $name)
    {
        $fixers = $this->getFixers();

        if (!isset($fixers[$name])) {
            throw new DescribeNameNotFoundException($name, 'rule');
        }

        /** @var FixerInterface $fixer */
        $fixer = $fixers[$name];
        if ($fixer instanceof DefinedFixerInterface) {
            $definition = $fixer->getDefinition();
        } else {
            $definition = new FixerDefinition('Description is not available.', []);
        }

        $description = $definition->getSummary();
        if ($fixer instanceof DeprecatedFixerInterface) {
            $successors = $fixer->getSuccessorsNames();
            $message = [] === $successors
                ? 'will be removed on next major version'
                : sprintf('use %s instead', Utils::naturalLanguageJoinWithBackticks($successors));
            $message = Preg::replace('/(`.+?`)/', '<info>$1</info>', $message);
            $description .= sprintf(' <error>DEPRECATED</error>: %s.', $message);
        }

        $output->writeln(sprintf('<info>Description of</info> %s <info>rule</info>.', $name));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('Fixer class: <comment>%s</comment>.', \get_class($fixer)));
        }

        $output->writeln($description);
        if ($definition->getDescription()) {
            $output->writeln($definition->getDescription());
        }
        $output->writeln('');

        if ($fixer->isRisky()) {
            $output->writeln('<error>Fixer applying this rule is risky.</error>');

            if ($definition->getRiskyDescription()) {
                $output->writeln($definition->getRiskyDescription());
            }

            $output->writeln('');
        }

        if ($fixer instanceof ConfigurationDefinitionFixerInterface) {
            $configurationDefinition = $fixer->getConfigurationDefinition();
            $options = $configurationDefinition->getOptions();

            $output->writeln(sprintf('Fixer is configurable using following option%s:', 1 === \count($options) ? '' : 's'));

            foreach ($options as $option) {
                $line = '* <info>'.OutputFormatter::escape($option->getName()).'</info>';

                $allowed = HelpCommand::getDisplayableAllowedValues($option);
                if (null !== $allowed) {
                    foreach ($allowed as &$value) {
                        if ($value instanceof AllowedValueSubset) {
                            $value = 'a subset of <comment>'.HelpCommand::toString($value->getAllowedValues()).'</comment>';
                        } else {
                            $value = '<comment>'.HelpCommand::toString($value).'</comment>';
                        }
                    }
                } else {
                    $allowed = array_map(
                        static function ($type) {
                            return '<comment>'.$type.'</comment>';
                        },
                        $option->getAllowedTypes()
                    );
                }

                if (null !== $allowed) {
                    $line .= ' ('.implode(', ', $allowed).')';
                }

                $description = Preg::replace('/(`.+?`)/', '<info>$1</info>', OutputFormatter::escape($option->getDescription()));
                $line .= ': '.lcfirst(Preg::replace('/\.$/', '', $description)).'; ';
                if ($option->hasDefault()) {
                    $line .= sprintf(
                        'defaults to <comment>%s</comment>',
                        HelpCommand::toString($option->getDefault())
                    );
                } else {
                    $line .= '<comment>required</comment>';
                }

                if ($option instanceof DeprecatedFixerOption) {
                    $line .= '. <error>DEPRECATED</error>: '.Preg::replace(
                        '/(`.+?`)/',
                        '<info>$1</info>',
                        OutputFormatter::escape(lcfirst($option->getDeprecationMessage()))
                    );
                }
                if ($option instanceof AliasedFixerOption) {
                    $line .= '; <error>DEPRECATED</error> alias: <comment>'.$option->getAlias().'</comment>';
                }

                $output->writeln($line);
            }

            $output->writeln('');
        } elseif ($fixer instanceof ConfigurableFixerInterface) {
            $output->writeln('<comment>Fixer is configurable.</comment>');

            if ($definition->getConfigurationDescription()) {
                $output->writeln($definition->getConfigurationDescription());
            }

            if ($definition->getDefaultConfiguration()) {
                $output->writeln(sprintf('Default configuration: <comment>%s</comment>.', HelpCommand::toString($definition->getDefaultConfiguration())));
            }

            $output->writeln('');
        }

        /** @var CodeSampleInterface[] $codeSamples */
        $codeSamples = array_filter($definition->getCodeSamples(), static function (CodeSampleInterface $codeSample) {
            if ($codeSample instanceof VersionSpecificCodeSampleInterface) {
                return $codeSample->isSuitableFor(\PHP_VERSION_ID);
            }

            return true;
        });

        if (!\count($codeSamples)) {
            $output->writeln([
                'Fixing examples can not be demonstrated on the current PHP version.',
                '',
            ]);
        } else {
            $output->writeln('Fixing examples:');

            $differ = new FullDiffer();
            $diffFormatter = new DiffConsoleFormatter(
                $output->isDecorated(),
                sprintf(
                    '<comment>   ---------- begin diff ----------</comment>%s%%s%s<comment>   ----------- end diff -----------</comment>',
                    PHP_EOL,
                    PHP_EOL
                )
            );

            foreach ($codeSamples as $index => $codeSample) {
                $old = $codeSample->getCode();
                $tokens = Tokens::fromCode($old);

                $configuration = $codeSample->getConfiguration();

                if ($fixer instanceof ConfigurableFixerInterface) {
                    $fixer->configure(null === $configuration ? [] : $configuration);
                }

                $file = $codeSample instanceof FileSpecificCodeSampleInterface
                    ? $codeSample->getSplFileInfo()
                    : new StdinFileInfo();

                $fixer->fix($file, $tokens);

                $diff = $differ->diff($old, $tokens->generateCode());

                if ($fixer instanceof ConfigurableFixerInterface) {
                    if (null === $configuration) {
                        $output->writeln(sprintf(' * Example #%d. Fixing with the <comment>default</comment> configuration.', $index + 1));
                    } else {
                        $output->writeln(sprintf(' * Example #%d. Fixing with configuration: <comment>%s</comment>.', $index + 1, HelpCommand::toString($codeSample->getConfiguration())));
                    }
                } else {
                    $output->writeln(sprintf(' * Example #%d.', $index + 1));
                }

                $output->writeln($diffFormatter->format($diff, '   %s'));
                $output->writeln('');
            }
        }
    }

    /**
     * @param string $name
     */
    private function describeSet(OutputInterface $output, $name)
    {
        if (!\in_array($name, $this->getSetNames(), true)) {
            throw new DescribeNameNotFoundException($name, 'set');
        }

        $ruleSet = new RuleSet([$name => true]);
        $rules = $ruleSet->getRules();
        ksort($rules);

        $fixers = $this->getFixers();

        $output->writeln(sprintf('<info>Description of</info> %s <info>set.</info>', $name));
        $output->writeln('');

        $help = '';

        foreach ($rules as $rule => $config) {
            $fixer = $fixers[$rule];

            if (!$fixer instanceof DefinedFixerInterface) {
                throw new \RuntimeException(sprintf(
                    'Cannot describe rule %s, the fixer does not implement %s',
                    $rule,
                    DefinedFixerInterface::class
                ));
            }

            $definition = $fixer->getDefinition();
            $help .= sprintf(
                " * <info>%s</info>%s\n   | %s\n%s\n",
                $rule,
                $fixer->isRisky() ? ' <error>risky</error>' : '',
                $definition->getSummary(),
                true !== $config ? sprintf("   <comment>| Configuration: %s</comment>\n", HelpCommand::toString($config)) : ''
            );
        }

        $output->write($help);
    }

    /**
     * @return array<string, FixerInterface>
     */
    private function getFixers()
    {
        if (null !== $this->fixers) {
            return $this->fixers;
        }

        $fixers = [];
        foreach ($this->fixerFactory->getFixers() as $fixer) {
            $fixers[$fixer->getName()] = $fixer;
        }

        $this->fixers = $fixers;
        ksort($this->fixers);

        return $this->fixers;
    }

    /**
     * @return string[]
     */
    private function getSetNames()
    {
        if (null !== $this->setNames) {
            return $this->setNames;
        }

        $set = new RuleSet();
        $this->setNames = $set->getSetDefinitionNames();
        sort($this->setNames);

        return $this->setNames;
    }

    /**
     * @param string $type 'rule'|'set'
     */
    private function describeList(OutputInterface $output, $type)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $describe = [
                'set' => $this->getSetNames(),
                'rules' => $this->getFixers(),
            ];
        } elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $describe = 'set' === $type ? ['set' => $this->getSetNames()] : ['rules' => $this->getFixers()];
        } else {
            return;
        }

        /** @var string[] $items */
        foreach ($describe as $list => $items) {
            $output->writeln(sprintf('<comment>Defined %s:</comment>', $list));
            foreach ($items as $name => $item) {
                $output->writeln(sprintf('* <info>%s</info>', \is_string($name) ? $name : $item));
            }
        }
    }
}
