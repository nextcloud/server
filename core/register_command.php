<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Core\Command;
use OC\Core\Command\App\Disable;
use OC\Core\Command\App\Enable;
use OC\Core\Command\App\GetPath;
use OC\Core\Command\App\Install;
use OC\Core\Command\App\ListApps;
use OC\Core\Command\App\Remove;
use OC\Core\Command\App\Update;
use OC\Core\Command\Background\Delete;
use OC\Core\Command\Background\Job;
use OC\Core\Command\Background\JobWorker;
use OC\Core\Command\Background\ListCommand;
use OC\Core\Command\Background\Mode;
use OC\Core\Command\Broadcast\Test;
use OC\Core\Command\Check;
use OC\Core\Command\Config\App\DeleteConfig;
use OC\Core\Command\Config\App\GetConfig;
use OC\Core\Command\Config\App\SetConfig;
use OC\Core\Command\Config\Import;
use OC\Core\Command\Config\ListConfigs;
use OC\Core\Command\Config\Preset;
use OC\Core\Command\Db\AddMissingColumns;
use OC\Core\Command\Db\AddMissingIndices;
use OC\Core\Command\Db\AddMissingPrimaryKeys;
use OC\Core\Command\Db\ConvertFilecacheBigInt;
use OC\Core\Command\Db\ConvertMysqlToMB4;
use OC\Core\Command\Db\ConvertType;
use OC\Core\Command\Db\ExpectedSchema;
use OC\Core\Command\Db\ExportSchema;
use OC\Core\Command\Db\Migrations\ExecuteCommand;
use OC\Core\Command\Db\Migrations\GenerateCommand;
use OC\Core\Command\Db\Migrations\GenerateMetadataCommand;
use OC\Core\Command\Db\Migrations\MigrateCommand;
use OC\Core\Command\Db\Migrations\PreviewCommand;
use OC\Core\Command\Db\Migrations\StatusCommand;
use OC\Core\Command\Encryption\ChangeKeyStorageRoot;
use OC\Core\Command\Encryption\DecryptAll;
use OC\Core\Command\Encryption\EncryptAll;
use OC\Core\Command\Encryption\ListModules;
use OC\Core\Command\Encryption\MigrateKeyStorage;
use OC\Core\Command\Encryption\SetDefaultModule;
use OC\Core\Command\Encryption\ShowKeyStorageRoot;
use OC\Core\Command\FilesMetadata\Get;
use OC\Core\Command\Group\AddUser;
use OC\Core\Command\Group\RemoveUser;
use OC\Core\Command\Info\File;
use OC\Core\Command\Info\Space;
use OC\Core\Command\Info\Storage;
use OC\Core\Command\Info\Storages;
use OC\Core\Command\Integrity\CheckApp;
use OC\Core\Command\Integrity\CheckCore;
use OC\Core\Command\Integrity\SignApp;
use OC\Core\Command\Integrity\SignCore;
use OC\Core\Command\L10n\CreateJs;
use OC\Core\Command\Log\Manage;
use OC\Core\Command\Maintenance\DataFingerprint;
use OC\Core\Command\Maintenance\Mimetype\UpdateDB;
use OC\Core\Command\Maintenance\Mimetype\UpdateJS;
use OC\Core\Command\Maintenance\Repair;
use OC\Core\Command\Maintenance\RepairShareOwnership;
use OC\Core\Command\Maintenance\UpdateHtaccess;
use OC\Core\Command\Maintenance\UpdateTheme;
use OC\Core\Command\Memcache\DistributedClear;
use OC\Core\Command\Memcache\DistributedDelete;
use OC\Core\Command\Memcache\DistributedGet;
use OC\Core\Command\Memcache\DistributedSet;
use OC\Core\Command\Memcache\RedisCommand;
use OC\Core\Command\Preview\Generate;
use OC\Core\Command\Preview\ResetRenderedTexts;
use OC\Core\Command\Router\ListRoutes;
use OC\Core\Command\Router\MatchRoute;
use OC\Core\Command\Security\BruteforceAttempts;
use OC\Core\Command\Security\BruteforceResetAttempts;
use OC\Core\Command\Security\ExportCertificates;
use OC\Core\Command\Security\ImportCertificate;
use OC\Core\Command\Security\ListCertificates;
use OC\Core\Command\Security\RemoveCertificate;
use OC\Core\Command\SetupChecks;
use OC\Core\Command\SnowflakeDecodeId;
use OC\Core\Command\Status;
use OC\Core\Command\SystemTag\Edit;
use OC\Core\Command\TaskProcessing\EnabledCommand;
use OC\Core\Command\TaskProcessing\GetCommand;
use OC\Core\Command\TaskProcessing\Statistics;
use OC\Core\Command\TwoFactorAuth\Cleanup;
use OC\Core\Command\TwoFactorAuth\Enforce;
use OC\Core\Command\TwoFactorAuth\State;
use OC\Core\Command\Upgrade;
use OC\Core\Command\User\Add;
use OC\Core\Command\User\ClearGeneratedAvatarCacheCommand;
use OC\Core\Command\User\Info;
use OC\Core\Command\User\Keys\Verify;
use OC\Core\Command\User\LastSeen;
use OC\Core\Command\User\Profile;
use OC\Core\Command\User\Report;
use OC\Core\Command\User\ResetPassword;
use OC\Core\Command\User\Setting;
use OC\Core\Command\User\SyncAccountDataCommand;
use OC\Core\Command\User\Welcome;
use OCP\IConfig;
use OCP\Server;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;

