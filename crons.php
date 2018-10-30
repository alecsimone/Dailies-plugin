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
   wp_schedule_event( time(), 'minute', 'pull_clips' );
}

add_action( 'pull_clips', 'pull_clips' );
function pull_clips() {
	$currentCronCount = get_option("cron-test");
	update_option( "cron-test", $currentCronCount + 1 );
}

// wp_clear_scheduled_hook('pull_clips');
// $a = _get_cron_array();
// basicPrint($a);

?>