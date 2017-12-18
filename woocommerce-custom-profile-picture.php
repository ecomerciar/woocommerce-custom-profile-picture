<?php

/*
Plugin Name: Woocommerce Custom Profile Picture
Plugin URI: http://ecomerciar.com/woocommerce-profile-picture
Description: Allows any user to upload their own profile picture to the WooCommerce store
Version: 1.0
Author: Ecomerciar
Author URI: http://ecomerciar.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// =========================================================================
/**
 * Function wc_cus_cpp_form
 *
 */
add_action( 'woocommerce_before_edit_account_form', 'wc_cus_cpp_form' );
function wc_cus_cpp_form( $atts, $content= NULL) {
	echo '<div style="margin-bottom: 15px;">';
	echo '<h4>';
	echo 'Change your profile picture (Max. 5 Mb)';
	echo '</h4><br>';
	echo '<form enctype="multipart/form-data" action="" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="500000" />
    <input name="profile_pic" type="file" size="25" /><br><br>
    <input type="submit" value="Upload" />
	</form>';
	echo '</div>';
	if($_FILES['profile_pic']){
		$picture_id = wc_cus_upload_picture($_FILES['profile_pic']);
		$user_id = get_current_user_id();
		wc_cus_save_profile_pic($picture_id, $user_id);
	}
}

// =========================================================================
/**
 * Function wc_cus_save_profile_pic
 *
 */
function wc_cus_save_profile_pic($picture_id, $user_id){
	update_user_meta( $user_id, 'profile_pic', $picture_id );
}


// =========================================================================
/**
 * Function wc_cus_upload_picture
 *
 */
function wc_cus_upload_picture( $foto ) {

	$wordpress_upload_dir = wp_upload_dir();
	// $wordpress_upload_dir['path'] is the full server path to wp-content/uploads/2017/05, for multisite works good as well
	// $wordpress_upload_dir['url'] the absolute URL to the same folder, actually we do not need it, just to show the link to file
	$i = 1; // number of tries when the file with the same name is already exists

	$profilepicture = $foto;
	$new_file_path = $wordpress_upload_dir['path'] . '/' . $profilepicture['name'];
	$new_file_mime = mime_content_type( $profilepicture['tmp_name'] );
	
	$log = new WC_Logger();		
	
	if( empty( $profilepicture ) )
	$log->add('custom_profile_picture','File is not selected.');	

	if( $profilepicture['error'] )
	$log->add('custom_profile_picture',$profilepicture['error']);	
	

	if( $profilepicture['size'] > wp_max_upload_size() )
	$log->add('custom_profile_picture','It is too large than expected.');	
	

	if( !in_array( $new_file_mime, get_allowed_mime_types() ))
	$log->add('custom_profile_picture','WordPress doesn\'t allow this type of uploads.' );		

	while( file_exists( $new_file_path ) ) {
	$i++;
	$new_file_path = $wordpress_upload_dir['path'] . '/' . $i . '_' . $profilepicture['name'];
	}

	// looks like everything is OK
	if( move_uploaded_file( $profilepicture['tmp_name'], $new_file_path ) ) {
	

	$upload_id = wp_insert_attachment( array(
		'guid'           => $new_file_path, 
		'post_mime_type' => $new_file_mime,
		'post_title'     => preg_replace( '/\.[^.]+$/', '', $profilepicture['name'] ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	), $new_file_path );

	// wp_generate_attachment_metadata() won't work if you do not include this file
	require_once( admin_url() . 'includes/image.php' );

	// Generate and save the attachment metas into the database
	wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $new_file_path ) );
	return $upload_id;
	}
}


// =========================================================================
/**
 * Function wc_cus_change_avatar
 *
 */
add_filter( 'get_avatar' , 'wc_cus_change_avatar' , 1 , 5 );
function wc_cus_change_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;
    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );	
    }

    if ( $user && is_object( $user ) ) {
		$picture_id = get_user_meta($user->data->ID,'profile_pic');
		if(! empty($picture_id)){
			$avatar = wp_get_attachment_url( $picture_id[0] );
			$avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		}
    }
    return $avatar;
}
