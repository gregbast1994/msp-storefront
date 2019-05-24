<?php 

defined( 'ABSPATH' ) || exit;

function msp_chevron_karma_form( $comment ){
    /**
     * Outputs the html for the karma buttons on each comment
     */
    if( ! $comment->comment_approved ) return;
    $vote = msp_get_user_karma_vote( get_current_user_id(), $comment->comment_ID );
    $vote = ( ! empty( $vote->karma_value ) ) ? $vote->karma_value : 0;
  ?>
    <div class="d-flex msp-karma flex-column text-center mr-3">
        <i class="fas fa-chevron-circle-up text-secondary fa-2x mb-1 karma karma-up-vote <?php if( $vote == 1 ) echo 'voted'; ?>"></i>
        <span class="mb-1 karma-score"><?php echo $comment->comment_karma ?></span>
        <i class="fas fa-chevron-circle-down text-secondary fa-2x karma karma-down-vote <?php if( $vote == -1 ) echo 'voted'; ?>" ></i>
    </div>
  <?php  
}

function msp_comment_actions_wrapper_open(){
    /**
     * Opens up a wrapper to place comment actions
     */
    echo '<div class="comment-actions">';
}

function msp_reply_to_comment_btn( $comment ){
    /**
     * Simple HTML button - TODO: Expand
     */
    ?>
    <button class="btn btn-outline-secondary comment-on-comment">
        Comment
        <i class="far fa-comment-alt pl-2"></i>
    </button>
    <?php
}

function msp_flag_comment_btn( $comment ){
    /**
     * Simple HTML button - TODO: Expand
     */
    ?>
    <button class="btn btn-outline-danger flag-comment">
        Report Abuse
        <i class="fab fa-font-awesome-flag"></i>
    </button>
    <?php
}

function msp_comment_actions_wrapper_close(){
    /**
     * Close comment actions
     */
    echo '</div><!-- .comment-actions -->';
}


function msp_get_create_a_review_btn(){
    /**
     * Outputs the link html to create a product review.
     */
    global $post;
    $url = msp_get_review_link( $post->ID );
    echo '<p class=""><a href="'. $url .'" role="button" class="btn btn-success btn-lg">Write a customer review</a></p>';
}

function msp_get_rating_histogram( $ratings, $count, $echo = true ){
    /**
     * Outputs a simple histrogram breakdown of a products ratings.
     */
    ob_start();
    ?>
        <table class="product-rating-histogram">
            <?php 
                for( $i = 5; $i > 0; $i-- ) :
                    $now = ( isset( $ratings[$i] ) ) ? intval( ( $ratings[$i] / $count ) * 100 ) : 0; ?>
                    <tr>
                        <td nowrap>
                            <a href=""><?php echo $i ?> stars</a>
                        </td>
                        <td style="width: 80%">
                            <a class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $now; ?>%"aria-valuenow="<?php echo $now ?>%" aria-valuemin="0" aria-valuemax="100"></div>
                            </a>
                        </td nowrap>
                        <td>
                            <a href=""><?php echo $now ?>%</a>
                        </td>
                    </tr>
                <?php endfor; ?>
            </table>
    <?php
    $html = ob_get_clean();

    if( ! $echo ){
        return $html;
    }

    echo $html;
}

function msp_single_product_create_review(){
    /**
     * Creates the parent div
     */
    ?>
    <h3>Review this product</h3>
    <p>Share your thoughts with other customers.</p>
    <?php
        msp_get_create_a_review_btn();
}

add_shortcode( 'review' , 'msp_get_review_template' );
function msp_get_review_template(){
    /**
     * Outputs the html generated by the msp-review.php file.
     */
    wc_get_template( '/template/msp-review.php' );
}

function msp_review_more_products(){
    /**
     * Displays a number of other products a user has purchased and not reviewed.
     */
    if( ! isset( $_GET['product_id'] ) ) return;
    $product_ids = explode( ',', $_GET['product_id'] );

    if( sizeof( $product_ids ) <= 1 ) return;

    foreach( $product_ids as $id ){
        $product = wc_get_product( $id );
        if( ! empty( $product ) ){
            ?>
            <div class="col-4">
                <a href="<?php echo $product->get_permalink() ?>" class="pt-5 mt-3 text-center link-normal">
                    <img src="<?php echo msp_get_product_image_src( $product->get_image_id() ) ?>" class="mx-auto" />
                    <p class="shorten link-normal text-dark"><?php echo $product->get_name() ?></p>
                    <?php msp_get_review_more_star_links( $product->get_id() ) ?>
                </a>
            </div>
            <?php
        }
    }
}

