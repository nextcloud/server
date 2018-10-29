<?php
style('dav', 'schedule-response');
//script('dav', 'schedule-response');
?>

<div class="update">
	<form action="" method="post">
		<fieldset id="partStat">
			<h2><?php p($l->t('Are you accepting the invitation?')); ?></h2>
			<div id="selectPartStatForm">
				<input type="radio" id="partStatAccept" name="partStat" value="ACCEPTED" checked />
				<label for="partStatAccept">
					<span><?php p($l->t('Accept')); ?></span>
				</label>

				<input type="radio" id="partStatTentative" name="partStat" value="TENTATIVE" />
				<label for="partStatTentative">
					<span><?php p($l->t('Tentative')); ?></span>
				</label>

				<input type="radio" class="declined" id="partStatDeclined" name="partStat" value="DECLINED" />
				<label for="partStatDeclined">
					<span><?php p($l->t('Decline')); ?></span>
				</label>
			</div>
		</fieldset>
		<fieldset id="more_options">
			<input type="number" min="0" name="guests" placeholder="Guests" />
			<input type="text" name="comment" placeholder="Comment" />
		</fieldset>
		<fieldset>
			<input type="submit" value="<?php p($l->t('Save'));?>">
		</fieldset>
	</form>
</div>
