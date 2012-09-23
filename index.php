<?php
ini_set('display_errors','on');
ini_set('memory_limit', '32M');

$NUM_PER_FEED = 25;

function make_key_from_date($ts) {
  $ts -= ($ts % (60 * 60 * 6));
  return gmdate('r', $ts);
}

include('simplepie.inc');
header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8" ?>'; 

$feed_items = array();

$now = time();

$newest_time = $now - ($now % (60 * 60 * 6)); // start of the latest 6 hour block
$oldest_time = $newest_time - (60 * 60 * 24);       // start of the latest 6 hour block 12 hours ago


$item_time   = $newest_time;
$feed_index   = 0;

while ($item_time > $oldest_time) {

  $feed = new SimplePie("http://ffffound.com/feed?offset={$feed_index}", './cache');
  $feed->handle_content_type();

 foreach ($feed->get_items() as $item) {
    $item_time = $item->get_date('U');
    if ($item_time < $newest_time && $item_time > $oldest_time) {
      $feed_items[make_key_from_date($item_time)][] = $item;
    }
  }

  $feed_index += $NUM_PER_FEED;
}

$date  = date('r');

$this_url = htmlentities('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']);

echo <<<END_OF_HTML
<rss version="2.0">
<channel>
<title><![CDATA[FFFFound.com Daily Digest]]></title>
<link>{$this_url}</link>
<description><![CDATA[FFFFound.com Daily Digest]]></description>
<language>en-us</language>
<lastBuildDate>{$date}</lastBuildDate>
END_OF_HTML;

foreach ($feed_items as $key => $block) {

  echo <<<END_OF_HTML
<item>
<title><![CDATA[FFFFound! Digest for {$key}]]></title>
<link>http://ffffound.com/</link>
<guid isPermaLink="false">fffound_digest:$key</guid>
<pubDate>$key</pubDate>
<description><![CDATA[
<table cellpadding=8 border=1>
END_OF_HTML;

  $offset = 0;

  foreach ($block as $item) {
 
    if (($offset % 4) == 0) {
      echo "<tr>";
    }

    $desc = $item->get_description();

    $enc = $item->get_enclosure();
    $thumb = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
    $thumb = $thumb[0]['attribs']['']['url'];
    $link  = $item->get_link();

    echo <<<END_OF_HTML
<td>
<a href="$link"><img src="$thumb"/></a>
</td>
END_OF_HTML;

    if (($offset % 4) == 3) {
      echo "</tr>";
    }

    $offset++;
  }
  echo '</table>]]></description></item>';
}

echo '</channel></rss>';
