<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::callCheck();

$user = OCP\USER::getUser();
// TODO translations
$toaddress = OCP\Util::sanitizeHtml($_POST['toaddress']);
$type = (strpos($_POST['file'], '.') === false) ? 'folder' : 'file';
$subject = $user.' shared a '.$type.' with you';
$link = $_POST['link'];
$text = $user.' shared the '.$type.' '.$_POST['file'].' with you. It is available for download here: '.$link;
$fromaddress = OCP\Config::getUserValue($user, 'settings', 'email', 'sharing-noreply@'.OCP\Util::getServerHost());
OCP\Util::sendMail($toaddress, $toaddress, $subject, $text, $fromaddress, $user);
