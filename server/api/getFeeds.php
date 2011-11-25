<?php

/**
 * Get feeds in a batch using an array of supplied params
 */
require_once(dirname(__FILE__) . "/../classes/parsers/ParserManager.php");
require_once(dirname(__FILE__) . "/../classes/helpers/GeneralUtils.php");
require_once(dirname(__FILE__) . "/../classes/helpers/RestUtils.php");

// Get all passed variables
$variables = $_GET;
$sources = array();
$options = array();

$possibleOptionKeys = array('format');

// Get our information
foreach ( $variables as $key=>$value ) {
	if ( in_array($key, $possibleOptionKeys) ) {
		$options[$key] = $value;
	}else {
		$sources[$key] = $value;
	}
}

$results = ParserManager::getFeedsForCategories($sources, $options);

// Testing
$body = RestUtils::getHTTPHeader();
$body .= "<h3>Sources</h3><pre>" . print_r($sources, true) . "</pre>";
$body .= "<h3>Options</h3><pre>" . print_r($options, true) . "</pre>";
$body .= "<h3>Content</h3><pre>" . print_r($results, true) . "</pre>";
$body .= RestUtils::getHTTPFooter();
RestUtils::sendResponse(200, $body);

// Production
//RestUtils::sendResponse(200, $results, '', 'application/json');

?>