<?php

require_once('../helpers/simple_html_dom.php');
require_once('../helpers/GeneralUtils.php');
require_once('../helpers/RestUtils.php');
require_once('../helpers/URLConnect.php');

/**
 * Parses all documents required for this application
 *
 * @author Draco Li
 * @version 1.0
 */
class FeedParser {
	
	/**
	 * Parses the feeds on the commerce portal
	 */
	public static function parsePortalContent()
	{	
		// Prepare the content
		$url = 'https://commerce.queensu.ca/commerce/2006/commerce.nsf/homepage';
		$content = getContentFromUrl($url);
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
				$startpos = strpos($title, '(');
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
				
				// Get feed status - new, default
				$style = $feed->getAttribute('style');
				$status = 'default';
				if ( strpos($style, 'red') > 0 ) {
					$status = 'new';
				}
				$oneResult['status'] = $status;
				
				// Get feed url
				$url = $feed->getAttribute('href');
				$oneResult['url'] = $url;
				
				$feeds[] = $oneResult;
			} // End one category
			
			$resultData[$catTitles[$categoryNum]] = $feeds;
			$categoryNum++;
		} // End categories
		
		return $resultData;
	}
	
	/**
	 * Parses feeds from DayOnBay and Comsoc (both using com_ninjarsssyndicator)
	 */
	public static function parseOtherSites($url, $category)
	{
		$content = getContentFromUrl($url);
		$feeds = str_get_html($content)->find('item');
		$resultData = array();
		
		foreach ( $feeds as $feed ) {
			
			$oneResult = array();
			
			// Get feed date
			$dateString = $feed->find('pubDate', 0)->plaintext;
			$date = (int)strtotime($dateString);
			$oneResult['date'] = $date;
			
			// Get feed title
			$title = $feed->find('title', 0)->plaintext;
			$oneResult['title'] = $title;
		
			// Get feed status - new, default
			$oneResult['status'] = 'default';
			
			// Get feed url	
			$theUrl = $feed->find('guid', 0)->plaintext;
			$oneResult['url'] = $theUrl;
			
			// Get feed description
			$description = $feed->find('description', 0)->plaintext;
			$description = cleanUpDescription($description);
			$oneResult['description'] = $description;
			
			$resultData[$category][] = $oneResult;
		}
		
		return $resultData;
	}
	
	function getContentFromUrl($url)
	{
		$urlconnect = new URLConnect($url, 200, FALSE);
		if ( $urlconnect->getHTTPCode() != 200 ) {
			RestUtils::sendResponse($urlconnect->getHTTPCode());
			exit;
		}
		return $urlconnect->getContent();	
	}

	function cleanUpDescription($description)
	{
		// Remove description CDATA
		$description = str_replace('<![CDATA[', '', $description);
		$description = str_replace(']]>', '', $description);
			
		// Remove description images
		$description = preg_replace('/\<img[^(\>)]*\/\>/', '', $description);
			
		// Remove empty paragraph tags
		$description = preg_replace('/\<p\>(\s)*\<\/p\>/', '', $description);
			
		// Remove read more paragrahs
		$description = preg_replace('/\<p\>\s*\<a[^(\\\>)]*\>Read more(.)*\<\/a\>\s*\<\/p\>/', '', $description);	
		
		return $description;
	}
}

?>