<?php

function getURLMetadata($url) {
	//allow only http(s) and (s)ftp
	$protocols = '/^[hs]{0,1}[tf]{0,1}tp[s]{0,1}\:\/\//i';
	//if not (allowed) protocol is given, assume http
	if(preg_match($protocols, $url) == 0) {
		$url = 'http://' . $url;
	} 
	$metadata['url'] = $url;

	$page = file_get_contents($url);
	@preg_match( "/<title>(.*)<\/title>/si", $page, $match );
	$metadata['title'] = htmlentities(strip_tags(@$match[1])); 

	$meta = get_meta_tags($url);

	if(array_key_exists('description', $meta)) {
		$metadata['description'] = $meta['description'];
	}
	
	return $metadata;
}