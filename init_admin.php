<?php

function whoowns_create_menu() {
	add_menu_page(__('Whoowns Brazil Plugin Settings','whoowns'), __('Owners','whoowns'), 'administrator', __FILE__, 'whoowns_settings_page');
	add_action( 'admin_init', 'whoowns_register_settings' );
}
add_action('admin_menu', 'whoowns_create_menu');

require_once dirname( __FILE__ ) . '/db_table.php';
$installed_ver = get_option('whoowns_table_db_version');
if (!$installed_ver || $installed_ver != $whoowns_table_db_version) {
	whoowns_table_update($installed_ver);
}

function whoowns_deactivate () {
	if ( ! current_user_can( 'activate_plugins' ) )
        return;
	// Up to now, I don't want anything...
	
}
register_deactivation_hook(__FILE__, 'whoowns_deactivate');

function whoowns_uninstall () {
	if ( ! current_user_can( 'activate_plugins' ) )
        return;
    delete_option('whoowns_table_db_version');
    whoowns_table_uninstall();
}
register_uninstall_hook(__FILE__, 'whoowns_uninstall');

function whoowns_populate_taxonomies() {
	wp_insert_term('Private enterprise','whoowns_owner_types');
	wp_insert_term('Person','whoowns_owner_types');
	wp_insert_term('State','whoowns_owner_types');
	/* __('Person','whoowns');
	 __('State','whoowns');*/
}

// Add custom taxonomies and custom post types counts to dashboard
function my_add_counts_to_dashboard() {
    // Custom taxonomies counts
    /*$taxonomies = get_taxonomies( array( '_builtin' => false ), 'objects' );
    foreach ( $taxonomies as $taxonomy ) {
        $num_terms  = wp_count_terms( $taxonomy->name );
        $num = number_format_i18n( $num_terms );
        $text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name, $num_terms );
        $associated_post_type = $taxonomy->object_type;
        if ( current_user_can( 'manage_categories' ) ) {
            $num = '<a href="edit-tags.php?taxonomy=' . $taxonomy->name . '&post_type=' . $associated_post_type[0] . '">' . $num . '</a>';
            $text = '<a href="edit-tags.php?taxonomy=' . $taxonomy->name . '&post_type=' . $associated_post_type[0] . '">' . $text . '</a>';
        }
        echo '<td class="first b b-' . $taxonomy->name . 's">' . $num . '</td>';
        echo '<td class="t ' . $taxonomy->name . 's">' . $text . '</td>';
        echo '</tr><tr>';
    }*/

    // Custom post types counts
    $post_types = get_post_types( array( '_builtin' => false ), 'objects' );
    foreach ( $post_types as $post_type ) {
        $num_posts = wp_count_posts( $post_type->name );
        $num = number_format_i18n( $num_posts->publish );
        $text = _n( $post_type->labels->singular_name, $post_type->labels->name, $num_posts->publish );
        if ( current_user_can( 'edit_posts' ) ) {
            $num = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . '</a>';
            $text = '<a href="edit.php?post_type=' . $post_type->name . '">' . $text . '</a>';
        }
        echo '<td class="first b b-' . $post_type->name . 's">' . $num . '</td>';
        echo '<td class="t ' . $post_type->name . 's">' . $text . '</td>';
        echo '</tr>';

        if ( $num_posts->pending > 0 ) {
            $num = number_format_i18n( $num_posts->pending );
            $text = _n( $post_type->labels->singular_name . ' pending', $post_type->labels->name . ' pending', $num_posts->pending );
            if ( current_user_can( 'edit_posts' ) ) {
                $num = '<a href="edit.php?post_status=pending&post_type=' . $post_type->name . '">' . $num . '</a>';
                $text = '<a class="waiting" href="edit.php?post_status=pending&post_type=' . $post_type->name . '">' . $text . '</a>';
            }
            echo '<td class="first b b-' . $post_type->name . 's">' . $num . '</td>';
            echo '<td class="t ' . $post_type->name . 's">' . $text . '</td>';
            echo '</tr>';
        }
    }
}
add_action( 'right_now_content_table_end', 'my_add_counts_to_dashboard' );

//Allow file upload
function update_edit_form() {  
    echo ' enctype="multipart/form-data"';  
}
add_action('post_edit_form_tag', 'update_edit_form');

//Add plugin javascript for admin
function add_whoowns_script($hook) {
	if( !in_array($hook, array('post.php', 'post-new.php')) )
        return;
	
	wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_script( 'whoowns-admin-script', plugins_url('/utils_admin.js', __FILE__ ), array('jquery') );
    wp_localize_script( 'whoowns-admin-script', 'ajax_object', 
    	array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 
    		'delete_confirmation' => __('Are you sure you want to delete the file "{file}"?','whoowns')
    	)
    );
}
add_action('admin_enqueue_scripts', 'add_whoowns_script');  

?>
