<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\CloudFederationApi\Events;

use OCP\EventDispatcher\Event;
use OCA\CloudFederationApi\OCMInvitation;

class OCMInvitationAcceptedEvent extends Event
{
  public function __construct(
    private OCMInvitation $invitation
  ) {
    parent::__construct();
  }

  public function getInvitation(): OCMInvitation
  {
    return $this->invitation;
  }
}
