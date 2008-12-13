<?php
/*
Plugin Name: Feedage Tracker
Plugin URI: http://www.feedage.com/wordpress/tracker.php
Description: This plugin will add subscription buttons to allow users to subscribe to your RSS Feed and allows you to track your rss feed usage on Feedage.com
Author: Mark Savoca
Version: 1.0.2
Author URI: http://www.feedage.com
*/

// Array of Feed readers and button images. Please do not change these because
// they need to match Feedage.com tracking. You can't add other readers by adding
// to this array.
global $readers;
$readers = array(
		"yahoo" => "http://us.i1.yimg.com/us.yimg.com/i/us/my/addtomyyahoo4.gif",
		"google" => "http://buttons.googlesyndication.com/fusion/add.gif",
		"aol" => "http://o.aolcdn.com/myfeeds/vis/myaol_cta1.gif",
		"msn" => "http://sc.msn.com/c/rss/rss_mymsn.gif",
		"newsgator" => "http://www.newsgator.com/images/ngsub1.gif",
		"netvibes" => "http://www.netvibes.com/img/add2netvibes.gif",
		"pageflakes" => "http://www.pageflakes.com/ImageFile.ashx?instanceId=Static_4&fileName=ATP_wht_91x17.gif",
		"bloglines" => "http://www.bloglines.com/images/sub_modern5.gif",
		"alesti" => "http://www.alesti.org/g/alesti-rss-reader.gif",
		"rsswebreader" => "http://www.WebFeedReader.com/images/WebReader_icon1.gif",
		"feedreader" => "http://www.feedreader.net/XML/images/RSS_FeedReaderLogo091bp.gif",
		"newsburst" => "http://i.i.com.com/cnwk.1d/i/newsbursts/btn/newsburst3.gif",
		"metarss" => "http://metarss.com/i/img/subtometa.png",
		"live" => "http://entimg.msn.com/i/rss/live.gif",
		"mojo" => "http://www.scenery.org/images/rojo.gif",
		"iping-it" => "http://www.scenery.org/images/iPing-it.gif",
		"feedagealerts" => "http://www.feedage.com/images/add2feedagealerts.gif");

// Create the Feedage.com tracker submenu
function feedage_admin_menu() {
	$hook = add_submenu_page('plugins.php', __('Feedage Tracker Plugin'), __('Feedage Tracker'), 'manage_options', 'ftconfig', 'feedage_admin_page');
}


// This function will use the feedage.com API to convert the feed URL to a feedage.com 
// feed ID. If the username is not provided or not a valid feedage.com username it will 
// be tracked as feedage forager.
function getFeedID($feed_url,$username) {
	
	// create a new cURL resource
	$ch = curl_init();
	
	// set URL 
	curl_setopt($ch, CURLOPT_URL, "http://www.feedage.com/url2id.php?url=$feed_url&un=$username");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	// grab URL 
	$feed_id = curl_exec($ch);

	// close cURL resource, and free up system resources
	curl_close($ch);
	
	return($feed_id);
}

// This function will generate the tracking buttons based on the option setting
// and return the generated HTML.
function genButtons() {
	global $readers;
	
	$state = getState();
	$feed_id = get_option('feedage_tracker_feed_id');
	$location = get_option('feedage_tracker_location');

	if($location == "sidebar") {
		$num_col = 1;
	} else {
		$num_col = 6;
	}
	
	// build the html
	$gen_out .= "<!-- Feedage.com RSS Feed Tracking -->\n";
	$gen_out .= "<table border='0' align='center' cellpadding='1' cellspacing='1'>\n";				  
	$gen_out .= "<tr align='left'>\n";
	$gen_out .= "<td><a href='http://www.feedage.com/feeds/$feed_id/$utitle'>\n";
	$gen_out .= "<img src='http://www.feedage.com/images/add2feedage.gif' width='69' height='17' border='0' alt='Preview on Feedage: $utitle'></a></td>\n";
	
	$cnt = 1;

	// Process each reader
	foreach ($readers as $reader => $image ) {
			
		if($cnt%$num_col == 0) {
			$gen_out .= "</tr>\n<tr align='left'>\n";
		}
		
		if($state[$reader] == "yes") {
			$gen_out .= "<td><a href='http://www.feedage.com/subscribe.php?fid=$feed_id&amp;s=$reader '>\n";
			$gen_out .= "<img src='$image'  alt='Subscribe with $reader'  ></a></td>\n";
			$cnt++;
		}
	}
	
	// end the gen out table
	$gen_out .= "</tr></table>\n";
	$gen_out .= "<!-- Feedage.com RSS Feed Tracking -->\n";
	
	return($gen_out);
}

