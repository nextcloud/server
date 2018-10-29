<div class="update">
	<p class="message"><?php p($l->t('There was an error updating your attendance status.'));?></p>
	<p class="message"><?php p($l->t('Please contact the organizer directly.'));?></p>
	<?php if(isset($_['organizer'])): ?>
		<p class="message"><a href="<?php p($_['organizer']) ?>"><?php p(substr($_['organizer'], 7)) ?></a></p>
	<?php endif; ?>
</div>
