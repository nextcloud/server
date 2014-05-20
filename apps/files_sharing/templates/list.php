<?php /** @var $l OC_L10N */ ?>
<div id="controls">
	<div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<div id="emptycontent" class="hidden"></div>

<input type="hidden" name="dir" value="" id="dir">

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName' class="hidden column-name">
				<div id="headerName-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t( 'Name' )); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
			<th id="headerSharedWith" class="hidden column-mtime">
				<a id="sharedwith" class="columntitle" data-sort="shareWith"><span><?php p($l->t( 'Shared with' )); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerDate" class="hidden column-mtime">
				<a id="modified" class="columntitle" data-sort="mtime"><span><?php p($l->t( 'Shared since' )); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