// Read and return a hash of the state (yes/no) of the readers.
// returns $state[reader] = yes or blank
function getState() {
	global $readers;
	
	// read setting from options
	foreach ($readers as $reader => $image ) {
		$option_name = "feedage_tracker_$reader";
		$state[$reader] = get_option($option_name);
	}	
	return($state);
}

// This function will call genButtons and then print the results. Used in the preview of
// the admin page and to output the buttons for the blog content page.
function feedage_tracker_show_buttons() {
	print "<br />";
	print genButtons();
}

// This is the page used to configure the Feedage.com tracker. The form submits 
// back to this page.
function feedage_admin_page() {
	global $plugin_page;	
	global $readers;

	if($_POST['action'] == "Update") {
		
		// Save the username
		$username = $_POST['un'];
		update_option('feedage_username', $username);
		
		// Save the feed ID	
		$feed_id = getFeedID(get_bloginfo('rss2_url'), $username);
		update_option('feedage_tracker_feed_id', $feed_id);
		
		// save the state settings
		foreach ($readers as $reader => $image ) {
			$option_name = "feedage_tracker_$reader";
			$state[$reader] = $_POST[$reader];
			update_option($option_name, $state[$reader]);		
		}
		
		// Save the location
		$location = $_POST['location'];
		update_option('feedage_tracker_location', $location);
		
		print "Settings Updated<br>\n";
		
	} else {
		// Get the current setting so we can display the form
		$state = getState();
		$location = get_option('feedage_tracker_location');
		$username = get_option('feedage_username');
	}
	
	if($location == "sidebar") {
		$horiz_check ="";
		$vert_check ="checked";
	} else {
		$horiz_check ="checked";
		$vert_check ="";
	}

	$form_cnt = 1;
	
	// output the form
	$form_out = "<form action='plugins.php?page=$plugin_page' method='post'>";
	$form_out .= "<table width='80%' border='0' align='center' cellpadding='1' cellspacing='1'>\n";
	$form_out .= "<tr><td colspan='8' align='center'><br>&nbsp;<label FOR='un' style='font-weight: bold;'>Feedage Username:</label>\n";
	$form_out .= "<input name='un' type='text' id='un' value='$username'><br>&nbsp;</td></tr><tr>\n";
	
	foreach ($readers as $reader => $image ) {
		
		// create checks for check boxes
		if($state[$reader] == "yes") { 
			$reader_check[$reader] = "checked"; 
		} else {
			$reader_check[$reader] = "";
		}
		
		$form_out .= "<td width='16%'><div align='right'><img src='$image'  alt='$reader'></div></td>\n";
        $form_out .= "<td width='9%'> <div align='left'><input name='$reader' type='checkbox' id='$reader' value='yes' $reader_check[$reader] ></div></td>\n";
        
		if($form_cnt%4 == 0) {
			$form_out .= "</tr><tr>\n";
		}        
		$form_cnt++;		
	}
	
	$form_out .= "<tr><td colspan='8'><table width='80%' border='0' align='center' cellpadding='1' cellspacing='1'>\n";
	$form_out .= "<tr>\n";
	$form_out .= "<td width='31%'><div align='right'>Sidebar</div></td>\n";
	$form_out .= "<td width='5%'><input type='radio' name='location' value='sidebar' $vert_check ></td>\n";
	$form_out .= "<td width='36%'><div align='right'>Footer</div></td>\n";
	$form_out .= "<td width='28%'><input name='location' type='radio' value='footer' $horiz_check></td>\n";
	$form_out .= "</tr>\n";
	$form_out .= "</table>\n";
	$form_out .= "<p align='center'> \n";
	$form_out .= "<input name='action' type='submit' id='gen' value='Update'>\n";
	$form_out .= "</p></td></tr></table></form>\n";

	// display the form
	print $form_out;
	
	// display the buttons
	print "<center><h2>Preview:</h2></center>\n";
	print genButtons();

}

function feedage_tracker_activate() {
	global $readers;
	
	
	// Set default settings
	
	// Enable all readers
	foreach ($readers as $reader => $image ) {
		$option_name = "feedage_tracker_$reader";
		update_option($option_name, 'yes');		
	}
	
	// Put buttons in sidebar
	update_option('feedage_tracker_location', 'sidebar');
	
	// Save the feed ID	
	$feed_id = getFeedID(get_bloginfo('rss2_url'), "");
	update_option('feedage_tracker_feed_id', $feed_id);
	
}

// Setup the hooks into Wordpress
add_action( 'admin_menu', 'feedage_admin_menu' );

// If the tracking buttons are configured for the sidebar, add the action to
// the sidebar. Else it goes on the footer.
if(get_option('feedage_tracker_location') == 'sidebar'){
	add_action('wp_meta', 'feedage_tracker_show_buttons');
} else {
	add_action('wp_footer', 'feedage_tracker_show_buttons');
}

register_activation_hook( __FILE__, 'feedage_tracker_activate' );

?>
