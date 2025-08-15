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

use function version_compare;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class MigrationBuilder
{
    private const AVAILABLE_MIGRATIONS = [
        '8.5' => [
            RemoveLogTypes::class,
        ],

        '9.2' => [
            RemoveCacheTokensAttribute::class,
            IntroduceCoverageElement::class,
            MoveAttributesFromRootToCoverage::class,
            MoveAttributesFromFilterWhitelistToCoverage::class,
            MoveWhitelistIncludesToCoverage::class,
            MoveWhitelistExcludesToCoverage::class,
            RemoveEmptyFilter::class,
            CoverageCloverToReport::class,
            CoverageCrap4jToReport::class,
            CoverageHtmlToReport::class,
            CoveragePhpToReport::class,
            CoverageTextToReport::class,
            CoverageXmlToReport::class,
            ConvertLogTypes::class,
        ],

        '9.5' => [
            RemoveListeners::class,
            RemoveTestSuiteLoaderAttributes::class,
            RemoveCacheResultFileAttribute::class,
            RemoveCoverageElementCacheDirectoryAttribute::class,
            RemoveCoverageElementProcessUncoveredFilesAttribute::class,
            IntroduceCacheDirectoryAttribute::class,
            RenameBackupStaticAttributesAttribute::class,
            RemoveBeStrictAboutResourceUsageDuringSmallTestsAttribute::class,
            RemoveBeStrictAboutTodoAnnotatedTestsAttribute::class,
            RemovePrinterAttributes::class,
            RemoveVerboseAttribute::class,
            RenameForceCoversAnnotationAttribute::class,
            RenameBeStrictAboutCoversAnnotationAttribute::class,
            RemoveConversionToExceptionsAttributes::class,
            RemoveNoInteractionAttribute::class,
            RemoveLoggingElements::class,
            RemoveTestDoxGroupsElement::class,
        ],

        '10.0' => [
            MoveCoverageDirectoriesToSource::class,
        ],

        '10.4' => [
            RemoveBeStrictAboutTodoAnnotatedTestsAttribute::class,
        ],
    ];

    public function build(string $fromVersion): array
    {
        $stack = [new UpdateSchemaLocation];

        foreach (self::AVAILABLE_MIGRATIONS as $version => $migrations) {
            if (version_compare($version, $fromVersion, '<')) {
                continue;
            }

            foreach ($migrations as $migration) {
                $stack[] = new $migration;
            }
        }

        return $stack;
    }
}
