<?php

/**
 * 
 */
require_once(dirname(__FILE__) . "/../classes/parsers/ParserManager.php");
require_once(dirname(__FILE__) . "/../classes/helpers/RestUtils.php");
require_once(dirname(__FILE__) . "/../classes/helpers/GeneralUtils.php");

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

ParserManager::updateFeedsFromSource('Commerce Portal');

?>