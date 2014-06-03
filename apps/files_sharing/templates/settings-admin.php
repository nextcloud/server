<div class="section" id="mailTemplateSettings" >

	<h2><?php p($l->t('Mail templates'));?></h2>

	<div class="actions">

		<div>
			<label for="mts-theme"><?php p($l->t('Theme'));?></label>
			<select id="mts-theme">
				<?php foreach($_['themes'] as $theme => $editable): ?>
				<option><?php p($theme); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div>
			<label for="mts-template"><?php p($l->t('Template'));?></label>
			<select id="mts-template">
				<?php foreach($_['editableTemplates'] as $template => $editable): ?>
				<option><?php p($template); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

	</div>

	<div class="templateEditor">
		<textarea></textarea>
	</div>

	<div class="actions">

		<button class="reset"><?php p($l->t('Reset'));?></button>

		<button class="save"><?php p($l->t('Save'));?></button>

		<span id="mts-msg" class="msg"></span>

	</div>

</div>