/**
 * This file registers Nextcloud core console commands for Nextcloud's CLI application (OCC).
 *
 * Core commands are explicitly added in this file, resolving them from the DI container via 
 * Server::get(...).
 *
 * Some commands are always registered; many are only registered when the instance is already 
 * installed. Other commands are only registered when `occ` is executed in debug mode. A 
 * single "install" command is registered when the instance is not yet installed.
 *
 * The Symfony Console `$application` instance ris provided by the including scope (see 
 * OC\Console\Application::loadCommands).
 *
 * TODO (maybe): 
 * - Refactor this into a real class/service/callable w/ clear dependency handling/etc.
 * - Make each core command a tagged service and have the container or a service aggregator
 *	 auto-register them.
 */

// These variables are expected to be provided by the including scope (Application::loadCommands)
/** @var \Symfony\Component\Console\Application $application */
/** @var bool $installed */
/** @var bool $maintenance */
/** @var bool $needUpgrade */
/** @var bool $debug */

/*
 * Commands that should always be registered (i.e. normal, pre-install, maintenance, upgrade needed)
*/
$alwaysCommands = [
	CompletionCommand::class,
	Status::class,
	Check::class,
	CreateJs::class,
	SignApp::class,
	SignCore::class,
	CheckApp::class,
	CheckCore::class,
	ListRoutes::class,
	MatchRoute::class,
];

/*
 * Commands required when an upgrade is needed (besides above)
 */
$upgradeCommands = [
	Command\Maintenance\Mode::class,
	Upgrade::class,
];

/*
 * Commands available only when not installed
 */
$installerCommands = [
	Command\Maintenance\Install::class,
];

/*
 * Commands allowed in maintenance mode (no apps loaded)
 */
$maintenanceCommands = [
	Command\Maintenance\Mode::class,
];

/*
 * Commands for normal (installed/up-to-date/non-maintenance) operating mode
 */
