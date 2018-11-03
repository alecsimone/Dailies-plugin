<?php

function add_custom_cron_schedules($schedules) {
	$schedules['twiceHourly'] = array(
		'interval' => 1800,
		'display' => __("Twice Hourly"),
	);

	$schedules['minute'] = array(
		'interval' => 60,
		'display' => __("Every Minute"),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'add_custom_cron_schedules' );

if( !wp_next_scheduled( 'pull_clips' ) ) {
   wp_schedule_event( time(), 'twiceHourly', 'pull_clips' );
}

add_action( 'pull_clips', 'pull_clips_cron_handler' );
function pull_clips_cron_handler() {
	pull_all_clips();
}

if( !wp_next_scheduled( 'clean_pull_clips_db' ) ) {
   wp_schedule_event( time(), 'daily', 'clean_pulled_clips_db' );
}
add_action( 'clean_pulled_clips_db', 'clean_pulled_clips_db_cron_handler' );
function clean_pulled_clips_db_cron_handler() {
	$clipTimestamp = convertTwitchTimeToTimestamp($clipData['age']);
	if ($clipTimestamp < time() - 14 * 24 * 60 * 60) {
		deleteSlugFromPulledClipsDB($clipData['slug']);
		continue;
	}
}

?>