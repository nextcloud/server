<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
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
use OC\Core\Command\TaskProcessing\WorkerCommand;
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
use Symfony\Component\Console\Application;

/** @var Application $application */
$application->addCommand(new CompletionCommand());
$application->addCommand(Server::get(Status::class));
$application->addCommand(Server::get(Check::class));
$application->addCommand(Server::get(CreateJs::class));
$application->addCommand(Server::get(SignApp::class));
$application->addCommand(Server::get(SignCore::class));
$application->addCommand(Server::get(CheckApp::class));
$application->addCommand(Server::get(CheckCore::class));
$application->addCommand(Server::get(ListRoutes::class));
$application->addCommand(Server::get(MatchRoute::class));

$config = Server::get(IConfig::class);

if ($config->getSystemValueBool('installed', false)) {
	$application->addCommand(Server::get(Disable::class));
	$application->addCommand(Server::get(Enable::class));
	$application->addCommand(Server::get(Install::class));
	$application->addCommand(Server::get(GetPath::class));
	$application->addCommand(Server::get(ListApps::class));
	$application->addCommand(Server::get(Remove::class));
	$application->addCommand(Server::get(Update::class));

	$application->addCommand(Server::get(Cleanup::class));
	$application->addCommand(Server::get(Enforce::class));
	$application->addCommand(Server::get(Command\TwoFactorAuth\Enable::class));
	$application->addCommand(Server::get(Command\TwoFactorAuth\Disable::class));
	$application->addCommand(Server::get(State::class));

	$application->addCommand(Server::get(Mode::class));
	$application->addCommand(Server::get(Job::class));
	$application->addCommand(Server::get(ListCommand::class));
	$application->addCommand(Server::get(Delete::class));
	$application->addCommand(Server::get(JobWorker::class));

	$application->addCommand(Server::get(Test::class));

	$application->addCommand(Server::get(DeleteConfig::class));
	$application->addCommand(Server::get(GetConfig::class));
	$application->addCommand(Server::get(SetConfig::class));
	$application->addCommand(Server::get(Import::class));
	$application->addCommand(Server::get(ListConfigs::class));
	$application->addCommand(Server::get(Preset::class));
	$application->addCommand(Server::get(Command\Config\System\DeleteConfig::class));
	$application->addCommand(Server::get(Command\Config\System\GetConfig::class));
	$application->addCommand(Server::get(Command\Config\System\SetConfig::class));

	$application->addCommand(Server::get(File::class));
	$application->addCommand(Server::get(Space::class));
	$application->addCommand(Server::get(Storage::class));
	$application->addCommand(Server::get(Storages::class));

	$application->addCommand(Server::get(ConvertType::class));
	$application->addCommand(Server::get(ConvertMysqlToMB4::class));
	$application->addCommand(Server::get(ConvertFilecacheBigInt::class));
	$application->addCommand(Server::get(AddMissingColumns::class));
	$application->addCommand(Server::get(AddMissingIndices::class));
	$application->addCommand(Server::get(AddMissingPrimaryKeys::class));
	$application->addCommand(Server::get(ExpectedSchema::class));
	$application->addCommand(Server::get(ExportSchema::class));

	$application->addCommand(Server::get(GenerateMetadataCommand::class));
	$application->addCommand(Server::get(PreviewCommand::class));
	if ($config->getSystemValueBool('debug', false)) {
		$application->addCommand(Server::get(StatusCommand::class));
		$application->addCommand(Server::get(MigrateCommand::class));
		$application->addCommand(Server::get(GenerateCommand::class));
		$application->addCommand(Server::get(ExecuteCommand::class));
	}

	$application->addCommand(Server::get(Command\Encryption\Disable::class));
	$application->addCommand(Server::get(Command\Encryption\Enable::class));
	$application->addCommand(Server::get(ListModules::class));
	$application->addCommand(Server::get(SetDefaultModule::class));
	$application->addCommand(Server::get(Command\Encryption\Status::class));
	$application->addCommand(Server::get(EncryptAll::class));
	$application->addCommand(Server::get(DecryptAll::class));

	$application->addCommand(Server::get(Manage::class));
	$application->addCommand(Server::get(Command\Log\File::class));

	$application->addCommand(Server::get(ChangeKeyStorageRoot::class));
	$application->addCommand(Server::get(ShowKeyStorageRoot::class));
	$application->addCommand(Server::get(MigrateKeyStorage::class));

	$application->addCommand(Server::get(DataFingerprint::class));
	$application->addCommand(Server::get(UpdateDB::class));
	$application->addCommand(Server::get(UpdateJS::class));
	$application->addCommand(Server::get(Command\Maintenance\Mode::class));
	$application->addCommand(Server::get(UpdateHtaccess::class));
	$application->addCommand(Server::get(UpdateTheme::class));

	$application->addCommand(Server::get(Upgrade::class));
	$application->addCommand(Server::get(Repair::class));
	$application->addCommand(Server::get(RepairShareOwnership::class));

	$application->addCommand(Server::get(Command\Preview\Cleanup::class));
	$application->addCommand(Server::get(Generate::class));
	$application->addCommand(Server::get(ResetRenderedTexts::class));

	$application->addCommand(Server::get(Add::class));
	$application->addCommand(Server::get(Command\User\Delete::class));
	$application->addCommand(Server::get(Command\User\Disable::class));
	$application->addCommand(Server::get(Command\User\Enable::class));
	$application->addCommand(Server::get(LastSeen::class));
	$application->addCommand(Server::get(Report::class));
	$application->addCommand(Server::get(ResetPassword::class));
	$application->addCommand(Server::get(Setting::class));
	$application->addCommand(Server::get(Profile::class));
	$application->addCommand(Server::get(Command\User\ListCommand::class));
	$application->addCommand(Server::get(ClearGeneratedAvatarCacheCommand::class));
	$application->addCommand(Server::get(Info::class));
	$application->addCommand(Server::get(SyncAccountDataCommand::class));
	$application->addCommand(Server::get(Command\User\AuthTokens\Add::class));
	$application->addCommand(Server::get(Command\User\AuthTokens\ListCommand::class));
	$application->addCommand(Server::get(Command\User\AuthTokens\Delete::class));
	$application->addCommand(Server::get(Verify::class));
	$application->addCommand(Server::get(Welcome::class));

	$application->addCommand(Server::get(Command\Group\Add::class));
	$application->addCommand(Server::get(Command\Group\Delete::class));
	$application->addCommand(Server::get(Command\Group\ListCommand::class));
	$application->addCommand(Server::get(AddUser::class));
	$application->addCommand(Server::get(RemoveUser::class));
	$application->addCommand(Server::get(Command\Group\Info::class));

	$application->addCommand(Server::get(Command\SystemTag\ListCommand::class));
	$application->addCommand(Server::get(Command\SystemTag\Delete::class));
	$application->addCommand(Server::get(Command\SystemTag\Add::class));
	$application->addCommand(Server::get(Edit::class));

	$application->addCommand(Server::get(ListCertificates::class));
	$application->addCommand(Server::get(ExportCertificates::class));
	$application->addCommand(Server::get(ImportCertificate::class));
	$application->addCommand(Server::get(RemoveCertificate::class));
	$application->addCommand(Server::get(BruteforceAttempts::class));
	$application->addCommand(Server::get(BruteforceResetAttempts::class));
	$application->addCommand(Server::get(SetupChecks::class));
	$application->addCommand(Server::get(SnowflakeDecodeId::class));
	$application->addCommand(Server::get(Get::class));

	$application->addCommand(Server::get(GetCommand::class));
	$application->addCommand(Server::get(EnabledCommand::class));
	$application->addCommand(Server::get(Command\TaskProcessing\ListCommand::class));
	$application->addCommand(Server::get(Statistics::class));
	$application->addCommand(Server::get(Command\TaskProcessing\Cleanup::class));
	$application->addCommand(Server::get(WorkerCommand::class));

	$application->addCommand(Server::get(RedisCommand::class));
	$application->addCommand(Server::get(DistributedClear::class));
	$application->addCommand(Server::get(DistributedDelete::class));
	$application->addCommand(Server::get(DistributedGet::class));
	$application->addCommand(Server::get(DistributedSet::class));
} else {
	$application->addCommand(Server::get(Command\Maintenance\Install::class));
}
