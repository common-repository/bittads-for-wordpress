<?php
/**
 * @package BittAds for Wordpress
 * @author Skyao Yang
 * @version 1.2.0
 */
/*
Plugin Name: BittAds for Wordpress
Plugin URI: http://wordpress.org/#
Description: Wordpress plugin for managing your online ads with BittAds. It integrates seamlessly with your Wordpress installation. Fill in your account information and place your ads by adding widgets or tags.  
Author: Skyao Yang
Version: 1.2.0
Author URI: http://www.bittads.com/wordpress
*/

/** widget initialization moved into one function,
 *  registered below at the end of the file.
 */
function widget_bittads_wpwidget_init(){
	
	if ( !function_exists('register_sidebar_widget')
	      || !function_exists('register_widget_control') )
	            return;

	/** 
	 *  get_head_script
	 */   
	function get_head_script(){ 
		$optionsAdmin = get_option('bittads_wpwidget_admin');
		?>
	    <script type='text/javascript' src='http://server.bittads.com/js/bitt.js'></script> 
		<script type='text/javascript'>
				bitt.instance = '<?php echo $optionsAdmin['instance']; ?>';
				bitt.zone = '<?php echo $optionsAdmin['zone']; ?>';
		<?php if(is_page()){?>
			bitt.location = '<?php wp_title('', true, 'right'); ?>';
		<?php }else if(is_home()){?>
			bitt.location = 'Home';
		<?php }else if(is_archive()){?>
			bitt.location = 'Archive';
		<?php }else if(is_search()){?>
			bitt.location = 'Search';
		<?php }else if(is_author()){?>
			bitt.location = 'Author';
		<?php }else if(is_date()){?>
			bitt.location = 'Date';
		<?php }else if (is_single()){?>
			bitt.location = 'Article';
			bitt.keywords = [<?php
					$posttags = get_the_tags();
					if ($posttags) {
					foreach($posttags as $tag) {
                                          echo json_encode("tag:" . $tag->name) . ', '; 
                                        }
                                        foreach((get_the_category()) as $category) { 
                                          echo json_encode("category:" . $category->cat_name) . ', '; 
                                        }
					}
			?>];		
		<?php }else{?>
			bitt.location = 'Unknown';
		<?php }?>
		</script>
	    <?php 
	}
	
	/**
	 * getInfoBittads
	 * @param $user
	 * @param $pass
	 * @param $network
	 * @param $flag
	 * @return instance
	 */
	function getInfoBittads($user,$pass,$network,$flag){
		$fp = fsockopen("admin.bittads.com", 80, $errno, $errstr, 30);
		$out = "";
		if (!$fp) {
    		echo "$errstr ($errno)<br />\n";
		} else {
			if($flag == "zone"){
				$out .= "GET /$network/xml/zones HTTP/1.1\r\n";
			}else if($flag == "shape"){
				$out .= "GET /$network/xml/shapes HTTP/1.1\r\n";
			}else if($flag == "location"){
				$out .= "GET /$network/xml/locations HTTP/1.1\r\n";
			}else{
				$out .= "GET /xml/networks HTTP/1.1\r\n";
			}

			$out .= "Host: admin.bittads.com\r\n";
			$out .= "Authorization: Basic ".base64_encode("$user:$pass")."\r\n";
			$out .= "Connection: Keep-Alive\r\n\r\n";
	   		fputs($fp, $out);
	     	while(!feof($fp)){
	        	$response .= fgets($fp, 1024);
	   		}
	    	$hlen = strpos($response,"\r\n\r\n");
		    $header = substr($response, 0, $hlen);
		    $entity = substr($response, $hlen + 4);
		    
		    $optionAdmin = get_option('bittads_wpwidget_admin');
		    $blocks = "";
		    if($flag == "zone"){
				preg_match_all( "/\<zone\>(.*?)\<\/zone\>/", $entity, $blocks );
				$optionAdmin['zonesname'] = $blocks[1];
			}else if($flag == "shape"){
				preg_match_all( "/\<shape id=\"(.*?)\"\>(.*?)\<\/shape\>/", $entity, $blocks );
				$optionAdmin['shapesname'] = $blocks[2];
			}else if($flag == "location"){
				preg_match_all( "/\<location id=\"(.*?)\"\>(.*?)\<\/location\>/", $entity, $blocks );
				$optionAdmin['locationsname'] = $blocks[2];
			}else{
				preg_match_all( "/\<network\>(.*?)\<\/network\>/", $entity, $blocks );
				$optionAdmin['instance'] = $blocks[1][0];
			}
			update_option('bittads_wpwidget_admin', $optionAdmin);
		    fclose($fp);
		    return $optionAdmin['instance'];
		}
	}

	/**
	 * update data of 'bittads_wpwidget_admin'
	 */
	if($_GET['info'] == '1'){
        $user = strip_tags(stripslashes($_POST['bittads_wpwidget_username']));
        $pass = strip_tags(stripslashes($_POST['bittads_wpwidget_password']));
        $network = getInfoBittads($user,$pass,$network,'');
        getInfoBittads($user,$pass,$network,'zone');	
        getInfoBittads($user,$pass,$network,'shape');
        getInfoBittads($user,$pass,$network,'location');
		
	}    
	
	/** this function represents the admin setup page for this
	 *  plugin.
	 */
	function bittads_wpwidget_adminsection(){
		$optionAdmin = get_option('bittads_wpwidget_admin');
		
		if (isset($_POST['bittads_wpwidget_username'])) {
			$optionAdmin['username'] = strip_tags(stripslashes($_POST['bittads_wpwidget_username']));
		}

		if (isset($_POST['bittads_wpwidget_zone'])) {
			$optionAdmin['zone'] = strip_tags(stripslashes($_POST['bittads_wpwidget_zone']));
		}

		if (isset($_POST['bittads_wpwidget_instance'])) {
			$optionAdmin['instance'] = strip_tags(stripslashes($_POST['bittads_wpwidget_instance']));
		}
		
		if(isset($_POST['zonesname'])){
			$optionAdmin['zonesname'] = strip_tags(stripslashes($_POST['zonesname']));
		}
		update_option('bittads_wpwidget_admin', $optionAdmin);
		?>
		<div class="wrap">
		  <div id="poststuff">
		    <div id="bittadswpwidget">
		    <h2>BittAds for Wordpress options</h2>
		      <p><b>1. Setup your BittAds account</b><br />
		       <p>If you do not have a free BittAds account yet, please <a href="http://bittads.com/ht/get-started" target="_blank">sign up</a>.<br />
			   In case you have a BittAds account already, please fill in your Username and Password.</p>
		      <form name="bittadswpwidget_form_t" id="bittadswpwidget_form_t" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=bittads-wp.php&info=1">
		          <p><b>Username:</b><br />
		         	<input type=textfield size="60" name="bittads_wpwidget_username" id="bittads_wpwidget_username" value="<?php echo $optionAdmin['username']; ?>"> </p>
		         
		          <p><b>Password:</b><br />
		         <input type=password size="60" name="bittads_wpwidget_password" id="bittads_wpwidget_password" value=""> </p>
		          <?php 
				     if(!empty($optionAdmin)&&empty($optionAdmin['zonesname'])){
				     	echo "<p><font style=\"color:red\">Supplied credentials are invalid.</font><br /><p>";
				     }
		     		?>
		          <p><input type="button" name="bittads_wpwidget_submit" id="bittads_wpwidget_submit" value="Submit" onclick="javascript:getInfos();"> </p>
		     </form>
		     <form name="bittadswpwidget_form" id="bittadswpwidget_form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=bittads-wp.php&updated=true">
		         <p><b>Network:</b><br />
		         Your Network ID.
		          </p>
		         <input type=textfield size="60" name="bittads_wpwidget_instance" id="bittads_wpwidget_instance" value="<?php echo $optionAdmin['instance']; ?>">
		         <p><b>Zone:</b><br />
		         Please feel free to change the name of your blog.
				</p>
		       <select name="bittads_wpwidget_zone" id="bittads_wpwidget_zone" style="width:390px;">
		       		<option name="bittads_wpwidget_zone_t" value="0" >Please select Zone</option>
		       <?php
		       	$optionAdmin = get_option('bittads_wpwidget_admin');
		       	if(!empty($optionAdmin)){
		       		$num = count($optionAdmin['zonesname']);
			       	for($i=0;$i<$num;$i++){
			       		if($optionAdmin[zonesname][$i] == $optionAdmin['zone']){
			       			echo "<option name=\"bittads_wpwidget_zone_t\" value=\"".$optionAdmin[zonesname][$i]."\" selected>".$optionAdmin[zonesname][$i]."</option>";
			       		}else{
			       			echo "<option name=\"bittads_wpwidget_zone_t\" value=\"".$optionAdmin[zonesname][$i]."\">".$optionAdmin[zonesname][$i]."</option>";
			       		}
			       	}
		       	}
		       ?>
		       </select>
		        <script type="text/javascript">
		        	 function getInfos(){
						var user = document.getElementById("bittads_wpwidget_username").value;
						if(user == ""){
							alert("Username is required and can't be empty");
							document.getElementById("bittads_wpwidget_username").focus();
							return false;
						}
						var pass = document.getElementById("bittads_wpwidget_password").value;
						if(pass == ""){
							alert("Password is required and can't be empty");
							document.getElementById("bittads_wpwidget_password").focus();
							return false;
						}
						document.getElementById("bittadswpwidget_form_t").submit();
					}
		        </script>
		         <p><input type="submit" name="submit" value="Save" /></p>
		      </form>
		      <p>&nbsp;</p>
		      <p><b>2. Place your ads</b><br />
		      Start defining your ads by <a href="widgets.php">placing widgets in your sidebar</a><br /> 
		      <b>or</b><br />
		      adding banner locations in your code (replace 'skyscraper' with your ad type).<br />
		      <p><code style="background: #EEE; display: block; margin-right: 10px; padding: 15px; max-width:400px;">
		      &lt;script type='text/javascript'&gt;
		      <br>bitt.showAdOnLoad('<b>skyscraper</b>');
		      <br>&lt;/script&gt;
		      </code></p>
		      </p>
		    </div>
		  </div>
		</div>
		<?php
	}
	    
	
	/** this function install the callback-function bittads_wp_adminsection
	 *  for a admin setup page for this plugin
	 */
	function bittads_wpwidget_admin_menuitem(){
		if (function_exists('add_options_page')) {
			add_options_page('options-general.php', 'BittAds', 8, basename(__FILE__), 'bittads_wpwidget_adminsection');
		}
	}
	
	/**
	 * register Widget
	 */
	function registerWidget()
	{
		register_widget('WP_Widget_BittAds');
	}
	
	/**
	 * BittAds widget class
	 */
	class WP_Widget_BittAds extends WP_Widget {
		
		function WP_Widget_BittAds() {
			$widget_ops = array( 'description' => __( "Place ads from Bittads in your sidebar") );
			$this->WP_Widget('bittAds', __('BittAds'), $widget_ops);
		}
	
		/**
		 * widget
		 * @see wp-includes/WP_Widget#widget($args, $instance)
		 */
		function widget( $args, $instance ) {
			extract($args);
			$title = apply_filters('widget_title', empty($instance['title']) ? __('BittAds') : $instance['title']);
			echo $before_widget;
			if ( $title )
				echo $before_title . $title . $after_title;
			echo $this->get_banner_script($instance['shape']);
			echo $after_widget;
		}
	
		/** 
		 *  get_banner_script
		 */ 
		function get_banner_script($shape){ 
			$optionAdmin = get_option('bittads_wpwidget_admin');
			?>
			<script type='text/javascript'>
				<?php if(in_array($shape, $optionAdmin['shapesname'])){?>
				bitt.showAdOnLoad('<?php echo $shape?>');
				<?php }else{?>
				document.write('Please select shape.');
				<?php }?>
			</script>
		    <?php 
		}
		
		/**
		 * update
		 * @see wp-includes/WP_Widget#update($new_instance, $old_instance)
		 */
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['shape'] =  $new_instance['shape'];
			$instance['shape-othernamely'] = $new_instance['shape-othernamely'];
			return $instance;
		}
		
		/**
		 * form
		 * @see wp-includes/WP_Widget#form($instance)
		 */
		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
			$title = $instance['title'];
			$shape = $instance['shape'];
			$othernamely = $instance['shape-othernamely'];
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
			<p><label for='bittads-wpwidget-shape'><?php _e('Shape:'); ?></label></p>
			<p><select id="<?php echo $this->get_field_id('shape'); ?>" name="<?php echo $this->get_field_name('shape'); ?>"  style="width:200px;" onchange="javascript:changeShapesname('<?php echo $this->get_field_id('shape'); ?>','<?php echo $this->get_field_id('shape-othernamely'); ?>');">
			 <option id="<?php echo $this->get_field_id('shape-t'); ?>" name="<?php echo $this->get_field_name('shape-t'); ?>" value="0" >Please select Shape</option>
			       <?php
			       	$optionAdmin = get_option('bittads_wpwidget_admin');
			       	if(!empty($optionAdmin)){
				       	$num = count($optionAdmin['shapesname']);
				       	for($i=0;$i<$num;$i++){
				       		if($optionAdmin[shapesname][$i] == $shape){?>
				       			<option id="<?php echo $this->get_field_id('shape-t'); ?>" name="<?php echo $this->get_field_name('shape-t'); ?>" value="<?php echo $optionAdmin[shapesname][$i];?>" selected><?php echo $optionAdmin[shapesname][$i];?></option>
				       		<?php }else{?>
				       			<option id="<?php echo $this->get_field_id('shape-t'); ?>" name="<?php echo $this->get_field_name('shape-t'); ?>" value="<?php echo $optionAdmin[shapesname][$i];?>" ><?php echo $optionAdmin[shapesname][$i];?></option>
				       		<?php }
				       	}
				       	if($shape == 'other'){?>
				       		<option id="<?php echo $this->get_field_id('shape-t'); ?>" name="<?php echo $this->get_field_name('shape-t'); ?>" value="other" selected>Other,namely:</option>
				       		<input type="text" id="<?php echo $this->get_field_id('shape-othernamely'); ?>" name="<?php echo $this->get_field_name('shape-othernamely'); ?>" value="<?php echo $othernamely;?>" style="display:block;" />
				       	<?php }else{?>
				       		<option id="<?php echo $this->get_field_id('shape-t'); ?>" name="<?php echo $this->get_field_name('shape-t'); ?>" value="other" >Other,namely:</option>
				       		<input type="text" id="<?php echo $this->get_field_id('shape-othernamely'); ?>" name="<?php echo $this->get_field_name('shape-othernamely'); ?>" value="" style="display:none;" />
				       	<?php }
			       	}
			       ?>
			</select></p>
			<script type="text/javascript">
				function changeShapesname(shapeid,othernamelyid){
					var shape = document.getElementById(shapeid);
					var len = shape.length;
					for(i=0;i<len;i++){
						if(shape[i].value == 'other' && shape[i].selected==true){
							document.getElementById(othernamelyid).style.display = "block";
							document.getElementById(othernamelyid).focus();
							break;
						}else{
							document.getElementById(othernamelyid).style.display="none";
						}
					}
				}
			</script>
		<?php
		}
	}
	
	// add_action
	add_action( 'widgets_init', 'registerWidget');
	add_action('admin_menu', 'bittads_wpwidget_admin_menuitem');
	if ( function_exists('register_sidebar_widget')
	      || function_exists('register_widget_control') ){
		add_action('wp_print_scripts', 'get_head_script');
	}
}

add_action('plugins_loaded', 'widget_bittads_wpwidget_init');

?>
