<?php

function getURLMetadata($url) {
	//allow only http(s) and (s)ftp
	$protocols = '/^[hs]{0,1}[tf]{0,1}tp[s]{0,1}\:\/\//i';
	//if not (allowed) protocol is given, assume http
	if(preg_match($protocols, $url) == 0) {
		$url = 'http://' . $url;
	} 
	$metadata['url'] = $url;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$page = curl_exec($ch);
	curl_close($ch);

	@preg_match( "/<title>(.*)<\/title>/si", $page, $match );
	$metadata['title'] = htmlspecialchars_decode(@$match[1]); 

	$meta = get_meta_tags($url);

	if(array_key_exists('description', $meta)) {
		$metadata['description'] = $meta['description'];
	}
	
	return $metadata;
}