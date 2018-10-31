<?php

function pull_all_clips() {
	pull_twitter_mentions();
}

function pull_twitch_clips() {

}

function pull_twitter_mentions() {
	$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';

	$authorization = generateTwitterAuthorization($url, "get");

	$args = array(
		"headers" => array(
			"Authorization" => $authorization,
		),
	);

	$response = wp_remote_get($url, $args);
	$responseBody = json_decode($response['body']);

	foreach ($responseBody as $key => $tweetData) {
		// basicPrint($key);
		if ( tweetIsProbablySubmission($tweetData) ) {
			$submission = submitTweet($tweetData);
		}
		// basicPrint("------------------------------------");
	};
}

function pull_twitter_timeline($timeline) {

}

function tweetIsProbablySubmission($tweetData) {
	$entities = $tweetData->entities;
	if ($entities->media) {
		return true;
	}
	if ($entities->urls) {
		// basicPrint("URLS");
		// basicPrint($entities->urls);
	}
}

function generateTwitterAuthorization($url, $method) {
	global $privateData;
	$OAuth = array(
		urlencode("oauth_consumer_key") => $privateData['twitterConsumerKey'],
		urlencode("oauth_nonce") => generateString(),
		urlencode("oauth_signature_method") => "HMAC-SHA1",
		urlencode("oauth_timestamp") => time(),
		urlencode("oauth_token") => $privateData['twitterAccessToken'],
		urlencode("oauth_version") => "1.0",
	);
	
	$signature = createTwitterOauthSignature($url, $OAuth, $method);
	$OAuth['oauth_signature'] = $signature;
	ksort($OAuth);

	$authorization = "OAuth ";
	foreach ($OAuth as $key => $value) {
		$authorization .= urlencode($key) . '="' . urlencode($value) . '", ';
	}
	$authorization = substr($authorization, 0, strlen($authorization) - 2);

	return $authorization;
}

function createTwitterOauthSignature($url, $OAuth, $method) {
	$signatureBaseString = strtoupper($method) . "&" . urlencode($url) . "&";

	foreach ($OAuth as $key => $value) {
		$parameterString .= $key . "=" . $value . "&";
	}
	$parameterString = substr($parameterString, 0, strlen($parameterString) - 1);

	$signatureBaseString .= urlencode($parameterString);

	global $privateData;
	$signingKey = urlencode($privateData['twitterConsumerSecret']) . "&" . urlencode($privateData['twitterAccessTokenSecret']);

	$signature = base64_encode(hash_hmac("sha1", $signatureBaseString, $signingKey, true));
	return $signature;
}

?>