<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    PipeLine
 * @subpackage PipeLine/admin/partials
 */

?>
<?php

add_action('admin_init','pipeline_integration_update');

function pipeline_integration_update() { //save form

	if ( isset($_POST['formpipelinemapping']) ) {

		$ldpipe_settings = $pipe_settings_final = array();

		if ( isset($_POST['group'])) {  // get form submitted values

		   foreach($_POST['group'] as $key =>$val) {   // donot save empty values
			   if($val['form_field']=='') {
				   unset($_POST['group'][$key]);
			   }
		   }
		   $ldpipe_settings['form_field'] = serialize($_POST['group']);
		}

		if( !empty($ldpipe_settings) ) {
			$pipe_settings_final = $ldpipe_settings;
		}

		if ( $pipe_settings_final ) {
		   // update new settings
			update_option('ldpipe_wizard_config',$pipe_settings_final);
		} else {
			// empty settings, revert back to default
			delete_option('ldpipe_wizard_config');
		}
		$ldpipe_updated = true;

	}
}


function ldpipe_getSettings($ldpipesetting='') {

	global $ldpipe_plugin_settings;

	if ( isset($ldpipe_plugin_settings) ) {
		if ( $ldpipesetting ) {
			return isset($ldpipe_plugin_settings[$ldpipesetting]) ? $ldpipe_plugin_settings[$ldpipesetting] : null;
		}
		return $ldpipe_plugin_settings;
	}

	$ldpipe_plugin_settings = wp_parse_args(get_option('ldpipe_wizard_config'), array(
		'form_field'=>null,
	));

	if ( $ldpipesetting ) {
		return isset($ldpipe_plugin_settings[$ldpipesetting]) ? $ldpipe_plugin_settings[$ldpipesetting] : null;
	}
	return $ldpipe_plugin_settings;

}


function pipe_line_render_plugin_settings_page() {

    $options = get_option(PIPE_LINE_OPTION_NAME);
    $enabled_text_widget = 0;
    $api_key = "";
    $text_widget_error = "0";
    $error_details = '';
    $warning_msg = "";

    if (isset($options[pipe_line_constants\pl_options_enable_text_widget])) {
        $enabled_text_widget = esc_attr($options[pipe_line_constants\pl_options_enable_text_widget]);
    }

    if (isset($options[pipe_line_constants\pl_options_api_key])) {
        $api_key = esc_attr($options[pipe_line_constants\pl_options_api_key]);
    }

    if (isset($options[pipe_line_constants\pl_options_text_widget_error])) {
        $text_widget_error = esc_attr($options[pipe_line_constants\pl_options_text_widget_error]);
    }

    if (isset($options[pipe_line_constants\pl_options_text_widget_error_details])) {
        $error_details = esc_attr($options[pipe_line_constants\pl_options_text_widget_error_details]);
    }
    if (isset($options[pipe_line_constants\pl_options_text_widget_warning_text])) {
        $warning_msg = esc_attr($options[pipe_line_constants\pl_options_text_widget_warning_text]);
    }

    $ui_setting = json_encode(array(
        'enable_text_widget' => $enabled_text_widget,
        'api_key' => base64_encode($api_key),
        'text_widget_error' => $text_widget_error,
        'warning_msg' => $warning_msg,
    ));

    ?>
    <?php if ( $ldpipe_updated ) : ?>
		<div class="updated notice is-dismissible">
			<p><?php _e('Settings updated successfully!', 'ldpipe'); ?></p>
		</div>
	<?php endif; ?>

    <form action="options.php" method="post">
		<?php
			settings_fields(PIPE_LINE_OPTION_NAME);
			do_settings_sections('pipe_line_plugin');
		?>
        <div id="pipe-line-settings-holder" data-settings="<?php esc_attr_e($ui_setting);?>"></div>
    </form>
    <div class="pipe-line-logo"><img src="<?php echo plugins_url( 'pipeline-so-logo-2022.png', __FILE__ );?>" class="pipe-line-logo-img"/>
    <h1 class="pipe-line-heading">Settings</h1>
    </div>
    <div id="app" data-enable_text_widget="<?php esc_attr_e($enabled_text_widget);?>"></div>
    <?php
}

