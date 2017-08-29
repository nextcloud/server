<?php
	/** @var array $_ */
	/** @var \OCP\IL10N $l */

style('core', ['styles', 'header']);
?>
<div class="error error-wide">
	<h2><?php p($l->t('Internal Server Error')) ?></h2>
	<p><?php p($l->t('The server encountered an internal error and was unable to complete your request.')) ?></p>
	<p><?php p($l->t('Please contact the server administrator if this error reappears multiple times, please include the technical details below in your report.')) ?></p>
	<p><?php p($l->t('More details can be found in the server log.')) ?></p>

	<h3><?php p($l->t('Technical details')) ?></h3>
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
		<h3><?php p($l->t('Trace')) ?></h3>
		<pre><?php p($_['trace']) ?></pre>
	<?php endif; ?>
</div>
