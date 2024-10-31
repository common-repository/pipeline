<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Pipeline.so Connect
 *
 * @wordpress-plugin
 * Plugin Name:       Pipeline.so Connect
 * Plugin URI:        https://pipeline.so/
 * Description:       Pipeline.so helps you start, converse, and book more appointments on autopilot through SMS, Email, Live Chat, Phone Calls—and much more! The Pipeline.so Connect plugin helps you install the text to chat widget to your WordPress website to drive better conversions. It'll also allow you to embed Pipeline.so funnel pages to your WordPress website. This will help you capture your visitor's information such as email and phone numbers to ensure you don't miss out on any new deals.Enterprises and small businesses alike have a lot going on. Simplify your pipeline to maximize conversions with cutting-edge tools, support, and resources all in one place. Stop missing out on conversations! Streamline all your communication in one place so that you can double your speed to lead and fill your pipeline!
 * Version:           1.9
 * Author:            Pipeline.so
 * Author URI:        Pipeline.so
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       PipeLine
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PIPE_LINE_VERSION', '1.9');
define('PIPE_LINE_PLUGIN_NAME', 'Pipeline');
define('PIPE_LINE_BASE_URL', 'https://rest.leadconnectorhq.com/');
define('PIPE_LINE_OPTION_NAME', 'pipe_line_plugin_options');
define('PIPE_LINE_CDN_BASE_URL', 'https://widgets.leadconnectorhq.com/');
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pl-activator.php
 */
function activate_pipe_line()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-pl-activator.php';
    PipeLine_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pl-deactivator.php
 */
function deactivate_pipe_line()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-pl-deactivator.php';
    PipeLine_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_pipe_line');
register_deactivation_hook(__FILE__, 'deactivate_pipe_line');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-pl.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pipe_line() {

    $plugin = new PipeLine();
    $plugin->run();


}


function run_api_data($email,$data,$firstName='',$lastName='') {
	//get api key dynamically
	
	$settings = get_option('pipe_line_plugin_options');
	if(!empty($settings)) {
		$pipeline_api_key  = $settings['api_key'];
	}	
	//print_r($data);
	
	//~ print_r($data['tags']);
	//~ print_r($data['customField']);
	//die('ss');

	
	$fields = array(
            'email' => $data['email'],
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $data['phone'],
            'tags' => $data['tags'],
            'customField' => $data['customField'],
            'source' => 'public api',
        );
   if(isset($data['address1'])){
	   $fields['address1'] = $data['address1'];
   }
	
   if(isset($data['city'])){
	   $fields['city'] = $data['city'];
   } 
	
   if(isset($data['state'])){
	   $fields['state'] = $data['state'];
   }
	
   if(isset($data['country'])){
	   $fields['country'] = $data['country'];
   }
	
   if(isset($data['postalCode'])){
	   $fields['postalCode'] = $data['postalCode'];
   } 
	
   $fields_string = json_encode($fields); //data in Json Format
   	
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://rest.gohighlevel.com/v1/contacts/',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $fields_string,
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer '.$pipeline_api_key.'',
			'Content-Type: application/json'
		),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	$resp = json_decode($response,true);
	return $resp;
	die();

}  

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

