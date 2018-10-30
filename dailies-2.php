<?php
/*
Plugin Name: The Dailies
Plugin URI:  https://dailies.gg/
Description: Handles all the additional functionality needed to run The Dailies.
Version:     2.0
Author:      Alec Simone
License:     Do whatever the hell you want with it, it's mostly pretty shit code
*/

require_once( __DIR__ . '/helpers.php');
require_once( __DIR__ . '/privateData.php');

//People
require_once( __DIR__ . '/People/people-databases-setup.php');
require_once( __DIR__ . '/People/people.php');

//Things
require_once( __DIR__ . '/Things/thing-databases-setup.php');
require_once( __DIR__ . '/Things/submissions.php');

//Voting
require_once( __DIR__ . '/Voting/vote-databases-setup.php');
require_once( __DIR__ . '/Voting/voting.php');

//Rules

require_once( __DIR__ . '/rest-endpoints.php');

?>