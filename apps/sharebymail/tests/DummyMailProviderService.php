<?php

namespace OCA\ShareByMail\Tests;

use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;

interface DummyMailProviderService extends IService, IMessageSend {
}
