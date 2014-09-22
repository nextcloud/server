<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>
<div id="app-content">
	<?php foreach ($_['appContents'] as $content) { ?>
	<div id="app-content-<?php p($content['id']) ?>" class="hidden viewcontainer">
	<?php print_unescaped($content['content']) ?>
	</div>
	<?php } ?>
</div><!-- closing app-content -->

<!-- config hints for javascript -->
<input type="hidden" name="filesApp" id="filesApp" value="1" />
<input type="hidden" name="usedSpacePercent" id="usedSpacePercent" value="<?php p($_['usedSpacePercent']); ?>" />
<?php if (!$_['isPublic']) :?>
<input type="hidden" name="encryptedFiles" id="encryptedFiles" value="<?php $_['encryptedFiles'] ? p('1') : p('0'); ?>" />
<input type="hidden" name="encryptedInitStatus" id="encryptionInitStatus" value="<?php p($_['encryptionInitStatus']) ?>" />
<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="mailPublicNotificationEnabled" id="mailPublicNotificationEnabled" value="<?php p($_['mailPublicNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<?php endif;
