<?php

require 'sigma-templates-utilities.php';

/**
 * This template is called near the end of payment tracker template
 */
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

echo "<div id='se-registration'>";

// First Name
echo "<div class='se-row' ><div class='se-fname se-half' >";
echo "<label>" . __("First Name: ", 'se'). "</label>";
echo "<label>" . $event_data['fname'] . "</label>";
echo "</div>";

// Last Name
echo "<div class='se-lname se-half' >";
echo "<label>" . __("Last Name: ", 'se') . "</label>";
echo "<label>" . $event_data['lname'] . "</label>";
echo "</div></div>";

// Nationality (+ Country Selection)
$country = '';
if(!$event_data['argentinian']):
    $country = ' ( ' . $event_data['country'] . ' ) ';
endif;
echo "<div class='se-row' ><div class='se-nationality' >";
echo "<label class='se-nation' >" . __("Nationality: ", 'se') . "</label>";
echo "<input disabled class='se-check-box' " . checked($event_data['argentinian'], true, false) . " type='checkbox' id='argentinian' name='argentinian' >
  <label class='se-checkbox'>Argentinian</label>";
echo "<input disabled class='se-check-box' " . checked(!$event_data['argentinian'], true, false) . " type='checkbox' id='nonargentinian' name='nonargentinian' >
  <label class='se-checkbox se-other-nation'>Other " . $country . "</label>";
echo "</div></div>";

// DNI
echo "<div class='se-row' ><div class='se-dni se-half' >";
echo "<label>" . __("DNI/Passport Number: ", 'se') . "</label>";
echo "<label>" . $event_data['dni'] . "</label>";
echo "</div>";

// Email
echo "<div class='se-email se-half' >";
echo "<label>" . __("Email Address: ", 'se') . "</label>";
echo "<label>" . $event_data['email'] . "</label>";
echo "</div></div>";

// Gender
echo "<div class='se-row' ><div class='se-gender se-half' >";
echo "<label>" . __("Gender: ", 'se') . "</label>";
echo "<label>" . $event_data['gender'] . "</label>";
echo "</div>";

// Birth Date
echo "<div class='se-bday se-half' >";
echo "<label>" . __("Birth Date: ", 'se') . "</label>";
echo "<label>" . $event_data['bday'] . "</label>";
echo "</div></div>";

// Club Name
echo "<div class='se-row' ><div class='se-club se-half' >";
echo "<label>" . __("Running Team: ", 'se') . "</label>";
echo "<label>" . $event_data['club'] . "</label>";
echo "</div>";

// Organizer Question
if($event_data['organizer']['question'] != ''):
    echo "<div class='se-question se-half' >";
    echo "<label>" . $event_data['organizer']['question'] . "</label>";
    echo "<label>" . $event_data['ans'] . "</label>";
    echo "</div>";
endif;

echo "</div>";
?>
