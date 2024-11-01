<?php
class yg_wpp_admin {

	/**
	 * @access   protected
	 * @var      string    $version
	 */
	protected $version;

	/**
     * Array to store duration values
     * @access   protected
     * @var      array    $options
     */
	protected $options;

	/**
     * Meta data array
     * @access   protected
     * @var      array    $wpp_meta_loc
     */
	protected $wpp_meta_loc;

    /**
     * Meta data array
     * @access   protected
     * @var      array    $wpp_meta_con
     */
    protected $wpp_meta_con;

    /**
     * Meta data array
     * @access   protected
     * @var      array    $wpp_meta_bg
     */
    protected $wpp_meta_bg;

    /**
     * Condition HTML string
     * @access   protected
     * @var      string    $conHtml
     */
    protected $conHtml;

    /**
     * Condition types
     * @access   protected
     * @var      array    $con_options
     */
    protected $con_options;

    /**
     * Condition type options
     * @access   protected
     * @var      array    $con_sub_options
     */
    protected $con_sub_options;

	public function __construct($version,$con_sub_options) {
		$this->version = $version;
        add_action('init', array($this,'yg_insert_post_type' ));
        add_action('admin_menu', array($this,'yg_add_wpp_metaboxe'));
        add_action('save_post', array($this,'yg_save_wpp_postdata'));
        add_action('admin_menu', array($this,'yg_wpp_settings_page_add'));
        add_action('admin_init', array($this,'yg_wpp_register_settings'));
        add_action('before_delete_post', array($this,'yg_wpp_bust_cache'));
        add_action('wp_trash_post', array($this,'yg_wpp_bust_cache'));
        add_action('untrash_post', array($this,'yg_wpp_bust_cache'));
        add_action('wp_ajax_yg_get_thumb_url', array(&$this,'yg_get_thumb_url'));
        add_action('wp_ajax_yg_wpp_pos_get_pst', array(&$this,'yg_wpp_pos_get_pst'));
        add_action('wp_ajax_yg_wpp_pos_get_ttls', array(&$this,'yg_wpp_pos_get_ttls'));
        add_action('wp_ajax_yg_wpp_pos_get_tax', array(&$this,'yg_wpp_pos_get_tax'));
        $this->con_options = array(
            'block'=>'New condition block',
            'location'=>'Visitor\'s location',
            'history'=>'Visitor\'s history',
            'interaction'=>'Visitor\'s interaction',
            'device'=>'Visitor\'s device'
        );
        
        $this->con_sub_options = $con_sub_options;

        if(!class_exists('WC_Order') && !class_exists('WP_eCommerce')){
            unset($this->con_options['interaction']);
            unset($this->con_sub_options['interaction']);
        }
        $this->trackOpen = array();
        //http://www.geoplugin.com/webservices/php
        //http://www.geoplugin.net/php.gp?ip=67.247.72.164
        //http://maps.googleapis.com/maps/api/geocode/json?address=a&components=postal_code:14454&sensor=false
	}

