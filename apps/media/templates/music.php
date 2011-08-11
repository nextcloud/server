<div id="controls">
	<ul class="jp-controls">
		<li><a href="#" class="jp-play" tabindex="1">play</a></li>
		<li><a href="#" class="jp-pause" tabindex="1">pause</a></li>
		<li><a href="#" class="jp-mute" tabindex="1">mute</a></li>
		<li><a href="#" class="jp-unmute" tabindex="1">unmute</a></li>
		<li><a href="#" class="jp-previous" tabindex="1">previous</a></li>
		<li><a href="#" class="jp-next" tabindex="1">next</a></li>
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
	<p id="scancount" style="display:none"><span class="songCount">0</span> <?php echo $l->t('Songs scanned')?></p>
	<div id="scanprogressbar"></div>
	<input type="button" class="start" value="<?php echo $l->t('Rescan Collection')?>"></input>
	<input type="button" class="stop" style="display:none" value="<?php echo $l->t('Pause')?>"></input>
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
