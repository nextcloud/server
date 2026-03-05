<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\OCM\Listeners;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\OCM\Enum\ParamType;
use OCP\OCM\Events\OCMEndpointRequestEvent;

/** @template-implements IEventListener<\OCP\OCM\Events\OCMEndpointRequestEvent> */
class OCMEndpointRequestTestEvent implements IEventListener {
	public function __construct(
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof OCMEndpointRequestEvent)) {
			return;
		}

		if ($event->getPath() === '/') {
			$event->setResponse(new Response(404));
			return;
		}

		$event->setResponse(new DataResponse(
			[
				'capability' => $event->getRequestedCapability(),
				'path' => $event->getPath(),
				'args' => $event->getArgs(),
				'totalArgs' => $event->getArgsCount(),
				'typedArgs' => $event->getArgs(
					ParamType::STRING,
					ParamType::STRING,
					ParamType::INT,
					ParamType::BOOL,
					ParamType::INT
				)
			]
		));
	}
}