function contactform7_wpcf7_mail_sent_function( $contact_form ) {
		
	$title = $contact_form->title;
	$submission = WPCF7_Submission::get_instance();
	$email = '';
	if ( $submission ) {
        $posted_data = $submission->get_posted_data();
    } 
	//print_r($posted_data); die();      
	if (!empty($posted_data)) {
		
		// get all keys of form here
		$form_keys = array_keys($posted_data);
		
		
		$first = array('name','first','first_name','fullname','firstname','your-name'); //Firstname
		$firstArr = implode(" ",$first);
		
		$last = array('last','last_name','lastname'); //LastName
		$lastArr = implode(" ",$last);

		$email = array('email','email-address','mail','email_address','your-email','user_email'); //EMail
		$emailArr = implode(" ",$email);

		$phone = array('phone','phone-number','tel','phone_number','your-phone','cell','cell-phone','cell-number','mobile-phone','other-phone','home-phone','office-phone','phone-other','phone-home','phone-office'); //Phone
		$phoneArr = implode(" ",$phone);
		$address = array('address_line_1','address1','address_1','addressline1');
		$addressArr = implode(" ",$address);
		
		//~ print_r($posted_data);
		//~ print_r($form_keys);
		
		$highlevelArr = array();
		$customfields = array();
		$tags = array();
		foreach($form_keys as $key ){
			
			if(stripos($key, 'tel') !== false ){   //tel, phone
				$highlevelArr['phone'] =  $posted_data[$key];

			} else if(stripos($key, 'hidden') !== false ){ //hidden tags
				$tags[] = $posted_data[$key];
				//~ $form_tags = implode(',', $tags);
				$highlevelArr['tags'] =  $tags;
				
			} else { // name, phone & email
				
				if(stripos($firstArr, $key) !== false ){
					$highlevelArr['firstName'] =  $posted_data[$key];
				} else if(stripos($lastArr, $key) !== false ) { 
					$highlevelArr['lastName'] =  $posted_data[$key];
				} else if(stripos($emailArr, $key) !== false ) { 
					$highlevelArr['email'] =  $posted_data[$key];
				} else if(stripos($phoneArr, $key) !== false ) { 
					$highlevelArr['phone'] =  $posted_data[$key];
				} else if(stripos($addressArr, $key) !== false ) { 
					$highlevelArr['address1'] =  $posted_data[$key];
				} else {
					$customfields[$key] = $posted_data[$key];
					$highlevelArr['customField'] =  $customfields;
				}
			}	
		}
		
		//print_r($highlevelArr);
		//exit;
		
		$first_name = '';
		if(isset($highlevelArr['firstName'])){
			$first_name = $highlevelArr['firstName'];
		}
		$last_name = '';
		if(isset($highlevelArr['lastName'])){
			$last_name = $highlevelArr['lastName'];
		}
		foreach($highlevelArr as $key => $_posted_data){
			$position = (int)strpos($key, 'email');
			//~ if($position >= 1){
				$email = strtolower($highlevelArr[$key]);
				if(($first_name != '') && ($last_name != '')){
					$ss = run_api_data($email, $highlevelArr, $first_name, $last_name);
					//print_r($ss); die();
				}
			//~ }
		}
	}
}
function gravityforms_submission_handler( $form ) {
    //print_r($_POST);
	if (!empty($_POST)) {
		//$email = trim($_POST["email"]);
		//$email = strtolower($posted_data['email']);
		
		//~ print_r($_POST);
		//~ die('test');
		
		foreach($_POST as $key => $_posted_data){
			$email = trim($_posted_data);			
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {				
				run_api_data($email, $_POST);
			}
		}
	}
}

function search_data($array, $key) { 
    $results = '';
      
    // if it is array
    if (is_array($array)) {
        //echo '1';
        // if array has required key and value
        // matched store result 
        if (isset($array[$key])) {
			//echo '2';
			//echo $array[$key];
            $results = $array[$key];
        }
        if($results == ''){ 
			// Iterate for each element in array
			foreach ($array as $subarray) {  
				if (is_array($subarray)) { 
						//echo '3';			
						$results = search_data($subarray, $key);
						if($results != ''){ 						
							break;
						}
				}
			}
		}
    }
	//echo $results.$key.'<br>';
	//echo '<br>';
  
    return $results;
}

