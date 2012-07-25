<?php

/**
* ownCloud - ATNotes plugin
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OCP\Util::addStyle('atnotes','jquery-te-1.0.3.min');
OCP\Util::addScript('atnotes','jquery-te-1.0.3.min');
OCP\Util::addStyle('atnotes','jquery.qtip.min');
OCP\Util::addScript('atnotes','jquery.qtip.min');
OCP\Util::addStyle('atnotes','atnotes.min');
OCP\Util::addScript('atnotes','atnotes.min');

?>

<div id="atnotes_container">
	<div id="save_dialog">
		<div class="atnotes-explorer"><table border="0" cellpadding="0" cellspacing="0"></table></div>
	</div>
	<div id="controls">
		<div class="title"><?php print('Always Take Notes !'); ?></div>
	</div>
	<ul class="atnotes-noteslist">
		<?php foreach($_['notes_list'] as $note){ ?>
		<li class="atnotes-elt" rel="<?php print($note['note_id']); ?>">
			<div class="atnotes-elt-title"><?php print($note['note_title']); ?></div>
			<div class="atnotes-elt-state"></div>
			<div class="atnotes-elt-date"><?php print(date('m/d/Y', $note['update_ts'])); ?></div>
			<div class="atnotes-elt-prerender"><?php print($note['note_content']); ?></div>
		</li>
		<?php } ?>
	</ul>
	<div class="atnotes-notesedit">
		<div class="anotes-notesedit-title">
			<input type="text" id="note_title" rel="0" maxlength="255" />
			<input type="hidden" id="note_path" maxlength="255" />
			<div class="atnotes-actions-btns">
				<img class="atnotes-actions-list" src="<?php print(OCP\Util::imagePath('atnotes','action.png')); ?>" />
				<div class="atnotes-actions-ddmenu">
					<ul>
						<li class="atnotes-new">New note</li>
						<li class="atnotes-delete">Delete note</li>
					</ul>
				</div>
				<img class="atnotes-save" src="<?php print(OCP\Util::imagePath('atnotes','save.png')); ?>" />
			</div>
		</div>
		<div class="anotes-notesedit-states">
			<div class="atnotes-created"></div>
			<div class="atnotes-saved"></div>
		</div>
	</div>
	<textarea class="atnotes-editor"></textarea>
</div>
