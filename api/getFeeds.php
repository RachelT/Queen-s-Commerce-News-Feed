<?php

/**
 * This script talks with the extension to get the information it requires
 * Support numerous params
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
					'dayonbay' => 'DayOnBay',
					'comsoc' => 'Comsoc');

if ( $options['action'] == 'getFeeds' )
{
	// Get parms and set feeds defaults
	$defaultParams = array();
	$options = array();
	foreach ( array_keys($categories) as $oneKey ) {
		$options[$oneKey] = $_GET[$oneKey];
		$defaultParams[$oneKey] = 10;
	}
	$options = GeneralUtils::getDefaults($defaultParams, $options);
	
	$feeds = getFeeds($options);
	
	if ( ENVIRONMENT == 'DEVELOPMENT' ) {
		RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($result, true) . '</pre>' . RestUtils::getHTTPFooter());
	}else {
		RestUtils::sendResponse(200, json_encode($feeds), '', 'application/json');
	}
			
}
else if ( $options['action'] == 'getDescription' )
{
	// Get description of feed based on title
	$options['title'] = $_GET['title'];
	$option['category'] = $_GET['category'];
	
	$description = getDescription($options);
	
	if ( ENVIRONMENT == 'DEVELOPMENT' ) {
		RestUtils::sendResponse(200, RestUtils::getHTTPHeader('Got it') . '<pre>' . print_r($description, true) . '</pre>' . RestUtils::getHTTPFooter());
	}else {
		RestUtils::sendResponse(200, json_encode($description), '', 'application/json');
	}
}

/**
 * Get feeds using supplied options
 * @return Array the feeds to be send to user
 */
function getFeeds($options)
{
	$content = file_get_contents('../cache/feeds.json');
	$feeds = json_decode($content);
	
	// Get results
	$results = array();
	foreach ( array_keys($options) as $oneKey ) {
		for ( $i = 0; $i < max($options[$oneKey], count($feeds[$oneKey])); $i++ ) {
			$results[$oneKey][] = $feeds[$oneKey][$i];
			unset($results[$oneKey]['description']);
		}
	}
	return $results;
}

/**
 * Get the description of the feed using the title and the category (so searches faster)
 */
function getDescription($options)
{
	$content = file_get_contents('../cache/feeds.json');
	$feeds = json_decode($content);
	
	if ( !array_key_exists('category', $options) ) {
		// If the category is not supplied, we search through the titles
		foreach ( array_keys($categories) as $oneKey ) {
			for ( $i = 0; $i < count($feeds[$oneKey]); $i++ ) {
				if ( $feeds[$oneKey][$i]['title'] == $options['title'] ) {
					return $feeds[$oneKey][$i]['description'];
				}
			}
		}
	}
	
	// Fastest when category is supplied with the title
	return $feeds[$options['category']]['description'];
}

?>