<?php

require_once(dirname(__FILE__) . '/../libraries/simple_html_dom.php');
require_once(dirname(__FILE__) . '/../helpers/GeneralUtils.php');
require_once(dirname(__FILE__) . '/../helpers/NetworkUtils.php');
require_once(dirname(__FILE__) . '/../helpers/DatabaseManager.php');

/**
 * Provides functions to parse feeds from the commerce portal
 * For commerce feeds, we do not have content or author
 *
 * @author Draco Li
 * @version 1.0
 */
class CommerceParser {
	
	public static $categories = array('Administrative', 'Career', 'AMS', 'General', 'Research Pool');
	
	/**
	 * Parses the feeds on the commerce portal
	 * @return Array An array of the feeds
	 */
	public static function parsePortalContent($url, $sourceID)
	{	
		// Prepare the content
		$content = NetworkUtils::getContentFromUrl($url);
		$categoryNodes = str_get_html($content)->find('#announ, .announText');
		$feeds = array();
		
		// Get our feeds
		$dbm = new DatabaseManager();
		$categoryNum = 0;
		foreach ( $categoryNodes as $oneCategory ) {
			$feedsNode = $oneCategory->find('a');
			$category = CommerceParser::$categories[$categoryNum];
			foreach ( $feedsNode as $feed ) {
				
				$oneResult = array();
				
				// Get feed date
				$title = $feed->plaintext;
				$startpos = strpos($title, '(') + 1;
				$endpos = strpos($title, ')');
				if ( $startpos >= 0 && $endpos >= 0 ) {
					$date = substr($title, $startpos, $endpos - $startpos);
					$date = GeneralUtils::naDateStringToStamp($date);
					$oneResult['pubDate'] = GeneralUtils::timeStampToMYSQLTime($date);
					
					// Adjust title
					$title = trim($feed->find('strong', 1)->plaintext);
				}else {
					// No date is provided so we used the current time
					$oneResult['pubDate'] = GeneralUtils::timeStampToMYSQLTime(time());
				}
				
				// Get feed title
				$oneResult['title'] = $dbm->sanitizeData($title);
				
				// Get feed url
				$url = $feed->getAttribute('href');
				$oneResult['link'] = $dbm->sanitizeData($url);
				
				// Set feed category
				$oneResult['category'] = $category;
				
				// Set feed sourceID
				$oneResult['sourceID'] = $sourceID;
				
				$feeds[] = $oneResult;
			} // End one category
			
			$categoryNum++;
		} // End categories
		
		return $feeds;
	}
}

?>