function your_custom_before_submission_function($insertData, $data, $form) {
	
   //if($form->id != 5) {
	//print_r($data); die();
	if (!empty($data)) {
		
		
		$settings = get_option('pipe_line_plugin_options');
		if(!empty($settings)) {
			$pipeline_api_key  = $settings['api_key'];
		}	
		
		// get all keys of form here
		$form_keys = array_keys($data);
		
		//GoHighLevel
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://rest.gohighlevel.com/v1/custom-fields/',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer '.$pipeline_api_key.'',
			'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$resp = json_decode($response,true);
		
		
		//get the settings of fields
		$pipe_settings = ldpipe_getSettings();

		if( !empty($pipe_settings) ) {
			$pipe_field_settings = unserialize($pipe_settings['form_field']);
		}
		
		// Pipeline settings custom fields
		if(!empty($pipe_field_settings)) {
			$k = 0;
			$pipeArr = array();
			foreach($pipe_field_settings as $pipeField) {
				$pipeArr[$k] = $pipeField['pipeline_field'];
				$k++;
			}
		}
		
		// Pipeline dynamic Custom fields
		
		if(!empty($resp)) {
			$customFields = $resp['customFields'];
			$fieldsArr = array();
			$k = 0;
			foreach($customFields as $customField) {
				//~ $fieldsArr[$k]['id'] = $customField['id'];
				$cc = explode('.', $customField['fieldKey']);
				//~ $fieldsArr[$k]['key'] = $cc[1];
				$fieldsArr[$cc[1]] = $customField['id'];
				$k++;
			}
		}
		
		//~ echo '<pre/>';
		//~ print_r($pipeArr);  
		//~ print_r($fieldsArr);  
		
		//~ die();
		
		$first = array('name','names','first','first_name','fullname','firstname','your-name'); //Firstname
		$firstArr = implode(" ",$first);
		
		$last = array('last','last_name','lastname'); //LastName
		$lastArr = implode(" ",$last);

		$email = array('email','email-address','mail','email_address','your-email','user_email'); //EMail
		$emailArr = implode(" ",$email);

		$phone = array('phone','phone-number','tel','phone_number','your-phone','cell','cell-phone','cell-number','mobile-phone','other-phone','home-phone','office-phone','phone-other','phone-home','phone-office'); //Phone
		$phoneArr = implode(" ",$phone);
		$address = array('address_line_1','address1','address_1','addressline1');
		$addressArr = implode(" ",$address);
		$city = array('city');
		$cityArr = implode(" ",$city);
		$state = array('state');
		$stateArr = implode(" ",$state);
		$country = array('country');
		$countryArr = implode(" ",$country);
		$postalCode = array('zip');
		$postalCodeArr = implode(" ",$postalCode);
		
		//print_r($data);
		//print_r($addressArr);
		
		$highlevelArr = array();
		$customfields = array();
		$tags = array();
		
		
		$search_data = array();
		foreach($address as $_address){			
			$search_data = search_data($data,$_address);
			if(!empty($search_data)){
				break;
			}
		}
		$search_data1 = array();
		foreach($city as $_city){			
			$search_data1 = search_data($data,$_city);
			if(!empty($search_data1)){
				break;
			}
		}
		$search_data2 = array();
		foreach($state as $_state){			
			$search_data2 = search_data($data,$_state);
			if(!empty($search_data2)){
				break;
			}
		}
		$search_data3 = array();
		foreach($country as $_country){			
			$search_data3 = search_data($data,$_country);
			if(!empty($search_data3)){
				break;
			}
		}
		$search_data4 = array();
		foreach($postalCode as $_postalCode){			
			$search_data4 = search_data($data,$_postalCode);
			if(!empty($search_data4)){
				break;
			}
		}
		//print_r($search_data);
		if(!empty($search_data)) {  
			$highlevelArr['address1'] =  $search_data;
		} 
		if(!empty($search_data1)) {  
			$highlevelArr['city'] =  $search_data1;
		}
		if(!empty($search_data2)) {  
			$highlevelArr['state'] =  $search_data2;
		}
		if(!empty($search_data3)) {  
			$highlevelArr['country'] =  $search_data3;
		}
		if(!empty($search_data4)) {  
			$highlevelArr['postalCode'] =  $search_data4;
		}
		
		foreach($form_keys as $key ){
			    
			    
				if(stripos($key, 'tel') !== false ){   //tel, phone
					$highlevelArr['phone'] =  $data[$key];

				} else if(stripos($key, 'hidden') !== false ){ //hidden tags
					$tags[] = $data[$key];
					//~ $form_tags = implode(',', $tags);
					$highlevelArr['tags'] =  $tags;
					
				} else { // name, phone & email
					
					if(stripos($key, 'names') !== false ){   //tel, phone
						$highlevelArr['firstName'] =  $data[$key]['first_name'];
						$highlevelArr['lastName'] =  $data[$key]['last_name'];					
					} else if(stripos($emailArr, $key) !== false ) { 
						$highlevelArr['email'] =  $data[$key];
					} else if(stripos($phoneArr, $key) !== false ) { 
						$highlevelArr['phone'] =  $data[$key];
					}else {
						$customfields[$key] = $data[$key];
						if(isset($fieldsArr[$key]) && $fieldsArr[$key] != '') { //only add fields when match is found
							$myfields[$fieldsArr[$key]] = $data[$key];
							$highlevelArr['customField'] =  $myfields;
						}
					}
				}	
		}
		
		//echo '<pre/>';
		//print_r($data);
		//print_r($search_data);
		//print_r($search_data1);
		//print_r($search_data2);
		//print_r($search_data3);
		//print_r($search_data4);
		//print_r($highlevelArr); 
		//die();      
		
		//echo json_encode($highlevelArr); die();
		
		//echo $first_name.'=='.$last_name; die();
		foreach($highlevelArr as $key => $_posted_data){
			$email = trim($_posted_data);			
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				if(($highlevelArr['firstName'] != '') && ($highlevelArr['lastName'] != '')){
					$ss = run_api_data($email, $highlevelArr, $highlevelArr['firstName'], $highlevelArr['lastName']);
					//print_r($ss); die();
				}
			}
		}
	}
      return;

}

