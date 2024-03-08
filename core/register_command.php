<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christian Kampka <christian@kampka.net>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Denis Mosolov <denismosolov@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Johannes Riedel <joeried@users.noreply.github.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Patrik Kernstock <info@pkern.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Ruben Homs <ruben@homs.codes>
 * @author Sean Molenaar <sean@seanmolenaar.eu>
 * @author sualko <klaus@jsxc.org>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Pulzer <t.pulzer@kniel.de>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Core\Command;
use OCP\IConfig;
use OCP\Server;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;

$application->add(new CompletionCommand());
$application->add(Server::get(Command\Status::class));
$application->add(Server::get(Command\Check::class));
$application->add(Server::get(Command\L10n\CreateJs::class));
$application->add(Server::get(Command\Integrity\SignApp::class));
$application->add(Server::get(Command\Integrity\SignCore::class));
$application->add(Server::get(Command\Integrity\CheckApp::class));
$application->add(Server::get(Command\Integrity\CheckCore::class));

$config = Server::get(IConfig::class);

if ($config->getSystemValueBool('installed', false)) {
	$application->add(Server::get(Command\App\Disable::class));
	$application->add(Server::get(Command\App\Enable::class));
	$application->add(Server::get(Command\App\Install::class));
	$application->add(Server::get(Command\App\GetPath::class));
	$application->add(Server::get(Command\App\ListApps::class));
	$application->add(Server::get(Command\App\Remove::class));
	$application->add(Server::get(Command\App\Update::class));

	$application->add(Server::get(Command\TwoFactorAuth\Cleanup::class));
	$application->add(Server::get(Command\TwoFactorAuth\Enforce::class));
	$application->add(Server::get(Command\TwoFactorAuth\Enable::class));
	$application->add(Server::get(Command\TwoFactorAuth\Disable::class));
	$application->add(Server::get(Command\TwoFactorAuth\State::class));

	$application->add(Server::get(Command\Background\Cron::class));
	$application->add(Server::get(Command\Background\WebCron::class));
	$application->add(Server::get(Command\Background\Ajax::class));
	$application->add(Server::get(Command\Background\Job::class));
	$application->add(Server::get(Command\Background\ListCommand::class));
	$application->add(Server::get(Command\Background\Delete::class));

	$application->add(Server::get(Command\Broadcast\Test::class));

	$application->add(Server::get(Command\Config\App\DeleteConfig::class));
	$application->add(Server::get(Command\Config\App\GetConfig::class));
	$application->add(Server::get(Command\Config\App\SetConfig::class));
	$application->add(Server::get(Command\Config\Import::class));
	$application->add(Server::get(Command\Config\ListConfigs::class));
	$application->add(Server::get(Command\Config\System\DeleteConfig::class));
	$application->add(Server::get(Command\Config\System\GetConfig::class));
	$application->add(Server::get(Command\Config\System\SetConfig::class));

	$application->add(Server::get(Command\Info\File::class));
	$application->add(Server::get(Command\Info\Space::class));

	$application->add(Server::get(Command\Db\ConvertType::class));
	$application->add(Server::get(Command\Db\ConvertMysqlToMB4::class));
	$application->add(Server::get(Command\Db\ConvertFilecacheBigInt::class));
	$application->add(Server::get(Command\Db\AddMissingColumns::class));
	$application->add(Server::get(Command\Db\AddMissingIndices::class));
	$application->add(Server::get(Command\Db\AddMissingPrimaryKeys::class));

	if ($config->getSystemValueBool('debug', false)) {
		$application->add(Server::get(Command\Db\Migrations\StatusCommand::class));
		$application->add(Server::get(Command\Db\Migrations\MigrateCommand::class));
		$application->add(Server::get(Command\Db\Migrations\GenerateCommand::class));
		$application->add(Server::get(Command\Db\Migrations\ExecuteCommand::class));
	}

	$application->add(Server::get(Command\Encryption\Disable::class));
	$application->add(Server::get(Command\Encryption\Enable::class));
	$application->add(Server::get(Command\Encryption\ListModules::class));
	$application->add(Server::get(Command\Encryption\SetDefaultModule::class));
	$application->add(Server::get(Command\Encryption\Status::class));
	$application->add(Server::get(Command\Encryption\EncryptAll::class));
	$application->add(Server::get(Command\Encryption\DecryptAll::class));

	$application->add(Server::get(Command\Log\Manage::class));
	$application->add(Server::get(Command\Log\File::class));

	$application->add(Server::get(Command\Encryption\ChangeKeyStorageRoot::class));
	$application->add(Server::get(Command\Encryption\ShowKeyStorageRoot::class));
	$application->add(Server::get(Command\Encryption\MigrateKeyStorage::class));

	$application->add(Server::get(Command\Maintenance\DataFingerprint::class));
	$application->add(Server::get(Command\Maintenance\Mimetype\UpdateDB::class));
	$application->add(Server::get(Command\Maintenance\Mimetype\UpdateJS::class));
	$application->add(Server::get(Command\Maintenance\Mode::class));
	$application->add(Server::get(Command\Maintenance\UpdateHtaccess::class));
	$application->add(Server::get(Command\Maintenance\UpdateTheme::class));

	$application->add(Server::get(Command\Upgrade::class));
	$application->add(Server::get(Command\Maintenance\Repair::class));
	$application->add(Server::get(Command\Maintenance\RepairShareOwnership::class));

	$application->add(Server::get(Command\Preview\Generate::class));
	$application->add(Server::get(Command\Preview\Repair::class));
	$application->add(Server::get(Command\Preview\ResetRenderedTexts::class));

	$application->add(Server::get(Command\User\Add::class));
	$application->add(Server::get(Command\User\Delete::class));
	$application->add(Server::get(Command\User\Disable::class));
	$application->add(Server::get(Command\User\Enable::class));
	$application->add(Server::get(Command\User\LastSeen::class));
	$application->add(Server::get(Command\User\Report::class));
	$application->add(Server::get(Command\User\ResetPassword::class));
	$application->add(Server::get(Command\User\Setting::class));
	$application->add(Server::get(Command\User\ListCommand::class));
	$application->add(Server::get(Command\User\Info::class));
	$application->add(Server::get(Command\User\SyncAccountDataCommand::class));
	$application->add(Server::get(Command\User\AuthTokens\Add::class));
	$application->add(Server::get(Command\User\AuthTokens\ListCommand::class));
	$application->add(Server::get(Command\User\AuthTokens\Delete::class));
	$application->add(Server::get(Command\User\Keys\Verify::class));

	$application->add(Server::get(Command\Group\Add::class));
	$application->add(Server::get(Command\Group\Delete::class));
	$application->add(Server::get(Command\Group\ListCommand::class));
	$application->add(Server::get(Command\Group\AddUser::class));
	$application->add(Server::get(Command\Group\RemoveUser::class));
	$application->add(Server::get(Command\Group\Info::class));

	$application->add(Server::get(Command\SystemTag\ListCommand::class));
	$application->add(Server::get(Command\SystemTag\Delete::class));
	$application->add(Server::get(Command\SystemTag\Add::class));
	$application->add(Server::get(Command\SystemTag\Edit::class));

	$application->add(Server::get(Command\Security\ListCertificates::class));
	$application->add(Server::get(Command\Security\ImportCertificate::class));
	$application->add(Server::get(Command\Security\RemoveCertificate::class));
	$application->add(Server::get(Command\Security\BruteforceAttempts::class));
	$application->add(Server::get(Command\Security\BruteforceResetAttempts::class));
	$application->add(Server::get(Command\SetupChecks::class));
	$application->add(Server::get(Command\FilesMetadata\Get::class));
} else {
	$application->add(Server::get(Command\Maintenance\Install::class));
}
