<?php
/**
 *
 * @link              http://www.ysgdesign.com/
 * @since             1.0.0
 * @package           WordPressonalize
 *
 * @wordpress-plugin
 * Plugin Name:       WordPressonalize
 * Plugin URI:        http://www.ysgdesign.com/
 * Description:       Personalize your website delivering unique content to visitors based on past views and actions. Or use this plugin to easily insert content on your site in any theme.
 * Version:           1.0.0
 * Author:            Yair Gelb
 * Author URI:        http://www.ysgdesign.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*  Copyright 2016 yair gelb
*/

class wpressonalize {
    /**
     * @access   protected
     * @var      string    $plugin_name
     */
    protected $plugin_name;

    /**
     * @access   protected
     * @var      string    $version
     */
    protected $version;

    /**
     * @access   protected
     * @var      array    $banners
     */
    protected $banners;

    /**
     * @access   protected
     * @var      string    $topbanners
     */
    protected $topbanners;

    /**
     * hold banners for manual placement
     * @access   static
     * @var      array    $bnnrStatic
     */
    static $bnnrStatic;

    /**
     * Condition type options
     * @access   protected
     * @var      array    $con_sub_options
     */
    protected $con_sub_options;

    /**
     * @access   protected
     * @var      string    $con_js
     */
    protected $con_js;
    

	public function __construct() {
        global $wpdb;
        $this->plugin_name = 'WordPressonalize';
        $this->version = '1.0.0';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $curr_ver = get_option( "yg_wpp_version" );
        if($curr_ver != $this->version) {
            $table_name = $wpdb->prefix . 'wpptransients';
            $sql = "CREATE TABLE $table_name (
              id bigint(20) NOT NULL AUTO_INCREMENT,
              transient varchar(24) NOT NULL,
              UNIQUE KEY id (id)
              );";
            
            dbDelta($sql);
            update_option("yg_wpp_version",$this->version);
        }

        $this->con_sub_options = array(
            'location'=>array(
                'ics'=>array('desc'=>'is in city, state','placeholder'=>array('city','state'),'js'=>'yg_wpp_city=="{{val}}" && yg_wpp_region_code=="{{val1}}"'),
                'ncs'=>array('desc'=>'is not in city, state','placeholder'=>array('city','state'),'js'=>'yg_wpp_city!="{{val}}" && yg_wpp_region_code!="{{val1}}"'),
                'iac'=>array('desc'=>'is in area code','placeholder'=>array('area code'),'js'=>'yg_wpp_area_code=={{val}}'),
                'nac'=>array('desc'=>'is not in area code','placeholder'=>array('area code'),'js'=>'yg_wpp_area_code!={{val}}'),
                'icn'=>array('desc'=>'is in country','placeholder'=>array('country'),'js'=>'yg_wpp_country_code=="{{val}}"'),
                'ncn'=>array('desc'=>'is not in country','placeholder'=>array('country'),'js'=>'yg_wpp_country_code!="{{val}}"'),
                'ird'=>array('desc'=>'is within radius','placeholder'=>array('radius','km/mi','longtitude','latitude'),'js'=>'getDistance({{val2}},{{val3}},yg_wpp_latitude,yg_wpp_longitude,"{{val1}}")<{{val}}'),
                'nrd'=>array('desc'=>'is outside radius','placeholder'=>array('radius','longtitude','latitude'),'js'=>'getDistance({{val2}},{{val3}},latitude,longitude,"{{val1}}")>{{val}}')
            ),
            'history'=>array(
                'vct'=>array('desc'=>'visited categories','placeholder'=>array('category id/s'),'js'=>'checkHasKys(yg_wpp_tx,"{{val}}")'),
                'nvct'=>array('desc'=>'didn\'t visit categories','placeholder'=>array('category id/s'),'js'=>'!checkHasKys(yg_wpp_pt,"{{val}}")'),
                'vpg'=>array('desc'=>'viewed pages','placeholder'=>array('page id/s'),'js'=>'checkHasKys(yg_wpp_pt,"{{val}}")'),
                'nvpg'=>array('desc'=>'didn\'t view pages','placeholder'=>array('page id/s'),'js'=>'!checkHasKys(yg_wpp_pt,"{{val}}")'),
                'vpst'=>array('desc'=>'viewed post, custom post','placeholder'=>array('post id/s'),'js'=>'checkHasKys(yg_wpp_pt,"{{val}}")'),
                'nvpst'=>array('desc'=>'didn\'t view post, custom post','placeholder'=>array('post id/s'),'js'=>'!checkHasKys(yg_wpp_pt,"{{val}}")'),
                'vmtpst'=>array('desc'=>'viewed more then','placeholder'=>array('number of distinct posts'),'js'=>'Object.keys(yg_wpp_pt).length > {{val}}'),
                'vmtpg'=>array('desc'=>'viewed more then','placeholder'=>array('number of distinct pages'),'js'=>'Object.keys(yg_wpp_pg).length > {{val}}'),
                'src'=>array('desc'=>'searched','placeholder'=>array('term'),'js'=>'checkHasKys(yg_wpp_sr,"{{val}}")')
            ),
            'interaction'=>array(
                'omt'=>array('desc'=>'ordered more then','placeholder'=>array('number of orders'),'js'=>'yg_wpp_ordernum.length > {{val}}'),
                'olt'=>array('desc'=>'ordered less then','placeholder'=>array('number of orders'),'js'=>'yg_wpp_ordernum.length < {{val}}'),
                'oprod'=>array('desc'=>'ordered products','placeholder'=>array('product id/s'),'js'=>'checkHasKys(yg_wpp_purch,"{{val}}")'),
                'noprod'=>array('desc'=>'didn\'t ordered products','placeholder'=>array('product id/s'),'js'=>'!checkHasKys(yg_wpp_purch,"{{val}}")'),
                'ptm'=>array('desc'=>'total purchases more then','placeholder'=>array('amount'),'js'=>'yg_wpp_totalpurch > {{val}}'),
                'ptl'=>array('desc'=>'total purchases less then','placeholder'=>array('amount'),'js'=>'yg_wpp_totalpurch < {{val}}'),
                'vmtpg'=>array('desc'=>'viewed more then','placeholder'=>array('number of product pages'),'js'=>'yg_wpp_product > {{val}}')
            ),
            'device'=>array(
                //'udv'=>array('desc'=>'is on','placeholder'=>array('desktop/tablet/mobile')),
                'ubrw'=>array('desc'=>'has browser','placeholder'=>array('browser name'),'js'=>'yg_wpp_browser=="{{val}}"'),
                'uos'=>array('desc'=>'uses operation system','placeholder'=>array('os name'),'js'=>'yg_wpp_os=="{{val}}"')
            )
        );

