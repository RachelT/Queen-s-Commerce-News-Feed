<?php

/**
 * Get feeds in a batch using an array of supplied params
 */
require_once(dirname(__FILE__) . "/../classes/parsers/ParserManager.php");
require_once(dirname(__FILE__) . "/../classes/helpers/GeneralUtils.php");
require_once(dirname(__FILE__) . "/../classes/helpers/RestUtils.php");

//ParserManager::getFeedsFromSources($sources, $options);
ParserManager::updateAllFeeds();

?>