Scheme
======
* Feeds table - Stores all feed entities
  * id - feed ID
  * title
  * description
  * link
  * author
  * pubDate
  * category
  * sourceID
  
* Sources table - Stores all feed categories
  * id
  * title
  * link - the link of the feed source
  * parser - parser type, can be 'custom' or 'rss'