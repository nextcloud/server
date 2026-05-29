<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App\AppStore\Fetcher;

/**
 * @psalm-type AppStoreFetcherCategory = array{
 *     id: string,
 *     translations: array<string, array{
 *         name: string,
 *         description: string,
 *     }>,
 * }
 *
 * @psalm-type AppStoreFetcherAppAuthor = array{
 *     name: string,
 *     mail: string,
 *     homepage: string,
 * }
 *
 * @psalm-type AppStoreFetcherAppScreenshot = array{
 *     url: string,
 *     smallThumbnail: string,
 * }
 *
 * @psalm-type AppStoreFetcherAppTranslationsEntry = array{
 *     name: string,
 *     summary: string,
 *     description: string,
 * }
 *
 * @psalm-type AppStoreFetcherAppTranslations = array{ en: AppStoreFetcherAppTranslationsEntry } & array<string, AppStoreFetcherAppTranslationsEntry>
 *
 * @psalm-type AppStoreFetcherAppReleasesEntryTranslations = array{ en: array{ changelog: string } } & array<string, array{ changelog: string }>
 *
 * @psalm-type AppStoreFetcherAppReleasesEntryRequirements = array{
 *     id: string,
 *     versionSpec: string,
 *     rawVersionSpec: string,
 * }
 *
 * @psalm-type AppStoreFetcherAppReleasesEntry = array{
 *     version: string,
 *     phpExtensions?: list<AppStoreFetcherAppReleasesEntryRequirements>,
 *     databases?: list<AppStoreFetcherAppReleasesEntryRequirements>,
 *     shellCommands?: list<string>,
 *     phpVersionSpec: string,
 *     platformVersionSpec: string,
 *     minIntSize: int,
 *     download: string,
 *     created: string,
 *     licenses?: list<string>,
 *     lastModified: string,
 *     isNightly: boolean,
 *     rawPhpVersionSpec: string,
 *     rawPlatformVersionSpec: string,
 *     signature: string,
 *     translations: AppStoreFetcherAppReleasesEntryTranslations,
 *     signatureDigest: string,
 * }
 *
 * @psalm-type AppStoreFetcherApp = array{
 *    id: string,
 *    authors?: list<AppStoreFetcherAppAuthor>,
 *    categories: string[],
 *    certificate: string,
 *    created: string,
 *    lastModified: string,
 *    translations: AppStoreFetcherAppTranslations,
 *    releases?: list<AppStoreFetcherAppReleasesEntry>,
 *    screenshots?: list<AppStoreFetcherAppScreenshot>,
 *    adminDocs: string,
 *    userDocs: string,
 *    developerDocs: string,
 *    discussion: string,
 *    issueTracker: string,
 *    website: string,
 *    isFeatured: boolean,
 *    ratingRecent: float,
 *    ratingOverall: float,
 *    ratingNumRecent: int,
 *    ratingNumOverall: int,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverLocalizedString = array{en: string} & array<string, string>
 *
 * @psalm-type AppStoreFetcherDiscoverMediaSource = array{
 *     mime: string,
 *     src: string,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverMediaContent = array{
 *     src: AppStoreFetcherDiscoverMediaSource|list<AppStoreFetcherDiscoverMediaSource>,
 *     alt: string,
 *     link?: string,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverLocalizedMediaContent = array{en: AppStoreFetcherDiscoverMediaContent} & array<string, AppStoreFetcherDiscoverMediaContent>
 *
 * @psalm-type AppStoreFetcherDiscoverMediaObject = array{
 *     content: AppStoreFetcherDiscoverLocalizedMediaContent,
 *     alignment?: 'start'|'end'|'center',
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverContainerBase = array{
 *     id?: string,
 *     order?: int,
 *     headline?: AppStoreFetcherDiscoverLocalizedString,
 *     text?: AppStoreFetcherDiscoverLocalizedString,
 *     link?: string,
 *     date?: string,
 *     expiryDate?: string,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverAppElement = array{
 *     type: 'app',
 *     appId: string,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverPostElement = AppStoreFetcherDiscoverContainerBase & array{
 *     type: 'post',
 *     media?: AppStoreFetcherDiscoverMediaObject,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverShowcaseElement = AppStoreFetcherDiscoverContainerBase & array{
 *     type: 'showcase',
 *     content: list<AppStoreFetcherDiscoverAppElement|AppStoreFetcherDiscoverPostElement>,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverCarouselElement = AppStoreFetcherDiscoverContainerBase & array{
 *     type: 'carousel',
 *     content: list<AppStoreFetcherDiscoverPostElement>,
 * }
 *
 * @psalm-type AppStoreFetcherDiscoverElement =
 *     AppStoreFetcherDiscoverPostElement|
 *     AppStoreFetcherDiscoverShowcaseElement|
 *     AppStoreFetcherDiscoverCarouselElement
 */
final class ResponseDefinitions {
}