        $this->banners = array();
        $this->topbanners = '';

        add_action('wp', array(&$this,'yg_wpp_page_setup'));
        if(is_admin()){
            require_once plugin_dir_path( __FILE__ ) . 'admin/wpp-admin.php';
            $wpp_admin = new yg_wpp_admin($this->version,$this->con_sub_options);
        }
        add_shortcode('wpp_block', array(&$this,'get_wpp_block_html'));
        add_filter('template_include',array(&$this,'wpp_open_outputbuffer'),1);
        add_filter('shutdown',array(&$this,'wpp_close_outputbuffer_with_top_bnnrs'),0);

        add_action('wp_ajax_yg_loc_data', array(&$this,'yg_loc_data'));
        add_action('wp_ajax_nopriv_yg_loc_data', array(&$this,'yg_loc_data'));
        add_action('wp_ajax_yg_display_bnnr', array(&$this,'yg_display_bnnr'));
        add_action('wp_ajax_nopriv_yg_display_bnnr', array(&$this,'yg_display_bnnr'));
	}

	/**
     * Adds view data on each page
     * type: pt-post/page/custom post,tx-taxonomy,sr-search
     */
	public function yg_wpp_page_setup(){
        global $wp_query;
        if(is_admin())
            return;
        
        $loc = array();
        $iv = substr(str_pad($_SERVER['HTTP_HOST'],16,'y'),0,16);
        $testMode = (isset($_GET['testbnnr']))?openssl_decrypt($_GET['testbnnr'],'AES-128-CBC','wpp',0,$iv):false;
        $bnnrs = array();
        if(is_single()){
            $loc['type'] = 'pt';
            $loc['page_id'] = $wp_query->post->ID;
            $loc['ptype'] = ($wp_query->post->post_type=='wpsc-product')?'product':$wp_query->post->post_type;
            $loc['name'] = $wp_query->query['name'];
            $bnnrs = $this->getCustomBanner($loc['page_id'],'wppb_onpost',$testMode);
        }
        if(is_home()){
            $loc['type'] = 'pg';
            $loc['page_id'] = 'hp';
            $loc['ptype'] = 'page';
            $loc['name'] = 'home';
            $bnnrs = $this->getCustomBanner($loc['page_id'],'wppb_onpage',$testMode);
        }
        if(is_page()){
            $loc['type'] = 'pg';
            $loc['page_id'] = $wp_query->post->ID;
            $loc['ptype'] = 'page';
            $loc['name'] = $wp_query->post->post_name;
            if($loc['name']=='checkout' && isset($wp_query->query['order-received'])){//WooCommerce
                $loc['name'] = 'order-confirmation';
                if(class_exists('WC_Order')){
                    $order = new WC_Order($wp_query->query['order-received']);
                    $prods = array();
                    if(isset($order) && sizeof($order->get_items()) > 0){
                        foreach($order->get_items() as $item){
                            $prods[$item['item_meta']['_product_id'][0]] = $item['item_meta']['_qty'][0];
                        }
                        $loc['purch'] = $prods;
                        $loc['totalpurch'] = $order->get_total();
                        $loc['ordernum'] = $order->get_order_number();
                    }
                }
            }
            if($wp_query->query_vars['pagename']=='transaction-results' && preg_match( "/\[transactionresults\]/",$wp_query->queried_object->post_content )){//WPeCommerce
                global $wpdb;
                $sessionid = '';
                if( isset( $_GET['sessionid'] ) )
                    $sessionid = $_GET['sessionid'];

                if( !isset( $_GET['sessionid'] ) && isset( $_GET['ms'] ) )
                    $sessionid = $_GET['ms'];

                $selected_gateway = wpsc_get_customer_meta( 'selected_gateway' );
                if( $selected_gateway && in_array( $selected_gateway, array( 'paypal_certified', 'wpsc_merchant_paypal_express' ) ) )
                    $sessionid = wpsc_get_customer_meta( 'paypal_express_sessionid' );

                if( isset( $_REQUEST['eway'] ) && '1' == $_REQUEST['eway'] )
                    $sessionid = $_GET['result'];

                $loc['name'] = 'order-confirmation';
                if($sessionid != ''){
                    $q = "SELECT i.purchaseid,o.totalprice,GROUP_CONCAT(i.prodid,'=',i.quantity SEPARATOR '||') AS `items` FROM wp_store.wp_wpsc_purchase_logs `o`,wp_store.wp_wpsc_cart_contents `i` WHERE o.id=i.purchaseid AND sessionid='".$sessionid."' GROUP BY o.id;";
                    $res = $wpdb->get_row($q);
                    $prods = array();
                    foreach (explode('||', $res->items) as $item) {
                        $itemData = explode('=',$item);
                        if(count($itemData)==2)
                            $prods[$itemData[0]] = $itemData[1];
                    }
                    $loc['purch'] = $prods;
                    $loc['totalpurch'] = $res->totalprice;
                    $loc['ordernum'] = $res->purchaseid;
                }
            }
            $bnnrs = $this->getCustomBanner($loc['page_id'],'wppb_onpage',$testMode);
        }
        if(is_archive()){
            if(class_exists('WP_eCommerce') && is_tax() && isset($wp_query->query['wpsc_product_category'])){
                $pattern = '/wp_term_relationships.term_taxonomy_id IN \(([0-9])+/';
                preg_match($pattern,$wp_query->request,$catId);
                $loc['type'] = 'tx';
                $loc['page_id'] = (isset($catId[1]))?$catId[1]:'0';
                $loc['ttype'] = 'category';
                $loc['name'] = $wp_query->query_vars['wpsc_product_category'];
            }elseif(is_tax()){//queried_object
                $loc['type'] = 'tx';
                $loc['page_id'] = $wp_query->queried_object->term_id;
                $loc['ttype'] = $wp_query->queried_object->taxonomy;
                $loc['name'] = $wp_query->queried_object->slug;
            }elseif(is_category()){
                $loc['type'] = 'tx';
                $loc['page_id'] = $wp_query->query_vars['cat'];
                $loc['ttype'] = 'category';
                $loc['name'] = $wp_query->query_vars['category_name'];
            }elseif(is_tag()){
                $loc['type'] = 'tx';
                $loc['page_id'] = $wp_query->query_vars['tag_id'];
                $loc['ttype'] = 'tag';
                $loc['name'] = $wp_query->query_vars['tag'];
            }
            $bnnrs = $this->getCustomBanner($loc['page_id'],'wppb_ontax',$testMode);
        }
        if(is_search()){
            $loc['type'] = 'sr';
            $loc['page_id'] = $wp_query->query['s'];
            $bnnrs = $this->getCustomBanner('sr','wppb_onpage',$testMode);
        }
        $loc['os'] = $this->yg_get_os();
        $loc['browser'] = $this->yg_get_browser();
        $loc['ip'] = $_SERVER['REMOTE_ADDR'];
        $loc['nn'] = wp_create_nonce('wpp_getloc_byip'.$_SERVER['REMOTE_ADDR']);
        $loc['ajx'] = admin_url('admin-ajax.php');
        $loc['pu_cookie'] = (int)get_option('yg_wpp_supprss_pu');
		wp_register_script('yg_wpp_js', plugin_dir_url( __FILE__ ) . 'js/yg_wpp.min.js', array('jquery'));
        wp_localize_script('yg_wpp_js','ygwpp',$loc);
        wp_enqueue_script('yg_wpp_js');
        wp_register_style('wpp_fe_css', plugins_url('css/wpp_fe.min.css',__FILE__ ));
        wp_enqueue_style('wpp_fe_css');
        foreach ($bnnrs as $bnnr) {
            if($bnnr->post_id!=''){//make sure we have a banner
                $bnnr_id = $bnnr->post_id;
                $attr = array();
                foreach (explode('||', $bnnr->attr) as $value) {
                    $vals = explode('=', $value);
                    $attr[$vals[0]] = $vals[1];
                }
                $attr['content'] = $bnnr->post_content;
                $this->banners[$attr['wppl_loc']][$bnnr_id] = $attr;
                //store js conditions
                if(isset($attr['wpp_condition']) && isset($attr['wppb_async']) && $attr['wppb_async']=='asyn')
                    $this->con_js .= $this->yg_set_js($bnnr_id,unserialize($attr['wpp_condition']));
            }
        }
        
        foreach ($this->banners as $loc => $_bnnrs) {
            if($loc == 'pu')
                add_action('wp_footer', array(&$this,'displayPopUpBnnr'), 1);
            if($loc == 'hd')
                add_action('wp_head', array(&$this,'displayBnnerTop'), 1);
            if($loc == 'bc')
                add_action('loop_start', array(&$this,'displayBnnerBc'), 1);
            if($loc == 'ac')
                add_action('loop_end', array(&$this,'displayBnnerAc'), 1);
            if($loc == 'bp')
                add_filter('the_content', array(&$this,'displayBnnerBp'));
            if($loc == 'ap')
                add_filter('the_content', array(&$this,'displayBnnerAp'));
            if($loc == 'mn')
                self::$bnnrStatic = $this->banners['mn'];
            if(strpos($loc, 'wdg|') !== false)
                add_filter('dynamic_sidebar_params',array(&$this,'displayBnnerSideBar'));
        }
        if($this->con_js != '')
            add_action('wp_footer',array(&$this,'yg_set_footer_script'));
        //add_filter('dynamic_sidebar_params',array(&$this,'check_sidebar_params'));
        
	}

    /**
     * Open and close ob to include banners after body tag if any
     */
    public function wpp_open_outputbuffer($template) {
        ob_start();
        return $template;
    }

    public function wpp_close_outputbuffer_with_top_bnnrs() {
        $insert =  '<div id="wpp-top-banner">'.$this->topbanners.'</div>';
        $content = ob_get_clean();
        $content = preg_replace('#<body([^>]*)>#i',"<body$1>".str_replace('$','\$',$insert),$content);
        echo $content;
    }
    /**
     * Helper functions
     */
    public function check_sidebar_params($params){ 
        global $wp_registered_widgets,$wp_registered_sidebars;
        if( isset( $params[0]['widget_name'] ))
            echo $params[0]['widget_name'];
        return $params;
    }
    public function yg_get_os(){ 
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $os_platform = "Unknown OS Platform";

        $os_array = array(
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        );

        foreach ($os_array as $regex => $value){
            if (preg_match($regex, $user_agent)){
                $os_platform = $value;
            }
        }   

        return $os_platform;
    }

    public function yg_get_browser(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser = "Unknown Browser";

        $browser_array = array(
            '/msie|trident/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/edge/i' => 'Edge',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser'
        );

        foreach ($browser_array as $regex => $value){
            if (preg_match($regex, $user_agent)){
                $browser = $value;
            }
        }

        return $browser;
    }

    public function getCustomBanner($id,$type,$testMode=false){
        global $wpdb;
        if(!$testMode && $res = get_transient('yg_wpp_cp_'.$type.'_'.$id)){
            //$res = get_transient('yg_wpp_cp_'.$type.'_'.$id);
        }else{
            $q = "SELECT pm.post_id,GROUP_CONCAT(pm2.meta_key,'=',pm2.meta_value SEPARATOR '||') AS `attr`,p.post_content FROM $wpdb->postmeta `pm`
    INNER JOIN $wpdb->postmeta `pm2` ON pm.post_id = pm2.post_id and pm2.meta_key not in('_edit_last','_edit_lock','wppb_onpage','wppb_onpost','wppb_ontax')
    INNER JOIN $wpdb->posts `p` ON pm.post_id = p.ID where pm.meta_key='$type' and pm.meta_value='$id' ";

            if(!$testMode)
                $q .= "and p.post_status='publish' and NOT EXISTS (SELECT meta_value FROM $wpdb->postmeta WHERE post_id=pm.post_id AND meta_key='wppt_testmode') group by pm.post_id;";
            else
                $q .= "and (p.ID=".$testMode." or (p.post_status='publish' and NOT EXISTS (SELECT meta_value FROM $wpdb->postmeta WHERE post_id=pm.post_id AND meta_key='wppt_testmode'))) group by pm.post_id;";


            $res = $wpdb->get_results($q);
            if(count($res)>0 && $res[0]->post_id!=''){
                if(!$testMode){
                    set_transient('yg_wpp_cp_'.$type.'_'.$id,$res,0);
                    $wpdb->insert($table_name = $wpdb->prefix . 'wpptransients',array('transient' => $type.'_'.$id),array('%s'));
                }
            }else{
                $res = array();
            }
        }
        return ($res);
    }
    public function displayPopUpBnnr(){
        echo $this->displayBnners($this->banners['pu'],'pu',"wpp-popup-bnnr");
    }
    public function displayBnnerTop(){
        $this->topbanners .= $this->displayBnners($this->banners['hd'],'hd');
    }
    public function displayBnnerBc(){
        global $wp_query;
        static $lpIndx = 0;
        if(is_main_query() && $lpIndx==0 && !is_404() && isset($this->banners['bc'])){
            echo $this->displayBnners($this->banners['bc'],'bc');
            $lpIndx++;
        } 
    }
    public function displayBnnerAc(){
        global $wp_query;
        static $lpIndx = 0;
        if(is_main_query() && $lpIndx==0 && !is_404() && isset($this->banners['ac'])){
            echo $this->displayBnners($this->banners['ac'],'ac');
            $lpIndx++;
        }
    }
    public function displayBnnerBp($content){
        return $this->displayBnners($this->banners['bp'],'bp').$content;
    }
    public function displayBnnerAp($content){
        return $content.$this->displayBnners($this->banners['ap'],'ap');
    }
    public function displayBnnerSideBar($params){
        $activeWdgts = get_option('sidebars_widgets');
        if(isset($this->banners['wdg|'.$params[0]['id']]) && isset($activeWdgts[$params[0]['id']][0]) && $activeWdgts[$params[0]['id']][0]==$params[0]['widget_id']){
            echo $this->displayBnners($this->banners['wdg|'.$params[0]['id']]);
        }
        return $params;
    }
    public function get_wpp_block_html($atts){
        extract( shortcode_atts( array(
            'id' => ''
        ), $atts ));
        if(isset($this->banners['sc'][$id]))
            return $this->displayBnners(array($this->banners['sc'][$id]));
    }
    public static function displayManualBnners($id){
        if(isset(self::$bnnrStatic[$id]))
        return self::displayBnners(array(self::$bnnrStatic[$id]));
    }
    public static function displayBnners($bnnrs,$type='',$class=''){
        $html = '';
        if($class!='')
            $class = ' '.$class;
        if(is_array($bnnrs)){
            foreach ($bnnrs as $key => $value) {
                if($value['wppb_async']=='inln'){
                    $style = self::yg_bnnr_style($value);
                    
                    if($style!='')
                        $style = ' style="'.$style.'"';
                    if($type=='pu'){
                        $bgstyle = (isset($value['wpppust_background-color']) && $value['wpppust_background-color']!='')?'background-color:'.$value['wpppust_background-color'].';':'';
                        $bgstyle .= (isset($value['wpppust_opacity']) && $value['wpppust_opacity']!='')?'opacity:'.($value['wpppust_opacity']/100).';':'';
                        $bgstyle = ($bgstyle != '')?' style="'.$bgstyle.'"':'';
                        $html .= '<div class="wpp-popup-bg" data-id="'.$key.'"'.$bgstyle.'></div>';
                    }

                    $html .= '<div class="wppdiv wpp-bc'.$class.'"'.$style.' data-id="'.$key.'">';
                    $html .= do_shortcode($value['content']);//apply_filters('the_content', $value['content']);
                    $html .= '</div>';
                }else{
                    if($type=='pu'){
                        $bgstyle = (isset($value['wpppust_background-color']) && $value['wpppust_background-color']!='')?'background-color:'.$value['wpppust_background-color'].';':'';
                        $bgstyle .= (isset($value['wpppust_opacity']) && $value['wpppust_opacity']!='')?'opacity:'.($value['wpppust_opacity']/100).';':'';
                        $bgstyle = ($bgstyle != '')?' style="'.$bgstyle.'"':'';
                        $html .= '<div class="wpp-popup-bg" data-id="'.$key.'"'.$bgstyle.'></div>';
                    }
                    $html .= '<div class="wppdiv wpp-bc wppajaxload'.$class.'" data-id="'.$key.'"></div>';
                }
            }
        }
        return $html;
    }
    public static function yg_bnnr_style($attrs){
        $style = '';
        foreach ($attrs as $k=>$val) {
            if(stripos($k,'wppst_')!==false)
                $style .= str_replace('wppst_', '', $k).':'.$val.';';
            if($k=='wppst_border-width' && $val!='')
                $style .= 'border-style:solid;';
            if($k=='wppimg_bgimg_img' && $val!=''){
                $bgInfo = wp_get_attachment_image_src($val,'medium');
                $style .= 'background-image:url('.$bgInfo[0].');';
            }
            if($k=='wppimg_bgimg_pos' && $val!=''){
                switch ($val) {
                    case 'scl':
                        $style .= 'background-repeat:no-repeat;background-size: cover;';
                        break;
                    case 'onc':
                        $style .= 'background-repeat:no-repeat;';
                        break;
                }
            }
        }
        return $style;
    }
    public function yg_set_js($bnnr_id,$js){
        $jsArray = unserialize($js);
        $jsCon = 'true';
        if(is_array($jsArray) && isset($jsArray[0]) && !empty($jsArray[0]) && count($jsArray[0])>2){
            //$jsCon = $this->yg_break_to_js($jsArray[0]);
            require_once plugin_dir_path( __FILE__ ) . 'inc/yg-class-walker-array.php';
            $walker = new YG_Walker_Array;
            $jsCon = $walker->walk($jsArray,0,array('defenition'=>$this->con_sub_options));
        }
        $_js = 'function showBn'.$bnnr_id.'(){
            return '.$jsCon.';
        };';
        return $_js;
    }
    public function yg_break_to_js(){

    }
    public function yg_set_footer_script(){ ?>
    <script type="text/javascript">
    <?php echo $this->con_js; ?>
    </script>
    <?php }

    /**
     * AJAX functions
     */
    public function yg_loc_data() {
        check_ajax_referer('wpp_getloc_byip'.$_SERVER['REMOTE_ADDR']);
        if(isset($_REQUEST['ip']))
            echo json_encode(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_REQUEST['ip'])));
        exit;
    }
    public function yg_display_bnnr() {
        global $wpdb;
        check_ajax_referer('wpp_getloc_byip'.$_SERVER['REMOTE_ADDR']);
        if(isset($_REQUEST['id'])){
            $q = "SELECT GROUP_CONCAT(pm2.meta_key,'=',pm2.meta_value SEPARATOR '||') AS `attr`,p.post_content FROM $wpdb->posts `p`,$wpdb->postmeta `pm`
            INNER JOIN $wpdb->postmeta `pm2` ON pm.post_id = pm2.post_id and pm2.meta_key not in('_edit_last','_edit_lock','wppb_onpage','wppb_onpost','wppb_ontax','wppb_async','wppl_loc','wpp_condition')
            where pm.post_id = p.ID and pm.meta_key='wppb_async' and pm.meta_value='asyn' and pm.post_id=".$_REQUEST['id']." group by pm.post_id;";

            $res = $wpdb->get_row($q);
            $style = '';
            $attr = array();
            foreach (explode('||', $res->attr) as $value) {
                $vals = explode('=', $value);
                $attr[$vals[0]] = $vals[1];
            }
            if(is_array($attr))
                $style = $this->yg_bnnr_style($attr);
            $cntnt = do_shortcode($res->post_content);
            echo json_encode(array('style'=>$style,'cntnt'=>$cntnt));
        }  
        exit;
    }
}
$wpressonalize = new wpressonalize();