function msp_get_review_more_star_links( $product_id, $echo = true ){
    /**
     * Checks if a user has already reviewed, if so auto fill previous rating.
     * @param int - The ID of the product.
     * @return string - HTML
     */
    $comment = msp_get_user_product_review( $product_id );
    $highlight = 'far';

    if( ! empty( $comment ) ){
        $rating = get_comment_meta( $comment['comment_ID'], 'rating', true );
    }

    ob_start();

    echo '<div class="d-flex justify-content-center">';
    for( $i = 1; $i <= 5; $i++ ) :
        if( isset( $rating ) ){
            $highlight = ( $i <= $rating ) ? 'fas' : 'far';
        }
    ?>
        <a href="<?php echo msp_get_review_link( $product_id, array('star' => $i) ) ?>" class="link-normal">
            <i class="<?php echo $highlight; ?> fa-star fa-2x"></i>
        </a>
    <?php endfor;
    echo '</div>';

    $html = ob_get_clean();

    if( ! $echo ) return $html;
    echo $html;
}

function msp_get_review_link( $product_id, $args = array() ){
    /**
     * Formats a url to play nice with custom product review functions.
     * @param int $product_id
     */
    $comment = msp_get_user_product_review( $product_id );

    $base_url = '/review/?product_id=';
    $base_url .= is_array($product_id) ? implode( ',', $product_id ) : $product_id;

    $defaults = array(
        'action' => ( empty( $comment ) ) ? 'create' : 'edit',
        'comment_id' => '',
        'star' => ( empty( $comment ) ) ? '' : get_comment_meta( $comment['comment_ID'], 'rating', true ), 
    );

    $args = wp_parse_args( $args, $defaults );

    foreach( $args as $key => $arg ){
        if( ! empty( $arg ) ) $base_url .= "&$key=$arg"; 
    }

    return $base_url;
}

function msp_create_review_wrapper_open(){
    /**
     * Opens up a form
     */
    echo '<div class="col-12">';
    echo '<form method="POST" action="'. admin_url( 'admin-post.php' ) .'" enctype="multipart/form-data">';
}

function msp_create_review_top( $product_id ){
    /**
     * HTML for the top of the review form.
     */
    $src = msp_get_product_image_src_by_product_id( $product_id );
    ?>
    <div class="d-flex align-items-center mt-2 mb-4 pb-4 border-bottom">
        <img src="<?php echo $src; ?>" class="img-mini pr-3">
        <p class="m-0 p-0"><?php echo get_the_title( $product_id ); ?></p>
    </div>
    <?php
}

function msp_get_review_more_star_buttons(){
    /**
     * Outputs the html for the review_more section.
     */
    $class = 'far';

    echo '<div class="d-flex msp-star-wrapper pb-2">';

    for( $i = 1; $i <= 5; $i++ ) :
        if( isset( $_GET['star'] ) ){
            $class = ( $i <= $_GET['star'] ) ? 'fas' : 'far';
        }
    ?>

        <a class="link-normal" href="javascript:void(0)">
            <i class="<?php echo $class; ?> fa-star fa-2x msp-star-rating rating-<?php echo $i ?>" data-rating="<?php echo $i; ?>"></i>
        </a>

    <?php endfor;

    echo '</div>';
    echo '<input type="hidden" id="rating" name="rating" value="" required />';
}


