<?php
/*
Plugin Name: BlogImpressions
Description: BlogImpressions for Wordpress. Easy way to connect Google Analytics or Mixpanel.
Version: 2.2
Author: BlogImpressions
Author URI: http://www.blogimpressions.com
License: GNU GPL2
*/

define( 'BLOGIMPRESSIONS_URL', plugin_dir_url( __FILE__ ) ); // http://www.yoursite.com/wp-content/plugins/blogimpressions/
define( 'BLOGIMPRESSIONS_NOT_READY', __( 'Your BlogImpressions plugin is not ready yet, click <a href="admin.php?page=blogimpressions">here</a> to configure.', 'blogimpressions' ));

/**
 * $blogimpressions_version - current version and used on plugin update
 */
global $blogimpressions_version;
$blogimpressions_version = '1.0';

/**
 * register_activation_hook implementation
 *
 */
function blogimpressions_install()
{
    global $blogimpressions_version;

    // save current version for later use (on upgrade)
    add_option('blogimpressions_version', $blogimpressions_version);
}

register_activation_hook(__FILE__, 'blogimpressions_install');


if ( is_admin() ) {
	// Add admin notices.
	add_action('admin_notices', 'blogimpressions_admin_notices');
} else {
	add_action('wp_head', 'blogimpressions_googleanalytics');
	add_action('wp_head', 'blogimpressions_mixpanel_library');
	add_action('wp_footer', 'blogimpressions_mixpanel_event');
}


/**
 * Administration
 */

/**
 * admin_menu hook implementation
 */
function blogimpressions_admin_menu()
{
    add_menu_page(__('BlogImpressions', 'blogimpressions'), __('BlogImpressions', 'blogimpressions'), 'activate_plugins', 'blogimpressions', 'blogimpressions_page_handler', BLOGIMPRESSIONS_URL . 'includes/images/menu-icon.png');
}

add_action('admin_menu', 'blogimpressions_admin_menu');

/**
 * Form page handler checks is there some data posted and tries to save it
 * Also it renders basic wrapper in which we are calling meta box render
 */
function blogimpressions_page_handler()
{
    $message = '';

    // default $item
    $default = array(
		'tracking_id'	=> get_option('blogimpressions_tracking_id') ? get_option('blogimpressions_tracking_id') : '',
		'token_id'		=> get_option('blogimpressions_token_id') ? get_option('blogimpressions_token_id') : '',
    );

	if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
		$_REQUEST = stripslashes_deep( $_REQUEST );

		if (isset($_REQUEST['tracking_id'])) {
			$_REQUEST['tracking_id'] = htmlspecialchars(trim($_REQUEST['tracking_id']), ENT_QUOTES);
		}
		if (isset($_REQUEST['token_id'])) {
			$_REQUEST['token_id'] = htmlspecialchars(trim($_REQUEST['token_id']), ENT_QUOTES);
		}
		
		$item = shortcode_atts($default, $_REQUEST);

		$item_valid = blogimpressions_validate($item);
        if ($item_valid === true) {
			if (get_option('blogimpressions_tracking_id')) {
				update_option('blogimpressions_tracking_id', $item['tracking_id']);
			} else {
				add_option('blogimpressions_tracking_id', $item['tracking_id']);	
			}

			if (get_option('blogimpressions_token_id')) {
				update_option('blogimpressions_token_id', $item['token_id']);
			} else {
				add_option('blogimpressions_token_id', $item['token_id']);
			}

			$message = __('Successfully saved.', 'blogimpressions');
			
			if (!get_option('blogimpressions_registered')) {
				blogimpressions_register();
				wp_redirect('admin.php?page=blogimpressions');
			}

        } else {
            $notice = $item_valid;
        }
    
	} else {
        $item = $default;
    }

    ?>
<div class="wrap">
    <h2><?php _e('Welcome. Its time to connect Google Analytics or Mixpanel', 'blogimpressions')?></h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>

	<h3><?php _e('Google Analytics', 'blogimpressions')?></h3>

	<ol>
		<li>
			<?php _e('Sign in to your Google Analytics account, and select the Admin tab, select the property you\'re working with. Click Tracking Info > Tracking Code.', 'blogimpressions')?>
		</li>
		<li>
			<?php _e('Copy your "UA-0000000-0" Tracking ID.', 'blogimpressions')?>
		</li>
		<li>
			<input id="tracking_id" name="tracking_id" type="text" style="width: 15%" value="<?php echo esc_attr($item['tracking_id'])?>" size="20" maxlength="300" class="code" placeholder="<?php _e('UA-0000000-0', 'blogimpressions')?>">
			<?php _e('Paste it here and press "Save Changes".', 'blogimpressions')?>
			<br />
			<?php _e('Your done, see the stats in your Google Analytics account.', 'blogimpressions')?>
		</li>
	</ol>

	<h3><?php _e('Mixpanel', 'blogimpressions')?></h3>

	<ol>
		<li>
			<?php _e('Sign in to your Mixpanel account, and select Account tab > Projects.', 'blogimpressions')?>
		</li>
		<li>
			<?php _e('Copy your "000000f0f0d0f000f00f00000f000fff" Token ID.', 'blogimpressions')?>
		</li>
		<li>
			<input id="token_id" name="token_id" type="text" style="width: 30%" value="<?php echo esc_attr($item['token_id'])?>" size="40" maxlength="60" class="code" placeholder="<?php _e('000000f0f0d0f000f00f00000f000fff', 'blogimpressions')?>">
			<?php _e('Paste it here and press "Save Changes".', 'blogimpressions')?>
			<br />
			<?php _e('Your done, see the stats in your Mixpanel account.', 'blogimpressions')?>
		</li>
	</ol>       
	
	<input type="submit" value="<?php _e('Save Changes', 'blogimpressions')?>" id="submit" class="button-primary" name="submit">
    </form>

	<p class="submit">
		<?php _e('*By pressing "Save Changes" I confirm I have read and accepted BlogImpressions', 'blogimpressions')?> <a href="http://blogimpressions.com/index.php/terms-conditions-2/" target="_blank" style="font-weight: bold"><?php _e('terms of use', 'blogimpressions')?></a> 
	</p>
	<p>For support, mail us at: <a href="mailto:support@blogimpressions.com">support@blogimpressions.com</a></p>
