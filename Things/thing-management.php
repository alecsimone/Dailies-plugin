<?php

function getPulledClipsDB() {
	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$pulledClipsDB = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		",
		ARRAY_A
	);

	return $pulledClipsDB;
}

function getCleanPulledClipsDB() {
	$pulledClipsDBRaw = getPulledClipsDB();
	foreach ($pulledClipsDBRaw as $key => $clipData) {
		$clipTimestamp = convertTwitchTimeToTimestamp($clipData['age']);
		$lastNomTime = getLastNomTimestamp();
		$eightHoursBeforeLastNom = $lastNomTime - 8 * 60 * 60;
		$twentyFourHoursAgo = time() - 24 * 60 * 60;
		$ourCutoff = $eightHoursBeforeLastNom < $twentyFourHoursAgo ? $eightHoursBeforeLastNom : $twentyFourHoursAgo;
		if ($clipTimestamp < $ourCutoff && (intval($clipData['score']) < 0 || $clipData['nuked'] == 1)) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			continue;
		}
		if ($clipTimestamp < time() - 14 * 24 * 60 * 60) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			continue;
		}
		$pulledClipsDB[$clipData['slug']] = $clipData;
	}
	return $pulledClipsDB;
}

?>
