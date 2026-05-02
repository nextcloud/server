<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\App;

/**
 * @psalm-type AppInfoLocalizedEntry = array{
 *     '@attributes'?: array{
 *         lang?: non-empty-string,
 *     },
 *     '@value': non-empty-string,
 * }
 *
 * @psalm-type AppInfoLocalizedData = array{
 *     'name': non-empty-string,
 *     'summary': non-empty-string,
 *     'description': non-empty-string,
 * }
 *
 * @psalm-type AppInfoRawXmlData = array{
 *     'name': non-empty-string|AppInfoLocalizedEntry|list<string|AppInfoLocalizedEntry>,
 *     'summary': non-empty-string|AppInfoLocalizedEntry|list<string|AppInfoLocalizedEntry>,
 *     'description': non-empty-string|AppInfoLocalizedEntry|list<string|AppInfoLocalizedEntry>,
 * }
 *
 * The enum definitions as per info.xsd:
 *
 * @psalm-type AppInfoFieldTypeArchitecture = 'x86'|'x86_64'|'aarch'|'aarch64'
 * @psalm-type AppInfoFieldTypeBits = 64|32
 * @psalm-type AppInfoFieldTypeCategory = 'dashboard'|'security'|'customization'|'files'|'integration'|'monitoring'|'multimedia'|'office'|'organization'|'social'|'tools'|'games'|'search'|'workflow'|'ai'
 * @psalm-type AppInfoFieldTypeCollaborationPluginType = 'collaborator-search'|'autocomplete-sort'
 * @psalm-type AppInfoFieldTypeDatabases = 'sqlite'|'mysql'|'pgsql'
 * @psalm-type AppInfoFieldTypeDonationPlatform = 'paypal'|'stripe'|'other'
 * @psalm-type AppInfoFieldTypeLicense = 'AGPL-3.0-only'|'AGPL-3.0-or-later'|'Apache-2.0'|'GPL-3.0-only'|'GPL-3.0-or-later'|'MIT'|'MPL-2.0'|'agpl'|'mit'|'mpl'|'apache'|'gpl3'
 * @psalm-type AppInfoFieldTypeNavigationType = 'link'|'settings'
 * @psalm-type AppInfoFieldTypeNavigationRole = 'all'|'admin'
 * @psalm-type AppInfoFieldTypeShareType = 'SHARE_TYPE_USER'|'SHARE_TYPE_GROUP'|'SHARE_TYPE_LINK'|'SHARE_TYPE_EMAIL'|'SHARE_TYPE_CONTACT'|'SHARE_TYPE_REMOTE'|'SHARE_TYPE_CIRCLE'|'SHARE_TYPE_GUEST'|'SHARE_TYPE_ROOM'
 * @psalm-type AppInfoFieldTypeTypes = 'prelogin'|'filesystem'|'authentication'|'extended_authentication'|'logging'|'dav'|'prevent_group_restriction'|'session'
 * @psalm-type AppInfoFieldTypeVcs = 'git'|'mercurial'|'subversion'|'bzr'
 *
 * The complex types as per info.xsd:
 *
 * @psalm-type AppInfoFieldTypeAuthor = string|array{
 *    '@attributes': array{
 *        'mail'?: non-empty-string,
 *        'homepage'?: non-empty-string
 *    },
 *    '@value': non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeDocumentation = array{
 *     'user'?: non-empty-string,
 *     'admin'?: non-empty-string,
 *     'developer'?: non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeRepository = string|array{
 *     '@attributes'?: array{
 *         'type'?: AppInfoFieldTypeVcs,
 *     },
 *    '@value': non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeScreenshot = string|array{
 *     '@attributes'?: array{
 *         'small-thumbnail'?: non-empty-string,
 *     },
 *     '@value': non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeDonation = string|array{
 *     '@attributes'?: array{
 *         'title'?: non-empty-string,
 *         'type'?: AppInfoFieldTypeDonationPlatform,
 *     },
 *     '@value': non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeDependenciesPhp = ''|array{
 *     '@attributes': array{
 *         'min-int-size'?: AppInfoFieldTypeBits,
 *         'min-version'?: non-empty-string,
 *         'max-version'?: non-empty-string,
 *     },
 *     '@value'?: '',
 * }
 * @psalm-type AppInfoFieldTypeDependenciesDatabase = AppInfoFieldTypeDatabases|array{
 *    '@attributes': array{
 *        'min-version'?: non-empty-string,
 *        'max-version'?: non-empty-string,
 *     },
 *    '@value': AppInfoFieldTypeDatabases,
 * }
 * @psalm-type AppInfoFieldTypeDependenciesOwnCloud = array{
 *    '@attributes': array{
 *         'min-version': non-empty-string,
 *         'max-version'?: non-empty-string,
 *    },
 * }
 * @psalm-type AppInfoFieldTypeDependenciesNextcloud = array{
 *    '@attributes': array{
 *         'min-version': non-empty-string,
 *         'max-version': non-empty-string,
 *    },
 * }
 * @psalm-type AppInfoFieldTypeDependencies = array{
 *     'php'?: AppInfoFieldTypeDependenciesPhp,
 *     'database'?: AppInfoFieldTypeDependenciesDatabase|list<AppInfoFieldTypeDependenciesDatabase>,
 *     'command'?: non-empty-string|list<non-empty-string>,
 *     'lib'?: non-empty-string|list<non-empty-string>,
 *     'owncloud'?: AppInfoFieldTypeDependenciesOwnCloud,
 *     'nextcloud': AppInfoFieldTypeDependenciesNextcloud,
 *     'architecture'?: non-empty-string|list<non-empty-string>,
 *     'backend'?: non-empty-string|list<non-empty-string>,
 * }
 * @psalm-type AppInfoFieldTypeRepairSteps = array{
 *     'pre-migration'?: list<class-string>,
 *     'post-migration'?: list<class-string>,
 *     'live-migration'?: list<class-string>,
 *     'install'?: list<class-string>,
 *     'uninstall'?: list<class-string>,
 * }
 * @psalm-type AppInfoFieldTypeSettings = array{
 *     'admin'?: list<class-string>,
 *     'admin-section'?: list<class-string>,
 *     'personal'?: list<class-string>,
 *     'personal-section'?: list<class-string>,
 *     'admin-delegation'?: list<class-string>,
 *     'admin-delegation-section'?: list<class-string>,
 * }
 * @psalm-type AppInfoFieldTypeActivity = array{
 *     'settings'?: list<non-empty-string>,
 *     'filters'?: list<non-empty-string>,
 *     'providers'?: list<non-empty-string>,
 * }
 * @psalm-type AppInfoFieldTypeDashboard = array{
 *     'widget': list<class-string>,
 * }
 * @psalm-type AppInfoFieldTypeFullTextSearchProvider = class-string|array{
 *     '@attributes'?: array{
 *         'min-version'?: non-empty-string,
 *         'max-version'?: non-empty-string,
 *     },
 *     '@value': class-string,
 * }
 * @psalm-type AppInfoFieldTypeFullTextSearch = array{
 *     'platform'?: list<class-string>,
 *     'provider'?: list<AppInfoFieldTypeFullTextSearchProvider>,
 * }
 * @psalm-type AppInfoFieldTypeNavigationEntryValue = array{
 *     'id'?: non-empty-string,
 *     'name': non-empty-string,
 *     'route'?: non-empty-string,
 *     'icon'?: non-empty-string,
 *     'order'?: numeric,
 *     'type'?: AppInfoFieldTypeNavigationType,
 * }
 * @psalm-type AppInfoFieldTypeNavigationEntry = AppInfoFieldTypeNavigationEntryValue|array{
 *     '@attributes'?: array{
 *         'role'?: AppInfoFieldTypeNavigationRole,
 *     },
 *     '@value': AppInfoFieldTypeNavigationEntryValue,
 * }
 * @psalm-type AppInfoFieldTypeNavigation = array{
 *     'navigation': AppInfoFieldTypeNavigationEntry|list<AppInfoFieldTypeNavigationEntry>,
 * }
 * @psalm-type AppInfoFieldTypeContactMenu = array{
 *    'provider': class-string,
 * }
 * @psalm-type AppInfoFieldTypeCollaborationPlugin = array{
 *     '@attributes': array{
 *         'type': AppInfoFieldTypeCollaborationPluginType,
 *         'share-type'?: AppInfoFieldTypeShareType,
 *     },
 *     '@value': class-string,
 * }
 * @psalm-type AppInfoFieldTypeCollaboration = array{
 *   'plugins': AppInfoFieldTypeCollaborationPlugin|list<AppInfoFieldTypeCollaborationPlugin>,
 * }
 * @psalm-type AppInfoFieldTypeOpenMetrics = array{
 *    'exporter': class-string|list<class-string>,
 * }
 * @psalm-type AppInfoFieldTypeSabre = array{
 *     'collections'?: class-string|list<class-string>,
 *     'plugins'?: class-string|list<class-string>,
 *     'address-book-plugins'?: class-string|list<class-string>,
 *     'calendar-plugins'?: class-string|list<class-string>,
 * }
 * @psalm-type AppInfoFieldTypeTrashBackend = array{
 *     '@attributes': array{
 *         'for': class-string,
 *     },
 *     '@value': class-string,
 * }
 * @psalm-type AppInfoFieldTypeTrash = array{
 *     'backend': AppInfoFieldTypeTrashBackend|list<AppInfoFieldTypeTrashBackend>,
 * }
 * @psalm-type AppInfoFieldTypeVersionsBackend = array{
 *     '@attributes': array{
 *         'for': class-string,
 *     },
 *     '@value': class-string,
 * }
 * @psalm-type AppInfoFieldTypeVersions = array{
 *     'backend': AppInfoFieldTypeVersionsBackend|list<AppInfoFieldTypeVersionsBackend>,
 * }
 * @psalm-type AppInfoFieldTypeExternalAppDockerInstall = array{
 *     'registry': non-empty-string,
 *     'image': non-empty-string,
 *     'image-tag': non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeExternalAppEnvironmentVariable = array{
 *     'name': non-empty-string,
 *     'display-name': non-empty-string,
 *     'description'?: non-empty-string,
 *     'default'?: non-empty-string,
 * }
 * @psalm-type AppInfoFieldTypeExternalApp = array{
 *     'docker-install'?: AppInfoFieldTypeExternalAppDockerInstall,
 *     'scopes'?: string|list<string>,
 *     'system'?: bool,
 *     'environment-variables'?: AppInfoFieldTypeExternalAppEnvironmentVariable|list<AppInfoFieldTypeExternalAppEnvironmentVariable>,
 * }
 *
 * // Only available for shipped apps:
 *
 * @psalm-type AppInfoFieldTypeShippedAppServices = array<non-empty-string, non-empty-string>
 *
 * @psalm-type AppInfoSharedDefinition = array{
 *     'id': non-empty-string,
 *     'version': non-empty-string,
 *     'default_enable'?: '',
 *     'licence': AppInfoFieldTypeLicense|list<AppInfoFieldTypeLicense>,
 *     'author': AppInfoFieldTypeAuthor|list<AppInfoFieldTypeAuthor>,
 *     'namespace'?: non-empty-string,
 *     'types'?: list<AppInfoFieldTypeTypes>,
 *     'documentation'?: AppInfoFieldTypeDocumentation,
 *     'category': list<AppInfoFieldTypeCategory>,
 *     'website'?: non-empty-string,
 *     'discussion'?: non-empty-string,
 *     'bugs': non-empty-string,
 *     'repository'?: AppInfoFieldTypeRepository,
 *     'screenshot'?: AppInfoFieldTypeScreenshot|list<AppInfoFieldTypeScreenshot>,
 *     'donation'?: AppInfoFieldTypeDonation|list<AppInfoFieldTypeDonation>,
 *     'dependencies': AppInfoFieldTypeDependencies,
 *     'background-jobs'?: class-string|list<class-string>,
 *     'repair-steps'?: AppInfoFieldTypeRepairSteps,
 *     'two-factor-providers'?: list<class-string>,
 *     'commands'?: list<class-string>,
 *     'settings'?: AppInfoFieldTypeSettings,
 *     'activity'?: AppInfoFieldTypeActivity,
 *     'dashboard'?: AppInfoFieldTypeDashboard,
 *     'fulltextsearch'?: AppInfoFieldTypeFullTextSearch,
 *     'navigations'?: AppInfoFieldTypeNavigation,
 *     'contactsmenu'?: AppInfoFieldTypeContactMenu,
 *     'collaboration'?: AppInfoFieldTypeCollaboration,
 *     'openmetrics'?: AppInfoFieldTypeOpenMetrics,
 *     'sabre'?: AppInfoFieldTypeSabre,
 *     'trash'?: AppInfoFieldTypeTrash,
 *     'versions'?: AppInfoFieldTypeVersions,
 *     'external-app'?: AppInfoFieldTypeExternalApp,
 *     'public'?: AppInfoFieldTypeShippedAppServices,
 *     'remote'?: AppInfoFieldTypeShippedAppServices,
 * }
 *
 * // The app info definition with localization applied:
 * @psalm-type AppInfoDefinition = AppInfoLocalizedData & AppInfoSharedDefinition
 * // The app info definition as it is parsed from XML:
 * @psalm-type AppInfoXmlDefinition = AppInfoRawXmlData & AppInfoSharedDefinition
 *
 * @warning This may change without regular deprecation cycle if the "appinfo.xml" definition changes. Use {@see https://apps.nextcloud.com/schema/apps/info.xsd } as the source of truth.
 * @since 34.0.0
 */
final class AppInfoDefinition {
}