function pipe_line_render_plugin_map_forms_page() {

    $options = get_option(PIPE_LINE_OPTION_NAME);
    $enabled_text_widget = 0;
    $api_key = "";
    $text_widget_error = "0";
    $error_details = '';
    $warning_msg = "";

	//get the settings of fields
    $pipe_settings = ldpipe_getSettings();

	if( !empty($pipe_settings) ) {
		$pipe_field_settings = unserialize($pipe_settings['form_field']);
	}


	//~ echo '<pre/>';
    //~ print_r($pipe_settings);
    //~ print_r($pipe_field_settings);

    wp_enqueue_script('pipeline-repeater', plugins_url('pipeline/admin/js/jquery.repeater.js'), array('jquery'), '1.3', false);
    wp_enqueue_style('pipeline-repeater-css', plugins_url('pipeline/admin/css/repeater.css'), array(), '0.1.0', 'all');

    ?>

    <script type="text/javascript">
		  jQuery(function() {
		  jQuery('#repeater').repeater({
			items: [{
				elements: [{
					id: 'form_field',
					//value: 'first_name'
				  },
				  {
					id: 'pipeline_field',
					//value: 'firstName'
				  }
				]
			  },
			]
		  });
		});
	</script>

    <form action="" method="post" class="pipeline-form">
		<?php //settings_fields(PIPE_LINE_OPTION_NAME); ?>
		<div id="pipe-line-settings-holder" data-settings="<?php esc_attr_e($ui_setting);?>"></div>
		<div class="pipe-line-logo"><img src="<?php echo plugins_url( 'pipeline-so-logo-2022.png', __FILE__ );?>" class="pipe-line-logo-img"/>
		<h1 class="pipe-line-heading"><?php echo _e('Integrations', 'pipeline'); ?></h1>
		</div>
		<div id="app" data-enable_text_widget="<?php esc_attr_e($enabled_text_widget);?>"></div>

		<?php
			$settings = get_option('pipe_line_plugin_options'); //get value from options
			if(!empty($settings)) {
				$pipeline_api_key  = $settings['api_key'];
			}

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
		?>

		<div id="repeater">

			  <input type="button" id="createElement" class="btn btn-danger" value="+ Add Field" />
				<p> <?php _e('Please map the Form fields (Contact Form 7, Fluent Form) here with Custom fields of Pipeline fields. Add the Names of fields to match them and send to API', 'pipeline'); ?></p>

			  <table>
				 <tr>
					<th><?php _e('Form Fields','pipeline'); ?></th>
					<th><?php _e('Pipeline.so Custom Fields','pipeline'); ?></th>
				 </tr>
			   </table>




			  <div id="structure" style="display:none;">
					<input type="text" name="form_field" id="form_field" value="" />


				<?php
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
				?>
				<?php if(!empty($fieldsArr)) { ?>
					<select name="pipeline_field" id="pipeline_field">
						<?php foreach($fieldsArr as $fieldk => $fieldv) { ?>
							<option value="<?php echo $fieldk; ?>"><?php echo $fieldk; ?></option>
						<?php } ?>
					</select>
				<?php } else { ?>
					<input type="text" name="pipeline_field" id="pipeline_field" value="" />
				<?php } ?>

			  </div>
			  <div id="containerElement">

			  <?php $j=0;
			  if(!empty($pipe_field_settings)) {
			  $db_count = count($pipe_field_settings); ?>
			  <input type="hidden" name="field_count" id="field_count" value="<?php echo $db_count; ?>" />
			  <?php  foreach($pipe_field_settings as $field) { ?>
			  <div id="structure" class="db_fields">
					<input type="text" name="group[<?php echo $j; ?>][form_field]" id="form_field" value="<?php echo $field['form_field']; ?>" />

				<?php
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
				?>
				<?php if(!empty($fieldsArr)) { ?>
					<select name="group[<?php echo $j; ?>][pipeline_field]" id="pipeline_field">
						<?php
						foreach($fieldsArr as $fieldk => $fieldv) {
						   $isSelected = '';
						   if($field['pipeline_field'] == $fieldk){
							 $isSelected = "selected=selected";
						   }
						?>
							<option value="<?php echo $fieldk; ?>" <?php echo $isSelected; ?> ><?php echo $fieldk; ?></option>
						<?php } ?>
					</select>
				<?php } else { ?>
					<input type="text" name="group[<?php echo $j; ?>][pipeline_field]" id="pipeline_field" value="<?php echo $field['pipeline_field']; ?>" />
				<?php } ?>
				<input type="button" class="removeElement custom" value="remove">
			  </div>

			<?php $j++; }} ?>
	    </div>
		</div>
		<input class="btn btn-primary" type="submit" name="formpipelinemapping" value="<?php echo __('Save Changes','cep'); ?>" />

    </form>

    <?php
}


