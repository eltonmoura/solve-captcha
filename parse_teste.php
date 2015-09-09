<?php

$content = file_get_contents($argv[1]);
$pattern = "#^((?:HTTP/1\.[01].*?[(?:\r)\n]{3,})+)#is" ;
if ( preg_match( $pattern , $content , $matches ) ) {
	$body = substr_replace( $content , "" , 0 , strlen( $matches[1] ) ) ;
	print($body);
}

die("Done.\n");
