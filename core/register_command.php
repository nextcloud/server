<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christian Kampka <christian@kampka.net>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OC\Console\Application;
use Psr\Log\LoggerInterface;

/**
 * @var Application $this
 */

$this->add(new \Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand());
$this->add(new OC\Core\Command\Status(\OC::$server->get(\OCP\IConfig::class), \OC::$server->get(\OCP\Defaults::class)));
$this->add(new OC\Core\Command\Check(\OC::$server->getSystemConfig()));
$this->add(new OC\Core\Command\L10n\CreateJs());
$this->add(new \OC\Core\Command\Integrity\SignApp(
	\OC::$server->getIntegrityCodeChecker(),
	new \OC\IntegrityCheck\Helpers\FileAccessHelper(),
	\OC::$server->getURLGenerator()
));
$this->add(new \OC\Core\Command\Integrity\SignCore(
	\OC::$server->getIntegrityCodeChecker(),
	new \OC\IntegrityCheck\Helpers\FileAccessHelper()
));
$this->add(new \OC\Core\Command\Integrity\CheckApp(
	\OC::$server->getIntegrityCodeChecker()
));
$this->add(new \OC\Core\Command\Integrity\CheckCore(
	\OC::$server->getIntegrityCodeChecker()
));


if (\OC::$server->getConfig()->getSystemValue('installed', false)) {
	$this->add(new OC\Core\Command\App\Disable(\OC::$server->getAppManager()));
	$this->add(new OC\Core\Command\App\Enable(\OC::$server->getAppManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\App\Install());
	$this->add(new OC\Core\Command\App\GetPath());
	$this->add(new OC\Core\Command\App\ListApps(\OC::$server->getAppManager()));
	$this->add(new OC\Core\Command\App\Remove(\OC::$server->getAppManager(), \OC::$server->query(\OC\Installer::class), \OC::$server->get(LoggerInterface::class)));
	$this->add(\OC::$server->query(\OC\Core\Command\App\Update::class));

	$this->add(\OC::$server->query(\OC\Core\Command\TwoFactorAuth\Cleanup::class));
	$this->add(\OC::$server->query(\OC\Core\Command\TwoFactorAuth\Enforce::class));
	$this->add(\OC::$server->query(\OC\Core\Command\TwoFactorAuth\Enable::class));
	$this->add(\OC::$server->query(\OC\Core\Command\TwoFactorAuth\Disable::class));
	$this->add(\OC::$server->query(\OC\Core\Command\TwoFactorAuth\State::class));

	$this->add(new OC\Core\Command\Background\Cron(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Background\WebCron(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Background\Ajax(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Background\Job(\OC::$server->getJobList(), \OC::$server->getLogger()));
	$this->add(new OC\Core\Command\Background\ListCommand(\OC::$server->getJobList()));

	$this->add(\OC::$server->query(\OC\Core\Command\Broadcast\Test::class));

	$this->add(new OC\Core\Command\Config\App\DeleteConfig(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Config\App\GetConfig(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Config\App\SetConfig(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Config\Import(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Config\ListConfigs(\OC::$server->getSystemConfig(), \OC::$server->getAppConfig()));
	$this->add(new OC\Core\Command\Config\System\DeleteConfig(\OC::$server->getSystemConfig()));
	$this->add(new OC\Core\Command\Config\System\GetConfig(\OC::$server->getSystemConfig()));
	$this->add(new OC\Core\Command\Config\System\SetConfig(\OC::$server->getSystemConfig()));

	$this->add(\OC::$server->get(OC\Core\Command\Info\File::class));

	$this->add(new OC\Core\Command\Db\ConvertType(\OC::$server->getConfig(), new \OC\DB\ConnectionFactory(\OC::$server->getSystemConfig())));
	$this->add(new OC\Core\Command\Db\ConvertMysqlToMB4(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection(), \OC::$server->getURLGenerator(), \OC::$server->get(LoggerInterface::class)));
	$this->add(new OC\Core\Command\Db\ConvertFilecacheBigInt(\OC::$server->get(\OC\DB\Connection::class)));
	$this->add(new OC\Core\Command\Db\AddMissingIndices(\OC::$server->get(\OC\DB\Connection::class), \OC::$server->getEventDispatcher()));
	$this->add(new OC\Core\Command\Db\AddMissingColumns(\OC::$server->get(\OC\DB\Connection::class), \OC::$server->getEventDispatcher()));
	$this->add(new OC\Core\Command\Db\AddMissingPrimaryKeys(\OC::$server->get(\OC\DB\Connection::class), \OC::$server->getEventDispatcher()));

	if (\OC::$server->getConfig()->getSystemValueBool('debug', false)) {
		$this->add(new OC\Core\Command\Db\Migrations\StatusCommand(\OC::$server->get(\OC\DB\Connection::class)));
		$this->add(new OC\Core\Command\Db\Migrations\MigrateCommand(\OC::$server->get(\OC\DB\Connection::class)));
		$this->add(new OC\Core\Command\Db\Migrations\GenerateCommand(\OC::$server->get(\OC\DB\Connection::class), \OC::$server->getAppManager()));
		$this->add(new OC\Core\Command\Db\Migrations\ExecuteCommand(\OC::$server->get(\OC\DB\Connection::class), \OC::$server->getConfig()));
	}

	$this->add(new OC\Core\Command\Encryption\Disable(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Encryption\Enable(\OC::$server->getConfig(), \OC::$server->getEncryptionManager()));
	$this->add(new OC\Core\Command\Encryption\ListModules(\OC::$server->getEncryptionManager(), \OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Encryption\SetDefaultModule(\OC::$server->getEncryptionManager(), \OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Encryption\Status(\OC::$server->getEncryptionManager()));
	$this->add(new OC\Core\Command\Encryption\EncryptAll(\OC::$server->getEncryptionManager(), \OC::$server->getAppManager(), \OC::$server->getConfig(), new \Symfony\Component\Console\Helper\QuestionHelper()));
	$this->add(new OC\Core\Command\Encryption\DecryptAll(
		\OC::$server->getEncryptionManager(),
		\OC::$server->getAppManager(),
		\OC::$server->getConfig(),
		new \OC\Encryption\DecryptAll(\OC::$server->getEncryptionManager(), \OC::$server->getUserManager(), new \OC\Files\View()),
		new \Symfony\Component\Console\Helper\QuestionHelper())
	);

	$this->add(new OC\Core\Command\Log\Manage(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Log\File(\OC::$server->getConfig()));

	$view = new \OC\Files\View();
	$util = new \OC\Encryption\Util(
		$view,
		\OC::$server->getUserManager(),
		\OC::$server->getGroupManager(),
		\OC::$server->getConfig()
	);
	$this->add(new OC\Core\Command\Encryption\ChangeKeyStorageRoot(
		$view,
		\OC::$server->getUserManager(),
		\OC::$server->getConfig(),
		$util,
		new \Symfony\Component\Console\Helper\QuestionHelper()
	)
	);
	$this->add(new OC\Core\Command\Encryption\ShowKeyStorageRoot($util));
	$this->add(new OC\Core\Command\Encryption\MigrateKeyStorage(
		$view,
		\OC::$server->getUserManager(),
		\OC::$server->getConfig(),
		$util,
		\OC::$server->getCrypto()
	)
	);

	$this->add(new OC\Core\Command\Maintenance\DataFingerprint(\OC::$server->getConfig(), new \OC\AppFramework\Utility\TimeFactory()));
	$this->add(new OC\Core\Command\Maintenance\Mimetype\UpdateDB(\OC::$server->getMimeTypeDetector(), \OC::$server->getMimeTypeLoader()));
	$this->add(new OC\Core\Command\Maintenance\Mimetype\UpdateJS(\OC::$server->getMimeTypeDetector()));
	$this->add(new OC\Core\Command\Maintenance\Mode(\OC::$server->getConfig()));
	$this->add(new OC\Core\Command\Maintenance\UpdateHtaccess());
	$this->add(new OC\Core\Command\Maintenance\UpdateTheme(\OC::$server->getMimeTypeDetector(), \OC::$server->getMemCacheFactory()));

	$this->add(new OC\Core\Command\Upgrade(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), \OC::$server->query(\OC\Installer::class)));
	$this->add(new OC\Core\Command\Maintenance\Repair(
		new \OC\Repair([], \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class), \OC::$server->get(LoggerInterface::class)),
		\OC::$server->getConfig(),
		\OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class),
		\OC::$server->getAppManager()
	));
	$this->add(\OC::$server->query(OC\Core\Command\Maintenance\RepairShareOwnership::class));

	$this->add(\OC::$server->get(\OC\Core\Command\Preview\Generate::class));
	$this->add(\OC::$server->query(\OC\Core\Command\Preview\Repair::class));
	$this->add(\OC::$server->query(\OC\Core\Command\Preview\ResetRenderedTexts::class));

	$this->add(new OC\Core\Command\User\Add(\OC::$server->getUserManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\User\Delete(\OC::$server->getUserManager()));
	$this->add(new OC\Core\Command\User\Disable(\OC::$server->getUserManager()));
	$this->add(new OC\Core\Command\User\Enable(\OC::$server->getUserManager()));
	$this->add(new OC\Core\Command\User\LastSeen(\OC::$server->getUserManager()));
	$this->add(\OC::$server->get(\OC\Core\Command\User\Report::class));
	$this->add(new OC\Core\Command\User\ResetPassword(\OC::$server->getUserManager(), \OC::$server->getAppManager()));
	$this->add(new OC\Core\Command\User\Setting(\OC::$server->getUserManager(), \OC::$server->getConfig()));
	$this->add(new OC\Core\Command\User\ListCommand(\OC::$server->getUserManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\User\Info(\OC::$server->getUserManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\User\AddAppPassword(\OC::$server->get(\OCP\IUserManager::class), \OC::$server->get(\OC\Authentication\Token\IProvider::class), \OC::$server->get(\OCP\Security\ISecureRandom::class), \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class)));

	$this->add(new OC\Core\Command\Group\Add(\OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\Group\Delete(\OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\Group\ListCommand(\OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\Group\AddUser(\OC::$server->getUserManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\Group\RemoveUser(\OC::$server->getUserManager(), \OC::$server->getGroupManager()));
	$this->add(new OC\Core\Command\Group\Info(\OC::$server->get(\OCP\IGroupManager::class)));

	$this->add(new OC\Core\Command\SystemTag\ListCommand(\OC::$server->get(\OCP\SystemTag\ISystemTagManager::class)));
	$this->add(new OC\Core\Command\SystemTag\Delete(\OC::$server->get(\OCP\SystemTag\ISystemTagManager::class)));
	$this->add(new OC\Core\Command\SystemTag\Add(\OC::$server->get(\OCP\SystemTag\ISystemTagManager::class)));
	$this->add(new OC\Core\Command\SystemTag\Edit(\OC::$server->get(\OCP\SystemTag\ISystemTagManager::class)));

	$this->add(new OC\Core\Command\Security\ListCertificates(\OC::$server->getCertificateManager(), \OC::$server->getL10N('core')));
	$this->add(new OC\Core\Command\Security\ImportCertificate(\OC::$server->getCertificateManager()));
	$this->add(new OC\Core\Command\Security\RemoveCertificate(\OC::$server->getCertificateManager()));
	$this->add(new OC\Core\Command\Security\ResetBruteforceAttempts(\OC::$server->getBruteForceThrottler()));

	if (\OC::$server->getConfig()->getSystemValueBool('maintenance')) {
		$this->add(new \OC\Core\Command\CommandUnavailableInMaintenanceMode());
	}
} else {
	$this->add(\OC::$server->get(\OC\Core\Command\Maintenance\Install::class));
}
