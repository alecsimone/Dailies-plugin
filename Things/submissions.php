<?php

add_action( 'wp_ajax_submitClip', 'submitClip' );
function submitClip () {
	$newSeedlingTitle = substr(sanitize_text_field($_POST['title']), 0, 80);
	$newSeedlingUrl = substr(esc_url($_POST['url']), 0, 140);

	$clipType = clipTypeDetector($newSeedlingUrl);

	if ($clipType === 'twitch') {
		$slug = turnURLIntoTwitchCode($newSeedlingUrl);
	} elseif ($clipType === 'youtube') {
		$slug = turnURLIntoYoutubeCode($newSeedlingUrl);
	} elseif ($clipType === 'ytbe') {
		$slug = turnURLIntoYtbeCode($newSeedlingUrl);
	} elseif ($clipType === 'twitter') {
		$slug = turnURLIntoTwitterCode($newSeedlingUrl);
	} elseif ($clipType === 'gfycat') {
		$slug = turnURLIntoGfycode($newSeedlingUrl);
	} else {
		killAjaxFunction("Invalid URL");
	}

	$existingSlug = getSlugInPulledClipsDB($slug);
	if ($existingSlug !== null) {
		killAjaxFunction("That clip has already been submitted");
	} else {
		$clipArray = array(
			'slug' => $slug,
			'title' => $newSeedlingTitle,
			'views' => 0,
			'age' => date('c'),
			'source' => "User Submit",
			'sourcepic' => 'unknown',
			'vodlink' => 'none',
			'thumb' => 'none',
			'clipper' => get_user_meta(get_current_user_id(), 'nickname', true),
			'votecount' => 0,
			'score' => 0,
			'nuked' => 0,
			'type' => $clipType,
		);
		$addSlugSuccess = addSlugToDB($clipArray);
	}

	$gussyResult = gussyClip($clipType, $slug);

	killAjaxFunction($addSlugSuccess);
}

function gussyClip($clipType, $slug) {
	if ($clipType === 'twitch') {
		$gussyResult = gussyTwitch($slug);
	} elseif ($clipType === 'youtube' || $clipType === 'ytbe') {
		$gussyResult = gussyYoutube($slug);
	} elseif ($clipType === 'twitter') {
		$gussyResult = gussyTweet($slug);
	} elseif ($clipType === 'gfycat') {
		$gussyResult = gussyGfy($slug);
	} else {
		return "Invalid Clip Type";
	}

	return $gussyResult;
}

function gussyTweet($tweetID) {
	$url = 'https://api.twitter.com/1.1/statuses/show.json?id=' . $tweetID . '&tweet_mode=extended';

	global $privateData;
	$args = array(
		"headers" => array(
			"Authorization" => $privateData['twitterAccessToken'],
		),
	);

	$response = wp_remote_get($url, $args);
	$responseBody = json_decode($response['body']);
	$date = $responseBody->created_at;
	$sourcePic = $responseBody->user->profile_image_url_https;
	$thumb = $responseBody->extended_entities->media[0]->media_url_https;

	$videosArray = $responseBody->extended_entities->media[0]->video_info->variants;
	$biggestVideoKey = array_keys($videosArray, max($videosArray))[0];
	$vodlink = $videosArray[$biggestVideoKey]->url;
	if (!$vodlink) {$vodlink = "none";}

	$clipArray = array(
		'slug' => $tweetID,
		'age' => $date,
		'vodlink' => $vodlink,
		'sourcepic' => $sourcePic,
		'thumb' => $thumb,
	);

	$editSuccess = editPulledClip($clipArray);
	if ($editSuccess >= 0) {
		return true;
	} else {
		return false;
	}
}

function gussyTwitch($twitchCode) {
	$url = 'https://api.twitch.tv/kraken/clips/' . $twitchCode;

	global $privateData;
	$args = array(
		"headers" => array(
			'Client-ID' => $privateData['twitchClientID'],
			'Accept' => 'application/vnd.twitchtv.v5+json',
		),
	);

	$response = wp_remote_get($url, $args);
	$responseBody = json_decode($response['body']);

	$clipArray = array(
		'slug' => $responseBody->slug,
		'source' => $responseBody->broadcaster->display_name,
		'age' => $responseBody->created_at,
		'thumb' => $responseBody->thumbnails->medium,
		'views' => $responseBody->views,
		'sourcepic' => $responseBody->broadcaster->logo,
		'vodlink' => $responseBody->vod ? $responseBody->vod->url : 'null',
	);

	$editSuccess = editPulledClip($clipArray);
	if ($editSuccess >= 0) {
		return true;
	} else {
		return false;
	}
}

