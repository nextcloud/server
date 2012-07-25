<?php
	function print_folder(OC_News_Folder $folder, $depth){
		$l = new OC_l10n('news');

		echo '<ul style="margin-left:' . 10*$depth . 'px;"> <li class="folder_list" >' .
			'<div class="collapsable" >' . strtoupper($folder->getName()) .
                        ( ($depth != 0) ? '<button class="svg action" id="feeds_delete" onClick="(News.Folder.delete(' . $folder->getId(). '))" title="' . $l->t('Delete folder') . '"></button>' . 
				'<button class="svg action" id="feeds_edit" title="' . $l->t('Rename folder') . '"></button>': '' ) .
                        '</div>';
		echo '<ul>';
		$children = $folder->getChildren();
		foreach($children as $child) {
			if ($child instanceOf OC_News_Folder){
				print_folder($child, $depth+1);
			}
			elseif ($child instanceOf OC_News_Feed) { //onhover $(element).attr('id', 'newID');

				echo '<li class="feeds_list" data-id="' . $child->getId() . '"><a href="' . OCP\Util::linkTo('news', 'index.php'). '?feedid=' . $child->getId() . '">' . $child->getTitle() .'</a>';
				echo '<button class="svg action" id="feeds_delete" onClick="(News.Feed.delete(' . $child->getId(). '))" title="' . $l->t('Delete feed') . '"></button>';
				echo '<button class="svg action" id="feeds_edit" title="' . $l->t('Edit feed') . '"></button>';
				echo '</li>';
			}
			else {
			//TODO:handle error in this case
			}
		}
		echo '</ul></li></ul>';
	}

	print_folder($_['allfeeds'], 0);
?>