</div>
<?php
}

/**
 * Validates data and retrieve bool on success
 * and error message(s) on error
 *
 * @param $item
 * @return bool|string
 */
function blogimpressions_validate($item)
{
    $messages = array();

    if (empty($item['tracking_id']) && empty($item['token_id'])) {
		$messages[] = __('Please fill form.', 'blogimpressions');
	}

    if (!empty($item['token_id']) && !ctype_alnum($item['token_id'])) {
		$messages[] = __('The Mixpanel Token ID is invalid (should be alpha numeric).', 'blogimpressions');
	}

    if (empty($messages)) {
		return true;
	} else {
		return implode('<br />', $messages);
	}
}

/**
 * Generate Google Analytics code for publishing
 */
function blogimpressions_googleanalytics() {
  $tracking_id = get_option('blogimpressions_tracking_id');
  if ($tracking_id) {
?>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '<?php echo $tracking_id ?>']);
_gaq.push(['_trackPageview']);
(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
<?php
  }
}

/**
 * Install Mixpanel library
 */
function blogimpressions_mixpanel_library() {
  $token_id = get_option('blogimpressions_token_id');
  if ($token_id) {
?>
<!-- start Mixpanel -->
<script type="text/javascript">(function(f,b){if(!b.__SV){var a,e,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(".");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;"undefined"!==typeof d?c=b[d]=[]:d="mixpanel";c.people=c.people||[];c.toString=function(b){var a="mixpanel";"mixpanel"!==d&&(a+="."+d);b||(a+=" (stub)");return a};c.people.toString=function(){return c.toString(1)+".people (stub)"};i="disable track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config people.set people.set_once people.increment people.append people.track_charge people.clear_charges people.delete_user".split(" ");
for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=f.createElement("script");a.type="text/javascript";a.async=!0;a.src="//cdn.mxpnl.com/libs/mixpanel-2.2.min.js";e=f.getElementsByTagName("script")[0];e.parentNode.insertBefore(a,e)}})(document,window.mixpanel||[]);
mixpanel.init("<?php echo $token_id; ?>");</script>
<!-- end Mixpanel -->
<?php
  }
}

/**
 * Mixpanel track an event
 */
function blogimpressions_mixpanel_event() {
	$token_id = get_option('blogimpressions_token_id');

	if ($token_id) {
		$event_label = esc_html(get_the_title());

		echo "<script type='text/javascript'>
		var rightNow = new Date();
		var humanDate = rightNow.toDateString();

		mixpanel.register_once({
			'first_wp_page': document.title,
			'first_wp_contact': humanDate
		});
		mixpanel.track(\"Viewed Page\", {
			'Page Name': ";
		$event_label == "" ? $page_name = "document.title" : $page_name = "'$event_label'";
		echo $page_name;
		echo ", 'Page URL': window.location.pathname
		});
		</script>";
	
		return true;
	} else {
		return false;
	}
}

/**
 * Admin Notices
 */
function blogimpressions_admin_notices() {
	if ($_GET['page'] != 'blogimpressions' && !get_option('blogimpressions_registered')) {
		echo '
			<div class="error blogimpressions" style="text-align: center; ">
				<p style="color: red; font-size: 14px; font-weight: bold;">' . 
					BLOGIMPRESSIONS_NOT_READY . '
				</p>
			</div>';

		// WP Pointers
		$seen_it = explode(',', get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true));
		if (!in_array('blogimpressions', $seen_it)) {
			blogimpressions_popup_setup();
		}

		
	}
}

/**
 * Admin Notices Popup
 */
function blogimpressions_popup_setup() { 
	wp_enqueue_style( 'wp-pointer' ); 
	wp_enqueue_script( 'jquery-ui' ); 
	wp_enqueue_script( 'wp-pointer' ); 
	wp_enqueue_script( 'utils' );
	?>
	<style>
		#blogimpressions-popup-header {background-color: #D81378; border-color: #D81378;}
		#blogimpressions-popup-header:before {color:#D81378;}
	</style>
	<script type="text/javascript">
		//<![CDATA[
		;(function($) {
			var setup = function() {
				$('#toplevel_page_blogimpressions').pointer({
						content: '<h3 id="blogimpressions-popup-header"><?php echo BLOGIMPRESSIONS_NOT_READY;?></h3>',
						position: {
							edge: 'left', // arrow direction
							align: 'center' // vertical alignment
						},
						pointerWidth: 350,
						close: function() {
							$.post(ajaxurl, {
								pointer: 'blogimpressions',
								action: 'dismiss-wp-pointer'
							});
						}
				}).pointer('open');
			};
			$(window).bind('load.wp-pointers', setup);
		})(jQuery);
		//]]>
	</script>
	<?php
}

/**
 * Register
 */
function blogimpressions_register() {
	add_option('blogimpressions_registered', 1);
}