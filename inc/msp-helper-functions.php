<?php 

defined( 'ABSPATH' ) || exit;

function msp_get_product_image_src( $img_id, $size = 'medium' ){
    $src = wp_get_attachment_image_src( $img_id, $size );
    return $src[0];
}

function msp_get_product_image_srcset( $img_id ){
	$sizes = array( 'woocommerce_thumbnail', 'woocommerce_single' );

	$srcset = array(
		'thumbnail' => msp_get_product_image_src( $img_id, 'woocommerce_thumbnail' ),
		'full' => msp_get_product_image_src( $img_id, 'woocommerce_single' ),
	);

    return $srcset;
}

function msp_get_product_image_src_by_product_id( $product_id ){
    $product = wc_get_product( $product_id );
    $product_image_id = ( ! empty( $product ) ) ? $product->get_image_id() : 0;
    
    return ( ! empty( $product_image_id ) ) ? msp_get_product_image_src( $product_image_id ) : null;
}


function deslugify( $str ){
    return ucwords( str_replace( array('_', '-'), ' ', $str ) );
}

function msp_get_user_product_review( $p_id, $format = ARRAY_A ){
	$comments = get_comments(array(
		'post_id' 						=> $p_id,
		'user_id' 						=> get_current_user_id(),
		'include_unapproved'  => false,
	));
	$comment = get_comment( $comments[0]->comment_ID , $format );
	return $comment;
}

function msp_customer_feedback( $format = ARRAY_A ){
    $comments = get_comments(array(
		'post_id' 						=> 0,
		'user_id' 						=> get_current_user_id(),
		'type' 					=> 'store_review',
		'include_unapproved'  => false,
	));
	return( $comments[0] );
}

function msp_get_product_resources( $id ){
	return User_history::unpackage( get_post_meta( $id, '_msp_resources', true ) );
}

function msp_get_product_videos( $id ){
	return User_history::unpackage( get_post_meta( $id, '_msp_product_videos', true ) );
}

function make_modal_btn( $args = array() ){
	$a_text = '<a data-toggle="modal" href="#msp_modal" data-title="%s" data-model="%s" data-action="%s" data-id="%d" class="%s">%s</a>';
	$button_text = '<button data-toggle="modal" data-target="#msp_modal" data-title="%s" data-model="%s" data-action="%s" data-id="%d" class="%s">%s</button>';
	$defaults = array(
		'type'	 => 'a',
		'class'	 => '',
		'text'   => 'text',
		'title'  => 'title',
		'model'  => '',
		'action' => '',
		'id'		 => '',
	);
	$args = wp_parse_args( $args, $defaults );

	$base_html = ( $args['type'] === 'a' ) ? $a_text : $button_text;
	
	echo sprintf( $base_html, $args['title'], $args['model'], $args['action'], $args['id'], $args['class'], $args['text'] );
}