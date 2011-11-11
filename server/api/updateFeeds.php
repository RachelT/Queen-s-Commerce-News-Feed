<?php

/**
 * This script updates all of our feeds and save it
 */
 
require_once('../classes/FeedParser.php');
require_once('../helpers/RestUtils.php');

define('ENVIRONMENT', 'DEVELOPMENT');

$result = array_merge(
			FeedParser::parsePortalContent(), 
			FeedParser::parseOtherSites('https://comsoc.queensu.ca/home/index.php?option=com_ninjarsssyndicator&feed_id=1&format=raw', 'Comsoc', ENVIRONMENT), 
			FeedParser::parseOtherSites('http://dayonbay.ca/index.php/component/option,com_ninjarsssyndicator/feed_id,1/format,raw/', 'DayOnBay', ENVIRONMENT)
		  );

// Save results to file
file_put_contents('../cache/feeds.json', json_encode($result), LOCK_EX);	

// Supply result to user based on environment
if ( ENVIRONMENT == 'DEVELOPMENT' ) {
	RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($result, true) . '</pre>' . RestUtils::getHTTPFooter());
}

?>