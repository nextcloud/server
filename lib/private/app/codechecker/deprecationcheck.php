<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\App\CodeChecker;

class DeprecationCheck extends AbstractCheck implements ICheck {
	/**
	 * @return string
	 */
	protected function getLocalDescription() {
		return 'deprecated';
	}

	/**
	 * @return array E.g.: `'ClassName' => 'oc version',`
	 */
	protected function getLocalClasses() {
		return [
			'OC_JSON' => '8.2.0',

			'OCP\Config' => '8.0.0',
			'OCP\Contacts' => '8.1.0',
			'OCP\DB' => '8.1.0',
			'OCP\IHelper' => '8.1.0',
			'OCP\JSON' => '8.1.0',
			'OCP\Response' => '8.1.0',
			'OCP\AppFramework\IApi' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'ClassName::CONSTANT_NAME' => 'oc version',`
	 */
	protected function getLocalConstants() {
		return [
			'OC_API::GUEST_AUTH' => '8.2.0',
			'OC_API::USER_AUTH' => '8.2.0',
			'OC_API::SUBADMIN_AUTH' => '8.2.0',
			'OC_API::ADMIN_AUTH' => '8.2.0',
			'OC_API::RESPOND_UNAUTHORISED' => '8.2.0',
			'OC_API::RESPOND_SERVER_ERROR' => '8.2.0',
			'OC_API::RESPOND_NOT_FOUND' => '8.2.0',
			'OC_API::RESPOND_UNKNOWN_ERROR' => '8.2.0',

			'OCP::PERMISSION_CREATE' => '8.0.0',
			'OCP::PERMISSION_READ' => '8.0.0',
			'OCP::PERMISSION_UPDATE' => '8.0.0',
			'OCP::PERMISSION_DELETE' => '8.0.0',
			'OCP::PERMISSION_SHARE' => '8.0.0',
			'OCP::PERMISSION_ALL' => '8.0.0',
			'OCP::FILENAME_INVALID_CHARS' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'functionName' => 'oc version',`
	 */
	protected function getLocalFunctions() {
		return [
			'OCP::image_path' => '8.0.0',
			'OCP::mimetype_icon' => '8.0.0',
			'OCP::preview_icon' => '8.0.0',
			'OCP::publicPreview_icon' => '8.0.0',
			'OCP::human_file_size' => '8.0.0',
			'OCP::relative_modified_date' => '8.0.0',
			'OCP::simple_file_size' => '8.0.0',
			'OCP::html_select_options' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'ClassName::methodName' => 'oc version',`
	 */
	protected function getLocalMethods() {
		return [
			'OC_L10N::get' => '8.2.0',

			'OCP\Activity\IManager::publishActivity' => '8.2.0',

			'OCP\App::register' => '8.1.0',
			'OCP\App::addNavigationEntry' => '8.1.0',
			'OCP\App::getActiveNavigationEntry' => '8.2.0',
			'OCP\App::setActiveNavigationEntry' => '8.1.0',

			'OCP\AppFramework\Controller::params' => '7.0.0',
			'OCP\AppFramework\Controller::getParams' => '7.0.0',
			'OCP\AppFramework\Controller::method' => '7.0.0',
			'OCP\AppFramework\Controller::getUploadedFile' => '7.0.0',
			'OCP\AppFramework\Controller::env' => '7.0.0',
			'OCP\AppFramework\Controller::cookie' => '7.0.0',
			'OCP\AppFramework\Controller::render' => '7.0.0',

			'OCP\AppFramework\IAppContainer::getCoreApi' => '8.0.0',
			'OCP\AppFramework\IAppContainer::isLoggedIn' => '8.0.0',
			'OCP\AppFramework\IAppContainer::isAdminUser' => '8.0.0',
			'OCP\AppFramework\IAppContainer::log' => '8.0.0',

			'OCP\BackgroundJob::addQueuedTask' => '6.0.0',
			'OCP\BackgroundJob::addRegularTask' => '6.0.0',
			'OCP\BackgroundJob::allQueuedTasks' => '6.0.0',
			'OCP\BackgroundJob::allRegularTasks' => '6.0.0',
			'OCP\BackgroundJob::deleteQueuedTask' => '6.0.0',
			'OCP\BackgroundJob::findQueuedTask' => '6.0.0',
			'OCP\BackgroundJob::queuedTaskWhereAppIs' => '6.0.0',
			'OCP\BackgroundJob::registerJob' => '8.1.0',

			'OCP\Files::tmpFile' => '8.1.0',
			'OCP\Files::tmpFolder' => '8.1.0',

			'OCP\IAppConfig::getValue' => '8.0.0',
			'OCP\IAppConfig::deleteKey' => '8.0.0',
			'OCP\IAppConfig::getKeys' => '8.0.0',
			'OCP\IAppConfig::setValue' => '8.0.0',
			'OCP\IAppConfig::deleteApp' => '8.0.0',

			'OCP\IDBConnection::createQueryBuilder' => '8.2.0',
			'OCP\IDBConnection::getExpressionBuilder' => '8.2.0',

			'OCP\ISearch::search' => '8.0.0',

			'OCP\IServerContainer::getCache' => '8.2.0',
			'OCP\IServerContainer::getDb' => '8.1.0',
			'OCP\IServerContainer::getHTTPHelper' => '8.1.0',

			'OCP\User::getUser' => '8.0.0',
			'OCP\User::getUsers' => '8.1.0',
			'OCP\User::getDisplayName' => '8.1.0',
			'OCP\User::getDisplayNames' => '8.1.0',
			'OCP\User::userExists' => '8.1.0',
			'OCP\User::logout' => '8.1.0',
			'OCP\User::checkPassword' => '8.1.0',

			'OCP\Util::encryptedFiles' => '8.1.0',
			'OCP\Util::formatDate' => '8.0.0',
			'OCP\Util::generateRandomBytes' => '8.1.0',
			'OCP\Util::getServerHost' => '8.1.0',
			'OCP\Util::getServerProtocol' => '8.1.0',
			'OCP\Util::getRequestUri' => '8.1.0',
			'OCP\Util::getScriptName' => '8.1.0',
			'OCP\Util::imagePath' => '8.1.0',
			'OCP\Util::isValidFileName' => '8.1.0',
			'OCP\Util::linkToRoute' => '8.1.0',
			'OCP\Util::linkTo' => '8.1.0',
			'OCP\Util::logException' => '8.2.0',
			'OCP\Util::mb_str_replace' => '8.2.0',
			'OCP\Util::mb_substr_replace' => '8.2.0',
			'OCP\Util::sendMail' => '8.1.0',
		];
	}
}