    public function yg_insert_post_type() {
        $labels = array(
            'name' => _x('Custom Content Block', 'post type general name'),
            'singular_name' => _x('Custom Content Block', 'post type singular name'),
            'add_new' => _x('Add New', 'Block'),
            'add_new_item' => __('Add new custom block'),
            'edit_item' => __('Edit block'),
            'new_item' => __('New block'),
            'all_items' => __('All blocks'),
            'view_item' => __('View block'),
            'search_items' => __('Search blocks'),
            'not_found' =>  __('No blocks found'),
            'not_found_in_trash' => __('No blocks found in Trash'), 
            'parent_item_colon' => '',
            'menu_name' => 'WPressonalize'
        );
        $args = array(
            'labels' => $labels,
            'menu_icon' => plugins_url('images/wpp_block.png',dirname(__FILE__)),
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true, 
            'show_in_menu' => true, 
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false, 
            'hierarchical' => false,
            'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' )
        ); 
        register_post_type('wpp_c_block',$args);
        $this->wpp_meta_test =
        array(
            "wppt_testmode" => array(
                "name" => "wppt_testmode",
                "std" => "",
                "title" => "",
                "inputType" => "custom",
                "description" => "",
                "callback"=>"yg_set_testmode")
            );
        $this->wpp_meta_loc =
        array(
            "wppb_async" => array(
                "name" => "wppb_async",
                "std" => "asyn",
                "title" => "When to load block",
                "inputType" => "radio",
                "description" => "Inline will load the page with custom block. Asynchronously will load the block after page load. To use user data you need to load the block asynchronously.",
                "options"=>array('inln'=>'inline','asyn'=>'asynchronously')),
            "wppl_loc" => array(
                "name" => "wppl_loc",
                "std" => "",
                "title" => "Location on page",
                "inputType" => "custom",
                "description" => "",
                //"options"=>array('pu'=>'Popup div','bc'=>'Before loop content','ac'=>'After loop content','bp'=>'Before post content','ap'=>'After post content','mn'=>'Position manually')),
                "callback"=>"yg_set_where_dd"),
            "wppb_onpage" => array(
                "name" => "wppb_onpage[]",
                "std" => "",
                "title" => "On what pages will custom block show",
                "inputType" => "custom",
                "description" => "",
                "callback"=>"yg_where_metabox"),
            "wppb_onpost" => array(
                "name" => "wppb_onpost[]",
                "std" => "",
                "title" => "",
                "inputType" => "noshow",
                "description" => "",
                "callback"=>"yg_where_metabox"),
            "wppb_ontax" => array(
                "name" => "wppb_ontax[]",
                "std" => "",
                "title" => "",
                "inputType" => "noshow",
                "description" => "",
                "callback"=>"yg_where_metabox")
            );
        $this->wpp_meta_bg =
        array(
            "wppimg_bgimg_img" => array(
                "name" => "wppimg_bgimg_img",
                "std" => "",
                "title" => "Background image",
                "inputType" => "image",
                "description" => ""),
            "wppimg_bgimg_pos" => array(
                "name" => "wppimg_bgimg_pos",
                "std" => "",
                "title" => "Background treatment",
                "inputType" => "radio",
                "description" => "",
                "options"=>array('scl'=>'Scale to fit','rp'=>'Repeat','onc'=>'Only once')),
            "wppst_background-color" => array(
                "name" => "wppst_background-color",
                "std" => "",
                "title" => "Background color",
                "inputType" => "color",
                "description" => ""),
            "wppst_color" => array(
                "name" => "wppst_color",
                "std" => "",
                "title" => "Text color",
                "inputType" => "color",
                "description" => ""),
            "wppst_text-align" => array(
                "name" => "wppst_text-align",
                "std" => "",
                "title" => "Text align",
                "inputType" => "radio",
                "description" => "",
                "options"=>array('left'=>'left','center'=>'center','right'=>'right')),
            "wppst_font-size" => array(
                "name" => "wppst_font-size",
                "std" => "",
                "title" => "Font size (in px or %)",
                "inputType" => "text",
                "description" => ""),
            "wppst_border-color" => array(
                "name" => "wppst_border-color",
                "std" => "",
                "title" => "Border color",
                "inputType" => "color",
                "description" => ""),
            "wppst_border-width" => array(
                "name" => "wppst_border-width",
                "std" => "",
                "title" => "Border width (in px or %)",
                "inputType" => "text",
                "description" => "Set to 0 for no border"),
            "wppst_border-top-left-radius" => array(
                "name" => "wppst_border-top-left-radius",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "top left",
                "title" => "Border radius (in px or %)",
                "inputType" => "text",
                "description" => ""),
            "wppst_border-top-right-radius" => array(
                "name" => "wppst_border-top-right-radius",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "top right",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wppst_border-bottom-left-radius" => array(
                "name" => "wppst_border-bottom-left-radius",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "bttm left",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wppst_border-bottom-right-radius" => array(
                "name" => "wppst_border-bottom-right-radius",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "bttm right",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wppst_padding-top" => array(
                "name" => "wppst_padding-top",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "top",
                "title" => "Padding (in px or %)",
                "inputType" => "text",
                "description" => ""),
            "wppst_padding-right" => array(
                "name" => "wppst_padding-right",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "right",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wppst_padding-bottom" => array(
                "name" => "wppst_padding-bottom",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "bottom",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wppst_padding-left" => array(
                "name" => "wppst_padding-left",
                "std" => "",
                "class" => "txt-sml",
                "placeholder" => "left",
                "title" => "",
                "inputType" => "text",
                "description" => ""),
            "wpppust_background-color" => array(
                "name" => "wpppust_background-color",
                "std" => "#000000",
                "wrapclass" => "wpp-popup-only",
                "placeholder" => "left",
                "title" => "Background color (behinde popup)",
                "inputType" => "color",
                "description" => ""),
            "wpppust_opacity" => array(
                "name" => "wpppust_opacity",
                "std" => "100",
                "wrapclass" => "wpp-popup-only",
                "class" => "pu-sldr",
                "title" => "Opacity",
                "inputType" => "slider",
                "description" => "")
            );
        $this->wpp_meta_con =
        array(
            "wpp_condition" => array(
                "name" => "wpp_condition",
                "std" => array(0=>array('type'=>'operator','val'=>'or',0 => array('type'=>''))),
                "stdLink" => "",
                "title" => "",
                "inputType" => "custom",
                "description" => "",
                "callback"=>"yg_con_metabox")
            );
        wp_enqueue_style('wpp-admin-css', plugin_dir_url( dirname(__FILE__) ) . '/css/wpp_admin.min.css');
    }

    public function yg_create_wpp_test_meta_boxes() {
        global $post;
        $this->yg_display_wpp_meta_boxes($post->ID,$this->wpp_meta_test);
    }

    public function yg_create_wpp_meta_boxes() {
        global $post;
        $this->yg_display_wpp_meta_boxes($post->ID,$this->wpp_meta_loc);
    }

    public function yg_create_wpp_bg_meta_boxes() {
        global $post;
        $this->yg_display_wpp_meta_boxes($post->ID,$this->wpp_meta_bg);
    }

    public function yg_create_wpp_con_meta_boxes() {
        global $post;
        $this->yg_display_wpp_meta_boxes($post->ID,$this->wpp_meta_con);
    }

    public function yg_display_wpp_meta_boxes($post_id,$meta_boxes) {
        foreach($meta_boxes as $meta_box) {
            $meta_box_value;
            $key = $meta_box['name'];
            if(stripos($meta_box['name'],'[]')!==false){
                $key = str_replace('[]','',$meta_box['name']);
                $meta_box_value = get_post_meta($post_id, $key);
            }else{
                $meta_box_value = get_post_meta($post_id, $key, true);
            }

            if($meta_box_value == "")
                $meta_box_value = $meta_box['std'];
            if($meta_box['inputType']!='noshow'){
                $calss = (isset($meta_box['class']) && $meta_box['class']!='')?' class="'.$meta_box['class'].'"':'';
                $placeholder = (isset($meta_box['placeholder']) && $meta_box['placeholder']!='')?' placeholder="'.$meta_box['placeholder'].'"':'';
                echo (isset($meta_box['wrapclass']) && $meta_box['wrapclass']!='')?'<div class="'.$meta_box['wrapclass'].'">':'';
                echo '<input type="hidden" name="'.$key.'_noncename" id="'.$key.'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
                if($meta_box['title']!='')
                    echo '<p><strong>'.$meta_box['title'].'</strong></p>';
                if($meta_box['inputType']=='text'){
                    echo '<input type="text" name="'.$key.'" value="'.$meta_box_value.'"'.$calss.$placeholder.' />';
                }elseif($meta_box['inputType']=='number'){
                    echo '<input type="number" name="'.$key.'" value="'.$meta_box_value.'" step="1" />';
                }elseif($meta_box['inputType']=='checkbox'){
                    $checked = ($meta_box_value=='yes')?' checked="checked"':'';
                    echo '<input type="checkbox" name="'.$key.'" value="yes"'.$checked.' />';
                }elseif($meta_box['inputType']=='select'){
                    echo '<select name="'.$key.'">';
                    foreach($meta_box['options'] as $option=>$label){
                        echo '<option value="'.$option.'"'.(($option==$meta_box_value)?' selected="selected"':'').'>'.$label.'</option>';
                    }
                    echo '</select>';
                }elseif($meta_box['inputType']=='wysiwyg'){
                    wp_editor( stripslashes($meta_box_value),$key,$settings = array('wpautop'=>false) );
                }elseif($meta_box['inputType']=='radio'){
                    foreach($meta_box['options'] as $option=>$label){
                        echo '<input type="radio" name="'.$key.'" value="'.$option.'"'.(($option==$meta_box_value)?' checked="checked"':'').' /> '.$label.' ';
                    }
                }elseif($meta_box['inputType']=='image'){
                    wp_register_script('add_img_js', plugin_dir_url( dirname(__FILE__) ) . '/js/img_admin.min.js', array('jquery'));
                    wp_enqueue_script('add_img_js');
                    echo '<div id="'.$key.'_holder">';
                    if (isset($meta_box_value) && $meta_box_value!=''){
                        $bgInfo = wp_get_attachment_image_src($meta_box_value,'medium');
                        echo '<img src="'.$bgInfo[0].'" id="'.$key.'_img" />';
                    }
                    echo '</div>
                    <input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$meta_box_value.'" />
                    <a href="javascript:;" id="'.$key.'_button" class="upld_img" media_ttl="image">add image</a> | <a href="javascript:;" class="dlt_img" attr_id="'.$key.'">remove image</a>';
                    
                }elseif($meta_box['inputType']=='color'){
                    wp_enqueue_style('wp-color-picker');
                    wp_enqueue_script( 'wpp-color-pkr', plugin_dir_url( dirname(__FILE__) ) . '/js/clr_admin.min.js', array( 'wp-color-picker' ), false, true );
                    echo '<div'.$calss.'>
                    <input type="text" name="'.$key.'" value="'.$meta_box_value.'" class="color-field" />
                    </div>';
                }elseif($meta_box['inputType']=='slider'){
                    wp_enqueue_style( 'jquery-ui-slider', plugin_dir_url( dirname(__FILE__) ) . '/css/jquery-ui.min.css' );
                    wp_enqueue_script('jquery-ui-slider', array('jquery'));
                    echo '<div'.$calss.'>
                    <input type="text" readonly name="'.$key.'" id="sldhold-'.$key.'" class="slider-holder" value="'.$meta_box_value.'" />
                    <div id="sld-'.$key.'" class="slider-field" txt-fld="sldhold-'.$key.'" val-data="'.$meta_box_value.'"></div>
                    </div>';
                }elseif($meta_box['inputType']=='custom'){
                    $f = $meta_box['callback'];
                    if($f!='')
                        $this->$f($post_id,$meta_box_value,$key);
                }
                if($meta_box['description']!='')
                    echo '<p class="mbox_desc">'.$meta_box['description'].'</p>';
                echo (isset($meta_box['wrapclass']) && $meta_box['wrapclass']!='')?'</div>':'';
            }
        }
    }

    public function yg_add_wpp_metaboxe(){
        add_meta_box("wpp_test_meta", "Test mode", array($this,"yg_create_wpp_test_meta_boxes"), "wpp_c_block", "normal", "high");
        add_meta_box("wpp_page_meta", "Block placement", array($this,"yg_create_wpp_meta_boxes"), "wpp_c_block", "normal", "high");
        add_meta_box("wpp_con_meta", "Block conditions", array($this,"yg_create_wpp_con_meta_boxes"), "wpp_c_block", "normal", "high");
        add_meta_box("wpp_bg_meta", "Custom block CSS", array($this,"yg_create_wpp_bg_meta_boxes"), "wpp_c_block", "side", "high");
        $post_id = (isset($_GET['post']) && isset($_GET['action']) && $_GET['action']=='edit')?$_GET['post']:'';
        if(isset($post_id) && $post_id!=''){
            $bnnrs = $this->yg_wpp_get_post_bnnrs($post_id);
            if(count($bnnrs)>0 && get_option('yg_wpp_show_bnnrs_edit')=='yes')
                add_meta_box("wpp_bnnrs_meta", "WPP Custom Blocks", array($this,"yg_create_wpp_postbnnr_meta_boxes"), array('page','post'), "side", "high", array('bnnrs'=>$bnnrs));
        }
    }

    public function yg_save_wpp_postdata( $post_id ) {
        global $post;
        if(isset($post->ID)){
            $this->yg_save_wpp_postdata_loop($post->ID,$this->wpp_meta_test);
            $this->yg_save_wpp_postdata_loop($post->ID,$this->wpp_meta_loc);
            $this->yg_save_wpp_postdata_loop($post->ID,$this->wpp_meta_bg);
            $this->yg_save_wpp_postdata_loop($post->ID,$this->wpp_meta_con);
        }
    }

    public function yg_save_wpp_postdata_loop($post_id,$meta_boxes) {
        foreach($meta_boxes as $meta_box) {
            // Verify
            if (isset($_POST[$meta_box['name'].'_noncename']) && !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
                return $post_id;
            }
         
            if (isset($_POST['post_type']) && 'page' == $_POST['post_type'] ) {
                if ( !current_user_can( 'edit_page', $post_id ))
                    return $post_id;
                } else {
                    if ( !current_user_can( 'edit_post', $post_id ))
                        return $post_id;
            }
            $key = (stripos($meta_box['name'],'[]')!==false)?str_replace('[]','',$meta_box['name']):$meta_box['name'];
            if(isset($_POST[$key])){
                $data = $_POST[$key];
                if(is_array($data) && strpos($meta_box['name'],'[]')!==false){//save multi key
                    if($key=='wppb_onpost' || $key=='wppb_ontax' || $key=='wppb_onpage'){//refresh old transients
                        $meta_box_value = get_post_meta($post_id, $key);
                        foreach ($meta_box_value as $value)
                            delete_transient('yg_wpp_cp_'.$key.'_'.$value);
                    }
                    delete_post_meta($post_id, $key);
                    foreach ($data as $value) {
                        add_post_meta($post_id, $key, $value);
                        //refresh new transients
                        if($key=='wppb_onpost' || $key=='wppb_ontax' || $key=='wppb_onpage')
                            delete_transient('yg_wpp_cp_'.$key.'_'.$value);
                    }
                }else{
                    if(is_array($data))
                        $data = serialize($data);

                    if(get_post_meta($post_id, $meta_box['name']) == "")
                        add_post_meta($post_id, $meta_box['name'], $data, true);
                    elseif($data != get_post_meta($post_id, $meta_box['name'], true))
                        update_post_meta($post_id, $meta_box['name'], $data);
                    elseif($data == "")
                        delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
                }
            }elseif($meta_box['name']=='wppt_testmode'){
                delete_post_meta($post_id, $meta_box['name']);
            }
        }
    }
    public function yg_wpp_bust_cache($post_id){
        global $post_type,$wpdb;   
        if($post_type != 'wpp_c_block')
            return;

        $q = "SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id=".$post_id." and meta_key in('wppb_onpage','wppb_onpost','wppb_ontax');";
        $res = $wpdb->get_results($q);
        foreach ($res as $trnsient) {
            delete_transient('yg_wpp_cp_'.$trnsient->meta_key.'_'.$trnsient->meta_value);
        }
    }
    public function yg_set_testmode($post_id,$mb_val,$mb_name){
        $checked = ($mb_val=='yes')?' checked="checked"':'';
        echo '<input type="checkbox" name="'.$mb_name.'" value="yes"'.$checked.' />';
        echo '<p class="mbox_desc wpp-set-test">Set to test mode</p>
        <div id="wpp-link-wrap-div"><p>Visitors will not see this banner, use links below to see/test the banner</p>';

        $linkArray = array();
        $iv = substr(str_pad($_SERVER['HTTP_HOST'],16,'y'),0,16);
        $testQuery = 'testbnnr='.urlencode(openssl_encrypt($post_id, 'AES-128-CBC','wpp',0,$iv));
        $mbp_val = get_post_meta($post_id, 'wppb_onpost');
        $mbpg_val = get_post_meta($post_id, 'wppb_onpage');
        $pagePosts = array_merge($mbp_val, $mbpg_val);
        foreach ($pagePosts as $pst) {
            if($pst=='hp')
                $linkArray[] = get_site_url();
            elseif($pst=='sr')
                $linkArray[] = get_site_url().'?s=test';
            else
                $linkArray[] = get_permalink($pst);
        }

        $mbt_val = get_post_meta($post_id, 'wppb_ontax');
        foreach ($mbt_val as $tax) {
            $linkArray[] = get_term_link((int)$tax);
        }
        foreach ($linkArray as $link) {
            $vr = (stripos($link, '?')===false)?'?':'&';
            echo '<a href="'.$link.$vr.$testQuery.'" target="_blank">'.$link.$vr.$testQuery.'</a><br />'."\n";
        }
        echo '</div>';
    }
    public function yg_set_where_dd($post_id,$mb_val,$mb_name){
        global $wp_registered_sidebars;
        $dd = array('pu'=>'Popup div','hd'=>'After Body Tag','bc'=>'Before loop content','ac'=>'After loop content','bp'=>'Before post content','ap'=>'After post content','mn'=>'Position manually','sc'=>'Sort code');
        foreach ($wp_registered_sidebars as $key => $sidebar) {
            $dd['wdg|'.$key] = 'Before sidebar - '.$sidebar['name'];
        }
        echo '<select name="'.$mb_name.'" id="pos_select" id-data="'.$post_id.'">';
        foreach($dd as $option=>$label){
            echo '<option value="'.$option.'"'.(($option==$mb_val)?' selected="selected"':'').'>'.$label.'</option>';
        }
        echo '</select>';
        echo '<span id="pos_select_comment"></span>';
    }

    public function yg_where_metabox($post_id,$mb_val,$mb_name){
        wp_enqueue_style('wpp-sumo-select', plugin_dir_url( dirname(__FILE__) ) . '/css/sumoselect.min.css');
        wp_enqueue_script('wpp-sumo-select', plugin_dir_url( dirname(__FILE__) ) . '/js/jquery.sumoselect.min.js', array('jquery'));
        wp_enqueue_script('wpp-admin-js', plugin_dir_url( dirname(__FILE__) ) . '/js/wpp_admin.min.js', array('jquery'));
        echo '<p>Select what pages, post pages or category/taxonomy pages to show the block. You can filter the result from which to choose or just hit the blue buttons to get all posts and taxonomies.</p>';

        //post
        $mbp_val = get_post_meta($post_id, 'wppb_onpost');
        echo '<div class="wpp_loc_set set_post">
        <h2 class="slct-ttl">Select post/custom post pages</h2>';
        $checked = (isset($mbp_val[0]) && $mbp_val[0]=='all')?' checked="checked"':'';
        //TODO figure out way to cleare transients with wildcard
        //echo '<p><input type="checkbox" name="wppb_onpost[]" id="wpp-allpost" value="all"'.$checked.' /> All posts on the site.</p>';
        echo '<div class="wpp-allpost-wrap">';
        $pts = get_post_types(array('public'=>true),'names' );
        $exc = array('page','wpp_c_block','attachment');
        echo '<select id="yg_postslct_type" name="yg_postslct_type" class="search-box SumoUnder sumo" multiple="multiple" placeholder="Select post type">';
        foreach ($pts as $typ) {
            if(!in_array($typ,$exc))
            echo '<option value="'.$typ.'">'.$typ.'</option>';
        }
        echo '</select>';

        require_once plugin_dir_path( __FILE__ ) . '../inc/yg-class-walker-tax.php';
        $walker = new YG_Walker_Category;
        $val_array = get_terms('category',array());
        echo '<select id="yg_postslct_cat" name="yg_postslct_cat" class="search-box SumoUnder sumo" multiple="multiple" placeholder="Select category">';
        if(!empty($val_array)){
            $walker = new YG_Walker_Category;
            print_r($walker->walk($val_array,0,array('style'=>'ddlist')));
        }
        echo '</select>';
        if(class_exists('WooCommerce')){
            $val_array = get_terms('product_cat',array());
            echo '<select id="yg_postslct_ecat" name="yg_postslct_ecat" class="search-box SumoUnder sumo" multiple="multiple" placeholder="Select ecomm category">';
            if(!empty($val_array)){
                $walker = new YG_Walker_Category;
                print_r($walker->walk($val_array,0,array('style'=>'ddlist')));
            }
            echo '</select>';
        }elseif(class_exists('WP_eCommerce')){
            $val_array = get_terms('wpsc_product_category',array());
            echo '<select id="yg_postslct_ecat" name="yg_postslct_ecat" class="search-box SumoUnder sumo" multiple="multiple" placeholder="Select ecomm category">';
            if(!empty($val_array)){
                $walker = new YG_Walker_Category;
                print_r($walker->walk($val_array,0,array('style'=>'ddlist')));
            }
            echo '</select>';
        }
        echo '<input type="text" id="yg_postslct_ttl" name="yg_postslct_ttl" value="" placeholder="Text in title" />';
        echo '<a href="" id="yg_postslct" class="button button-primary button-large" flt_post_nonce="'.wp_create_nonce('flt-get-post8642').'">Get post list</a>';
        if(is_array($mbp_val)){
            echo '<div class="fnl_slct"><input type="hidden" name="wppb_onpost_old" id="wppb_onpost_old" value="'.implode(',', $mbp_val).'" />';
            echo '<select name="wppb_onpost[]" id="wppb_onpost" class="wpp_loading wpp_init search-box SumoUnder" multiple="multiple" placeholder="Select post page">';
            foreach ($mbp_val as $pst) {
                echo '<option value="'.$pst.'" selected="selected"></option>';
            }
            echo '</select></div>';
        }
        echo '</div></div>';

        //pages
        echo '<div class="wpp_loc_set set_page">
        <h2 class="slct-ttl">Select pages</h2>';
        $checked = (isset($mb_val[0]) && $mb_val[0]=='all')?' checked="checked"':'';
        //TODO figure out way to cleare transients with wildcard
        //echo '<p><input type="checkbox" name="'.$mb_name.'[]" id="wpp-allpage" value="all"'.$checked.' /> All pages on the site.</p>
        echo '<div class="wpp-allpage-wrap">';
        echo '<a href="" id="yg_pageslct" class="button button-primary button-large" flt_page_nonce="'.wp_create_nonce('flt-get-post8642').'">Get page list</a>';
        if(is_array($mb_val)){
            echo '<div class="fnl_slct"><input type="hidden" name="wppb_onpage_old" id="wppb_onpage_old" value="'.implode(',', $mb_val).'" />';
            echo '<select name="wppb_onpage[]" id="wppb_onpage" class="wpp_loading wpp_init search-box SumoUnder" multiple="multiple" placeholder="Select page">';
            foreach ($mb_val as $pst) {
                if('posts' === get_option('show_on_front') && $pst=='hp')
                    echo '<option value="hp" selected="selected">Home Page</option>';
                elseif($pst=='sr')
                    echo '<option value="sr" selected="selected">Search</option>';
                else
                    echo '<option value="'.$pst.'" selected="selected"></option>';
            }
            echo '</select></div>';
        }
        echo '</div></div>';

        //taxonomy
        $mbt_val = get_post_meta($post_id, 'wppb_ontax');
        echo '<div class="wpp_loc_set set_tax">
        <h2 class="slct-ttl">Select taxonomy pages (archives)</h2>';
        $tx = get_taxonomies(array('public'=>true),'names');
        echo '<select id="yg_txslct" name="yg_txslct" class="search-box SumoUnder sumo" multiple="multiple" placeholder="Select taxonomy">';
        foreach ($tx as $tax) {
            echo '<option value="'.$tax.'">'.$tax.'</option>';
        }
        echo '</select>';
        echo '<a href="" id="yg_taxslct" class="button button-primary button-large" flt_tax_nonce="'.wp_create_nonce('flt-get-post8642').'">Get taxonomy list</a>';
        if(is_array($mbt_val)){
            echo '<div class="fnl_slct"><input type="hidden" name="wppb_ontax_old" id="wppb_ontax_old" value="'.implode(',', $mbt_val).'" />';
            echo '<select name="wppb_ontax[]" id="wppb_ontax" class="wpp_loading wpp_init search-box SumoUnder" multiple="multiple" placeholder="Select taxonomy">';
            foreach ($mbt_val as $pst) {
                echo '<option value="'.$pst.'" selected="selected"></option>';
            }
            echo '</select></div>';
        }
        echo '</div>';
    }

    public function yg_con_metabox($post_id,$mb_val,$mb_name){
        $wpp_js = array(
            'con_options' => $this->con_options,//for now hardcode options, TODO move to settings allow plugins to tap in
            'con_sub_options' => $this->con_sub_options
        );
        wp_localize_script('wpp-admin-js','wpp_js',$wpp_js);
        if(!is_array($mb_val))
            $mb_val = unserialize($mb_val);
        if(count($mb_val)==1 && count($mb_val[0])==2)
            $mb_val[0][0]=array('type'=>'');
        $this->build_con_block($mb_name,$mb_val);
        ?>
        <div id="wpp_conditions"><?php echo $this->conHtml ?></div>
        <?php
    }
    /**
     * Helper functions
     */
    public function build_con_block($name,$data){
        $i = 0;
        foreach ($data as $key => $con) {
            $_name = $name.'['.$i.']';
            if($con['type']=='operator'){
                $this->trackOpen[] = $_name;
                $this->conHtml .= $this->get_con_header($_name,$con,$i);
                unset($con['type']);
                unset($con['val']);
                if(!empty($con))
                    $this->conHtml .= $this->build_con_block($_name,$con);
            }
            if(isset($con['type']) && array_key_exists($con['type'],$this->con_sub_options))
                $this->conHtml .= $this->build_con_line($_name,$con,$con['type'],$i);
            if($i+1==count($data) && in_array($name,$this->trackOpen))
                $this->conHtml .= $this->close_con_header($name);
            $i++;
        }
    }
    public function get_con_header($name,$data,$indx){
        $slct1 = '';
        $slct2 = '';
        if($data['val']=='or' || $data['val']=='')
            $slct1 = ' selected="selected"';
        else
            $slct2 = ' selected="selected"';
        $close = ($name!='wpp_condition[0]')?'<div class="remove_row">x</div>':'';
        $html = '
        <div class="tp-new">
        <input type="hidden" name="'.$name.'[type]" value="operator" />
        If <select name="'.$name.'[val]">
        <option value="or"'.$slct1.'>any</option>
        <option value="and"'.$slct2.'>all</option>
        </select> of these conditions are true '.$close.'
        <div class="block rowdiv" indx-data="'.$indx.'">';

        return $html;
    }
    public function close_con_header($name){
        $html = '<div class="add_row" name-data="'.$name.'">+</div>
        </div>
        </div>';

        return $html;
    }
    public function build_con_line($name,$data,$type,$indx){
        $select = '<select name="'.$name.'[desc]" class="con_desc_dd">';
        foreach ($this->con_sub_options[$type] as $key => $value) {
            $slct = ($data['desc']==$key)?' selected="selected"':'';
            $select .= '<option value="'.$key.'"'.$slct.'>'.$value['desc'].'</option>';
        }
        $select .= '</select>';
        $html = '<div class="rowdiv tp-'.$type.'" indx-data="'.$indx.'">
        <input type="hidden" name="'.$name.'[type]" value="'.$data['type'].'" />
        Visitor '.$select.' <input type="text" name="'.$name.'[val]" value="'.$data['val'].'">';
        if(isset($data['val1']))
            $html .= ' <input type="text" name="'.$name.'[val1]" value="'.$data['val1'].'">';
        if(isset($data['val2']))
            $html .= ' <input type="text" name="'.$name.'[val2]" value="'.$data['val2'].'">';
        if(isset($data['val3']))
            $html .= ' <input type="text" name="'.$name.'[val3]" value="'.$data['val3'].'">';
        $html .= '<div class="remove_row">x</div></div>';

        return $html;
    }
    public function yg_create_wpp_postbnnr_meta_boxes($post, $metabox){
        foreach ($metabox['args']['bnnrs'] as $bnnr) {
            echo $bnnr->post_title.' <a href="/wp-admin/post.php?post='.$bnnr->post_id.'&action=edit">edit</a><br />';
        }
    }
    public function yg_wpp_get_post_bnnrs($post_id){
        global $wpdb;
        $q = "SELECT pm.post_id,p.post_title FROM $wpdb->postmeta `pm`,$wpdb->posts `p` WHERE pm.post_id = p.ID AND ((pm.meta_key='wppb_onpost' and pm.meta_value=".$post_id.") OR (pm.meta_key='wppb_onpage' and pm.meta_value=".$post_id."));";
        $res = $wpdb->get_results($q);
        return $res;
    }

    /**
     * general settings
     */
    public function yg_wpp_settings_page_add(){
        add_submenu_page( 'edit.php?post_type=wpp_c_block', 'General Settings', 'General Settings', 'manage_options', 'WPP_global_settings', array($this,'yg_wpp_global_settings_page') );
    }
    public function yg_wpp_global_settings_page(){
        ?>
        <div class="wrap">
        <h2>Global WPressonalize Settings</h2>
            <div class="cm_wrap_div">
            <form method="post" action="options.php">
            <p><label for="yg_wpp_show_bnnrs_edit">Show which banners appear on post or page in the admin edit page:</label>
            <input type="checkbox" name="yg_wpp_show_bnnrs_edit" value="yes"<?php echo (get_option('yg_wpp_show_bnnrs_edit')=='yes')?' checked="checked"':''; ?> /> Yes</p>
            <p><label for="yg_wpp_supprss_pu">Once popup is seen it should not show for X days (0 being not shown for session only):</label>
            <input type="number" name="yg_wpp_supprss_pu" value="<?php echo esc_attr(get_option('yg_wpp_supprss_pu')); ?>" min="0" max="180" /></p>
            </div>
            <?php
            if(is_admin()){
                settings_fields( 'wpp_gn_option_group' );   
                do_settings_sections( 'wpp_gn_option_group' );
                submit_button();
            }
            ?>
            </form>
            </div>
        </div>
        <?php
    }
    public function yg_wpp_register_settings() {
        //register our settings
        register_setting('wpp_gn_option_group','yg_wpp_show_bnnrs_edit');
        register_setting('wpp_gn_option_group','yg_wpp_supprss_pu');
    }

    /**
     * AJAX functions
     */
    public function yg_get_thumb_url() {
        $id = intval($_POST['obj_id']);
        $size = (isset($_POST['thmb_size']) && $_POST['thmb_size']!='')?$_POST['thmb_size']:'medium';
        $objId = (isset($_POST['elm_id']) && $_POST['elm_id']!='')?$_POST['elm_id']:'';
        
        $imgInfo = wp_get_attachment_image_src($id,$size);
        $idAttr ='';
        if($objId!='')
            $idAttr =' id="'.$objId.'_img"';
        $img='<img src="'.$imgInfo[0].'"'.$idAttr.' />';
        echo $img;
        exit;
    }
    public function yg_wpp_pos_get_pst(){
        if(check_admin_referer('flt-get-post8642')){
            $args = array(
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );
            $tax = array('relation' => 'AND');
            $hasTax = false;
            $posts = array();
            $srchTtl = (isset($_REQUEST['ttl']) && $_REQUEST['ttl']!='')?$_REQUEST['ttl']:'';
            $excldAfter = (isset($_REQUEST['eafter']) && $_REQUEST['eafter']!='')?$_REQUEST['eafter']:'page';
            if(isset($_REQUEST['exc']))
                $args['post__not_in'] = explode(',', $_REQUEST['exc']);
            if(isset($_REQUEST['typ']) && is_array($_REQUEST['typ']))
                $args['post_type'] = $_REQUEST['typ'];
            else
                $args['post_type'] = 'any';

            if(isset($_REQUEST['cat']) && is_array($_REQUEST['cat'])){
                $hasTax = true;
                foreach($_REQUEST['cat'] as $catslug){
                    $tax[] = array(
                        'taxonomy' => 'category',
                        'field' => 'slug',
                        'terms' => array($catslug),
                    );
                }
            }

            if(isset($_REQUEST['ecat']) && is_array($_REQUEST['ecat'])){
                $hasTax = true;
                foreach($_REQUEST['ecat'] as $catslug){
                    $tax[] = array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => array($catslug),
                    );
                }
            }
            if($hasTax)
                $args['tax_query'] = $tax;
            $psts = get_posts($args);

            if(isset($_REQUEST['typ']) && is_array($_REQUEST['typ']) && count($_REQUEST['typ'])==1 && $_REQUEST['typ'][0]=='page'){
                if('posts' === get_option('show_on_front'))
                    $posts['hp'] = 'Home Page';
                $posts['sr'] = 'Search';
            }
            foreach ($psts as $pst) {
                if($pst->post_type != $excldAfter && $pst->post_type != 'wpp_c_block' && ($srchTtl=='' || stripos($pst->post_title,$srchTtl)!==false))
                    $posts[$pst->ID] = $pst->post_title;
            }
            echo json_encode($posts);
        }
        exit;
    }
    public function yg_wpp_pos_get_ttls(){
        if(check_admin_referer('flt-get-post8642') && isset($_REQUEST['ids'])){
            $args = array(
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'post_type' => 'any',
                'post__in' => explode(',', $_REQUEST['ids'])
            );
            $psts = get_posts($args);
            foreach ($psts as $pst) {
                if($pst->post_type != 'wpp_c_block')
                    $posts[$pst->ID] = $pst->post_title;
            }
            echo json_encode($posts);
        }
        exit;
    }
    public function yg_wpp_pos_get_tax(){
        if(check_admin_referer('flt-get-post8642')){
            $args = array();
            $cats = array();
            if(isset($_REQUEST['exc']) && is_array($_REQUEST['exc']))
                $args['exclude'] = $_REQUEST['exc'];
            if(isset($_REQUEST['ids']) && is_array($_REQUEST['ids']))
                $args['include'] = $_REQUEST['ids'];
            if(isset($_REQUEST['taxs']) && is_array($_REQUEST['taxs']))
                $taxonomies = $_REQUEST['taxs'];
            else
                $taxonomies = get_taxonomies(array('public'=>true),'names');
            $terms = get_terms($taxonomies,$args);
            foreach ($terms as $term) {
                $cats[$term->term_id] = $term->name;
            }
            echo json_encode($cats);
        }
        exit;
    }
}