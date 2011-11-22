<?php

/**
 * Get a single feed for a category
 */
require_once(dirname(__FILE__) . "/../classes/parsers/ParserManager.php");
require_once(dirname(__FILE__) . "/../classes/helpers/RestUtils.php");
require_once(dirname(__FILE__) . "/../classes/helpers/GeneralUtils.php");

/**
 * Supported interactions:
 *  1. Get a feed by id
 *  2. Get a feed by category and prevID - used by our increase max
 */
