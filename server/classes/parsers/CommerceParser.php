<?php

require_once('../libraries/simple_html_dom.php');
require_once('../helpers/GeneralUtils.php');
require_once('../helpers/NetworkUtils.php');
require_once('../helpers/RestUtils.php');

/**
 * Provides functions to parse feeds from the commerce portal
 * For commerce feeds, we do not have content or author
 *
 * @author Draco Li
 * @version 1.0
 */
class CommerceParser {
	
	/**
	 * Parses the feeds on the commerce portal
	 * @return Array An array of the feeds
	 */
	public static function parsePortalContent()
	{	
		// Prepare the content
		$url = 'https://commerce.queensu.ca/commerce/2006/commerce.nsf/homepage';
		$content = NetworkUtils::getContentFromUrl($url);
		$categories = str_get_html($content)->find('.announText');
		$feeds = array();
		$catTitles = array('Administrative', 'Career', 'AMS', 'General', 'Research Pool');
		
		// Get our feeds
		$categoryNum = 0;
		foreach ( $categories as $oneCategory ) {
			$feedsNode = $oneCategory->find('a');
			$category = $categories[$categoryNum];
			foreach ( $feedsNode as $feed ) {
				
				$oneResult = array();
				
				// Get feed date
				$title = $feed->plaintext;
				$startpos = strpos($title, '(') + 1;
				$endpos = strpos($title, ')');
				if ( $startpos >= 0 && $endpos >= 0 ) {
					$date = substr($title, $startpos, $endpos - $startpos);
					$date = GeneralUtils::naDateStringToStamp($date);
					$oneResult['pubDate'] = $date;
					
					// Adjust title
					$title = trim($feed->find('strong', 1)->plaintext);
				}else {
					// No date is provided so we used the current time
					$oneResult['pubDate'] = time();
				}
				
				// Get feed title
				$oneResult['title'] = $title;
				
				// Get feed url
				$url = $feed->getAttribute('href');
				$oneResult['link'] = $url;
				
				// Set feed category
				$oneResult['category'] = $category;
				
				$feeds[] = $oneResult;
			} // End one category
			
			$categoryNum++;
		} // End categories
		
		return $resultData;
	}
}

?>