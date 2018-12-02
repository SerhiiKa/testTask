<?php // Silence is golden

add_filter( 'pre_get_document_title', function(){
	$route = codingninjas\App::$route;
	global $post;


	return 'dash';
} );