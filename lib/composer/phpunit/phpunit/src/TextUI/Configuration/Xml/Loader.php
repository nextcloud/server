<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\XmlConfiguration;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION;
use function assert;
use function defined;
use function dirname;
use function explode;
use function is_numeric;
use function preg_match;
use function realpath;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use PHPUnit\Runner\TestSuiteSorter;
use PHPUnit\Runner\Version;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\TextUI\Configuration\Constant;
use PHPUnit\TextUI\Configuration\ConstantCollection;
use PHPUnit\TextUI\Configuration\Directory;
use PHPUnit\TextUI\Configuration\DirectoryCollection;
use PHPUnit\TextUI\Configuration\ExtensionBootstrap;
use PHPUnit\TextUI\Configuration\ExtensionBootstrapCollection;
use PHPUnit\TextUI\Configuration\File;
use PHPUnit\TextUI\Configuration\FileCollection;
use PHPUnit\TextUI\Configuration\FilterDirectory;
use PHPUnit\TextUI\Configuration\FilterDirectoryCollection;
use PHPUnit\TextUI\Configuration\Group;
use PHPUnit\TextUI\Configuration\GroupCollection;
use PHPUnit\TextUI\Configuration\IniSetting;
use PHPUnit\TextUI\Configuration\IniSettingCollection;
use PHPUnit\TextUI\Configuration\Php;
use PHPUnit\TextUI\Configuration\Source;
use PHPUnit\TextUI\Configuration\TestDirectory;
use PHPUnit\TextUI\Configuration\TestDirectoryCollection;
use PHPUnit\TextUI\Configuration\TestFile;
use PHPUnit\TextUI\Configuration\TestFileCollection;
use PHPUnit\TextUI\Configuration\TestSuite as TestSuiteConfiguration;
use PHPUnit\TextUI\Configuration\TestSuiteCollection;
use PHPUnit\TextUI\Configuration\Variable;
use PHPUnit\TextUI\Configuration\VariableCollection;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\CodeCoverage;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Clover;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Cobertura;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Crap4j;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Html as CodeCoverageHtml;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Php as CodeCoveragePhp;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Text as CodeCoverageText;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report\Xml as CodeCoverageXml;
use PHPUnit\TextUI\XmlConfiguration\Logging\Junit;
use PHPUnit\TextUI\XmlConfiguration\Logging\Logging;
use PHPUnit\TextUI\XmlConfiguration\Logging\TeamCity;
use PHPUnit\TextUI\XmlConfiguration\Logging\TestDox\Html as TestDoxHtml;
use PHPUnit\TextUI\XmlConfiguration\Logging\TestDox\Text as TestDoxText;
use PHPUnit\Util\VersionComparisonOperator;
use PHPUnit\Util\Xml\Loader as XmlLoader;
use PHPUnit\Util\Xml\XmlException;
use SebastianBergmann\CodeCoverage\Report\Html\Colors;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Loader
{
    /**
     * @throws Exception
     */
    public function load(string $filename): LoadedFromFileConfiguration
    {
        try {
            $document = (new XmlLoader)->loadFile($filename);
        } catch (XmlException $e) {
            throw new Exception(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        $xpath = new DOMXPath($document);

        try {
            $xsdFilename = (new SchemaFinder)->find(Version::series());
        } catch (CannotFindSchemaException $e) {
            throw new Exception(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        $configurationFileRealpath = realpath($filename);

        return new LoadedFromFileConfiguration(
            $configurationFileRealpath,
            (new Validator)->validate($document, $xsdFilename),
            $this->extensions($xpath),
            $this->source($configurationFileRealpath, $xpath),
            $this->codeCoverage($configurationFileRealpath, $xpath),
            $this->groups($xpath),
            $this->logging($configurationFileRealpath, $xpath),
            $this->php($configurationFileRealpath, $xpath),
            $this->phpunit($configurationFileRealpath, $document),
            $this->testSuite($configurationFileRealpath, $xpath),
        );
    }

    private function logging(string $filename, DOMXPath $xpath): Logging
    {
        $junit   = null;
        $element = $this->element($xpath, 'logging/junit');

        if ($element) {
            $junit = new Junit(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $teamCity = null;
        $element  = $this->element($xpath, 'logging/teamcity');

        if ($element) {
            $teamCity = new TeamCity(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $testDoxHtml = null;
        $element     = $this->element($xpath, 'logging/testdoxHtml');

        if ($element) {
            $testDoxHtml = new TestDoxHtml(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $testDoxText = null;
        $element     = $this->element($xpath, 'logging/testdoxText');

        if ($element) {
            $testDoxText = new TestDoxText(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        return new Logging(
            $junit,
            $teamCity,
            $testDoxHtml,
            $testDoxText,
        );
    }

    private function extensions(DOMXPath $xpath): ExtensionBootstrapCollection
    {
        $extensionBootstrappers = [];

        foreach ($xpath->query('extensions/bootstrap') as $bootstrap) {
            assert($bootstrap instanceof DOMElement);

            $parameters = [];

            foreach ($xpath->query('parameter', $bootstrap) as $parameter) {
                assert($parameter instanceof DOMElement);

                $parameters[$parameter->getAttribute('name')] = $parameter->getAttribute('value');
            }

            $extensionBootstrappers[] = new ExtensionBootstrap(
                $bootstrap->getAttribute('class'),
                $parameters,
            );
        }

        return ExtensionBootstrapCollection::fromArray($extensionBootstrappers);
    }

    /**
     * @psalm-return non-empty-string
     */
    private function toAbsolutePath(string $filename, string $path): string
    {
        $path = trim($path);

        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Matches the following on Windows:
        //  - \\NetworkComputer\Path
        //  - \\.\D:
        //  - \\.\c:
        //  - C:\Windows
        //  - C:\windows
        //  - C:/windows
        //  - c:/windows
        if (defined('PHP_WINDOWS_VERSION_BUILD') &&
            !empty($path) &&
            ($path[0] === '\\' || (strlen($path) >= 3 && preg_match('#^[A-Z]:[/\\\]#i', substr($path, 0, 3))))) {
            return $path;
        }

        if (str_contains($path, '://')) {
            return $path;
        }

        return dirname($filename) . DIRECTORY_SEPARATOR . $path;
    }

    private function source(string $filename, DOMXPath $xpath): Source
    {
        $baseline                           = null;
        $restrictDeprecations               = false;
        $restrictNotices                    = false;
        $restrictWarnings                   = false;
        $ignoreSuppressionOfDeprecations    = false;
        $ignoreSuppressionOfPhpDeprecations = false;
        $ignoreSuppressionOfErrors          = false;
        $ignoreSuppressionOfNotices         = false;
        $ignoreSuppressionOfPhpNotices      = false;
        $ignoreSuppressionOfWarnings        = false;
        $ignoreSuppressionOfPhpWarnings     = false;

        $element = $this->element($xpath, 'source');

        if ($element) {
            $baseline = $this->getStringAttribute($element, 'baseline');

            if ($baseline !== null) {
                $baseline = $this->toAbsolutePath($filename, $baseline);
            }

            $restrictDeprecations               = $this->getBooleanAttribute($element, 'restrictDeprecations', false);
            $restrictNotices                    = $this->getBooleanAttribute($element, 'restrictNotices', false);
            $restrictWarnings                   = $this->getBooleanAttribute($element, 'restrictWarnings', false);
            $ignoreSuppressionOfDeprecations    = $this->getBooleanAttribute($element, 'ignoreSuppressionOfDeprecations', false);
            $ignoreSuppressionOfPhpDeprecations = $this->getBooleanAttribute($element, 'ignoreSuppressionOfPhpDeprecations', false);
            $ignoreSuppressionOfErrors          = $this->getBooleanAttribute($element, 'ignoreSuppressionOfErrors', false);
            $ignoreSuppressionOfNotices         = $this->getBooleanAttribute($element, 'ignoreSuppressionOfNotices', false);
            $ignoreSuppressionOfPhpNotices      = $this->getBooleanAttribute($element, 'ignoreSuppressionOfPhpNotices', false);
            $ignoreSuppressionOfWarnings        = $this->getBooleanAttribute($element, 'ignoreSuppressionOfWarnings', false);
            $ignoreSuppressionOfPhpWarnings     = $this->getBooleanAttribute($element, 'ignoreSuppressionOfPhpWarnings', false);
        }

        return new Source(
            $baseline,
            false,
            $this->readFilterDirectories($filename, $xpath, 'source/include/directory'),
            $this->readFilterFiles($filename, $xpath, 'source/include/file'),
            $this->readFilterDirectories($filename, $xpath, 'source/exclude/directory'),
            $this->readFilterFiles($filename, $xpath, 'source/exclude/file'),
            $restrictDeprecations,
            $restrictNotices,
            $restrictWarnings,
            $ignoreSuppressionOfDeprecations,
            $ignoreSuppressionOfPhpDeprecations,
            $ignoreSuppressionOfErrors,
            $ignoreSuppressionOfNotices,
            $ignoreSuppressionOfPhpNotices,
            $ignoreSuppressionOfWarnings,
            $ignoreSuppressionOfPhpWarnings,
        );
    }

    private function codeCoverage(string $filename, DOMXPath $xpath): CodeCoverage
    {
        $cacheDirectory            = null;
        $pathCoverage              = false;
        $includeUncoveredFiles     = true;
        $ignoreDeprecatedCodeUnits = false;
        $disableCodeCoverageIgnore = false;

        $element = $this->element($xpath, 'coverage');

        if ($element) {
            $cacheDirectory = $this->getStringAttribute($element, 'cacheDirectory');

            if ($cacheDirectory !== null) {
                $cacheDirectory = new Directory(
                    $this->toAbsolutePath($filename, $cacheDirectory),
                );
            }

            $pathCoverage = $this->getBooleanAttribute(
                $element,
                'pathCoverage',
                false,
            );

            $includeUncoveredFiles = $this->getBooleanAttribute(
                $element,
                'includeUncoveredFiles',
                true,
            );

            $ignoreDeprecatedCodeUnits = $this->getBooleanAttribute(
                $element,
                'ignoreDeprecatedCodeUnits',
                false,
            );

            $disableCodeCoverageIgnore = $this->getBooleanAttribute(
                $element,
                'disableCodeCoverageIgnore',
                false,
            );
        }

        $clover  = null;
        $element = $this->element($xpath, 'coverage/report/clover');

        if ($element) {
            $clover = new Clover(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $cobertura = null;
        $element   = $this->element($xpath, 'coverage/report/cobertura');

        if ($element) {
            $cobertura = new Cobertura(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $crap4j  = null;
        $element = $this->element($xpath, 'coverage/report/crap4j');

        if ($element) {
            $crap4j = new Crap4j(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
                $this->getIntegerAttribute($element, 'threshold', 30),
            );
        }

        $html    = null;
        $element = $this->element($xpath, 'coverage/report/html');

        if ($element) {
            $defaultColors     = Colors::default();
            $defaultThresholds = Thresholds::default();

            $html = new CodeCoverageHtml(
                new Directory(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputDirectory'),
                    ),
                ),
                $this->getIntegerAttribute($element, 'lowUpperBound', $defaultThresholds->lowUpperBound()),
                $this->getIntegerAttribute($element, 'highLowerBound', $defaultThresholds->highLowerBound()),
                $this->getStringAttributeWithDefault($element, 'colorSuccessLow', $defaultColors->successLow()),
                $this->getStringAttributeWithDefault($element, 'colorSuccessMedium', $defaultColors->successMedium()),
                $this->getStringAttributeWithDefault($element, 'colorSuccessHigh', $defaultColors->successHigh()),
                $this->getStringAttributeWithDefault($element, 'colorWarning', $defaultColors->warning()),
                $this->getStringAttributeWithDefault($element, 'colorDanger', $defaultColors->danger()),
                $this->getStringAttribute($element, 'customCssFile'),
            );
        }

        $php     = null;
        $element = $this->element($xpath, 'coverage/report/php');

        if ($element) {
            $php = new CodeCoveragePhp(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
            );
        }

        $text    = null;
        $element = $this->element($xpath, 'coverage/report/text');

        if ($element) {
            $text = new CodeCoverageText(
                new File(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputFile'),
                    ),
                ),
                $this->getBooleanAttribute($element, 'showUncoveredFiles', false),
                $this->getBooleanAttribute($element, 'showOnlySummary', false),
            );
        }

        $xml     = null;
        $element = $this->element($xpath, 'coverage/report/xml');

        if ($element) {
            $xml = new CodeCoverageXml(
                new Directory(
                    $this->toAbsolutePath(
                        $filename,
                        (string) $this->getStringAttribute($element, 'outputDirectory'),
                    ),
                ),
            );
        }

        return new CodeCoverage(
            $cacheDirectory,
            $this->readFilterDirectories($filename, $xpath, 'coverage/include/directory'),
            $this->readFilterFiles($filename, $xpath, 'coverage/include/file'),
            $this->readFilterDirectories($filename, $xpath, 'coverage/exclude/directory'),
            $this->readFilterFiles($filename, $xpath, 'coverage/exclude/file'),
            $pathCoverage,
            $includeUncoveredFiles,
            $ignoreDeprecatedCodeUnits,
            $disableCodeCoverageIgnore,
            $clover,
            $cobertura,
            $crap4j,
            $html,
            $php,
            $text,
            $xml,
        );
    }

    private function getBoolean(string $value, bool $default): bool
    {
        if (strtolower($value) === 'false') {
            return false;
        }

        if (strtolower($value) === 'true') {
            return true;
        }

        return $default;
    }

    private function getValue(string $value): bool|string
    {
        if (strtolower($value) === 'false') {
            return false;
        }

        if (strtolower($value) === 'true') {
            return true;
        }

        return $value;
    }

    private function readFilterDirectories(string $filename, DOMXPath $xpath, string $query): FilterDirectoryCollection
    {
        $directories = [];

        foreach ($xpath->query($query) as $directoryNode) {
            assert($directoryNode instanceof DOMElement);

            $directoryPath = $directoryNode->textContent;

            if (!$directoryPath) {
                continue;
            }

            $directories[] = new FilterDirectory(
                $this->toAbsolutePath($filename, $directoryPath),
                $directoryNode->hasAttribute('prefix') ? $directoryNode->getAttribute('prefix') : '',
                $directoryNode->hasAttribute('suffix') ? $directoryNode->getAttribute('suffix') : '.php',
            );
        }

        return FilterDirectoryCollection::fromArray($directories);
    }

    private function readFilterFiles(string $filename, DOMXPath $xpath, string $query): FileCollection
    {
        $files = [];

        foreach ($xpath->query($query) as $file) {
            assert($file instanceof DOMNode);

            $filePath = $file->textContent;

            if ($filePath) {
                $files[] = new File($this->toAbsolutePath($filename, $filePath));
            }
        }

        return FileCollection::fromArray($files);
    }

    private function groups(DOMXPath $xpath): Groups
    {
        $include = [];
        $exclude = [];

        foreach ($xpath->query('groups/include/group') as $group) {
            assert($group instanceof DOMNode);

            $include[] = new Group($group->textContent);
        }

        foreach ($xpath->query('groups/exclude/group') as $group) {
            assert($group instanceof DOMNode);

            $exclude[] = new Group($group->textContent);
        }

        return new Groups(
            GroupCollection::fromArray($include),
            GroupCollection::fromArray($exclude),
        );
    }

    private function getBooleanAttribute(DOMElement $element, string $attribute, bool $default): bool
    {
        if (!$element->hasAttribute($attribute)) {
            return $default;
        }

        return $this->getBoolean(
            $element->getAttribute($attribute),
            false,
        );
    }

    private function getIntegerAttribute(DOMElement $element, string $attribute, int $default): int
    {
        if (!$element->hasAttribute($attribute)) {
            return $default;
        }

        return $this->getInteger(
            $element->getAttribute($attribute),
            $default,
        );
    }

    private function getStringAttribute(DOMElement $element, string $attribute): ?string
    {
        if (!$element->hasAttribute($attribute)) {
            return null;
        }

        return $element->getAttribute($attribute);
    }

    private function getStringAttributeWithDefault(DOMElement $element, string $attribute, string $default): string
    {
        if (!$element->hasAttribute($attribute)) {
            return $default;
        }

        return $element->getAttribute($attribute);
    }

    private function getInteger(string $value, int $default): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function php(string $filename, DOMXPath $xpath): Php
    {
        $includePaths = [];

        foreach ($xpath->query('php/includePath') as $includePath) {
            assert($includePath instanceof DOMNode);

            $path = $includePath->textContent;

            if ($path) {
                $includePaths[] = new Directory($this->toAbsolutePath($filename, $path));
            }
        }

        $iniSettings = [];

        foreach ($xpath->query('php/ini') as $ini) {
            assert($ini instanceof DOMElement);

            $iniSettings[] = new IniSetting(
                $ini->getAttribute('name'),
                $ini->getAttribute('value'),
            );
        }

        $constants = [];

        foreach ($xpath->query('php/const') as $const) {
            assert($const instanceof DOMElement);

            $value = $const->getAttribute('value');

            $constants[] = new Constant(
                $const->getAttribute('name'),
                $this->getValue($value),
            );
        }

        $variables = [
            'var'     => [],
            'env'     => [],
            'post'    => [],
            'get'     => [],
            'cookie'  => [],
            'server'  => [],
            'files'   => [],
            'request' => [],
        ];

        foreach (['var', 'env', 'post', 'get', 'cookie', 'server', 'files', 'request'] as $array) {
            foreach ($xpath->query('php/' . $array) as $var) {
                assert($var instanceof DOMElement);

                $name     = $var->getAttribute('name');
                $value    = $var->getAttribute('value');
                $force    = false;
                $verbatim = false;

                if ($var->hasAttribute('force')) {
                    $force = $this->getBoolean($var->getAttribute('force'), false);
                }

                if ($var->hasAttribute('verbatim')) {
                    $verbatim = $this->getBoolean($var->getAttribute('verbatim'), false);
                }

                if (!$verbatim) {
                    $value = $this->getValue($value);
                }

                $variables[$array][] = new Variable($name, $value, $force);
            }
        }

        return new Php(
            DirectoryCollection::fromArray($includePaths),
            IniSettingCollection::fromArray($iniSettings),
            ConstantCollection::fromArray($constants),
            VariableCollection::fromArray($variables['var']),
            VariableCollection::fromArray($variables['env']),
            VariableCollection::fromArray($variables['post']),
            VariableCollection::fromArray($variables['get']),
            VariableCollection::fromArray($variables['cookie']),
            VariableCollection::fromArray($variables['server']),
            VariableCollection::fromArray($variables['files']),
            VariableCollection::fromArray($variables['request']),
        );
    }

    private function phpunit(string $filename, DOMDocument $document): PHPUnit
    {
        $executionOrder      = TestSuiteSorter::ORDER_DEFAULT;
        $defectsFirst        = false;
        $resolveDependencies = $this->getBooleanAttribute($document->documentElement, 'resolveDependencies', true);

        if ($document->documentElement->hasAttribute('executionOrder')) {
            foreach (explode(',', $document->documentElement->getAttribute('executionOrder')) as $order) {
                switch ($order) {
                    case 'default':
                        $executionOrder      = TestSuiteSorter::ORDER_DEFAULT;
                        $defectsFirst        = false;
                        $resolveDependencies = true;

                        break;

                    case 'depends':
                        $resolveDependencies = true;

                        break;

                    case 'no-depends':
                        $resolveDependencies = false;

                        break;

                    case 'defects':
                        $defectsFirst = true;

                        break;

                    case 'duration':
                        $executionOrder = TestSuiteSorter::ORDER_DURATION;

                        break;

                    case 'random':
                        $executionOrder = TestSuiteSorter::ORDER_RANDOMIZED;

                        break;

                    case 'reverse':
                        $executionOrder = TestSuiteSorter::ORDER_REVERSED;

                        break;

                    case 'size':
                        $executionOrder = TestSuiteSorter::ORDER_SIZE;

                        break;
                }
            }
        }

        $cacheDirectory = $this->getStringAttribute($document->documentElement, 'cacheDirectory');

        if ($cacheDirectory !== null) {
            $cacheDirectory = $this->toAbsolutePath($filename, $cacheDirectory);
        }

        $cacheResultFile = $this->getStringAttribute($document->documentElement, 'cacheResultFile');

        if ($cacheResultFile !== null) {
            $cacheResultFile = $this->toAbsolutePath($filename, $cacheResultFile);
        }

        $bootstrap = $this->getStringAttribute($document->documentElement, 'bootstrap');

        if ($bootstrap !== null) {
            $bootstrap = $this->toAbsolutePath($filename, $bootstrap);
        }

        $extensionsDirectory = $this->getStringAttribute($document->documentElement, 'extensionsDirectory');

        if ($extensionsDirectory !== null) {
            $extensionsDirectory = $this->toAbsolutePath($filename, $extensionsDirectory);
        }

        $backupStaticProperties = false;

        if ($document->documentElement->hasAttribute('backupStaticProperties')) {
            $backupStaticProperties = $this->getBooleanAttribute($document->documentElement, 'backupStaticProperties', false);
        } elseif ($document->documentElement->hasAttribute('backupStaticAttributes')) {
            $backupStaticProperties = $this->getBooleanAttribute($document->documentElement, 'backupStaticAttributes', false);
        }

        $requireCoverageMetadata = false;

        if ($document->documentElement->hasAttribute('requireCoverageMetadata')) {
            $requireCoverageMetadata = $this->getBooleanAttribute($document->documentElement, 'requireCoverageMetadata', false);
        } elseif ($document->documentElement->hasAttribute('forceCoversAnnotation')) {
            $requireCoverageMetadata = $this->getBooleanAttribute($document->documentElement, 'forceCoversAnnotation', false);
        }

        $beStrictAboutCoverageMetadata = false;

        if ($document->documentElement->hasAttribute('beStrictAboutCoverageMetadata')) {
            $beStrictAboutCoverageMetadata = $this->getBooleanAttribute($document->documentElement, 'beStrictAboutCoverageMetadata', false);
        } elseif ($document->documentElement->hasAttribute('forceCoversAnnotation')) {
            $beStrictAboutCoverageMetadata = $this->getBooleanAttribute($document->documentElement, 'beStrictAboutCoversAnnotation', false);
        }

        return new PHPUnit(
            $cacheDirectory,
            $this->getBooleanAttribute($document->documentElement, 'cacheResult', true),
            $cacheResultFile,
            $this->getColumns($document),
            $this->getColors($document),
            $this->getBooleanAttribute($document->documentElement, 'stderr', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnAllIssues', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnIncompleteTests', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnSkippedTests', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnTestsThatTriggerDeprecations', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnPhpunitDeprecations', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnTestsThatTriggerErrors', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnTestsThatTriggerNotices', false),
            $this->getBooleanAttribute($document->documentElement, 'displayDetailsOnTestsThatTriggerWarnings', false),
            $this->getBooleanAttribute($document->documentElement, 'reverseDefectList', false),
            $requireCoverageMetadata,
            $bootstrap,
            $this->getBooleanAttribute($document->documentElement, 'processIsolation', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnAllIssues', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnDeprecation', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnPhpunitDeprecation', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnPhpunitWarning', true),
            $this->getBooleanAttribute($document->documentElement, 'failOnEmptyTestSuite', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnIncomplete', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnNotice', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnRisky', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnSkipped', false),
            $this->getBooleanAttribute($document->documentElement, 'failOnWarning', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnDefect', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnDeprecation', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnError', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnFailure', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnIncomplete', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnNotice', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnRisky', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnSkipped', false),
            $this->getBooleanAttribute($document->documentElement, 'stopOnWarning', false),
            $extensionsDirectory,
            $this->getBooleanAttribute($document->documentElement, 'beStrictAboutChangesToGlobalState', false),
            $this->getBooleanAttribute($document->documentElement, 'beStrictAboutOutputDuringTests', false),
            $this->getBooleanAttribute($document->documentElement, 'beStrictAboutTestsThatDoNotTestAnything', true),
            $beStrictAboutCoverageMetadata,
            $this->getBooleanAttribute($document->documentElement, 'enforceTimeLimit', false),
            $this->getIntegerAttribute($document->documentElement, 'defaultTimeLimit', 1),
            $this->getIntegerAttribute($document->documentElement, 'timeoutForSmallTests', 1),
            $this->getIntegerAttribute($document->documentElement, 'timeoutForMediumTests', 10),
            $this->getIntegerAttribute($document->documentElement, 'timeoutForLargeTests', 60),
            $this->getStringAttribute($document->documentElement, 'defaultTestSuite'),
            $executionOrder,
            $resolveDependencies,
            $defectsFirst,
            $this->getBooleanAttribute($document->documentElement, 'backupGlobals', false),
            $backupStaticProperties,
            $this->getBooleanAttribute($document->documentElement, 'registerMockObjectsFromTestArgumentsRecursively', false),
            $this->getBooleanAttribute($document->documentElement, 'testdox', false),
            $this->getBooleanAttribute($document->documentElement, 'controlGarbageCollector', false),
            $this->getIntegerAttribute($document->documentElement, 'numberOfTestsBeforeGarbageCollection', 100),
        );
    }

    private function getColors(DOMDocument $document): string
    {
        $colors = Configuration::COLOR_DEFAULT;

        if ($document->documentElement->hasAttribute('colors')) {
            /* only allow boolean for compatibility with previous versions
              'always' only allowed from command line */
            if ($this->getBoolean($document->documentElement->getAttribute('colors'), false)) {
                $colors = Configuration::COLOR_AUTO;
            } else {
                $colors = Configuration::COLOR_NEVER;
            }
        }

        return $colors;
    }

    private function getColumns(DOMDocument $document): int|string
    {
        $columns = 80;

        if ($document->documentElement->hasAttribute('columns')) {
            $columns = $document->documentElement->getAttribute('columns');

            if ($columns !== 'max') {
                $columns = $this->getInteger($columns, 80);
            }
        }

        return $columns;
    }

    private function testSuite(string $filename, DOMXPath $xpath): TestSuiteCollection
    {
        $testSuites = [];

        foreach ($this->getTestSuiteElements($xpath) as $element) {
            $exclude = [];

            foreach ($element->getElementsByTagName('exclude') as $excludeNode) {
                $excludeFile = $excludeNode->textContent;

                if ($excludeFile) {
                    $exclude[] = new File($this->toAbsolutePath($filename, $excludeFile));
                }
            }

            $directories = [];

            foreach ($element->getElementsByTagName('directory') as $directoryNode) {
                assert($directoryNode instanceof DOMElement);

                $directory = $directoryNode->textContent;

                if (empty($directory)) {
                    continue;
                }

                $prefix = '';

                if ($directoryNode->hasAttribute('prefix')) {
                    $prefix = $directoryNode->getAttribute('prefix');
                }

                $suffix = 'Test.php';

                if ($directoryNode->hasAttribute('suffix')) {
                    $suffix = $directoryNode->getAttribute('suffix');
                }

                $phpVersion = PHP_VERSION;

                if ($directoryNode->hasAttribute('phpVersion')) {
                    $phpVersion = $directoryNode->getAttribute('phpVersion');
                }

                $phpVersionOperator = new VersionComparisonOperator('>=');

                if ($directoryNode->hasAttribute('phpVersionOperator')) {
                    $phpVersionOperator = new VersionComparisonOperator($directoryNode->getAttribute('phpVersionOperator'));
                }

                $directories[] = new TestDirectory(
                    $this->toAbsolutePath($filename, $directory),
                    $prefix,
                    $suffix,
                    $phpVersion,
                    $phpVersionOperator,
                );
            }

            $files = [];

            foreach ($element->getElementsByTagName('file') as $fileNode) {
                assert($fileNode instanceof DOMElement);

                $file = $fileNode->textContent;

                if (empty($file)) {
                    continue;
                }

                $phpVersion = PHP_VERSION;

                if ($fileNode->hasAttribute('phpVersion')) {
                    $phpVersion = $fileNode->getAttribute('phpVersion');
                }

                $phpVersionOperator = new VersionComparisonOperator('>=');

                if ($fileNode->hasAttribute('phpVersionOperator')) {
                    $phpVersionOperator = new VersionComparisonOperator($fileNode->getAttribute('phpVersionOperator'));
                }

                $files[] = new TestFile(
                    $this->toAbsolutePath($filename, $file),
                    $phpVersion,
                    $phpVersionOperator,
                );
            }

            $name = $element->getAttribute('name');

            assert(!empty($name));

            $testSuites[] = new TestSuiteConfiguration(
                $name,
                TestDirectoryCollection::fromArray($directories),
                TestFileCollection::fromArray($files),
                FileCollection::fromArray($exclude),
            );
        }

        return TestSuiteCollection::fromArray($testSuites);
    }

    /**
     * @psalm-return list<DOMElement>
     */
    private function getTestSuiteElements(DOMXPath $xpath): array
    {
        $elements = [];

        $testSuiteNodes = $xpath->query('testsuites/testsuite');

        if ($testSuiteNodes->length === 0) {
            $testSuiteNodes = $xpath->query('testsuite');
        }

        if ($testSuiteNodes->length === 1) {
            $element = $testSuiteNodes->item(0);

            assert($element instanceof DOMElement);

            $elements[] = $element;
        } else {
            foreach ($testSuiteNodes as $testSuiteNode) {
                assert($testSuiteNode instanceof DOMElement);

                $elements[] = $testSuiteNode;
            }
        }

        return $elements;
    }

    private function element(DOMXPath $xpath, string $element): ?DOMElement
    {
        $nodes = $xpath->query($element);

        if ($nodes->length === 1) {
            $node = $nodes->item(0);

            assert($node instanceof DOMElement);

            return $node;
        }

        return null;
    }
}
