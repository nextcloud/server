<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
OCP\JSON::checkAppEnabled('files_sharing');

// read post variables
$user = OCP\USER::getUser();
$type = $_POST['type'];
$link = $_POST['link'];
$file = $_POST['file'];
$to_address = $_POST['toaddress'];

// enable l10n support
$l = OC_L10N::get('files_sharing');

// setup the email
$subject = (string)$l->t('User %s shared a file with you', $user);
if ($type === 'dir')
	$subject = (string)$l->t('User %s shared a folder with you', $user);

$text = (string)$l->t('User %s shared the file "%s" with you. It is available for download here: %s', array($user, $file, $link));
if ($type === 'dir')
	$text = (string)$l->t('User %s shared the folder "%s" with you. It is available for download here: %s', array($user, $file, $link));

// handle localhost installations
$server_host = OCP\Util::getServerHost();
if ($server_host === 'localhost')
	$server_host = "example.com";

$default_from = 'sharing-noreply@' . $server_host;
$from_address = OCP\Config::getUserValue($user, 'settings', 'email', $default_from );

// send it out now
try {
	OCP\Util::sendMail($to_address, $to_address, $subject, $text, $from_address, $user);
	OCP\JSON::success();
} catch (Exception $exception) {
	OCP\JSON::error(array('data' => array('message' => $exception->getMessage())));
}
