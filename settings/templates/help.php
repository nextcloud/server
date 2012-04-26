<?php /**
 * Copyright (c) 2011, Frank Karlitschek karlitschek@kde.org
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>

<div id="controls">
	<a class="button newquestion" href="http://owncloud.org/support" target="_blank"><?php echo $l->t( 'Documentation' ); ?></a>
	<a class="button newquestion" href="http://owncloud.org/support/big-files" target="_blank"><?php echo $l->t( 'Managing Big Files' ); ?></a>
	<a class="button newquestion" href="http://apps.owncloud.com/knowledgebase/editquestion.php?action=new" target="_blank"><?php echo $l->t( 'Ask a question' ); ?></a>
	<?php
		$url=OC_Helper::linkTo( "settings", "help.php" ).'?page=';
		$pageNavi=OC_Util::getPageNavi($_['pagecount'],$_['page'],$url);
		if($pageNavi)
		{
			$pageNavi->printPage();
		}
	?>
</diV>
<?php if(is_null($_["kbe"])):?>
	<div class="helpblock">
		<p><?php echo $l->t('Problems connecting to help database.');?></p>
		<p><a href="http://apps.owncloud.com/kb"><?php echo $l->t('Go there manually.');?></a></p>
	</div>
<?php else:?>
	<?php foreach($_["kbe"] as $kb): ?>
	<div class="helpblock">
		<?php if($kb["preview1"] <> "") { echo('<img class="preview" src="'.$kb["preview1"].'" />'); } ?>
		<?php if($kb['detailpage']<>'') echo('<p><a target="_blank" href="'.$kb['detailpage'].'"><strong>'.$kb["name"].'</strong></a></p>');?>
		<p><?php echo $kb['description'];?></p>
		<?php if($kb['answer']<>'') echo('<p><strong>'.$l->t('Answer').':</strong><p>'.$kb['answer'].'</p>');?>
	</div>
	<?php endforeach;
endif?>