function learndash_corse_complete($data){
	//$current_user = wp_get_current_user();
	//$data = get_course_data();
	$user = $data['user']->data;
	$user_id = $data['user']->ID;
	$user_data = get_userdata($user_id);
	$first_name = $user_data->first_name;
	$last_name = $user_data->last_name;
	$course_id = $data['course']->ID;
	$course_name = $data['course']->post_title;
	$progress = $data['progress'];
	$progress_id = $progress[$course_id];
	
	$email = $user->user_email;
	//$course_complete = 
	//~ echo '<pre>';
	//~ print_r($data);
	
	//~ echo '</pre>';
	$highlevelArr = [];
		$customfields = array();
		$tags = array();
		if($first_name == null){
		$highlevelArr['firstName'] = $user->display_name;
	} else {
		$highlevelArr['firstName'] = $user_data->first_name;
		}

		$highlevelArr['lastName'] = $user_data->last_name;
		$highlevelArr['email'] = $user->user_email;
		$highlevelArr['tags'] = $course_name.' - completed';
		$email = $highlevelArr['email'];
		//$highlevelArr['customField'] = ['KgQRFA6oVqSnFkbSKrf7' => $course_name];
		//~ echo '<pre>';
	//~ print_r($highlevelArr);
	
	//~ echo '</pre>';
		
       run_api_data($email, $highlevelArr, $highlevelArr['firstName'], $highlevelArr['lastName']);			
//die();
	}
	add_action( 'learndash_course_completed', 'learndash_corse_complete' );
function run_api_form_data()
{
	add_action( 'wpcf7_before_send_mail', 'contactform7_wpcf7_mail_sent_function' ); 
	add_action( 'gform_pre_submission', 'gravityforms_submission_handler' );
	add_action('fluentform_before_insert_submission', 'your_custom_before_submission_function', 10, 3);

}
run_pipe_line();
//run_api_data();
run_api_form_data();
