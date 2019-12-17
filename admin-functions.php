<?php 

class MSP_Admin{
    /**
     * Class used for everything backend on this child-theme.
     */
    
    function __construct(){
        // Backend settings and UI changed
        add_action('admin_menu', array( $this, 'theme_options') );

        // Custom meta boxes for use in backend (product edit mostly)
        add_action( 'add_meta_boxes', array( $this, 'msp_meta_boxes' ) );
        add_action( 'woocommerce_product_options_advanced', array( $this, 'submit_resources_tab' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_resources_meta' ), 10, 2 );
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_videos_meta' ), 10, 2 );
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_size_guide_meta' ), 10, 2 );
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_specifications_meta' ), 10, 2 );
        add_action( 'woocommerce_process_product_meta', array( $this,'iww_add_gsf_title'), 10, 2 );

        add_action( 'woocommerce_product_options_general_product_data', 'msp_specifications_table' );
        add_action( 'woocommerce_product_options_general_product_data',  array( $this,'iww_gsf_title') );

        // Add purchase order meta data to order emails and edit order page.
        add_filter('woocommerce_email_order_meta_keys', 'sc_add_po_to_emails');
        add_action( 'woocommerce_admin_order_data_after_billing_address', 'sc_add_po_meta_data', 10, 1 );
        

        // Net 30 checkbox - For both self and other users.
        add_action( 'show_user_profile', array( $this, 'add_net30_metabox'), 1 );
        add_action( 'edit_user_profile', array( $this, 'add_net30_metabox'), 1 );

        // Saving Net 30 checkbox data - For both self and other users.
        add_action( 'personal_options_update', array( $this, 'update_user_to_net30_terms'), 5 );
        add_action( 'edit_user_profile_update', array( $this, 'update_user_to_net30_terms'), 5 );
    }


    public function iww_gsf_title(){
      global $woocommerce, $post;
      echo '<div class="options_group">';
      woocommerce_wp_text_input(
        array(
            'id'            => 'gsf_title',
            'wrapper_class' => 'form-field-wide',
            'label'         => __('GSF Title', 'woocommerce' ),
          'description'   => 'Try to stay under 70',
          'custom_attributes' => array('autocomplete' => 'off'),
        )
      );
      echo '<p class="form-field form-field-wide">Title Length: <span id="title-length"></span></p>';
      echo '</div>';
    }
    
    // Save Fields
    public function iww_add_gsf_title( $post_id ){
      if( isset( $_POST['gsf_title'] ) )
            update_post_meta( $post_id, 'gsf_title', $_POST['gsf_title'] );
    }

    public function add_next_order_btn(){
        /**
         * Adds a next & previous order button for quick pagination of orders.
         */
        $orders = wc_get_orders( array('return' => 'ids', 'limit' => 100) );
        for( $i = 0; $i < sizeof($orders); $i++ ){
            if( $orders[$i] == $_GET['post'] ){
                if( ! empty( $orders[$i - 1] ) ) $prev = $orders[$i - 1];
                if( ! empty( $orders[$i + 1] ) ) $next = $orders[$i + 1];
            }
        }
        ?>
        <div class="wrap">
            <?php if( ! empty( $next ) ) : ?>
            <a href="/wp-admin/post.php?post=<?php echo $next ?>&action=edit" class="btn" style="float:left">Previous Order</a>
            <?php endif; ?>
            <?php if( ! empty( $prev ) ) : ?>
            <a href="/wp-admin/post.php?post=<?php echo $prev ?>&action=edit" class="btn" style="float:right">Next Order</a>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_delete_option(){
        /**
         * Is passed the key of the row to be removed from the array. Then serialize and put back in DB.
         * @see ../js/admin.js
         */

        // get position to remove
        $pos = $_POST['target'];

        // get array from db
        $promos = msp_get_promos();

        // remove the selected row from promos array
        unset( $promos[$pos] );

        // put back in db
        update_option( 'msp_promos', maybe_serialize( $promos ) );

        wp_die();
    }

    public function ajax_create_option(){
        /**
         * AJAX function which adds data to options API
         */
        if( isset( $_POST['options'] ) )
            update_option( 'msp_promos', maybe_serialize( $_POST['options'] ) );

        wp_die();
    }

    public function add_net30_metabox($user){
        $is_net30 = get_user_meta( $user->ID, 'iww_net30', true );
        ?>
        <h1><?php esc_html_e( 'Activate Net 30', 'iww' ) ?></h1>
        <table class="form-table" style="background-color: red; color: #fff;">
                <tr>
                    <th><label for="iww_net30"><?php esc_html_e( 'Activate NET 30', 'iww' ); ?></label></th>
                    <td>
                <input type="checkbox" id="iww_net30" name="iww_net_30_data" value="1" <?php if ( $is_net30 ) echo ' checked="checked"'; ?> />
                    </td>
                </tr>
            </table>
        <?php
    }

    public function update_user_to_net30_terms( $user_id ){
        if( current_user_can( 'edit_user', $user_id ) ) {
            update_user_meta( $user_id, 'iww_net30', $_POST['iww_net_30_data'] );
            $sessions = WP_Session_Tokens::get_instance( $user_id );
            $sessions->destroy_all();
        }
    }

    public function msp_meta_boxes(){
        add_meta_box(
            'msp-product-video',
            __('Product Videos', 'msp'),
            'msp_product_video_callback',
            'product',
            'side',
            'low'
        );

        add_meta_box(
            'msp-size-guide',
            __('Product Size Guide', 'msp'),
            'msp_size_guide_callback',
            'product',
            'side',
            'low'
        );
    }

    public function theme_options(){
        /**
        * hooked into the admin_init so we can create menus and customize site settings
        */
        add_theme_page( 'MSP Theme Options', 'MSP Theme Options', 'manage_options', 'msp_options', array( $this, 'msp_options_callback' ) );

        add_action( 'admin_init', array( $this, 'register_theme_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'submit_tracking_form' ) );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_next_order_btn' ) );
    }

    public function process_product_size_guide_meta( $id ){
        /**
         * Updates the size guide
         */
        if( isset( $_POST['_msp_size_guide'] ) ){
            update_post_meta( $id, '_msp_size_guide', $_POST['_msp_size_guide'] );
        }
    }

    public function process_product_videos_meta( $id ){
        /**
         * Updates product videos
         */
        
        $limit = sizeof($_POST['product_video']);
        $arr = array();
        for( $i = 0; $i <= $limit; $i++ ){
            if( ! empty( $_POST['product_video'][$i] ) ){
                array_push( $arr, array( $_POST['product_video'][$i] ) );
            }
        }

        update_post_meta( $id, '_msp_product_videos', MSP::package( $arr ) );
    }

    public function process_product_resources_meta( $id ){
        /**
         * Updates resources TODO: Could easily combine these functions.. ^^
         */
        $limit = sizeof($_POST['resource_url']);
        $arr = array();
        for( $i = 0; $i <= $limit; $i++ ){
            if( ! empty( $_POST['resource_label'][$i] ) && ! empty( $_POST['resource_url'][$i] ) ){
                array_push( $arr, array( $_POST['resource_label'][$i], $_POST['resource_url'][$i] ) );
            }
        }

        update_post_meta( $id, '_msp_resources', MSP::package( $arr ) );
    }

    public function process_product_specifications_meta( $id ){
        $specs = $_POST['specification'];

        foreach( $specs as $spec ){
            if( ! empty( $spec['label'] ) && ! empty( $spec['value'] ) ){
                msp_update_specification( $id, $spec['label'], $spec['value'] );
            }
        }

    }

    public function submit_resources_tab(){
        /**
         * HTML form on back end for linking resources to products
         */
        global $post;
        $resources = msp_get_product_resources( $post->ID );

        ?>
        <div id="resource_tab" class="option_group">
            <p class="form-field resource_label_field">
                <label for="resource_label">Resources</label>
                <div style="display: flex;">
                    <p id="resource_input_wrapper">
                        <?php if( empty( $resources ) ) : ?>
                            <input type="text" id="resource_label" name="resource_label[0]" style="margin-right: 1rem;" placeholder="Label" />
                            <input type="text" id="resource_url" name="resource_url[0]" placeholder="URL" />
                            <br>
                        <?php else : ?>
                            <?php foreach( $resources as $index => $arr ) : ?>
                                <input type="text" id="resource_label" name="resource_label[<?php echo $index ?>]" style="margin-right: 1rem;" placeholder="Label" value="<?php echo $arr[0] ?>" />
                                <input type="text" id="resource_url" name="resource_url[<?php echo $index ?>]" placeholder="URL" value="<?php echo $arr[1] ?>" />
                                <br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button" class="add_input_line" data-count=0>+</button>
            </p>
        </div>
        <?php
    }

    public function enqueue_scripts( $hook ){
        wp_enqueue_script('admin', get_stylesheet_directory_uri() . '/js/admin.js');
    }

    public function submit_tracking_form(){
        /**
         * simple form which allows backend users to submit tracking information.
         */
        woocommerce_wp_select( array(
            'id' => 'shipper',
            'label' => 'Shipper:',
            'value' => '',
            'options' => array(
            '' => '',
                'ups' => 'UPS',
                'fedex' => 'Fedex',
                'usps' => 'Post Office',
            ),
            'wrapper_class' => 'form-field-wide'
        ) );

        woocommerce_wp_text_input( array(
            'id' => 'tracking',
            'label' => 'Tracking #:',
            'value' => '',
            'wrapper_class' => 'form-field-wide',
        ) );

        echo '<button class="button button-primary" style="width: 100%; margin-top: 1rem;">Post Tracking</button>';
    }



    public static function manage_cron_jobs( $key, $order_id, $create = true  ){
        /**
         * Run when saving order meta data, this function checks if the key is in the $cron_map array
         * if true, clear any old cron_jobs, and create the new one mapped to the function in $cron_map.
         * @param string $key - meta key
         * @param int $order_id - order id
         */

        $cron_map = array(
            'tracking' => 'msp_update_order_tracking'
        );

        if( isset( $cron_map[$key] ) ){
            //create key
            $cron_key = 'msp_update_order_' . $order_id . '_' . $key;
    
            //get rid of old job
            $timestamp = wp_next_scheduled( $cron_key, $order_id );
            wp_unschedule_event( $timestamp, $cron_key, $order_id );
            update_post_meta( $order_id, $cron_key, $timestamp );
            
            if( $create ){
                // //make new job
                wp_schedule_event( time(), 'daily', $cron_key, $order_id );
                add_action( $cron_key, $cron_map[$key], 1, 1 );
            }
        }
    }

    

    public function add_settings_field_and_register( $page, $section, $prefix, $keys ){
        /**
         * simplfies the task of adding settings fields and registering.
         */

        foreach( $keys as $key ){
            add_settings_field(
                $prefix . "_$key",
                deslugify( $key ) . ':',
                $prefix . '_' . $key . '_callback',
                $page,
                $section
            );
            register_setting( $page, $prefix . "_$key" );
        }
    }

    /**
     * simple html wrapper for the theme options page.
     */
    public function msp_options_callback(){
        
        ?>
        <div class="wrap">
            <h1>MSP Theme Options</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'msp_options' ); ?>
                <?php submit_button(); ?>
                <?php do_settings_sections( 'msp_options' ); ?>
                <?php submit_button(); ?>
            </form>        
        </div>
        <?php
    }

    /**
     *
     * dynamically creates options fields based on the arguments passed to add_settings_section.
     * */
    public function register_theme_settings(){
        // add_settings_section(
        //     'front_page',
        //     'Front Page:',
        //     '', 
        //     'msp_options'
        // );

        add_settings_section(
            'theme_options',
            'Theme Layout:',
            '', 
            'msp_options'
        );

        add_settings_section(
            'emails',
            'Emails:',
            '', 
            'msp_options'
        );


        add_settings_section(
            'integration', 
            'Integration:',
            '', 
            'msp_options'
        );

        add_settings_section(
            'woocommerce',
            'Woocommerce:',
            '', 
            'msp_options'
        );

        add_settings_section(
            'promotions',
            'Promotions:',
            '', 
            'msp_options'
        );

        $this->add_settings_field_and_register( 'msp_options', 'promotions', 'promo', 
        array( 'top_bar_link', 'top_bar_image_id', 'pop_up_title', 'pop_up_link', 'pop_up_image_id', 'pop_up_version', 'bogo_needle', 'bogo_discount' ) );

        $this->add_settings_field_and_register( 'msp_options', 'theme_options', 'msp',
         array( 'primary_color', 'link_color', 'header_background', 'header_links', 'footer_background', 'footer_link_color', 
         'logo_width', 'shop_nav_color', 'shop_nav_color_link', 'shop_nav_images', 'copyright_color', 'copyright_link_color' ) );

        $this->add_settings_field_and_register( 'msp_options', 'emails', 'msp', 
        array( 'contact_email', 'gtin_field' ) );

        $this->add_settings_field_and_register( 'msp_options', 'integration', 'integration', 
        array( 'google_analytics_account_id', 'google_recaptcha', 'google_adwords', 'google_aw_campaign' ) );
        
        $this->add_settings_field_and_register( 'msp_options', 'woocommerce', 'wc', 
        array( 'easy_qty_breaks', 'add_net_30_to_single_product', 'three_day_shipping_id', 'two_day_shipping_id', 'free_shipping_id', 'ground_shipping_id' ) );
    }
}

new MSP_Admin();

// templates called by $this->add_settings_field_and_register();

/** ALL THE HTML CALLBACKS FOR THE THEME OPTIONS PAGE /wp-admin/themes.php?page=msp_options */
function promo_pop_up_title_callback(){
    $option = get_option( 'promo_pop_up_title' );
    echo '<input name="promo_pop_up_title" id="promo_pop_up_title" type="text" value="'. get_option( 'promo_pop_up_title' ) .'" class="code" />';
}

function promo_pop_up_link_callback(){
    echo '<input name="promo_pop_up_link" id="promo_pop_up_link" type="text" value="'. get_option( 'promo_pop_up_link' ) .'" class="code" />';
}

function promo_pop_up_image_id_callback(){
    echo '<input name="promo_pop_up_image_id" id="promo_pop_up_image_id" type="text" value="'. get_option( 'promo_pop_up_image_id' ) .'" class="code" />';
}

function promo_pop_up_version_callback(){
    echo '<input name="promo_pop_up_version" id="promo_pop_up_version" type="text" value="'. get_option( 'promo_pop_up_version' ) .'" class="code" />';
}

function promo_top_bar_link_callback(){
    $option = get_option( 'promo_top_bar_link' );
    echo '<input name="promo_top_bar_link" id="promo_top_bar_link" type="text" placeholder="shop/" value="'. get_option( 'promo_top_bar_link' ) .'" class="code" />';
}

function promo_top_bar_image_id_callback(){
    $option = get_option( 'promo_top_bar_image_id' );
    echo '<input name="promo_top_bar_image_id" id="promo_top_bar_image_id" type="text" value="'. get_option( 'promo_top_bar_image_id' ) .'" class="code" />';
}

function promo_bogo_needle_callback(){
    echo '<input name="promo_bogo_needle" id="promo_bogo_needle" type="text" value="'. get_option( 'promo_bogo_needle' ) .'" class="code" />';
}

function promo_bogo_discount_callback(){
    echo '<input name="promo_bogo_discount" id="promo_bogo_discount" type="number" value="'. get_option( 'promo_bogo_discount' ) .'" class="code" />';
}


function wc_add_net_30_to_single_product_callback(){
    $option = get_option( 'wc_add_net_30_to_single_product' );
    echo '<input name="wc_add_net_30_to_single_product" id="wc_add_net_30_to_single_product" type="checkbox" value="1" '. checked(1, $option, false) .' />';
}

function wc_easy_qty_breaks_callback(){
    $option = get_option( 'wc_easy_qty_breaks' );
    echo '<input name="wc_easy_qty_breaks" id="wc_easy_qty_breaks" type="checkbox" value="1" '. checked(1, $option, false) .' />';
}

function wc_free_shipping_id_callback(){
    echo '<input name="wc_free_shipping_id" id="wc_free_shipping_id" type="text" value="'. get_option( 'wc_free_shipping_id' ) .'" class="code" />';
}

function wc_ground_shipping_id_callback(){
    echo '<input name="wc_ground_shipping_id" id="wc_ground_shipping_id" type="text" value="'. get_option( 'wc_ground_shipping_id' ) .'" class="code" />';
}
function wc_two_day_shipping_id_callback(){
    echo '<input name="wc_two_day_shipping_id" id="wc_two_day_shipping_id" type="text" value="'. get_option( 'wc_two_day_shipping_id' ) .'" class="code" />';
}

function wc_three_day_shipping_id_callback(){
    echo '<input name="wc_three_day_shipping_id" id="wc_three_day_shipping_id" type="text" value="'. get_option( 'wc_three_day_shipping_id' ) .'" class="code" />';
}


function msp_logo_width_callback(){
    echo '<input name="msp_logo_width" id="msp_logo_width" type="number" value="'. get_option( 'msp_logo_width' ) .'" class="code" />';
}

function msp_primary_color_callback(){
    echo '<input name="msp_primary_color" id="msp_primary_color" type="text" value="'. get_option( 'msp_primary_color' ) .'" class="color-field code" />';
}

function msp_copyright_color_callback(){
    echo '<input name="msp_copyright_color" id="msp_copyright_color" type="text" value="'. get_option( 'msp_copyright_color' ) .'" class="color-field code" />';
}

function msp_copyright_link_color_callback(){
    echo '<input name="msp_copyright_link_color" id="msp_copyright_link_color" type="text" value="'. get_option( 'msp_copyright_link_color' ) .'" class="color-field code" />';
}

function msp_link_color_callback(){
    echo '<input name="msp_link_color" id="msp_link_color" type="text" value="'. get_option( 'msp_link_color' ) .'" class="color-field code" />';
}

function msp_header_background_callback(){
    echo '<input name="msp_header_background" id="msp_header_background" type="text" value="'. get_option( 'msp_header_background' ) .'" class="color-field code" />';
}

function msp_header_links_callback(){
    echo '<input name="msp_header_links" id="msp_header_links" type="text" value="'. get_option( 'msp_header_links' ) .'" class="color-field code" />';
}

function msp_shop_nav_color_callback(){
    echo '<input name="msp_shop_nav_color" id="msp_shop_nav_color" type="text" value="'. get_option( 'msp_shop_nav_color' ) .'" class="color-field code" />';
}

function msp_shop_nav_color_link_callback(){
    echo '<input name="msp_shop_nav_color_link" id="msp_shop_nav_color_link" type="text" value="'. get_option( 'msp_shop_nav_color_link' ) .'" class="color-field code" />';
}

function msp_shop_nav_images_callback(){
    $option = get_option( 'msp_shop_nav_images' );
    echo '<input name="msp_shop_nav_images" id="msp_shop_nav_images" type="checkbox" value="1" '. checked(1, $option, false) .' />';
}


function msp_footer_background_callback(){
    echo '<input name="msp_footer_background" id="msp_footer_background" type="text" value="'. get_option( 'msp_footer_background' ) .'" class="color-field code" />';
}

function msp_footer_link_color_callback(){
    echo '<input name="msp_footer_link_color" id="msp_footer_link_color" type="text" value="'. get_option( 'msp_footer_link_color' ) .'" class="color-field code" />';
}

function msp_contact_email_callback(){
    echo '<input name="msp_contact_email" id="msp_contact_email" type="email" value="'. get_option( 'msp_contact_email' ) .'" class="code" />';
}

function msp_gtin_field_callback(){
    echo '<input name="msp_gtin_field" id="msp_gtin_field" type="text" value="'. get_option( 'msp_gtin_field' ) .'" class="code" />';
}

function ups_api_key_callback(){
    echo '<input name="ups_api_key" id="ups_api_key" type="text" value="'. get_option( 'ups_api_key' ) .'" class="code" />';
}
function ups_api_username_callback(){
    echo '<input name="ups_api_username" id="ups_api_username" type="text" value="'. get_option( 'ups_api_username' ) .'" class="code" />';
}
function ups_api_password_callback(){
    echo '<input name="ups_api_password" id="ups_api_password" type="text" value="'. get_option( 'ups_api_password' ) .'" class="code" />';
}
function ups_api_account_callback(){
    echo '<input name="ups_api_account" id="ups_api_account" type="text" value="'. get_option( 'ups_api_account' ) .'" class="code" />';
}
function ups_api_mode_callback(){
    echo '<input name="ups_api_mode" id="ups_api_mode_test" type="radio" value="https://wwwcie.ups.com/ups.app/xml/" class="code" '. checked( 'https://wwwcie.ups.com/ups.app/xml/', get_option( 'ups_api_mode' ), false ) .' />Test';
    echo '<br>';
    echo '<input name="ups_api_mode" id="ups_api_mode_production" type="radio" value="https://onlinetools.ups.com/ups.app/xml/" class="code" '. checked( 'https://onlinetools.ups.com/ups.app/xml/', get_option( 'ups_api_mode' ), false ) .' />Production';
}
function ups_api_end_of_day_callback(){
    echo '<input type="time" id="ups_api_end_of_day" name="ups_api_end_of_day" value="'. get_option( 'ups_api_end_of_day' ) .'">';
}

function integration_google_analytics_account_id_callback(){
    echo '<input name="integration_google_analytics_account_id" id="integration_google_analytics_account_id" type="text" value="'. get_option( 'integration_google_analytics_account_id' ) .'" class="code" />';
}

function integration_google_recaptcha_callback(){
    echo '<input name="integration_google_recaptcha" id="integration_google_recaptcha" type="text" value="'. get_option( 'integration_google_recaptcha' ) .'" class="code" />';
}

function integration_google_adwords_callback(){
    echo '<input name="integration_google_adwords" id="integration_google_adwords" type="text" value="'. get_option( 'integration_google_adwords' ) .'" class="code" />';
}

function integration_google_aw_campaign_callback(){
    echo '<input name="integration_google_aw_campaign" id="integration_google_aw_campaign" type="text" value="'. get_option( 'integration_google_aw_campaign' ) .'" class="code" />';
}

function msp_product_video_callback( $post ){
    /**
     * Html form for submitting product videos // Maybe make a template
     */
    wp_nonce_field( basename( __FILE__ ), 'msp_product_video_callback' );
    $saved_urls = msp_get_product_videos( $post->ID );
    ?>
    <div id="msp_product_video_input_table">
        <p>Video Url(s)</p>
        <?php if( empty( $saved_urls ) ) : ?>
            <input type="text" name="product_video[0]">
        <?php else : ?>
            <?php foreach( $saved_urls as $index => $url ) : ?>
                <input type="text" name="product_video[<?php echo $index ?>]" value="<?php echo $url[0]; ?>">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" class="add" data-count=<?php echo sizeof( $saved_urls ) ?>>Add</button>
    <?php
}

function msp_specifications_table(){
    global $woocommerce, $post;

    $specs = msp_get_product_specifications( $post->ID );

    $count = sizeof( $specs );

    ?>

    <div id="msp-specifications" class="options_group" style="padding-left: 165px">
        <table>
            <thead>
                <th>Attribute</th>
                <th>Value</th>
                <th>Action</th>
            </thead>
            <?php for( $i = 0; $i < sizeof( $specs); $i++ ) : ?>
                <tr class="<?php echo $i ?>">
                    <td><input type="text" name="specification[<?php echo $i ?>][label]" style="width: 100%" value="<?php echo $specs[$i]->spec_label ?>" /></td>
                    <td><input type="text" name="specification[<?php echo $i ?>][value]" style="width: 100%" value="<?php echo $specs[$i]->spec_value ?>" /></td>
                    <td><button class="remove" type="button">&times;</button></td>
                </tr>
            <?php endfor; ?>

            <tr class="<?php echo sizeof( $specs ) + 1 ?>">
                    <td><input type="text" name="specification[<?php echo sizeof( $specs ) + 1 ?>][label]" style="width: 100%" /></td>
                    <td><input type="text" name="specification[<?php echo sizeof( $specs ) + 1 ?>][value]" style="width: 100%" /></td>
                    <td><button class="remove" type="button">&times;</button></td>
            </tr>

        </table>
        <button class="add" type="button">+</button>
    </div>

    <?php
}

function msp_size_guide_callback( $post ){
    /**
     * Html form for submitting product size guide // Maybe make a template
     */
    $size_guide_src = get_post_meta( $post->ID, '_msp_size_guide', true );
    ?>
    <div id="msp_size_guide_input_table">
        <p>Size Guide</p>
        <input type="url" name="_msp_size_guide" class="code" value="<?php echo $size_guide_src ?>" />
    </div>
    <?php
}

add_action( 'woocommerce_process_shop_order_meta', 'sc_save_tracking_details', 50 );
function sc_save_tracking_details( $ord_id ){
    /*
    Quick fix for sending customers tracking - eventually want to hook into API's and automate task.
    */
    if ( isset( $_POST[ 'shipper' ] ) && !empty( $_POST[ 'shipper' ] ) ){
    $shipper = wc_clean( $_POST[ 'shipper' ] );
    update_post_meta( $ord_id, 'shipper', $shipper );
  }

  if ( isset( $_POST[ 'tracking' ] ) && ! empty( $_POST[ 'tracking' ] ) ){
    $tracking = wc_clean( $_POST[ 'tracking' ] );
    update_post_meta( $ord_id, 'tracking', $tracking );
  }

  if( isset( $shipper, $tracking ) ){
    $order = wc_get_order( $ord_id );
    $link = sc_make_tracking_link( $shipper, $tracking );
    update_post_meta( $ord_id, 'tracking_link', $link );
    $sitename = get_bloginfo('name');
    $button_color = get_option( 'msp_primary_color' ) ?: '#ff9900';

    $note = 'Good news!<br> Your order has shipped and can be tracked using the link below.<br><br>';
    $note .= '<p style="text-align: center;"><table cellspacing="0" cellpadding="0" style="text-align: center; margin-bottom: 1rem;"><tr align="center"><td align="center" width="300" height="40" bgcolor="'. $button_color .'" style="-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; color: #ffffff; display: block;"><a href="'.$link.'" style="font-size:16px; text-align: center; font-weight: bold; font-family: Helvetica, Arial, sans-serif; text-decoration: none; line-height:40px; width:100%; display:inline-block"><span style="color: #FFFFFF">Track Package</span></a></td></tr></table></p><br><br>';
    $note .= 'Regards, <br> ' . $sitename;
    $order->add_order_note( $note, 1 );
  }

}


/**
 * Add new WooCommerce Twilio message variables.
 * Adds shipping method provider, Sequential Order Numbers support, and customer first name.
 *
 * Can work with any custom order meta as well.
 *
 * @param string $message the SMS message
 * @param \WC_Order $order the order object
 * @return string updated message
 */
function sv_wc_twilio_sms_variable_replacement( $message, $order ) {

    // Shipment tracking: use first package
    $tracking_link = get_post_meta( $order->get_id(), 'tracking_link', true );
    if( ! empty( $tracking_link ) ){
        $message = str_replace( '%tracking_link%', $tracking_link, $message );
    }

	return $message;
}
add_filter( 'wc_twilio_sms_customer_sms_before_variable_replace', 'sv_wc_twilio_sms_variable_replacement', 10, 2 );


function sc_make_tracking_link( $shipper, $tracking ){
  $base_urls = array(
    'ups' => 'https://www.ups.com/track?loc=en_US&tracknum=',
    'fedex' => 'https://www.fedex.com/apps/fedextrack/?tracknumbers=',
    'usps' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels='
  );
  return $base_urls[$shipper] . $tracking;
}
