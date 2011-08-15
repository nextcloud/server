<div id="controls">
	<ul class="jp-controls">
		<li><a href="#" class="jp-play action"><img class="svg" src="<?php echo image_path('core', 'actions/play-big.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-pause action"><img class="svg" src="<?php echo image_path('core', 'actions/pause-big.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-previous action"><img class="svg" src="<?php echo image_path('core', 'actions/play-previous.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-next action"><img class="svg" src="<?php echo image_path('core', 'actions/play-next.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-mute action"><img class="svg" src="<?php echo image_path('core', 'actions/sound.svg'); ?>" /></a></li>
		<li><a href="#" class="jp-unmute action"><img class="svg" src="<?php echo image_path('core', 'actions/sound-off.svg'); ?>" /></a></li>
	</ul>
	<div class="jp-progress">
		<div class="jp-seek-bar">
			<div class="jp-play-bar"></div>
		</div>
	</div>
	<div class="jp-current-time"></div>
	<div class="jp-duration"></div>
	<div class="jp-volume-bar">
		<div class="jp-volume-bar-value"></div>
	</div>

	<div class="player" id="jp-player"></div>
</div>

<ul id="leftcontent"></ul>

<div id="rightcontent">
<div id="scan">
	<p id="scancount" style="display:none"><span class="songCount">0</span> <?php echo $l->t('Songs scanned')?>
	<input type="button" class="start" value="<?php echo $l->t('Rescan Collection')?>"></input>
	<input type="button" class="stop" style="display:none" value="<?php echo $l->t('Pause')?>"></input></p>
	<div id="scanprogressbar"></div>
	
</div>
<table id="collection">
	<thead>
		<th><?php echo $l->t('Artist')?></th>
		<th><?php echo $l->t('Album')?></th>
		<th><?php echo $l->t('Title')?></th>
	</thead>
	<tbody>
		<tr class="template">
			<td class="artist"><a/></td>
			<td class="album"><a/></td>
			<td class="title"><a/></td>
		</tr>
	</tbody>
</table>
</div>
