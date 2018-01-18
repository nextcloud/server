<?php
declare(strict_types=1);
/**
 * @copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */
if (\OC::$server->getConfig()->getSystemValue('debug', false)) {
	script('updatenotification', 'vue');
} else {
	script('updatenotification', 'vue.min');
}
script('updatenotification', [
	'components/root',
	'admin',
]);
style('updatenotification', 'admin');
/** @var array $_ */
?>
<div id="updatenotification" data-json="<?php p($_['json']); ?>"></div>
