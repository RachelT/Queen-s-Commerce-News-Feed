<?php

/**
 * Get feeds in a batch using an array of supplied params
 */
require_once(dirname(__FILE__) . "/../classes/parsers/ParserManager.php");
require_once(dirname(__FILE__) . "/../classes/helpers/RestUtils.php");

// Update all feeds
$results = ParserManager::updateAllFeeds();

// Show all updated feeds if asked
$show = $_GET['show'];
if ( $show == 'true' ) {
	$body = RestUtils::getHTTPHeader() . 
					'<pre>' . print_r($results, true) . '</pre>' . 
					RestUtils::getHTTPFooter();
	RestUtils::sendResponse(200, $body);
}

?>