<?php
/**
 * Sigma Events Template
 *
 * After redirected by custom rewrite rules specific to
 * Sigma Events, this template is used to serve the custom
 * Sigma Event Information in the front end.
 *
 * Additionally, this template has a form which allows visitors
 * to register for the event, after viewing the event information.
 *
 * @package     SigmaEvents
 * @subpackage  SigmaTempaltes
 */

if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

global $wpdb;

get_header();
if(have_posts()): while(have_posts()): the_post();
    // Get Event ID
	$event_id           = get_the_ID();
	$home=get_home_url(); 
	
    // Get Event Meta Information
	$details            = get_post_meta($event_id, 'sigma_event_details', true);
	$period             = get_post_meta($event_id, 'sigma_event_period', true);	
	$age                = get_post_meta($event_id, 'sigma_event_age', true);
	$legal_information  = get_post_meta($event_id, 'sigma_event_legal_information', true);
    $organizer          = get_post_meta($event_id, 'sigma_event_organizer', true);
    $sequence           = get_post_meta($event_id, 'sigma_event_sequence', true);
    $codes              = get_post_meta($event_id, 'sigma_event_codes', true);
	$eventcode = $organizer['eventcode'];
	$max = date("Y") - $age['max']-1;
	$min = date("Y") - $age['min']+1;
	//echo " MAX =  " .$max;
	
    if(!$codes):
        $codes['enable_discount_codes'] = false;
    endif;

    // (1) Future Event?
    if($period['start'] > time()):
        echo __('Future Event. Registration is not available yet.', 'se');

    // (2) Past Event?
    elseif($period['end'] < time()):
        echo __('Past Event. Registration is not allowed now.', 'se');

	else:
        // Event Title
        echo "<div class='se-header' >";
        the_title();
        echo "</div>";

        // Event Picture/Thumbnail
        echo "<div class='se-thumbnail sigma-image-preview' >";
            echo '<img src="' . $details['header_image'] . '" >';
        echo "</div>";

        // Retrive the 'Event Period' Information
            // Organizer Logo
            echo "<div class='se-ologo se-half' >";
              echo "<img src='" . $organizer['logo'] . "' alt='Event Logo'>";
            echo "</div>";

            // Organizer Name
            echo "<div class='se-oname se-half' >";
              echo __('Organized by: ', 'se') . $organizer['name'];
            echo "</div>";

            // Organizer Url/Website
            echo "<div class='se-ourl se-half' >";
            echo " <a target='_blank' href='" .
                $organizer['url'] . "' title='" . __('Visit Event Organizer Site', 'se') .
                "'>" . __('Visit Organizer Site', 'se') . "</a>";
            echo "</div>";

            // Final Day of Registration Of The Event
            echo "<div class='se-pend se-half' >";
              //echo __('Booking for this event will be finalized on: ', 'se') . date('Y-m-d', $period['end']);
            echo "</div>";

			// Event Description
			echo "<div class='se-content se-half' >";
				echo $details['description'];
			echo "</div>";
			
			 if(!isset($_POST['dni-track'])){
				echo "<div class='form-show'>";
				echo"<form method='POST' action='". $PHP_SELF ."' name='track' id='track'>";
				// Form errors will be appended here after jQuery processing
					echo "<div id='se-form-errors' >";
					echo "</div>";
				
				// DNI
				echo "<div class='se-dni se-half' >";
					echo "<label id='se-dni-first'>" . __("Identity Number (ID, DNI, Passport, RG or CI): ", 'se') . "</label>";
					echo "<input type='text' id='dni-track' name='dni-track' value='' >";
					echo "</br>Your identity card or passport number, without letters.";
				echo "</div>";
				echo "<div class='se-row se-half'>";
					echo "<input type='submit' id='se-track-button' name='track' value='" .
							__("Submit / Register", 'se') . "'>";
				echo "</div>";
				echo "<div class='se-row se-half'> </br></br>";
				
				echo '<a id="se-retrieve" class="button" href="'.$home.'/forget_code/">';
					echo __("I don't remember my ID and want to retrieve", 'se');
				echo "</a></div>";
			
				echo "</form>";
				echo "</div>";
			}
	  
			if(isset($_POST['dni-track'])){
			$dni=$_POST['dni-track'];
			//check if dni data in database or not	
			$table_name   = $wpdb->prefix . $this->registration_table;
			/*$SQL = "SELECT fname, lname, dni, email FROM".$table_name." WHERE dni =%s AND eid =%d";*/			
			$SQL = "SELECT token,fname, lname, dni, email FROM ".$table_name." WHERE dni =".$dni." AND eid ='".$event_id."'";
			$dniRow = $wpdb->get_row( $wpdb->prepare( $SQL, $_POST['dni-track'], get_the_ID()));
			$dniInDB = $dniRow->dni;
			$fnameInDB = $dniRow->fname;
			$lnameInDB = $dniRow->lname;
			$emailInDB = $dniRow->email;
			$tkn= $dniRow->token;
			$home=get_home_url();
			
			 
				// if user with dni already exists
				if($dni==$dniInDB ){
					$loc=$home.'/sigma-events/tracker/?sigma_token='.$tkn;
					?>
				<script type='text/javascript'>
				window.location.href = "<?php echo $loc; ?>";
				</script>
				<?php
				}
				
				// if dni doesnt exist display Registration Form
				
				else if($dni!=$dniInDB){
					echo "<div class='form-hide'>";
					$home=get_home_url(); 
					echo "<form id='se-registration' name='se-registration' method='POST' action='" . get_home_url() . "/sigma-events/registration' >";
				
					// Dummy fields to trick bots
					echo "<div class='se-row' ><div class='se-firstname se-half' >";
						echo "<label>" . __("Enter First Name: ", 'se'). "</label>";
						echo "<input type='text' id='firstname' name='firstname' value='' >";
					echo "</div>";
					echo "<div class='se-lastname se-half' >";
						echo "<label>" . __("Enter Last Name: ", 'se') . "</label>";
						echo "<input type='text' id='lastname' name='lastname' value='' >";
					echo "</div></div>";

					// Event ID
					echo "<input type='hidden' name='event_id' value='". $event_id  ."' >";
		
					// Nonce Fields
					wp_nonce_field('sigma-registration-form-action', 'sigma-registration-form-data');
					
					// DNI
					echo "<div class='se-row' ><div class='se-dni se-half' >";
						echo "<label>" . __("Identity Number: ", 'se') . "</label>";
						echo "<input type='text' id='se-dni' name='dni' value='".$dni."' readonly='readonly' >";
					echo "</div>";
		
					// First Name
					echo "<div class='se-row' ><div class='se-fname se-half' >";
						echo "<label>" . __("First Name: ", 'se'). "</label>";
						echo "<input type='text' id='se-fname' name='fname' value='' >";
					echo "</div></div>";
		
					// Last Name
					echo "<div class='se-row' ><div class='se-lname se-half' >";
						echo "<label>" . __("Last Name: ", 'se') . "</label>";
						echo "<input type='text' id='se-lname' name='lname' value='' >";
					echo "</div></div>";

					// Nationality (+ Country Selection)
						echo "<div class='se-row' ><div class='se-nationality' >";
						echo "<label class='se-nation' >" . __("Nationality: ", 'se') . "</label>";
						echo "<input class='se-check-box' type='checkbox' id='argentinian' name='argentinian' > <label class='se-checkbox'>Argentinian</label>";
						echo "<input class='se-check-box' type='checkbox' id='nonargentinian' name='nonargentinian' > <label class='se-checkbox se-other-nation'>Other</label>";
						echo '<select id="se-country" class="se-country" name="country" >
								<option value="not-selected">Select Nationality</option>
								<option value="af">Afghanistan</option>
								<option value="al">Albania</option>
								<option value="dz">Algeria</option>
								<option value="as">American Samoa</option>
								<option value="ad">Andorra</option>
								<option value="ao">Angola</option>
								<option value="ai">Anguilla</option>
								<option value="ag">Antigua and Barbuda</option>
								<option value="am">Armenia</option>
								<option value="aw">Aruba</option>
								<option value="ac">Ascension Island</option>
								<option value="au">Australia</option>
								<option value="at">Austria</option>
								<option value="az">Azerbaijan</option>
								<option value="bs">Bahamas</option>
								<option value="bh">Bahrain</option>
								<option value="bd">Bangladesh</option>
								<option value="bb">Barbados</option>
								<option value="by">Belarus</option>
								<option value="be">Belgium</option>
								<option value="bz">Belize</option>
								<option value="bj">Benin</option>
								<option value="bm">Bermuda</option>
								<option value="bt">Bhutan</option>
								<option value="bo">Bolivia</option>
								<option value="bq">Bonaire, Sint Eustatius, and Saba</option>
								<option value="ba">Bosnia and Herzegovina</option>
								<option value="bw">Botswana</option>
								<option value="br">Brazil</option>
								<option value="bn">Brunei</option>
								<option value="bg">Bulgaria</option>
								<option value="bf">Burkina Faso</option>
								<option value="bi">Burundi</option>
								<option value="kh">Cambodia</option>
								<option value="cm">Cameroon</option>
								<option value="ca">Canada</option>
								<option value="cv">Cape Verde</option>
								<option value="ky">Cayman Islands</option>
								<option value="cf">Central African Republic</option>
								<option value="td">Chad</option>
								<option value="cl">Chile</option>
								<option value="cn">China</option>
								<option value="co">Colombia</option>
								<option value="km">Comoros and Mayotte</option>
								<option value="cg">Congo</option>
								<option value="cd">Congo Dem Rep</option>
								<option value="ck">Cook Islands</option>
								<option value="cr">Costa Rica</option>
								<option value="ci">Cote dIvoire</option>
								<option value="hr">Croatia</option>
								<option value="cu">Cuba</option>
								<option value="cw">Curaçao</option>
								<option value="cy">Cyprus</option>
								<option value="cz">Czech Republic</option>
								<option value="dk">Denmark</option>
								<option value="io">Diego Garcia</option>
								<option value="dj">Djibouti</option>
								<option value="dm">Dominica</option>
								<option value="do">Dominican Republic</option>
								<option value="ec">Ecuador</option>
								<option value="eg">Egypt</option>
								<option value="sv">El Salvador</option>
								<option value="gq">Equatorial Guinea</option>
								<option value="er">Eritrea</option>
								<option value="ee">Estonia</option>
								<option value="et">Ethiopia</option>
								<option value="fk">Falkland Islands</option>
								<option value="fo">Faroe Islands</option>
								<option value="fj">Fiji</option>
								<option value="fi">Finland</option>
								<option value="fr">France</option>
								<option value="gf">French Guiana</option>
								<option value="pf">French Polynesia</option>
								<option value="ga">Gabon</option>
								<option value="gm">Gambia</option>
								<option value="ge">Georgia</option>
								<option value="de">Germany</option>
								<option value="gh">Ghana</option>
								<option value="gi">Gibraltar</option>
								<option value="gr">Greece</option>
								<option value="gl">Greenland</option>
								<option value="gd">Grenada</option>
								<option value="gp">Guadeloupe</option>
								<option value="gu">Guam</option>
								<option value="gt">Guatemala</option>
								<option value="gn">Guinea</option>
								<option value="gw">Guinea Bissau</option>
								<option value="gy">Guyana</option>
								<option value="ht">Haiti</option>
								<option value="hn">Honduras</option>
								<option value="hk">Hong Kong</option>
								<option value="hu">Hungary</option>
								<option value="is">Iceland</option>
								<option value="in">India</option>
								<option value="id">Indonesia</option>
								<option value="ir">Iran</option>
								<option value="iq">Iraq</option>
								<option value="ie">Ireland</option>
								<option value="il">Israel</option>
								<option value="it">Italy</option>
								<option value="jm">Jamaica</option>
								<option value="jp">Japan</option>
								<option value="jo">Jordan</option>
								<option value="kz">Kazakhstan</option>
								<option value="ke">Kenya</option>
								<option value="ki">Kiribati</option>
								<option value="kp">Korea, North</option>
								<option value="kr">Korea, South</option>
								<option value="kw">Kuwait</option>
								<option value="kg">Kyrgyzstan</option>
								<option value="la">Laos</option>
								<option value="lv">Latvia</option>
								<option value="lb">Lebanon</option>
								<option value="ls">Lesotho</option>
								<option value="lr">Liberia</option>
								<option value="ly">Libya</option>
								<option value="li">Liechtenstein</option>
								<option value="lt">Lithuania</option>
								<option value="lu">Luxembourg</option>
								<option value="mo">Macao</option>
								<option value="mk">Macedonia</option>
								<option value="mg">Madagascar</option>
								<option value="mw">Malawi</option>
								<option value="my">Malaysia</option>
								<option value="mv">Maldives</option>
								<option value="ml">Mali</option>
								<option value="mt">Malta</option>
								<option value="mh">Marshall Islands</option>
								<option value="mq">Martinique</option>
								<option value="mr">Mauritania</option>
								<option value="mu">Mauritius</option>
								<option value="mx">Mexico</option>
								<option value="fm">Micronesia</option>
								<option value="md">Moldova</option>
								<option value="mc">Monaco</option>
								<option value="mn">Mongolia</option>
								<option value="me">Montenegro</option>
								<option value="ms">Montserrat</option>
								<option value="ma">Morocco</option>
								<option value="mz">Mozambique</option>
								<option value="mm">Myanmar</option>
								<option value="na">Namibia</option>
								<option value="nr">Nauru</option>
								<option value="np">Nepal</option>
								<option value="nl">Netherlands</option>
								<option value="nc">New Caledonia</option>
								<option value="nz">New Zealand</option>
								<option value="ni">Nicaragua</option>
								<option value="ne">Niger</option>
								<option value="ng">Nigeria</option>
								<option value="nu">Niue</option>
								<option value="nf">Norfolk Island</option>
								<option value="mp">Northern Mariana Islands</option>
								<option value="no">Norway</option>
								<option value="om">Oman</option>
								<option value="pk">Pakistan</option>
								<option value="pw">Palau</option>
								<option value="ps">Palestinian Territories</option>
								<option value="pa">Panama</option>
								<option value="pg">Papua New Guinea</option>
								<option value="py">Paraguay</option>
								<option value="pe">Peru</option>
								<option value="ph">Philippines</option>
								<option value="pl">Poland</option>
								<option value="pt">Portugal</option>
								<option value="pr">Puerto Rico</option>
								<option value="qa">Qatar</option>
								<option value="re">Reunion</option>
								<option value="ro">Romania</option>
								<option value="ru">Russia</option>
								<option value="rw">Rwanda</option>
								<option value="bl">Saint Barthélemy</option>
								<option value="sh">Saint Helena</option>
								<option value="kn">Saint Kitts and Nevis</option>
								<option value="lc">Saint Lucia</option>
								<option value="mf">Saint Martin</option>
								<option value="pm">Saint Pierre and Miquelon</option>
								<option value="vc">Saint Vincent Grenadines</option>
								<option value="ws">Samoa</option>
								<option value="sm">San Marino</option>
								<option value="st">Sao Tome and Principe</option>
								<option value="sa">Saudi Arabia</option>
								<option value="sn">Senegal</option>
								<option value="rs">Serbia</option>
								<option value="sc">Seychelles</option>
								<option value="sl">Sierra Leone</option>
								<option value="sg">Singapore</option>
								<option value="sx">Sint Maarten</option>
								<option value="sk">Slovakia</option>
								<option value="si">Slovenia</option>
								<option value="sb">Solomon Islands</option>
								<option value="so">Somalia</option>
								<option value="za">South Africa</option>
								<option value="ss">South Sudan</option>
								<option value="es">Spain</option>
								<option value="lk">Sri Lanka</option>
								<option value="sd">Sudan</option>
								<option value="sr">Suriname</option>
								<option value="sz">Swaziland</option>
								<option value="se">Sweden</option>
								<option value="ch">Switzerland</option>
								<option value="sy">Syria</option>
								<option value="tw">Taiwan</option>
								<option value="tj">Tajikistan</option>
								<option value="tz">Tanzania</option>
								<option value="th">Thailand</option>
								<option value="tl">Timor-Leste</option>
								<option value="tg">Togo</option>
								<option value="tk">Tokelau</option>
								<option value="to">Tonga</option>
								<option value="tt">Trinidad and Tobago</option>
								<option value="tn">Tunisia</option>
								<option value="tr">Turkey</option>
								<option value="tm">Turkmenistan</option>
								<option value="tc">Turks and Caicos</option>
								<option value="tv">Tuvalu</option>
								<option value="ug">Uganda</option>
								<option value="ua">Ukraine</option>
								<option value="ae">United Arab Emirates</option>
								<option value="gb">United Kingdom</option>
								<option value="us">United States</option>
								<option value="uy">Uruguay</option>
								<option value="uz">Uzbekistan</option>
								<option value="vu">Vanuatu</option>
								<option value="va">Vatican City</option>
								<option value="ve">Venezuela</option>
								<option value="vn">Vietnam</option>
								<option value="vg">Virgin Islands, British</option>
								<option value="vi">Virgin Islands, US</option>
								<option value="wf">Wallis and Futuna</option>
								<option value="ye">Yemen</option>
								<option value="zm">Zambia</option>
								<option value="zw">Zimbabwe</option>
							</select>';
						echo "  <input class='se-check-box se-country' type='checkbox' style='display:none;' id='nonargentinian' name='ar_resident' >";
						echo "<label class='se-checkbox se-other-nation se-country' style='display:none;' >" . __("I'm resident of Argentina", 'se') . "</label>";
						echo "</div></div>";
		
					
		
					// Email
					echo "<div class='se-row' ><div class='se-email se-half' >";
						echo "<label>" . __("Email Address: ", 'se') . "</label>";
						echo "<input type='text' id='se-email' name='email' value='' >";
					echo "</div></div>";
		
					// Gender
					echo "<div class='se-row' ><div class='se-gender se-half' >";
						echo "<label>" . __("Gender: ", 'se') . "</label>";
						echo "<select id='gender' name='gender' >
							<option value='select'>" . __("Select", 'se') . "</option>
							<option value='male'>" . __("Male", 'se') . "</option>
							<option value='female'>" . __("Female", 'se') . "</option>
							</select>";
					echo "</div>";
			
					// Birth Date
					echo "<div class='se-row' >";
					echo "<div class='se-bday se-half' >";
						echo "<label>" . __("Birth Date: ", 'se') . "</label>";
						echo "<select id='day' name='day'>
							<option value='' selected='selected'>day</option>
							<option value='01'>01</option>
							<option value='02'>02</option>
							<option value='03'>03</option>
							<option value='04'>04</option>
							<option value='05'>05</option>
							<option value='06'>06</option>
							<option value='07'>07</option>
							<option value='08'>08</option>
							<option value='09'>09</option>
							<option value='10'>10</option>
							<option value='11'>11</option>
							<option value='12'>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
							<option value='21'>21</option>
							<option value='22'>22</option>
							<option value='23'>23</option>
							<option value='24'>24</option>
							<option value='25'>25</option>
							<option value='26'>26</option>
							<option value='27'>27</option>
							<option value='28'>28</option>
							<option value='29'>29</option>
							<option value='30'>30</option>
							<option value='31'>31</option>
						</select>";
					
					echo "<select id='month' name='month'>
						<option value='' selected='selected'>month</option>
						<option value='01'>Jan (01)</option>
						<option value='02'>Feb (02)</option>
						<option value='03'>Mar (03)</option>
						<option value='04'>Apr (04)</option>
						<option value='05'>May (05)</option>
						<option value='06'>Jun (06)</option>
						<option value='07'>Jul (07)</option>
						<option value='08'>Aug (08)</option>
						<option value='09'>Sep (09)</option>
						<option value='10'>Oct (10)</option>
						<option value='11'>Nov (11)</option>
						<option value='12'>Dec (12)</option>
					</select>";
					
					echo "<select id='year' name='year'>";
					echo "<option value='' selected='selected'>year</option>";
					for ($i=$max; $i<=$min; $i++)
						echo "<option value=$i>$i</option>";
					echo "</select>";
					echo "</div></div>";
					
					// Telephone Number
					echo "<div class='se-row' ><div class='se-phone se-half' >";
						echo "<label>" . __("Telephone Number: ", 'se') . "</label>";
						echo "<input type='text' id='phone' name='phone' value='' >";
					echo "</div></div>";
					
					// Full Address
					echo "<div class='se-row' >";	
					echo "<div class='se-addr se-half' >";
						echo "<label>" . __("Full Address (incl. District/Province): ", 'se') . "</label>";
						echo "<input type='text' id ='address' name='addr' value='' >";
					echo "</div></div>";
					
					echo "<input type='hidden' id ='eventcode' name='eventcode' value='".$eventcode."' >";
		
					// Club Name
					echo "<div class='se-row' ><div class='se-club se-half' >";
						echo "<label>" . __("Running Team: ", 'se') . "</label>";
						echo "<input type='text' name='club' value='' >";
					echo "</div>";
		
					// Discount Code
					if($codes['enable_discount_codes']):
						echo "<div class='se-discount-code se-half' >";
							echo "<label>" . __("Discount Code(optional): ", 'se') . "</label>";
							echo "<input type='text' name='dcode' value='' >";
						echo "</div>";
					endif;
		
					echo "</div>";
		
					// Organizer Answer
					$answers = isset($organizer['answer']) ? explode(';', $organizer['answer']) : false;
					if(sizeof($answers) > 1){
						$answer_html = "<select name='answer' id='se-q-answers' >";
						foreach($answers as $answer){
							$answer_html .= '<option value="'. $answer . '">' . $answer . '</option>';
						}
						$answer_html .= '</select>';
					} else {
						$answer_html = "<textarea name='answer' id='se-q-answer' ></textarea>";
					}
		
					// Organizer Question
					if($organizer['question'] != ''):
						echo "<div id='se-number-selection-anchor' class='se-row' >";
							echo "<div class='se-question se-half' >";
								echo "<label>" . $organizer['question'] . "</label>";
								echo $answer_html;
							echo "</div>";
						echo "</div>";
					endif;
		
					// Number Selection
					if($sequence['enable_number_reservation']):
						echo "<div id='se-number-selection-form' class='se-row' >";
						global $sigma_events;
						echo $sigma_events->sequences->get_number_selection_form( $event_id );
						echo "</div>";
					endif;
		
					// Form errors will be appended here after jQuery processing
					echo "<div id='se-form-errors' >";
					echo "</div>";

				echo "<div class='se-submit'  >";
                // Above Disclaimer Text
			    if( isset($organizer['above_disclimer_text']) && $organizer['above_disclimer_text'] != ''):
				echo '<p>' . $organizer['above_disclimer_text'] . '</p>';
                endif;

				// Legal Information
				echo '<p>' . __('By clicking "Book for this event" button below, you are accepting', 'se') . '
					<a href="' . esc_url($legal_information['terms_and_conditions']) . '" >' .
						__('terms and conditions', 'se') . '</a>,' . '
					<a href="' . esc_url($legal_information['privacy_policy']) . '" >' .
						__('privacy policy', 'se') . '</a> ' . __('and', 'se') . '
					<a href="' . esc_url($legal_information['organizer_terms_and_conditions']) . '" >' .
						__('event organizer terms and conditions', 'se') . '</a>.</p>';

				// Submit Button
				echo "<input type='submit' id='se-reg-button' name='submit' value='" .
						__("Book for this event", 'se') . "'>";

			echo "</div>";
			echo "</form>";
		
		}//END OF dniInDB IF
	 }	endif;
		
endwhile; endif;
get_footer();
?>
