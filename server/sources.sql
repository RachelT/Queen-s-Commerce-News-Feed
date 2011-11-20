USE commercefeeds;

INSERT INTO sources (`title`, `link`, `parser`) VALUES
('Commerce Portal', 'https://commerce.queensu.ca/commerce/2006/commerce.nsf/homepage',
	'CommercePortal'),
('ComSoc', 'https://comsoc.queensu.ca/home/index.php?option=com_ninjarsssyndicator&feed_id=1&format=raw',
	'RSS'),
('DayOnBay', 'http://dayonbay.ca/index.php/component/option,com_ninjarsssyndicator?feed_id=1&format=raw',
	'RSS');