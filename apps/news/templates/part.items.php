<?php

$feedid = isset($_['feedid']) ? $_['feedid'] : '';

$itemmapper = new OC_News_ItemMapper();

$items = $itemmapper->findAll($feedid);

echo '<ul class="accordion">';
foreach($items as $item) {
	$title = $item->getTitle();
	echo '<li>';
	echo '<div data-id="' . $item->getId() . '"';
	if ($item->isRead()) {
		echo ' class="title_read">';
	}
	else {
		echo ' class="title_unread" onClick="News.Feed.markItem(' . $item->getId() . ')">';
	}
	echo $title . '</div><div class="body">' . $item->getBody() . '</div>';
	echo '</li>';
}
echo '</ul>';
