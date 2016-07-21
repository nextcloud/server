<?php
/**

 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
style('core', ['styles', 'header']);
?>
<span class="error error-wide">
	<h2><strong><?php p($_['title']) ?></strong></h2>
		<p><?php p($_['message']) ?></p>
	<br>

	<h2><strong><?php p($l->t('Technical details')) ?></strong></h2>
	<ul>
		<li><?php p($l->t('Remote Address: %s', $_['remoteAddr'])) ?></li>
		<li><?php p($l->t('Request ID: %s', $_['requestID'])) ?></li>
		<?php if($_['debugMode']): ?>
			<li><?php p($l->t('Type: %s', $_['errorClass'])) ?></li>
			<li><?php p($l->t('Code: %s', $_['errorCode'])) ?></li>
			<li><?php p($l->t('Message: %s', $_['errorMsg'])) ?></li>
			<li><?php p($l->t('File: %s', $_['file'])) ?></li>
			<li><?php p($l->t('Line: %s', $_['line'])) ?></li>
		<?php endif; ?>
	</ul>

	<?php if($_['debugMode']): ?>
		<br />
		<h2><strong><?php p($l->t('Trace')) ?></strong></h2>
		<pre><?php p($_['trace']) ?></pre>
	<?php endif; ?>
</span>
