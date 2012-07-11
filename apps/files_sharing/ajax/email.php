<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');
$user = OCP\USER::getUser();
// TODO translations
$type = (strpos($_POST['file'], '.') === false) ? 'folder' : 'file';
$subject = $user.' shared a '.$type.' with you';
$link = $_POST['link'];
$text = $user.' shared the '.$type.' '.$_POST['file'].' with you. It is available for download here: '.$link;
$fromaddress = OCP\Config::getUserValue($user, 'settings', 'email', 'sharing-noreply@'.OCP\Util::getServerHost());
OCP\Util::sendMail($_POST['toaddress'], $_POST['toaddress'], $subject, $text, $fromaddress, $user);
