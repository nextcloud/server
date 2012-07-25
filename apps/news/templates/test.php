<?php 

$feedmapper = new OC_News_FeedMapper();
$foldermapper = new OC_News_FolderMapper();
$itemmapper = new OC_News_ItemMapper();

$folder = new OC_News_Folder( 'Friends' );
$folderid = $foldermapper->save($folder);

$feed = OC_News_Utils::fetch( 'http://www.dabacon.org/newpontiff/?feed=rss2' );

$feedmapper->save($feed, $folder->getId());

$feed = $feedmapper->findWithItems($feed->getId());
echo '<br>' . $feed->getTitle() . '<br>';
$items = $feed->getItems();

foreach($items as $item) {

	echo $item->getTitle() . ' - ';
	if ($item->isRead()) {
		echo $l->t('Read');
	}
	else {
		echo $l->t('Unread');
	}
	echo ' - ';
	if ($item->isImportant()) {
		echo $l->t('Important');
	}
	else {
		echo $l->t('Not important');
	}
	echo '<br>';
	$item->setImportant();
}

echo '<br>...after changing status';
echo '<br>' . $feed->getTitle() . '<br>';

foreach($items as $item) {
	echo $item->getTitle() . ' - ';
	if ($item->isRead()) {
		echo $l->t('Read');
	}
	else {
		echo $l->t('Unread');
	}
	echo ' - ';
	if ($item->isImportant()) {
		echo $l->t('Important');
	}
	else {
		echo $l->t('Not important');
	}
	echo '<br>';
	$item->setUnimportant();
}

$feedmapper->save($feed, $folder->getId());

echo '<br>...after saving and reloading';

$feed = $feedmapper->findWithItems($feed->getId());
echo '<br>' . $feed->getTitle() . '<br>';
$items = $feed->getItems();

foreach($items as &$item) {

	echo $item->getTitle() . ' - ';
	if ($item->isRead()) {
		echo $l->t('Read');
	}
	else {
		echo $l->t('Unread');
	}
	echo ' - ';
	if ($item->isImportant()) {
		echo $l->t('Important');
	}
	else {
		echo $l->t('Not important');
	}
	echo '<br>';
}