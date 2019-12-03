<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Notification;

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @param IFactory $l10nFactory
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator) {
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
	}

	public function getID(): string {
		return 'files';
	}

	public function getName(): string {
		return $this->l10nFactory->get('files')->t('Files');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'files') {
			throw new \InvalidArgumentException('Unhandled app');
		}

		if ($notification->getSubject() === 'transferownershipRequest') {
			return $this->handleTransferownershipRequest($notification, $languageCode);
		}
		if ($notification->getSubject() === 'transferOwnershipFailedSource') {
			return $this->handleTransferOwnershipFailedSource($notification, $languageCode);
		}
		if ($notification->getSubject() === 'transferOwnershipFailedTarget') {
			return $this->handleTransferOwnershipFailedTarget($notification, $languageCode);
		}
		if ($notification->getSubject() === 'transferOwnershipDoneSource') {
			return $this->handleTransferOwnershipDoneSource($notification, $languageCode);
		}
		if ($notification->getSubject() === 'transferOwnershipDoneTarget') {
			return $this->handleTransferOwnershipDoneTarget($notification, $languageCode);
		}

		throw new \InvalidArgumentException('Unhandled subject');
	}

	public function handleTransferownershipRequest(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$id = $notification->getObjectId();
		$param = $notification->getSubjectParameters();

		$approveAction = $notification->createAction()
			->setParsedLabel($l->t('Accept'))
			->setPrimary(true)
			->setLink(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkTo(
						'',
						'ocs/v2.php/apps/files/api/v1/transferownership/' . $id
					)
				),
				IAction::TYPE_POST
			);

		$disapproveAction = $notification->createAction()
			->setParsedLabel($l->t('Decline'))
			->setPrimary(false)
			->setLink(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkTo(
						'',
						'ocs/v2.php/apps/files/api/v1/transferownership/' . $id
					)
				),
				IAction::TYPE_DELETE
			);

		$notification->addParsedAction($approveAction)
			->addParsedAction($disapproveAction)
			->setRichSubject(
				$l->t('Incoming file transfer from {user}'),
				[
					'user' => [
						'type' => 'user',
						'id' => $param['sourceUser'],
						'name' => $param['sourceUser'],
					],
				])
			->setParsedSubject(str_replace('{user}', $param['sourceUser'], $l->t('Incoming file transfer from {user}')))
			->setRichMessage(
				$l->t('Do you want to accept {path}?'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					]
				])
			->setParsedMessage(str_replace('{path}', $param['nodeName'], $l->t('Do you want to accept {path}?')));

		return $notification;
	}

	public function handleTransferOwnershipFailedSource(INotification $notification,  string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$notification->setRichSubject($l->t('File transfer failed'))
			->setParsedSubject($l->t('File transfer failed'))

			->setRichMessage(
				$l->t('Your transfer of {path} to {user} failed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $param['targetUser'],
						'name' => $param['targetUser'],
					],
				])
			->setParsedMessage(str_replace(['{path}', '{user}'], [$param['nodeName'], $param['targetUser']], $l->t('Your transfer of {path} to {user} failed.')));
		return $notification;
	}

	public function handleTransferOwnershipFailedTarget(INotification $notification,  string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$notification->setRichSubject($l->t('File transfer failed'))
			->setParsedSubject($l->t('File transfer failed'))

			->setRichMessage(
				$l->t('The transfer of {path} from {user} failed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['sourceUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $param['sourceUser'],
						'name' => $param['sourceUser'],
					],
				])
			->setParsedMessage(str_replace(['{path}', '{user}'], [$param['nodeName'], $param['sourceUser']], $l->t('The transfer of {path} from {user} failed.')));

		return $notification;
	}

	public function handleTransferOwnershipDoneSource(INotification $notification,  string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$notification->setRichSubject($l->t('File transfer done'))
			->setParsedSubject($l->t('File transfer done'))

			->setRichMessage(
				$l->t('Your transfer of {path} to {user} has completed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $param['targetUser'],
						'name' => $param['targetUser'],
					],
				])
			->setParsedMessage(str_replace(['{path}', '{user}'], [$param['nodeName'], $param['targetUser']], $l->t('Your transfer of {path} to {user} has completed.')));

		return $notification;
	}

	public function handleTransferOwnershipDoneTarget(INotification $notification,  string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$notification->setRichSubject($l->t('File transfer done'))
			->setParsedSubject($l->t('File transfer done'))

			->setRichMessage(
				$l->t('The transfer of {path} from {user} has completed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['sourceUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $param['sourceUser'],
						'name' => $param['sourceUser'],
					],
				])
			->setParsedMessage(str_replace(['{path}', '{user}'], [$param['nodeName'], $param['sourceUser']], $l->t('The transfer of {path} from {user} has completed.')));

		return $notification;
	}
}
