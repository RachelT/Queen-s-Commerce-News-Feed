<?php

/**
 * This script talks with the extension to get the information it requires
 * Support numerous params
 * In order to get feeds pass action=getFeeds then optionally supply count for each category
 * In order to get description for feed, pass action=getDescription. Optionally supply the category of the feed for faster retrieval
 */
require('../helpers/GeneralUtils.php');
require('../helpers/RestUtils.php');
 
define('ENVIRONMENT', 'DEVELOPMENT');
 
// Get basic parameters
$options = array();
$options['action'] = $_GET['action'];	// getFeeds or getDescription
$categories = array('administrative' => 'Administrative', 
					'career' => 'Career', 
					'ams' => 'AMS',
					'general' => 'General',
					'research' => 'Research Pool',
					'comsoc' => 'Comsoc',
					'dayonbay' => 'DayOnBay');

if ( $options['action'] == 'getFeeds' )
{
	// Get category parms and set feeds defaults
	$defaultParams = array();
	$options = array();
	foreach ( array_keys($categories) as $oneKey ) {
		$options[$oneKey] = $_GET[$oneKey];
		$defaultParams[$oneKey] = 10;
	}
	$options = GeneralUtils::getDefaults($defaultParams, $options);
	
	// Call our method to grab all feeds
	$feeds = getFeeds($options);
	
	// Send information to client based on environment
	if ( ENVIRONMENT == 'DEVELOPMENT' ) {
		RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($feeds, true) . '</pre>' . RestUtils::getHTTPFooter());
	}else {
		RestUtils::sendResponse(200, json_encode($feeds), '', 'application/json');
	}
			
}
else if ( $options['action'] == 'getDescription' )
{
	// Get description of feed based on title and category (optional)
	$options['identifier'] = $_GET['identifier'];
	$option['category'] = $_GET['category'];
	
	// Call our method to actually get the description
	$description = getDescription($options);
	
	// Send info to client based on environment
	if ( ENVIRONMENT == 'DEVELOPMENT' ) {
		RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($description, true) . '</pre>' . RestUtils::getHTTPFooter());
	}else {
		RestUtils::sendResponse(200, json_encode($description), '', 'application/json');
	}
}

/**
 * Get feeds using supplied options (mostly category params)
 * @return Array the feeds to send to user
 */
function getFeeds($options)
{
	global $categories;
	
	// Get cached content
	$content = file_get_contents('../cache/feeds.json');
	$feeds = get_object_vars(json_decode($content));
	
	// Get results
	$results = array();
	foreach ( $categories as $oneKey=>$oneValue ) {
		for ( $i = 0; $i < min($options[$oneKey], count($feeds[$oneValue])); $i++ ) {
			$results[$oneValue][] = (array)$feeds[$oneValue][$i];
			unset($results[$oneValue][$i]['description']);
		}
	}
	return $results;
}

/**
 * Get the description of the feed using the title and the category (so searches faster)
 * @return String the description of the feed
 */
function getDescription($options)
{
	global $categories;
	
	// Get cached content
	$content = file_get_contents('../cache/feeds.json');
	$feeds = (array)json_decode($content);
	
	if ( !array_key_exists('category', $options) ) {
		// If the category is not supplied, we search through the titles
		foreach ( $categories as $oneKey=>$oneValue ) {
			for ( $i = 0; $i < count($feeds[$oneValue]); $i++ ) {
				$targetFeed = (array)$feeds[$oneValue][$i];
				if ( $targetFeed['identifier'] == $options['identifier'] ) {
					return $targetFeed['description'];
				}
			}
		}
	}
	
	// Fastest when category is supplied with the title
	$theFeed = (array)$feeds[$options['category']];
	return $theFeed['description'];
}

?>