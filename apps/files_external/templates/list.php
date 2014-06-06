<?php /** @var $l OC_L10N */ ?>
<div id="controls">
	<div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<div id="emptycontent" class="hidden"><?php p($l->t( 'You don\'t have any external storages' )); ?></div>

<input type="hidden" name="dir" value="" id="dir">

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName' class="hidden column-name">
				<div id="headerName-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t( 'Name' )); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