function msp_create_review_upload_form( $product_id ){
    /**
     * Outputs the html for the image upload portion of the review form.
     */
    if( ! is_user_logged_in() ) return;
    $comment = msp_get_user_product_review( $product_id, OBJECT );
    ?>

     <div class="pt-4">
        <h3>Add a photo or video</h3>
        <p>Shoppers find images much more helpful than text alone.</p>
        <?php 
            if( ! empty( $comment ) ){
                $attachment_ids = msp_get_user_attachment_uploaded_to_comment( $comment, $product_id );
                if( ! empty( $attachment_ids ) ){
                    echo '<div class="d-flex">';
                    foreach( $attachment_ids as $image_id ){
                        $image_src = msp_get_product_image_src( $image_id, 'woocommerce_thumbnail' );
                        ?>
                        <div class="product-review-upload">
                            <img src="<?php echo $image_src ?>" class="mr-2 img-mini" />
                            <i class="far fa-times-circle fa-2x remove-product-image-from-review" data-id="<?php echo $image_id; ?>"></i>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }
            }
        ?>
        <input type="file" name="file" class="pt-3" />
     </div>

    <?php
}

function msp_create_review_headline( $product_id ){
    $headline = '';
    if( $_GET['action'] == 'edit' ){
        $comment = msp_get_user_product_review( $product_id );
        $headline = get_comment_meta( $comment['comment_ID'], 'headline', true );
    }

    echo '<div class="pt-4">';
        echo '<h3>Add a headline</h3>';
        echo '<input required type="text" name="headline" placeholder="What\'s the most important thing to know?" class="form-control w-50" value="'. $headline .'" />';
    echo '</div>';
}

function msp_create_review_content( $product_id ){
    $content['comment_content'] = '';
    if( $_GET['action'] == 'edit' ){
        $content = msp_get_user_product_review( $product_id );
    }
    echo '<div class="pt-4">';
        echo '<h3>Write your review</h3>';
        echo '<textarea required name="content" class="form-control w-75" placeholder="What did you like or dislike? What did you use this product for?">'. $content['comment_content'] .'</textarea>';
    echo '</div>';
}

function msp_create_review_wrapper_close(){
                echo '<div class="pt-4">';
                    wp_nonce_field( 'create-review_' . $_GET['product_id'] );
                    echo '<input type="hidden" name="product_id" value="'. $_GET['product_id'] .'" />';
                    echo '<input type="hidden" name="action" value="msp_process_create_review" />';
                    echo '<button class="btn btn-success submit-review" />Submit</button>';
                echo '</div>';
            echo '</form>';
        echo '</div> <!-- .row -->';
}


function msp_process_create_review(){
    /**
     * Processes the review, either creates a new comment or edits an old one.
     */
    if( check_admin_referer( 'create-review_' . $_POST['product_id'] ) ){
        $data = $_POST;
        $user = wp_get_current_user();

        if( isset( $_FILES['file'] ) && ! empty( $_FILES['file'] ) ){
            $attachment_id = media_handle_upload( 'file', $_POST['product_id'], array( 'post_name' => 'user_upload_' . uniqid() ) );
        }

        
        $args = array(
            'comment_post_ID' => $data['product_id'],
            'comment_author'	=> $user->user_login,
            'comment_author_email'	=> $user->user_email,
            'comment_author_url'	=> $user->user_url,
            'comment_content' =>  $data['content'],
            'comment_type'			=> 'review',
            'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
            'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
            'comment_date' => current_time( 'mysql', $gmt = 0 ),
            'user_id' => get_current_user_id(),
            'comment_approved' => 0,
        );
        
        $comment = msp_get_user_product_review( $data['product_id'] );

        if( ! is_null( $comment ) ){
            // comment_id needs to be available for after this if statement.
            $comment_id = $comment['comment_ID'];
            $args['comment_ID'] = $comment['comment_ID'];
            wp_update_comment($args);
        } else {
            $comment_id = wp_insert_comment( $args );
        }

        update_post_meta( $attachment_id, '_msp_attached_to_comment', $comment_id );
        update_comment_meta( $comment_id, 'rating', $data['rating'] );
        update_comment_meta( $comment_id, 'headline', $data['headline'] );

        $verified = ( wc_customer_bought_product( $user->user_email, get_current_user_id(), $data['product_id'] ) ) ? 1 : 0;
        update_comment_meta( $comment_id, 'verified', $verified);

        // redirect to review more products!
        $review_more_ids = msp_get_customer_unique_order_items( get_current_user_id() );
        wp_redirect( msp_get_review_link( $review_more_ids, array('action' => 'show_more') ) );
    }
}

function msp_get_comment_headline( $comment ){
    /**
     * Outputs the headline of a product review
     */
    $headline = get_comment_meta( $comment->comment_ID, 'headline', true );
    if( ! empty( $headline ) ){
        echo '<h4 class="review-headline">'. $headline .'</h4>';
    }
}

function msp_add_to_karma_table(){
    /**
     * Updates a users karma vote on a comment.
     */
    if( ! isset( $_POST['comment_id'], $_POST['vote'] ) ) return;
    global $wpdb;
    $table_name = 'msp_karma';

    $last_vote = msp_get_user_karma_vote( get_current_user_id(), $_POST['comment_id'] );

    $args = array(
        'karma_user_id'    => get_current_user_id(),
        'karma_comment_id' => $_POST['comment_id'],
        'karma_value'      => $_POST['vote']
    );

    if( empty( $last_vote ) ){
        $wpdb->insert( $table_name, $args );
    } else {
        $wpdb->update( $table_name, $args, array( 'karma_id' => $last_vote->karma_id ) );
    }

    $karma_score = msp_update_comment_karma( $_POST['comment_id'] );
    wp_send_json( $karma_score );

    wp_die();
}

function msp_update_comment_karma( $comment_id ){
    /**
     * Updates the karma of a comment.
     */
    $comment = get_comment( $comment_id, ARRAY_A );
    if( empty( $comment ) ) return;

    global $wpdb;
    $score = 0;

    $results = $wpdb->get_results(
        "SELECT karma_value
         FROM msp_karma
         WHERE karma_comment_id = $comment_id"
    );

    foreach( $results as $vote ){
        $score += $vote->karma_value;
    }

    $comment['comment_karma'] = $score;
    wp_update_comment( $comment );
    
    return $score;
}

function msp_get_user_karma_vote( $user_id, $comment_id ){
    /**
     * Checks if user has already voted on a comment
     */
    global $wpdb;

    $row = $wpdb->get_row( 
        "SELECT * 
         FROM msp_karma
         WHERE karma_user_id = $user_id
         AND karma_comment_id = $comment_id" 
    );

    return $row;
}

function msp_get_user_uploaded_product_image_id( $product_id = '' ){
    /**
     * Gets all user uploaded images to a product.
     */
    global $wpdb;
    global $post;

    $product_id = ( ! empty( $product_id ) ) ? $product_id : $post->ID;
    
    $sql = "SELECT DISTINCT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_value
            FROM {$wpdb->posts}, {$wpdb->postmeta}
            WHERE {$wpdb->posts}.post_parent = {$product_id}
            AND {$wpdb->posts}.post_type = 'attachment'
            AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
            AND {$wpdb->postmeta}.meta_key = '_msp_attached_to_comment'";
    
    $results = $wpdb->get_results( $sql, ARRAY_A );

    $arr = array();
    foreach( $results as $id ){
        $comment = get_comment( $id['meta_value'] );
        if( $comment->comment_approved ) array_push( $arr, $id['ID'] );
    }

    return $arr;
}

function msp_get_user_attachment_uploaded_to_comment( $comment, $product_id = '' ){
    /**
     * Gets the ID of a image uploaded by a user to a product review 
     */
    global $wpdb;
    global $post;
    $product_id = ( empty( $product_id ) ) ? $post->ID : $product_id;
    $user_id = get_current_user_id();
    
    $sql = "SELECT DISTINCT ID
            FROM {$wpdb->posts}, {$wpdb->postmeta}
            WHERE {$wpdb->posts}.post_parent = {$product_id}
            AND {$wpdb->posts}.post_type = 'attachment'
            AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
            AND {$wpdb->postmeta}.meta_key = '_msp_attached_to_comment'
            AND {$wpdb->postmeta}.meta_value = {$comment->comment_ID}
            AND {$wpdb->posts}.post_author = {$user_id}";
    
    $results = $wpdb->get_results( $sql, ARRAY_A );

    $arr = array();
    foreach( $results as $id ){
        array_push( $arr, $id['ID'] );
    }

    return $arr;
}

function msp_review_get_user_upload_image( $comment ){
    /**
     * Outputs each of the images uploaded to a comment.
     */
    $ids = msp_get_user_attachment_uploaded_to_comment( $comment );
    foreach( $ids as $id ){
        $srcset = msp_get_product_image_srcset( $id );
        ?>
        <a href="<?php echo $srcset['full'] ?>">
            <img src="<?php echo $srcset['thumbnail'] ?>" class="mr-2 border img-mini" />
        </a>
        <?php
    }
}

function msp_display_user_uploaded_product_images( $ids ){
    /**
     * Outputs the html of all user uploaded images to a product.
     */
    $limit = ( sizeof( $ids ) < 4 ) ? sizeof( $ids ) : 4;

    echo '<h3>'. sizeof( $ids ) .' customer uploaded images</h3>';
    echo '<div id="user-uploads" class="d-flex pb-3 mb-3 border-bottom">';

    for( $i = 0; $i < $limit; $i++ ){
        $srcset = msp_get_product_image_srcset( $ids[$i] );
        echo '<a href="'. $srcset['full'] .'">';
            echo '<img src="'. $srcset['thumbnail'] .'" class="mx-2 img-small" />';
        echo '</a>';
    }
    echo '</div>';
}

function msp_delete_user_product_image(){
    /**
     * Ajax function to delete image uploaded by user.
     */
    global $wpdb;
    echo $wpdb->delete( $wpdb->posts, array( 'ID' => $_POST['id'] ) );
    wp_die();
}

function msp_submit_question_form(){
    $user = get_userdata( get_current_user_id() );
    ?>
        <div id="msp_submit_question" class="form-group mt-3">
            <input type="input" name="question" class="form-control" placeholder="Ask your question">
            <?php if( ! isset( $user->user_email ) ) : ?>
                <input type="email" name="email" class="form-control" placeholder="Where can we email you the answer?" />
            <?php else : ?>
                <input type="hidden" name="email" value="<?php echo $user->user_email ?>"/>
            <?php endif; ?>
            <input type="hidden" name="post_id" value="<?php echo get_the_ID() ?>">
            <button id="msp_submit_question_btn" class="btn btn-success btn-lg ml-auto mt-2" disabled>Submit question</button>
        </div> 
    <?php
}

function msp_get_submit_answer_form( $comment_id ){
    ?>  
        <div id="msp_submit_answer" class="d-flex">
            <input type="input" name="answer" class="form-control" placeholder="Do you have an answer to this question?" />
            <input type="hidden" name="comment_id" value="<?php echo $comment_id ?>" />
            <input type="hidden" name="user_id" value="<?php echo get_current_user_id() ?>" />
            <button class="btn btn-success btn-sm msp-submit-answer">answer</button>
        </div> 
    <?php
}

function msp_process_customer_submit_question(){
    parse_str( $_POST['formdata'], $form_data );
    if( isset( $form_data['question'], $form_data['email'] ) ){
        $args = array(
            'comment_post_ID'      => $form_data['post_id'],
            'comment_author_email' => $form_data['email'],
            'comment_content'      => wp_strip_all_tags($form_data['question']),
            'comment_type'      => 'product_question',
            'comment_approved' => 0
        );

        // $comment_id = wp_insert_comment( $args );
        // echo $comment_id;
        echo 126;
    }
    wp_die();
}



function product_question_wrapper_open(){
    echo '<div class="p-3 border">';
}


function msp_get_product_question( $question ){
    ?>  <div class="product_question_inner">
            <div class="question">
                <p>
                    <label class="pr-4">Question:</label>
                    <span><?php echo $question->comment_content ?></span>
                </p>
            </div>
    <?php
    
}


function product_question_wrapper_end(){
    echo '</div>';
}

function msp_get_product_question_answers( $question ){
    $answers = get_comments( array(
        'post_id' => get_the_ID(),
        'type' => 'product_answer',
        'post_parent' => $question->comment_ID,
    ) );
    ?>

        <div class="answer d-flex">
        <label class="pr-4">answer:</label>
            <p>
                <?php 
                    if( ! empty( $answers ) ) {
                        foreach( $answers as $answer ){
                            var_dump( $answer );
                        }
                    } else {
                        echo '<p class="text-muted">We still dont have an answer for this one.</p>';
                    }

                    ?>
            </p>
        </div><!-- .answer -->
        <p> 
            <?php msp_get_submit_answer_form( $question->comment_ID ); ?> 
        </p>
    </div><!-- .product_question_inner -->
    <?php
}

function msp_process_customer_submit_awnser(){
    parse_str( $_POST['form_data'], $form_data );
    wp_send_json( $form_data );

    

    wp_die();
}