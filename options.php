<?php

//Create settings for the plugin
function whoowns_register_settings() {
	//register_setting( 'whoowns', 'whoowns_default_shareholders_number' );
	register_setting( 'whoowns', 'whoowns_supported_file_types' );
	register_setting( 'whoowns', 'whoowns_standard_decimal_symbol' );
	register_setting( 'whoowns', 'whoowns_standard_thousand_symbol' );
	//register_setting( 'whoowns', 'whoowns_capabilities' );
	//register_setting( 'whoowns', 'whoowns_relative_share_for_dummy_shareholders' );
	register_setting( 'whoowns', 'whoowns_owners_per_page' );
	register_setting( 'whoowns', 'whoowns_owner_image_size' );
}

function whoowns_set_defaults() {
	add_option('whoowns_default_shareholders_number',15);
	add_option('whoowns_supported_file_types',array('application/pdf', 'application/postscript', 'text/plain', 'image/bmp', 'application/msword', 'image/gif', 'text/html', 'image/jpeg', 'application/vnd.ms-powerpoint', 'text/richtext', 'image/tiff', 'application/zip', 'application/x-abiword', 'text/csv', 'message/rfc822', 'application/x-gnumeric', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.presentation', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.text', 'image/vnd.adobe.photoshop', 'image/png', 'text/richtext', 'application/rtf', 'image/svg+xml'));
	add_option('whoowns_capabilities',array (
		'contributor'=>array(
			'read_whoowns_owners',
			'edit_whoowns_owners',
			'delete_whoowns_owners',
		),
		'admin'=>array(
			'read_private_whoowns_owners',
			'edit_private_whoowns_owners',
			'edit_published_whoowns_owners',
			'edit_others_whoowns_owners',
			'publish_whoowns_owners',
			'delete_private_whoowns_owners',
			'delete_published_whoowns_owners',
			'delete_others_whoowns_owners'
		)
	));
	add_option('whoowns_standard_decimal_symbol',',');
	add_option('whoowns_standard_thousand_symbol','.');
	add_option('whoowns_relative_share_for_dummy_shareholders',5);
	add_option('whoowns_owners_per_page',30);
	add_option('whoowns_owner_image_size','300x300');
}

// Version of the table
global $whoowns_table_db_version;
$whoowns_table_db_version = '0.1';

function whoowns_settings_page() {
	?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?=__('Settings of the Who Owns Plugin','whoowns')?></h2>
	<form method="post" action="options.php">
	<?
	settings_fields( 'whoowns' );
	do_settings_sections( 'myoption-group' );
	?>
	<table class="form-table">
        <tr valign="top">
        <th scope="row"><?=__('Maximum number of shareholders to display in the edit form','whoowns')?></th>
        <td><input type="text" name='whoowns_default_shareholders_number' value="<?=get_option('whoowns_default_shareholders_number')?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?=__('Number of owners to display per page in the ranking table','whoowns')?></th>
        <td><input type="text" name='whoowns_owners_per_page' value="<?=get_option('whoowns_owners_per_page')?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row"><?=__('Standard symbol for decimals','whoowns')?></th>
        <td><select name="whoowns_standard_decimal_symbol">
        	<option value="."<? if (get_option('whoowns_standard_decimal_symbol')=='.') echo " selected='selected'"; ?>">.</option>
        	<option value=","<? if (get_option('whoowns_standard_decimal_symbol')==',') echo " selected='selected'"; ?>">,</option>
        	</select>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?=__('Standard symbol for thousands','whoowns')?></th>
        <td><select name="whoowns_standard_thousand_symbol">
        	<option value="."<? if (get_option('whoowns_standard_thousand_symbol')=='.') echo " selected='selected'"; ?>">.</option>
        	<option value=","<? if (get_option('whoowns_standard_thousand_symbol')==',') echo " selected='selected'"; ?>">,</option>
        	</select>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?=__('Default size of the owner image. Put width and height separated by an "x". For example: 300x300','whoowns')?></th>
        <td><input type="text" name='whoowns_owner_image_size' value="<?=get_option('whoowns_owner_image_size')?>" /></td>
        </tr>
    </table>
    <?php submit_button(); ?>
	</form>
	</div>
	<?
}