function pipe_line_render_plugin_map_forms_help_page() {
	
 ?>

	<div class="pipeline-form">
				<div id="pipe-line-settings-holder" data-settings=""></div>
		<div class="pipe-line-logo"><img src="<?php echo plugins_url( 'pipeline-so-logo-2022.png', __FILE__ );?>" class="pipe-line-logo-img"/>
		<h1 class="pipe-line-heading">Help</h1>
		</div>
		<div id="app">  
		
			<div id="pipe-line-settings" class="pipe-line-settings">
				
				<div class="api-key-input-contaier">
					
					<p class="text-left">This guide shows what attributes are preset for standard form fields used (Fluent Forms, Contact Form 7).<br/><strong><i>If one preset map name has been used in a form any additional form fields will require a custom mapped field.</i></strong></p>
					
					<div id="fieldset-api-input" role="group" class="form-group field-first">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">First Name</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{name},{first},{first_name},{fullname},{firstname},{your-name}</small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Last Name</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{last},{last_name},{lastname}</small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Email</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{email},{email-address},{mail},{email_address},{your-email},{user_email}</small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Phone</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{phone},{phone-number},{tel},{phone_number},{your-phone},{cell},{cell-phone},{cell-number},{mobile-phone},{other-phone},{home-phone},{office-phone},{phone-other},{phone-home},{phone-office}</small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Address</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{my-address},{address-loc},{work-address},{home_address},{my_address},{home-address},{work-address},{location},{my-location}<br/> <i>You should not add "address" name alone. There should be some prefix or suffix with it as shown in example, only then it will reflect</i></small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Tags</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{hidden-tag1},{hidden-tag2} etc. <br/> <i>"hidden" text needs to be added with name attribute, only then it will reflect.</i></small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-inner">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Lead Source</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">{lead-source},{lead-one} etc. <br/> <i>You need to map the Lead Source field with Custom field in Map forms menu, only then it will reflect</i></small>
					</div>
					<div id="fieldset-api-input" role="group" class="form-group field-last">
						<label id="fieldset-api-input__BV_label_" for="api-key-input" class="d-block text-left">Below is the example showing Name Attribue of forms:</label>
						<small tabindex="-1" id="fieldset-api-input__BV_description_" class="form-text text-muted">						
						<img width="800px" height="400px" src="<?php echo plugins_url( 'name-attr.png', __FILE__ );?>" class="pipe-line-name-attr"/>
						</small>
					</div>
						
				<div>
			<div>
		
		</div>
    </div>
    
<?php
}



function pipe_line_section_text() {
    // echo '<p>' . __('Here you can set all the options for using the Chat Widget', 'PipeLine') . '</p>';
}

function pipe_line_section_text1() {
    echo '';
}
function pipe_line_render_plugin_integrations_page() {

	$data = array();
	$data = unserialize(get_option('pipe_line_render_options'));
	?>
	<form action="" method="post">
		<div class="form_header_sec">
			<h2>Integrations</h2>
		<label class="heading">Here is the list of all Integration modules. You can enable or disable the modules based on your need.</label>
		
		</div>
		
		<!----- Including PHP Script ----->
	<div class="pipeline_integration_form">
<div class="add_on_card addon_enabled_no">
	<div class="addon_header">Mapform</div> 
	     <div class="addon_body"><p><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/pipeline-logo-2022.png'; ?>"></p>
               <p>Mapform allows the Form fields (Contact Form 7, Fluent Form) here with Custom fields of Pipeline fields. Add the Names of fields to match them and send to API </p>
         </div> 
         <div class="addon_footer">
			<div role="switch" class="el-switch">
			<label for="mapform"><input type="checkbox" name="check_list[]" value="mapforms" id="mapform" <?php if( !empty($data) && in_array('mapforms',$data) ){ echo 'checked'; } ?> ><span class="slider round"></span></label>
			<span class="el-switch__core" style="width: 40px;"></span><!---->
			</div> 

		</div>
</div>
<div class="add_on_card addon_enabled_no">
	<div class="addon_header">Learndash</div> 
	     <div class="addon_body"><p><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/learndash.png'; ?>"></p>
                <p>Fluent Forms Mailchimp module allows you to create Mailchimp newsletter signup forms in WordPress</p>
         </div> 
         <div class="addon_footer">
			<div role="switch" class="el-switch">
			<label for="learndash" ><input type="checkbox" name="check_list[]" value="learndash" id="learndash" <?php if( !empty($data) && in_array('learndash',$data) ){ echo 'checked'; } ?> ><span class="slider round"></span></label>
			<span class="el-switch__core" style="width: 40px;"></span><!---->
			</div> 

		</div>
</div>
</div>
<input type="submit" name="pipe_line_render_options_submit" Value="Submit"/>
</form>
					<?php
	}
add_action('init','my_custom_funct',1);

function my_custom_funct(){
	if(isset($_POST['pipe_line_render_options_submit']) && $_POST['pipe_line_render_options_submit'] == 'Submit'){
		if(isset($_POST['check_list'])){
			update_option( 'pipe_line_render_options',serialize( $_POST['check_list']));
		}else{
				update_option( 'pipe_line_render_options',serialize(array()));
		}
	}
}
