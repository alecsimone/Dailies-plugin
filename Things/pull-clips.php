<?php

function pull_all_clips() {
	update_option("cron-test", 696969);
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
		$tweetURL = "https://twitter.com/" . $tweetData->user->screen_name . "/status/" . $tweetData->id_str;
	// basicPrint($tweetURL);
		if ($tweetData->in_reply_to_status_id_str) {
			$parentTweet = getTweet($tweetData->in_reply_to_status_id_str);
			if ($parentTweet->user->screen_name === "Rocket_Dailies") {continue;}
			if ( tweetIsProbablySubmission($parentTweet) ) {
				$submission = submitTweet($parentTweet);
			}
		}

		if ($tweetData->entities->urls) {
			foreach ($tweetData->entities->urls as $urlArray) {
				if (!strpos($urlArray->expanded_url, 'twitter.com/i/') && strpos($urlArray->expanded_url, '/status/') >= 0) {
					$linkedTweetID = turnURLIntoTwitterCode($urlArray->expanded_url);
					$linkedTweet = getTweet($linkedTweetID);
					if ($linkedTweet->user->screen_name === "Rocket_Dailies") {continue;}
					if ( tweetIsProbablySubmission($linkedTweet) ) {
						$submission = submitTweet($linkedTweet);
					}
				}
			}
		}

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
		foreach ($entities->urls as $urlArray) {
			if (strpos($urlArray->expanded_url, "clips.twitch.tv") || strpos($urlArray->expanded_url, "gfycat.com") || strpos($urlArray->expanded_url, "youtube.com") ||strpos($urlArray->expanded_url, "youtu.be")) {
				return true;
			}
		}
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