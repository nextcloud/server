<?php
namespace OCA\ShareByMail\Tests;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\IMessageSend;
interface DummyMailProviderService extends IService, IMessageSend {}