function gussyYoutube($youtubeCode) {
	$url = 'https://www.googleapis.com/youtube/v3/videos';

	global $privateData;
	$args = array(
		"body" => array(
			'id' => $youtubeCode,
			'key' => $privateData['googleAPIKey'],
			'part' => 'snippet,statistics',
		),
	);

	$response = wp_remote_get($url, $args);
	$responseBody = json_decode($response['body']);

	$clipArray = array(
		'slug' => $youtubeCode,
		'age' => $responseBody->items[0]->snippet->publishedAt,
		'thumb' => $responseBody->items[0]->snippet->thumbnails->standard->url,
		'views' => $responseBody->items[0]->statistics->viewCount,
	);

	$editSuccess = editPulledClip($clipArray);
	if ($editSuccess >= 0) {
		return true;
	} else {
		return false;
	}
}

function gussyGfy($gfyCode) {
	$url = 'https://api.gfycat.com/v1/gfycats/' . $gfyCode;

	global $privateData;
	$args = array();

	$response = wp_remote_get($url, $args);
	$responseBody = json_decode($response['body']);

	$clipArray = array(
		"slug" => $gfyCode,
		"age" => date('c', $responseBody->gfyItem->createDate),
		"thumb" => $responseBody->gfyItem->posterUrl,
		"views" => $responseBody->gfyItem->views,
		"vodlink" => $responseBody->gfyItem->mp4Url,
	);

	$editSuccess = editPulledClip($clipArray);
	if ($editSuccess >= 0) {
		return true;
	} else {
		return false;
	}
}

add_action( 'wp_ajax_addProspect', 'addProspect' );
function addProspect () {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}

	$newProspectTitle = substr(sanitize_text_field($_POST['title']), 0, 80);
	$newProspectUrl = substr(esc_url($_POST['url']), 0, 140);

	$starID = starChecker($newProspectTitle);

	$clipTax = array(
		'stars' => $starID,
	);

	$clipMeta = array();

	$clipType = clipTypeDetector($newProspectUrl);

	if ($clipType === 'twitch') {
		$slug = turnURLIntoTwitchCode($newProspectUrl);
		$clipMeta['TwitchCode'] = $slug;
	} elseif ($clipType === 'youtube') {
		$slug = turnURLIntoYoutubeCode($newProspectUrl);
		$clipMeta['YouTubeCode'] = $slug;
	} elseif ($clipType === 'ytbe') {
		$slug = turnURLIntoYtbeCode($newProspectUrl);
		$clipMeta['YouTubeCode'] = $slug;
	} elseif ($clipType === 'twitter') {
		$slug = turnURLIntoTwitterCode($newProspectUrl);
		$clipMeta['TwitterCode'] = $slug;
	} elseif ($clipType === 'gfycat') {
		$slug = turnURLIntoGfycode($newProspectUrl);
		$clipMeta['GFYtitle'] = $slug;
	} else {
		killAjaxFunction("Invalid URL");
	}
	nukeSlug($slug);

	$prospectArray = array(
		'post_title' => $newProspectTitle,
		'post_content' => '',
		'post_excerpt' => '',
		'post_status' => 'publish',
		'tax_input' => $clipTax,
		'meta_input' => $clipMeta,
	);
	$didPost = wp_insert_post($prospectArray, true);
	if ($didPost > 0) {
		absorb_votes($didPost);
	}

	killAjaxFunction($didPost);
}

add_action( 'wp_ajax_gussyProspect', 'gussyProspect' );
function gussyProspect() {
	$channelURL = $_POST['channelURL'];
	$channelPic = $_POST['channelPic'];
	$postID = $_POST['postID'];
	$sourceID = sourceFinder($channelURL);
	wp_set_post_terms( $postID, $sourceID, 'source');
	update_post_meta( $postID, 'sourcepic', $channelPic);
	echo json_encode($sourceID);
	wp_die();
}


?>