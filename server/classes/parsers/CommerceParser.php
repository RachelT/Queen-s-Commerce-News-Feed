<?php

require_once('../libraries/simple_html_dom.php');
require_once('../helpers/GeneralUtils.php');
require_once('../helpers/NetworkUtils.php');
require_once('../helpers/RestUtils.php');

/**
 * Provides functions to parse feeds from the commerce portal
 *
 * @author Draco Li
 * @version 1.0
 */
class CommerceParser {
	/**
	 * Parses the feeds on the commerce portal
	 * @param $returnType How the user wanted the result. Can be array, object, or json
	 */
	public static function parsePortalContent()
	{	
		// Prepare the content
		$url = 'https://commerce.queensu.ca/commerce/2006/commerce.nsf/homepage';
		$content = FeedParser::getContentFromUrl($url);
		$categories = str_get_html($content)->find('#announ, .announText');
		$resultData = array();
		$catTitles = array('Administrative', 'Career', 'AMS', 'General', 'Research Pool');
		
		// Get our results
		$categoryNum = 0;
		foreach ( $categories as $oneCategory ) {
			$feeds = array();
			$feedsNode = $oneCategory->find('a');
			foreach ( $feedsNode as $feed ) {
				
				$oneResult = array();
				
				// Get feed date
				$title = $feed->plaintext;
				$startpos = strpos($title, '(') + 1;
				$endpos = strpos($title, ')');
				if ( $startpos >= 0 && $endpos >= 0 ) {
					$date = substr($title, $startpos, $endpos - $startpos);
					$date = GeneralUtils::naDateStringToStamp($date);
					$oneResult['date'] = $date;
					
					// Adjust title
					$title = trim($feed->find('strong', 1)->plaintext);
				}
				
				// Get feed title
				$oneResult['title'] = $title;
				
				/* TODO
				 * Apparently the parser does not detect the style attribute. 
				 * Might be because the style is loaded through javascript
				 *
				// Get feed status - new, default
				$style = $feed->getAttribute('style');
				$status = 'default';
				if ( strpos($style, 'red') > 0 ) {
					$status = 'new';
				}
				$oneResult['status'] = $status;
				 */
				
				// Get feed url
				$url = $feed->getAttribute('href');
				$oneResult['url'] = $url;
				
				// We also associate a hash for each feed - used for identifying feeds
				$oneResult['identifier'] = md5($title);
				
				$feeds[] = $oneResult;
			} // End one category
			
			$resultData[$catTitles[$categoryNum]] = $feeds;
			$categoryNum++;
		} // End categories
		
		return $resultData;
	}
}

?>