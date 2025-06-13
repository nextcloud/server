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
use OC\Core\Command\Memcache\RedisCommand;
use OC\Core\Command\Preview\Generate;
use OC\Core\Command\Preview\ResetRenderedTexts;
use OC\Core\Command\Security\BruteforceAttempts;
use OC\Core\Command\Security\BruteforceResetAttempts;
use OC\Core\Command\Security\ExportCertificates;
use OC\Core\Command\Security\ImportCertificate;
use OC\Core\Command\Security\ListCertificates;
use OC\Core\Command\Security\RemoveCertificate;
use OC\Core\Command\SetupChecks;
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

$application->add(new CompletionCommand());
$application->add(Server::get(Status::class));
$application->add(Server::get(Check::class));
$application->add(Server::get(CreateJs::class));
$application->add(Server::get(SignApp::class));
$application->add(Server::get(SignCore::class));
$application->add(Server::get(CheckApp::class));
$application->add(Server::get(CheckCore::class));

$config = Server::get(IConfig::class);

if ($config->getSystemValueBool('installed', false)) {
	$application->add(Server::get(Disable::class));
	$application->add(Server::get(Enable::class));
	$application->add(Server::get(Install::class));
	$application->add(Server::get(GetPath::class));
	$application->add(Server::get(ListApps::class));
	$application->add(Server::get(Remove::class));
	$application->add(Server::get(Update::class));

	$application->add(Server::get(Cleanup::class));
	$application->add(Server::get(Enforce::class));
	$application->add(Server::get(Command\TwoFactorAuth\Enable::class));
	$application->add(Server::get(Command\TwoFactorAuth\Disable::class));
	$application->add(Server::get(State::class));

	$application->add(Server::get(Mode::class));
	$application->add(Server::get(Job::class));
	$application->add(Server::get(ListCommand::class));
	$application->add(Server::get(Delete::class));
	$application->add(Server::get(JobWorker::class));

	$application->add(Server::get(Test::class));

	$application->add(Server::get(DeleteConfig::class));
	$application->add(Server::get(GetConfig::class));
	$application->add(Server::get(SetConfig::class));
	$application->add(Server::get(Import::class));
	$application->add(Server::get(ListConfigs::class));
	$application->add(Server::get(Command\Config\System\DeleteConfig::class));
	$application->add(Server::get(Command\Config\System\GetConfig::class));
	$application->add(Server::get(Command\Config\System\SetConfig::class));

	$application->add(Server::get(File::class));
	$application->add(Server::get(Space::class));
	$application->add(Server::get(Storage::class));
	$application->add(Server::get(Storages::class));

	$application->add(Server::get(ConvertType::class));
	$application->add(Server::get(ConvertMysqlToMB4::class));
	$application->add(Server::get(ConvertFilecacheBigInt::class));
	$application->add(Server::get(AddMissingColumns::class));
	$application->add(Server::get(AddMissingIndices::class));
	$application->add(Server::get(AddMissingPrimaryKeys::class));
	$application->add(Server::get(ExpectedSchema::class));
	$application->add(Server::get(ExportSchema::class));

	$application->add(Server::get(GenerateMetadataCommand::class));
	$application->add(Server::get(PreviewCommand::class));
	if ($config->getSystemValueBool('debug', false)) {
		$application->add(Server::get(StatusCommand::class));
		$application->add(Server::get(MigrateCommand::class));
		$application->add(Server::get(GenerateCommand::class));
		$application->add(Server::get(ExecuteCommand::class));
	}

	$application->add(Server::get(Command\Encryption\Disable::class));
	$application->add(Server::get(Command\Encryption\Enable::class));
	$application->add(Server::get(ListModules::class));
	$application->add(Server::get(SetDefaultModule::class));
	$application->add(Server::get(Command\Encryption\Status::class));
	$application->add(Server::get(EncryptAll::class));
	$application->add(Server::get(DecryptAll::class));

	$application->add(Server::get(Manage::class));
	$application->add(Server::get(Command\Log\File::class));

	$application->add(Server::get(ChangeKeyStorageRoot::class));
	$application->add(Server::get(ShowKeyStorageRoot::class));
	$application->add(Server::get(MigrateKeyStorage::class));

	$application->add(Server::get(DataFingerprint::class));
	$application->add(Server::get(UpdateDB::class));
	$application->add(Server::get(UpdateJS::class));
	$application->add(Server::get(Command\Maintenance\Mode::class));
	$application->add(Server::get(UpdateHtaccess::class));
	$application->add(Server::get(UpdateTheme::class));

	$application->add(Server::get(Upgrade::class));
	$application->add(Server::get(Repair::class));
	$application->add(Server::get(RepairShareOwnership::class));

	$application->add(Server::get(Command\Preview\Cleanup::class));
	$application->add(Server::get(Generate::class));
	$application->add(Server::get(Command\Preview\Repair::class));
	$application->add(Server::get(ResetRenderedTexts::class));

	$application->add(Server::get(Add::class));
	$application->add(Server::get(Command\User\Delete::class));
	$application->add(Server::get(Command\User\Disable::class));
	$application->add(Server::get(Command\User\Enable::class));
	$application->add(Server::get(LastSeen::class));
	$application->add(Server::get(Report::class));
	$application->add(Server::get(ResetPassword::class));
	$application->add(Server::get(Setting::class));
	$application->add(Server::get(Profile::class));
	$application->add(Server::get(Command\User\ListCommand::class));
	$application->add(Server::get(ClearGeneratedAvatarCacheCommand::class));
	$application->add(Server::get(Info::class));
	$application->add(Server::get(SyncAccountDataCommand::class));
	$application->add(Server::get(Command\User\AuthTokens\Add::class));
	$application->add(Server::get(Command\User\AuthTokens\ListCommand::class));
	$application->add(Server::get(Command\User\AuthTokens\Delete::class));
	$application->add(Server::get(Verify::class));
	$application->add(Server::get(Welcome::class));

	$application->add(Server::get(Command\Group\Add::class));
	$application->add(Server::get(Command\Group\Delete::class));
	$application->add(Server::get(Command\Group\ListCommand::class));
	$application->add(Server::get(AddUser::class));
	$application->add(Server::get(RemoveUser::class));
	$application->add(Server::get(Command\Group\Info::class));

	$application->add(Server::get(Command\SystemTag\ListCommand::class));
	$application->add(Server::get(Command\SystemTag\Delete::class));
	$application->add(Server::get(Command\SystemTag\Add::class));
	$application->add(Server::get(Edit::class));

	$application->add(Server::get(ListCertificates::class));
	$application->add(Server::get(ExportCertificates::class));
	$application->add(Server::get(ImportCertificate::class));
	$application->add(Server::get(RemoveCertificate::class));
	$application->add(Server::get(BruteforceAttempts::class));
	$application->add(Server::get(BruteforceResetAttempts::class));
	$application->add(Server::get(SetupChecks::class));
	$application->add(Server::get(Get::class));

	$application->add(Server::get(GetCommand::class));
	$application->add(Server::get(EnabledCommand::class));
	$application->add(Server::get(Command\TaskProcessing\ListCommand::class));
	$application->add(Server::get(Statistics::class));

	$application->add(Server::get(RedisCommand::class));
} else {
	$application->add(Server::get(Command\Maintenance\Install::class));
}
