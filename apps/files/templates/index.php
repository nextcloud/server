<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>
<div id="app-content">
	<?php foreach ($_['appContents'] as $content) { ?>
	<div id="app-content-<?php p($content['id']) ?>" class="hidden viewcontainer">
	<?php print_unescaped($content['content']) ?>
	</div>
	<?php } ?>
	<div id="searchresults" class="hidden"></div>
</div><!-- closing app-content -->

<!-- config hints for javascript -->
<input type="hidden" name="filesApp" id="filesApp" value="1" />
<input type="hidden" name="usedSpacePercent" id="usedSpacePercent" value="<?php p($_['usedSpacePercent']); ?>" />
<input type="hidden" name="owner" id="owner" value="<?php p($_['owner']); ?>" />
<input type="hidden" name="ownerDisplayName" id="ownerDisplayName" value="<?php p($_['ownerDisplayName']); ?>" />
<input type="hidden" name="fileNotFound" id="fileNotFound" value="<?php p($_['fileNotFound']); ?>" />
<?php if (!$_['isPublic']) :?>
<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="mailPublicNotificationEnabled" id="mailPublicNotificationEnabled" value="<?php p($_['mailPublicNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<input type="hidden" name="defaultFileSorting" id="defaultFileSorting" value="<?php p($_['defaultFileSorting']) ?>" />
<input type="hidden" name="defaultFileSortingDirection" id="defaultFileSortingDirection" value="<?php p($_['defaultFileSortingDirection']) ?>" />
<input type="hidden" name="showHiddenFiles" id="showHiddenFiles" value="<?php p($_['showHiddenFiles']); ?>" />
<?php endif;
