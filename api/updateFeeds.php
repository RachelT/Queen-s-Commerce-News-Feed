<?php

/**
 * This script updates all of our feeds and save it
 */
 
require('../classes/FeedParser.php');
require('../helpers/RestUtils.php');

define('ENVIRONMENT', 'DEVELOPMENT');

$result = array_merge(
			FeedParser::parsePortalContent(), 
			FeedParser::parseOtherSites('https://comsoc.queensu.ca/home/index.php?option=com_ninjarsssyndicator&feed_id=1&format=raw', 'Comsoc', ENVIRONMENT), 
			FeedParser::parseOtherSites('http://dayonbay.ca/index.php/component/option,com_ninjarsssyndicator/feed_id,1/format,raw/', 'DayOnBay', ENVIRONMENT)
		  );

if ( ENVIRONMENT == 'DEVELOPMENT' ) {
	RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($result, true) . '</pre>' . RestUtils::getHTTPFooter());
}

// Save results to file
file_put_contents('/assets/feeds.json', json_encode($result), LOCK_EX);	

?>