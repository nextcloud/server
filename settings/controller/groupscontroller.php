<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Controller;

use OC\AppFramework\Http;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @package OC\Settings\Controller
 */
class GroupsController extends Controller {
	/** @var IGroupManager */
	private $groupManager;
	/** @var IL10N */
	private $l10n;
	/** @var IUserSession */
	private $userSession;
	/** @var bool */
	private $isAdmin;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param bool $isAdmin
	 * @param IL10N $l10n
	 */
	public function __construct($appName,
								IRequest $request,
								IGroupManager $groupManager,
								IUserSession $userSession,
								$isAdmin,
								IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->isAdmin = $isAdmin;
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $pattern
	 * @param bool $filterGroups
	 * @return DataResponse
	 */
	public function index($pattern = '', $filterGroups = false) {
		$groupPattern = $filterGroups ? $pattern : '';

		$groupsInfo = new \OC\Group\MetaData($this->userSession->getUser()->getUID(),
			$this->isAdmin, $this->groupManager);
		$groupsInfo->setSorting($groupsInfo::SORT_USERCOUNT);
		list($adminGroups, $groups) = $groupsInfo->get($groupPattern, $pattern);

		return new DataResponse(
			array(
				'data' => array('adminGroups' => $adminGroups, 'groups' => $groups)
			)
		);
	}

	/**
	 * @param string $id
	 * @return DataResponse
	 */
	public function create($id) {
		if($this->groupManager->groupExists($id)) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Group already exists.')
				),
				Http::STATUS_CONFLICT
			);
		}
		if($this->groupManager->createGroup($id)) {
			return new DataResponse(
				array(
					'groupname' => $id
				),
				Http::STATUS_CREATED
			);
		}

		return new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => (string)$this->l10n->t('Unable to add group.')
				)
			),
			Http::STATUS_FORBIDDEN
		);
	}

	/**
	 * @param string $id
	 * @return DataResponse
	 */
	public function destroy($id) {
		$group = $this->groupManager->get($id);
		if ($group) {
			if ($group->delete()) {
				return new DataResponse(
					array(
						'status' => 'success',
						'data' => array(
							'groupname' => $id
						)
					),
					Http::STATUS_NO_CONTENT
				);
			}
		}
		return new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => (string)$this->l10n->t('Unable to delete group.')
				),
			),
			Http::STATUS_FORBIDDEN
		);
	}

}
