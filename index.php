<?php
error_reporting  (E_ERROR | E_WARNING | E_PARSE);
error_reporting (E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
error_reporting (E_ALL ^ E_NOTICE);
error_reporting (E_ALL);
ini_set ('error_reporting', E_ALL);
define ("CJ_SITEID", "SITE_ID");
define ("CJ_DEVKEY", 'KEY_ID');

 

  function cjGetMerchants() {
	$targeturl = "https://advertiser-lookup.api.cj.com/v3/advertiser-lookup?advertiser-ids=joined";
	$ch = curl_init($targeturl);
	curl_setopt($ch, CURLOPT_POST, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.CJ_DEVKEY)); // send development key
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
	$text = curl_exec($ch);
	$adObj = simplexml_load_string( $text );
    $json = json_encode((array) $adObj);
	$response = json_decode( $json,1);
	curl_close($ch);
	return $response;
}

function cjGetAds($advertiserid,$pagnumber='') {
	if($pagnumber=='') {
		$targeturl = "https://linksearch.api.cj.com/v2/link-search?website-id=".CJ_SITEID."&advertiser-ids=".$advertiserid."&records-per-page=100";
	} else {
		$targeturl = "https://linksearch.api.cj.com/v2/link-search?website-id=".CJ_SITEID."&advertiser-ids=".$advertiserid."&page-number=".$pagnumber."&records-per-page=100";
	}
	$ch = curl_init($targeturl);
	curl_setopt($ch, CURLOPT_POST, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.CJ_DEVKEY)); // send development key
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = json_decode(json_encode((array) simplexml_load_string( curl_exec($ch) )),1);;
	curl_close($ch);
	
	$creatives_total = $response['links']['@attributes']['total-matched'];
	$creatives_returned = $response['links']['@attributes']['records-returned'];
	$creatives_page = $response['links']['@attributes']['page-number'];
	
	if($creatives_total>$creatives_returned) {
		$pages = $creatives_total/100;
		if($creatives_page<$pages) {
			$newResponse = cjGetAds($advertiserid,$creatives_page+1);
			$response = array_merge($response['links']['link'],$newResponse['links']['link']);
		}
	} else {
		$response = $response['links']['link'];
	}
	return $response;
}


function addAd($vid, $ad_date , $type, $code, $advertiser_id, $prov){
//Do what ever you want with ads code
echo $vid." ". $ad_date ." ". $type." ". $code." ".$advertiser_id." ".$prov."<br>";
}

function getInfo($id, $name){

                    echo "Started importing ".$name."<br>";
                    $ads = cjGetAds($id);
                    $count = 0;
  					foreach($ads as $ad) {
  					 var_dump($ad);
  					 break;
    					$vid = $ad['link-id'];
    					$date = $ad['promotion-end-date'];
    					$type = $ad['creative-height'] . "x" . $ad['creative-width'];
    					$code = $ad['link-code-html'];
    					$advertiser_id=$ad['advertiser-id'];
    					$language =$ad['language'];
    					$ad_date = false;
    					$now = new DateTime('now');
						if ( $language=="en" && $ad['creative-height'] > 0) {
						if(!$ad_date) {
      						$count += addAd($vid, NULL , $type, $code, $advertiser_id, 'cj');
   		 				} else if($ad_date > $now) {
      						$count += addAd($vid, $ad_date, $type, $code, $advertiser_id, 'cj');
   		 				}
   		 				}
   		 				
  					}
  				    echo "Imported $count <br>";
            }


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta content="text/html;charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>CJ Import</title>


</head>
<body>
		<?php 
		    date_default_timezone_set('America/Los_Angeles');
		    $merchants = cjGetMerchants();
            if($merchants['advertisers']['@attributes']['records-returned']>1) {
                foreach($merchants['advertisers']['advertiser'] as $merchant) {
                    getInfo($merchant['advertiser-id'], $merchant['advertiser-name']);
                }
            } else {
               getInfo($merchants['advertisers']['advertiser']['advertiser-id'], $merchant['advertiser-name']);
            }
            ?>
</body>
</html>
		