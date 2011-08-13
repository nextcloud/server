<div id="controls">
	<ul class="jp-controls">
		<li><a href="#" class="jp-play"><img class="svg action" src="<?php echo image_path('core', 'actions/play-big'); ?>" /></a></li>
		<li><a href="#" class="jp-pause"><img class="svg action" src="<?php echo image_path('core', 'actions/pause-big'); ?>" /></a></li>
		<li><a href="#" class="jp-mute"><img class="svg action" src="<?php echo image_path('core', 'actions/sound'); ?>" /></a></li>
		<li><a href="#" class="jp-unmute"><img class="svg action" src="<?php echo image_path('core', 'actions/sound-off'); ?>" /></a></li>
		<li><a href="#" class="jp-previous"><img class="svg action" src="<?php echo image_path('core', 'actions/play-previous'); ?>" /></a></li>
		<li><a href="#" class="jp-next"><img class="svg action" src="<?php echo image_path('core', 'actions/play-next'); ?>" /></a></li>
	</ul>
	<div class="jp-progress">
		<div class="jp-seek-bar">
			<div class="jp-play-bar"></div>
		</div>
	</div>
	<div class="jp-volume-bar">
		<div class="jp-volume-bar-value"></div>
	</div>
	<div class="jp-current-time"></div>
	<div class="jp-duration"></div>
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
