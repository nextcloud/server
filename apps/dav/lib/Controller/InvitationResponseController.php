<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\IRequest;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Reader;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class InvitationResponseController extends Controller {

	/**
	 * InvitationResponseController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IDBConnection $db
	 * @param ITimeFactory $timeFactory
	 * @param InvitationResponseServer $responseServer
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private IDBConnection $db,
		private ITimeFactory $timeFactory,
		private InvitationResponseServer $responseServer,
	) {
		parent::__construct($appName, $request);
		// Don't run `$server->exec()`, because we just need access to the
		// fully initialized schedule plugin, but we don't want Sabre/DAV
		// to actually handle and reply to the request
	}

	/**
	 * @param string $token
	 * @return TemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function accept(string $token):TemplateResponse {
		$row = $this->getTokenInformation($token);
		if (!$row) {
			return new TemplateResponse($this->appName, 'schedule-response-error', [], 'guest');
		}

		$iTipMessage = $this->buildITipResponse($row, 'ACCEPTED');
		$this->responseServer->handleITipMessage($iTipMessage);
		if ($iTipMessage->getScheduleStatus() === '1.2') {
			return new TemplateResponse($this->appName, 'schedule-response-success', [], 'guest');
		}

		return new TemplateResponse($this->appName, 'schedule-response-error', [
			'organizer' => $row['organizer'],
		], 'guest');
	}

	/**
	 * @param string $token
	 * @return TemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function decline(string $token):TemplateResponse {
		$row = $this->getTokenInformation($token);
		if (!$row) {
			return new TemplateResponse($this->appName, 'schedule-response-error', [], 'guest');
		}

		$iTipMessage = $this->buildITipResponse($row, 'DECLINED');
		$this->responseServer->handleITipMessage($iTipMessage);

		if ($iTipMessage->getScheduleStatus() === '1.2') {
			return new TemplateResponse($this->appName, 'schedule-response-success', [], 'guest');
		}

		return new TemplateResponse($this->appName, 'schedule-response-error', [
			'organizer' => $row['organizer'],
		], 'guest');
	}

	/**
	 * @param string $token
	 * @return TemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function options(string $token):TemplateResponse {
		return new TemplateResponse($this->appName, 'schedule-response-options', [
			'token' => $token
		], 'guest');
	}

	/**
	 * @param string $token
	 *
	 * @return TemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function processMoreOptionsResult(string $token):TemplateResponse {
		$partstat = $this->request->getParam('partStat');

		$row = $this->getTokenInformation($token);
		if (!$row || !\in_array($partstat, ['ACCEPTED', 'DECLINED', 'TENTATIVE'])) {
			return new TemplateResponse($this->appName, 'schedule-response-error', [], 'guest');
		}

		$iTipMessage = $this->buildITipResponse($row, $partstat);
		$this->responseServer->handleITipMessage($iTipMessage);
		if ($iTipMessage->getScheduleStatus() === '1.2') {
			return new TemplateResponse($this->appName, 'schedule-response-success', [], 'guest');
		}

		return new TemplateResponse($this->appName, 'schedule-response-error', [
			'organizer' => $row['organizer'],
		], 'guest');
	}

	/**
	 * @param string $token
	 * @return array|null
	 */
	private function getTokenInformation(string $token) {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('calendar_invitations')
			->where($query->expr()->eq('token', $query->createNamedParameter($token)));
		$stmt = $query->executeQuery();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		if (!$row) {
			return null;
		}

		$currentTime = $this->timeFactory->getTime();
		if (((int)$row['expiration']) < $currentTime) {
			return null;
		}

		return $row;
	}

	/**
	 * @param array $row
	 * @param string $partStat participation status of attendee - SEE RFC 5545
	 * @param int|null $guests
	 * @param string|null $comment
	 * @return Message
	 */
	private function buildITipResponse(array $row, string $partStat):Message {
		$iTipMessage = new Message();
		$iTipMessage->uid = $row['uid'];
		$iTipMessage->component = 'VEVENT';
		$iTipMessage->method = 'REPLY';
		$iTipMessage->sequence = $row['sequence'];
		$iTipMessage->sender = $row['attendee'];

		if ($this->responseServer->isExternalAttendee($row['attendee'])) {
			$iTipMessage->recipient = $row['organizer'];
		} else {
			$iTipMessage->recipient = $row['attendee'];
		}

		$message = <<<EOF
BEGIN:VCALENDAR
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
VERSION:2.0
BEGIN:VEVENT
ATTENDEE;PARTSTAT=%s:%s
ORGANIZER:%s
UID:%s
SEQUENCE:%s
REQUEST-STATUS:2.0;Success
%sEND:VEVENT
END:VCALENDAR
EOF;

		$vObject = Reader::read(vsprintf($message, [
			$partStat, $row['attendee'], $row['organizer'],
			$row['uid'], $row['sequence'] ?? 0, $row['recurrenceid'] ?? ''
		]));
		$vEvent = $vObject->{'VEVENT'};
		$vEvent->DTSTAMP = date('Ymd\\THis\\Z', $this->timeFactory->getTime());
		$iTipMessage->message = $vObject;

		return $iTipMessage;
	}
}
