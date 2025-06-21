<?php
$opts = array(
  "http"=>array(
        "header"=>"User-Agent: SamsungBD/1.0 (IPTV client)",
		"method"=>"GET",
		),
  "ssl"=>array(
        "allow_self_signed"=>true,
        "verify_peer"=>false,
        "verify_peer_name"=>false,
        )
);

$url = $_GET["url"];
//get extension - you'll see why later
$path = parse_url($url, PHP_URL_PATH); 
$parts = explode('.', $path); 
$extension = end($parts); 

if(isset($url)) {
//we have a URL set, don't get default
if (in_array($extension, array("m3u", "m3u8"))) {
//we have an m3u(8) file, go ahead and continue parsing it

  //we're proxying something, we want to make any changes we need and then pass it through
  $m3ufile = file_get_contents($url, false, stream_context_create($opts));
  //check which arrays don't start with HTTP or #, fix the ones that don't start with HTTP and then prep the ones that don't start with # for the proxy, then put it all back together and send it off
function httpize($matches)
{
$url = $_GET["url"]; //we need to get our URL a second time, we can't bring it into this function
$dirparts = explode('/', $url);
unset($dirparts[count($dirparts) - 1]); 
$m3upath = implode('/',$dirparts) . '/';
    if(!preg_match('@(?:^|[?=])https?[:%]@', $matches[0])) return 'm3u8parser.php?url=' . urlencode($m3upath) . $matches[0];
    return 'm3u8parser.php?url=' . urlencode($matches[0]);
}
echo str_replace('%0D', '', preg_replace_callback('@^[^#].*$@m', 'httpize', $m3ufile)); //handle a very specific case where a line return gets encoded onto the end of our URL
} else {
//our file is NOT an m3u(8), relay it directly
$resultdata = file_get_contents($url, false, stream_context_create($opts));

echo $resultdata;
}} else {
  //we're using the default, go ahead and get json
  header('Content-Type: application/json');
  $m3ufile = file_get_contents('https://iptv-org.github.io/iptv/index.m3u', false, stream_context_create($opts));


//$m3ufile = str_replace('tvg-', 'tvg_', $m3ufile);
$m3ufile = str_replace('group-title', 'tvgroup', $m3ufile);
$m3ufile = str_replace("tvg-", "tv", $m3ufile);

//$re = '/#(EXTINF|EXTM3U):(.+?)[,]\s?(.+?)[\r\n]+?((?:https?|rtmp):\/\/(?:\S*?\.\S*?)(?:[\s)\[\]{};"\'<]|\.\s|$))/';
$re = '/#EXTINF:(.+?)[,]\s?(.+?)[\r\n]+?((?:https?|rtmp):\/\/(?:\S*?\.\S*?)(?:[\s)\[\]{};"\'<]|\.\s|$))/';
$attributes = '/([a-zA-Z0-9\-]+?)="([^"]*)"/';

preg_match_all($re, $m3ufile, $matches);

// Print the entire match result
//print_r($matches);

$i = 1;

$items = array();

 foreach($matches[0] as $list) {
    
     //echo "$list <br>";
	 
   preg_match($re, $list, $matchList);

   //$mediaURL = str_replace("\r\n","",$matchList[4]);
   //$mediaURL = str_replace("\n","",$matchList[4]);
   //$mediaURL = str_replace("\n","",$mediaURL);
   $mediaURL = preg_replace("/[\n\r]/","",$matchList[3]);
   $mediaURL = preg_replace('/\s+/', '', $mediaURL);
   //URL encode and prepare for our proxy file
   $mediaURL = 'm3u8parser.php?url=' . urlencode($mediaURL);
   //$mediaURL = preg_replace( "/\r|\n/", "", $matches[4] );
   

   $newdata =  array (
    //'ATTRIBUTE' => $matchList[2],
    'id' => $i++,
    'tvtitle' => $matchList[2],
    'tvmedia' => $mediaURL
    );
    
    preg_match_all($attributes, $list, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
       $newdata[$match[1]] = $match[2];
    }
    
    //array_push($newdata,$attribute);
    //$newdata[] = $attribute;
	 
	 $items[] = $newdata;
	 //$items[] = $matchList[2];
    
 }

//print_r($items);

$callback= $_GET['callback'];

  if($callback)
    echo $callback. '(' . json_encode($items) . ')';  // jsonP callback
  else
    echo json_encode($items);
}
?>