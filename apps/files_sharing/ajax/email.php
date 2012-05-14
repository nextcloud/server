<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');
$user = OCP\USER::getUser();
// TODO translations
$subject = $user + ' ' + 'shared a file with you';
$link = $_POST['link'] + '&f=' + $_POST['f'];
$text = $user + ' ' + 'shared the file' + ' ' + $_POST['f'] + ' ' + 'with you.' + ' ' + 'It is available for download here:' + ' ' + $link;
$fromaddress = OCP\Config::getUserValue($user, 'settings', 'email', 'sharing-noreply@'.$_SERVER['HTTP_HOST']);
OC_Mail::send($_POST['toaddress'], $_POST['toaddress'], $subject, $text, $fromaddress, $user);

?>