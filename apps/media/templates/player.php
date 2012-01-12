<?php
if(!isset($_)){//allow the template to be loaded standalone
	require_once '../../../lib/base.php';
	$tmpl = new OC_Template( 'media', 'player');
	$tmpl->printPage();
	exit;
}
?>
<?php echo $l->t('Music');?>
<div class='player-controls' id="playercontrols">
	<div class="player" id="jp-player"></div>
	<ul class="jp-controls">
		<li><a href="#" class="jp-play action"><img class="svg" alt="<?php echo $l->t('Play');?>" src="<?php echo image_path('core', 'actions/play.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-pause action"><img class="svg" alt="<?php echo $l->t('Pause');?>" src="<?php echo image_path('core', 'actions/pause.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-next action"><img class="svg" alt="<?php echo $l->t('Next');?>" src="<?php echo image_path('core', 'actions/play-next.svg'); ?>" /></a></li>
	</ul>
</div>