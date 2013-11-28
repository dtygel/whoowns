<?php
/*
Plugin Name: Who owns Brazil?
Plugin URI: http://github.com/dtygel/whoownsWPPlugin
Description: Plugin of 'Proprietarios do Brasil' for Wordpress
Author: DanielTygel
Version: 0.1
Author URI: http://cirandas.net/dtygel
*/

require_once dirname( __FILE__ ) . '/utils.php';
require_once dirname( __FILE__ ) . '/init.php';

if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/init_admin.php';
	require_once dirname( __FILE__ ) . '/options.php';
	require_once dirname( __FILE__ ) . '/admin.php';
}

function whoowns_activate () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	create_whoowns_owner_post_type();
	whoowns_set_defaults();
	create_whoowns_taxonomies();
	whoowns_populate_taxonomies();
}
register_activation_hook(__FILE__, 'whoowns_activate');

?>