$installedCommands = [
	// "app"
	Disable::class,
	Enable::class,
	GetPath::class,
	Install::class,
	ListApps::class,
	Remove::class,
	Update::class,

	// "background"
	Mode::class,

	// "background-job"
	Delete::class,
	Job::class,
	JobWorker::class,
	ListCommand::class,

	// "broadcast"
	Test::class,

	// "config"
	Import::class,
	ListConfigs::class,
	Preset::class,

	// "config:app"
	DeleteConfig::class,
	GetConfig::class,
	SetConfig::class,

	// "config:system"
	Command\Config\System\DeleteConfig::class,
	Command\Config\System\GetConfig::class,
	Command\Config\System\SetConfig::class,

	// "db"
	AddMissingColumns::class,
	AddMissingIndices::class,
	AddMissingPrimaryKeys::class,
	ConvertFilecacheBigInt::class,
	ConvertMysqlToMB4::class,
	ConvertType::class,
	ExpectedSchema::class,
	ExportSchema::class,

	// "encryption"
	ChangeKeyStorageRoot::class,
	DecryptAll::class,
	Command\Encryption\Disable::class,
	Command\Encryption\Enable::class,
	EncryptAll::class,
	ListModules::class,
	MigrateKeyStorage::class,
	SetDefaultModule::class,
	ShowKeyStorageRoot::class,
	Command\Encryption\Status::class,

	// "group"
	Command\Group\Add::class,
	AddUser::class,
	Command\Group\Delete::class,
	Command\Group\Info::class,
	Command\Group\ListCommand::class,
	RemoveUser::class,

	// "info"
	File::class,
	Space::class,
	Storage::class,
	Storages::class,

	// "log"
	Command\Log\File::class,
	Manage::class,

	// "maintenance"
	DataFingerprint::class,
	Command\Maintenance\Mode::class,
	Repair::class,
	RepairShareOwnership::class,
	UpdateTheme::class,
	UpdateHtaccess::class,

	// "maintenance:mimetype"
	UpdateDB::class,
	UpdateJS::class,

	// "memcache""
	RedisCommand::class,		// TODO: Should probably be moved under debug; it's not currently gated
	DistributedClear::class,	// ditto
	DistributedDelete::class,	// ditto
	DistributedGet::class,		// ditto probably
	DistributedSet::class,		// ditto

	// "metadata"
	Get::class,

	// "migrations"
	GenerateMetadataCommand::class,
	PreviewCommand::class,

	// "preview"
	Command\Preview\Cleanup::class,
	Generate::class,
	ResetRenderedTexts::class,

	// "tag"
	Command\SystemTag\Add::class,
	Command\SystemTag\Delete::class,
	Edit::class,
	Command\SystemTag\ListCommand::class,

	// "twofactorauth"
	Cleanup::class,
	Command\TwoFactorAuth\Disable::class,
	Command\TwoFactorAuth\Enable::class,
	Enforce::class,
	State::class,

	// "security:bruteforce"
	BruteforceAttempts::class,
	BruteforceResetAttempts::class,

	// "security:certificates"
	ListCertificates::class,
	ExportCertificates::class,
	ImportCertificate::class,
	RemoveCertificate::class,

	// "setupchecks"
	SetupChecks::class,

	// "snowflake"
	SnowflakeDecodeId::class,

	// "taskprocessing"
	EnabledCommand::class,
	Command\TaskProcessing\Cleanup::class,
	GetCommand::class,
	Command\TaskProcessing\ListCommand::class,
	Statistics::class,

	// "user"
	Add::class,
	Command\User\AuthTokens\Add::class,
	Command\User\AuthTokens\Delete::class,
	Command\User\AuthTokens\ListCommand::class,
	ClearGeneratedAvatarCacheCommand::class,
	Command\User\Delete::class,
	Command\User\Disable::class,
	Command\User\Enable::class,
	Info::class,
	Verify::class,
	LastSeen::class,
	Command\User\ListCommand::class,
	Profile::class,
	Report::class,
	ResetPassword::class,
	Setting::class,
	SyncAccountDataCommand::class,
	Welcome::class,
];

/*
 * Debug-mode only commands
 */
$debugCommands = [
	// "migrations"
	ExecuteCommand::class,
	GenerateCommand::class,
	MigrateCommand::class,
	StatusCommand::class,
];

/** 
 * Helper to resolve & add a list of command classes.
 *
 * Will abort registering if any app/service fails to load.
 * 
 */
/** @var \Symfony\Component\Console\Application $application */
$addCommands = function (array $classes) use ($application) {
	foreach ($classes as $class) {
		// CompletionCommand is instantiated directly (not resolved from container).
		if ($class === CompletionCommand::class) {
			$application->add(new CompletionCommand());
		} else {
			$application->add(Server::get($class));
		}
	}
};

/*
 * Register commands according to state
 */

// Register always available commands
$addCommands($alwaysCommands);

if ($needUpgrade) {
	// Register minimal extra commands needed to perform or diagnose the upgrade.
	$addCommands($upgradeCommands);
	return;
}

if (!$installed) {
	// Register pre-install only commands
	$addCommands($installerCommands);
	return;
}

if ($maintenance) {
	$addCommands($maintenanceCommands);
	return;
}

// Normal installed & not maintenance path
$addCommands($installedCommands);

if ($debug) {
	$addCommands($debugCommands);
}
