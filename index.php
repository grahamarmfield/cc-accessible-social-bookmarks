<?php
/*------------------------------------------------------------------------------
Plugin Name: Accessible Social Bookmarks
Plugin URI: http://www.coolfields.co.uk
Description: This plugin will add social bookmarking links to a WordPress site. 
Author: Graham Armfield
Version: 0.2
Author URI: http://www.coolfields.co.uk
------------------------------------------------------------------------------*/ 
// include() or require() any necessary files here...
//include_once('includes/admin-page.php');

// Private Internal Functions
   
function _cc_acc_soc_book_getCustomPostTypes() {
// Function to find all public custom post types
   $args=array(
      'public'   => true,  // Get public ones
      '_builtin' => false  // Ignore built in post types
   ); 
   
   $output = 'names'; // names or objects, note names is the default
   $operator = 'and'; // 'and' or 'or'
   
   $post_types=get_post_types($args,$output,$operator); 
   
   /* foreach ($post_types  as $post_type ) {
   echo '<p>'. $post_type. '</p>';
   } */
   
   return $post_types;
}

function _cc_acc_soc_book_shortenText($text, $chars, $add = false) { 
// Shortens $text to word boundary if longer than specified length - $chars.
// Optional parameter $add determines whether to show elipsis
   
   // First check to see if string is longer than allowed
   if (strlen($text) < $chars ) {
      // no need to shorten text so return orig
      return $text;
   }
   
   
   if ($add && ($chars > 3)) {  
   // If elipsis required and allowed length greater than 3
      $newChars = $chars - 3;  // take 3 off allowed length
      
      // Try and break the string at a suitable
      $newText = substr($text, 0, strrpos(substr($text, 0, $newChars), ' '));
      
      if (strlen($newText) == 0 ) {
         $newText = substr($text, 0, $newChars).'...';
      } else {
         $newText = $newText.'...';
      }
   } else {
      $newText = substr($text, 0, strrpos(substr($text, 0, $chars), ' '));
      if (strlen($newText) == 0 ) {
         $newText = substr($text, 0, $chars);
      }
   }
   return $newText; 

} 


// Public Functions
function cc_acc_soc_book_activate() {
/*********************************************************************
   Sets the options for this plugin in the database
*********************************************************************/
   add_option('cc_acc_soc_book_position', array('before' => false,'after' => true ));
   add_option('cc_acc_soc_book_prefix', array('reqd' => true,'text' => 'Share this page' ));
   add_option('cc_acc_soc_book_post_types', 
      array('post' => true, // Blog posts
         'page' => true, // Static pages
         'front' => false, // Front page - if there is one
         'home' => false, // Index page
         'archive' => false, // archive page of some sort
         'custom' => false, // custom post types
      ));
   add_option('cc_acc_soc_book_exclude', '');  // Should links open new window
   add_option('cc_acc_soc_book_bookmarks', 
      array( 
         array('Facebook', true),
         array('Twitter', true), 
         array('LinkedIn', true), 
         array('Delicious', true), 
         array('Digg', true), 
         array('Posterous', true), 
         array('Reddit', true),
      ));
   add_option('cc_acc_soc_book_open_window', true);  // Should links open new window
   add_option('cc_acc_soc_book_highlight', '#FFFF99');  // Highlight colour
   add_option('cc_acc_soc_book_remove', false);  // Should deactivating remove all the plugin options

}
// Public Functions
function cc_acc_soc_book_deactivate() {
/*********************************************************************
   Check to see whether to clean up options when plugin deleted
*********************************************************************/

   $deleteStuff = get_option('cc_acc_soc_book_remove' );
   //$deleteStuff = true;
   if($deleteStuff) {
      // Go ahead and delete the options
      delete_option('cc_acc_soc_book_position');
      delete_option('cc_acc_soc_book_prefix');
      delete_option('cc_acc_soc_book_post_types');
      delete_option('cc_acc_soc_book_bookmarks');
      delete_option('cc_acc_soc_book_open_window');
      delete_option('cc_acc_soc_book_highlight');
      delete_option('cc_acc_soc_book_exclude');
      delete_option('cc_acc_soc_book_remove');
      
   }

}

// Set up hooks for activation and deactivation
register_activation_hook( __FILE__, 'cc_acc_soc_book_activate' );
register_deactivation_hook( __FILE__, 'cc_acc_soc_book_deactivate' );

function cc_acc_soc_book_printLinks($input) {
/*********************************************************************
   writes out the social bookmark links either before or after the $input
*********************************************************************/
   $strHtml = '';
   // Firstly work out if social bookmarks need to be shown
   $thisPage = get_the_ID(); // Get the page ID
   
   //check if page in exclude list
   $excludeList = explode(',',get_option('cc_acc_soc_book_exclude'));
   foreach($excludeList as $pageId){
      // 
      if ($pageId == $thisPage) { return $input; }
   }
   
   // Check we are required to output something for this given post type
   $postTypes = get_option('cc_acc_soc_book_post_types'); // array
   
   // Return out if not required on this post type
   if(is_page() and !($postTypes['page']) ) return $input;
   if(is_single() and !($postTypes['page'])  ) return $input;
   if(is_archive() and !($postTypes['archive'])  ) return $input;
   if(is_front_page() and !($postTypes['front'])  ) return $input;
   if(is_home() and !($postTypes['home'])  ) return $input;

   // Add check in here for custom post types   
   
   
   // If we're still here then output may well be required
   // Check at least one position marker is true
   $position = get_option('cc_acc_soc_book_position'); // array
   // If neither set then exit
   if (!($position['before']) and !($position['after'])) return $input;
   
   $bookmarks = get_option('cc_acc_soc_book_bookmarks');

   // Check that some links are actually required so look for true values in 
   // $bookmarks array
   //$linksReqd = in_array(true, $bookmarks);
   if (!in_array(true, $bookmarks)) return $input;
  
   
   
   
   // Now we know we're actually going to output stuff
   $prefix = get_option('cc_acc_soc_book_prefix'); //array
   $openWin = get_option('cc_acc_soc_book_open_window');  // Should links open new window

   // Set various strings if open windindow is required
   $prefixOpen = ($openWin) ? ' (Links open new window/tab)':'';
   $target = ($openWin) ? ' target="_socbook"':'';
   
   $strHtml = '<div class="acc-soc-book">'; // Containing div
   // Is a prefix required
   if ($prefix['reqd']) {
      $strHtml .= '<p><strong>'.$prefix['text'].$prefixOpen.'</strong></p>';
   } 
   
   // Pull out page info
   $pageTitle = get_the_title();
   $shortTitle = '';
   $pageUrl = get_permalink();
   
   // The icon image
   $imgTransp = '<img src="'.plugin_dir_url(__FILE__).'images/transp.gif" height="30" width="30" alt="Share this page on XXXXX (opens new window)" title="Share this page on XXXXX (opens new window)">';

   //echo plugin_dir_url(__FILE__);
   $strHtml .= '<ul>'; // Start list
   
   foreach ($bookmarks as $bookmark) {
      // Processing to see which links are required
      if (!$bookmark[1]) continue;
      
      // Process links which are required
      switch ($bookmark[0]) {
         case 'Facebook':
            $max = 70; // Logest allowed string for title
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 70,true);
            $strHtml .= '<li><a class="acc-soc-book-facebook" rel="nofollow" '.$target.'  href="http://www.facebook.com/sharer.php?u='.urlencode($pageUrl).'&amp;t='.urlencode($shortTitle).'"  >'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';
            break; 

         case 'Twitter': 
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 60,true);
            $strHtml .= '<li><a class="acc-soc-book-twitter" rel="nofollow" '.$target.'  href="http://twitter.com/share?url='.urlencode($pageUrl).'&amp;text='.urlencode($shortTitle).'">'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';
            break; 
         
         case 'LinkedIn': 
         
            $excerpt =  _cc_acc_soc_book_shortenText($content, 50,true);
            $strHtml .= '<li><a class="acc-soc-book-linkedin" rel="nofollow" '.$target.'  href="http://www.linkedin.com/shareArticle?mini=true&amp;url='.urlencode($pageUrl).'">'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';

            break; 
         
         case 'Delicious': 
            $max = 70; // Logest allowed string for title
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 70,true);
            $strHtml .= '<li><a class="acc-soc-book-delicious" rel="nofollow" '.$target.'  href="http://delicious.com/post?url='.urlencode($pageUrl).'&amp;title='.urlencode($shortTitle).'"  >'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';

            break; 
         
         case 'Digg': 
            $max = 70; // Logest allowed string for title
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 70,true);
            $strHtml .= '<li><a class="acc-soc-book-digg" rel="nofollow" '.$target.'  href="http://digg.com/submit?url='.urlencode($pageUrl).'&amp;title='.urlencode($shortTitle).'"  >'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';

            break; 
         
         case 'Posterous':
            $max = 70; // Logest allowed string for title
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 70,true);
            $strHtml .= '<li><a class="acc-soc-book-posterous" rel="nofollow" '.$target.'  href="http://www.posterous.com/share?linkto='.urlencode($pageUrl).'&amp;title='.urlencode($shortTitle).'"  >'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';
            
            break; 
         
         case 'Reddit':
            $max = 70; // Logest allowed string for title
            $shortTitle = _cc_acc_soc_book_shortenText($pageTitle, 70,true);
            $strHtml .= '<li><a class="acc-soc-book-reddit" rel="nofollow" '.$target.'  href="http://www.reddit.com/submit?url='.urlencode($pageUrl).'&amp;title='.urlencode($shortTitle).'"  >'.str_replace ( 'XXXXX', $bookmark[0], $imgTransp).'</a></li>';
            
            break; 
      }
   }
      
      
      
   $strHtml .= '</ul><div style="clear:left"></div></div>'; // end list and div

   // Prepare output - either before, after or both
   $strBefore = '';
   $strAfter = '';
   if ($position['before']) $strBefore = $strHtml;
   if ($position['after']) $strAfter = $strHtml;

   return $strBefore.$input.$strAfter;
}
add_filter('the_content','cc_acc_soc_book_printLinks');
add_filter('the_excerpt','cc_acc_soc_book_printLinks');

function cc_acc_soc_book_addCssToHeader() {
/*********************************************************************
   writes out any necessary CSS into the header of the page
*********************************************************************/
   $highlight = get_option('cc_acc_soc_book_highlight'); //array

   $strHtml = '<style type="text/css">
/* Accessible Social bookmarks*/
.acc-soc-book { margin:2em 0 1em 0}
.acc-soc-book ul,
.acc-soc-book p {margin:0 !important; padding:0 !important;}
.acc-soc-book li { margin:0 5px 0 0 !important; padding:0 !important; list-style-type:none; float:left; display:block; height:30px; width:30px; overflow:hidden;  background-image:none !important }
.acc-soc-book a img {padding:0;  background:url('.plugin_dir_url(__FILE__).'images/soc-icons.png) no-repeat 3px 3px; }
.acc-soc-book a:link, .acc-soc-book a:visited { display:block; background:transparent; background-image:none !important}
.acc-soc-book a:hover, .acc-soc-book a:active, .acc-soc-book a:focus { background:'.$highlight.';}
.acc-soc-book a.acc-soc-book-facebook img {background-position: 3px -65px; }
.acc-soc-book a.acc-soc-book-twitter img {background-position: 3px -439px; }
.acc-soc-book a.acc-soc-book-linkedin img {background-position: 3px -133px; }
.acc-soc-book a.acc-soc-book-delicious img {background-position: 3px 3px; }
.acc-soc-book a.acc-soc-book-digg img {background-position: 3px -31px; }
.acc-soc-book a.acc-soc-book-reddit img {background-position: 3px -269px; }
.acc-soc-book a.acc-soc-book-posterous img {background-position: 3px -235px; }   
   
   ';
   $strHtml .= '</style>';
   
   echo $strHtml;
}
add_action('wp_head', 'cc_acc_soc_book_addCssToHeader');


/*****************************************************************
** Admin menus
******************************************************************/
function _cc_acc_soc_book_add_menu_item()	{
		add_submenu_page(
			'options-general.php', 							// Menu page to attach to
			'Accessible Social Bookmarks Configuration', 		// page title
			'Accessible Social Bookmarks', 						// menu title
			'manage_options', 						// permissions
			'accessible-social-bookmarks',					// page-name (used in the URL)
			'_cc_acc_soc_book_generate_admin_page'	// clicking callback function
		);
	}
add_action('admin_menu', '_cc_acc_soc_book_add_menu_item');

function _cc_acc_soc_book_generate_admin_page()	{
   if (!current_user_can('manage_options'))  {
   		wp_die( __('You do not have sufficient permissions to access this page.') );
   	}
   // variables for the field and option names 
   $fieldPrefix = 'cc_acc_soc_book_';
   $hidden_field_name = 'submit_hidden';
   $strErr = '';
   

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
      // Read in existing option value from database
      //$prefix = get_option('cc_acc_soc_book_prefix');
      //$position = get_option('cc_acc_soc_book_position');
      //$postTypes = get_option('cc_acc_soc_book_post_types');
      $bookmarks = get_option('cc_acc_soc_book_bookmarks');
      $openWin = get_option('cc_acc_soc_book_open_window');
      $remove = get_option('cc_acc_soc_book_remove');
     
     
      // Read their posted values and store
      // Process links
      $newBookmarks = array();
      foreach ($bookmarks as $bookmark) {
         $state = isset($_POST[ $fieldPrefix.'link_'.$bookmark[0] ]);
         $newBookmarks[] = array($bookmark[0], $state);
      }
      update_option( 'cc_acc_soc_book_bookmarks', $newBookmarks );
      
      
      // Post types
      $postTypes = array(
         'post' => isset($_POST[ $fieldPrefix.'type_post' ]), // Blog posts
         'page' => isset($_POST[ $fieldPrefix.'type_page' ]), // Static pages
         'front' => isset($_POST[ $fieldPrefix.'type_front' ]), // Front page - if there is one
         'home' => isset($_POST[ $fieldPrefix.'type_home' ]), // Index page
         'archive' => isset($_POST[ $fieldPrefix.'type_archive' ]), // archive page of some sort
         'custom' => false, // custom post types - not in yet
      );
      update_option( 'cc_acc_soc_book_post_types',  $postTypes );
      
      // Exclude string
      $subExclude = $_POST[ $fieldPrefix.'exclude' ];
      // Save the posted value in the database
      update_option( 'cc_acc_soc_book_exclude',  strip_tags($subExclude) );
         
      // Position
      $position = array(
         'before' => isset($_POST[ $fieldPrefix.'before' ]),
         'after' => isset($_POST[ $fieldPrefix.'after' ]) 
      );
      update_option( 'cc_acc_soc_book_position',  $position );

      // Prefix paragrph
      $prefix = array(
         'reqd' => isset($_POST[ $fieldPrefix.'prefix_req' ]),
         'text' => strip_tags($_POST[ $fieldPrefix.'prefix_text' ]) 
      );
      update_option( 'cc_acc_soc_book_prefix',  $prefix );

      // Highlight colour
      if (empty($_POST[ $fieldPrefix.'highlight' ])) {
         $highlight = '#FFFF99';
      } else {
         $highlight = $_POST[ $fieldPrefix.'highlight' ];
      }
      update_option('cc_acc_soc_book_highlight',$highlight );
      
      // New window
      update_option('cc_acc_soc_book_open_window', isset($_POST[ $fieldPrefix.'open_window'])); 
            
      // Deactivation
      update_option('cc_acc_soc_book_remove', isset($_POST[ $fieldPrefix.'remove' ]));
?>
<div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
<?php

    } // End of ( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )

    // Now display the settings editing screen
    echo '<div class="wrap">';

    // header
    echo "<h2>" . __( 'Accessible Social Bookmarks Plugin Settings', 'menu-test' ) . "</h2>";

    // settings form 
   $prefix = get_option('cc_acc_soc_book_prefix');
   $position = get_option('cc_acc_soc_book_position');
   $postTypes = get_option('cc_acc_soc_book_post_types');
   $bookmarks = get_option('cc_acc_soc_book_bookmarks');
   $openWin = get_option('cc_acc_soc_book_open_window');
   $remove = get_option('cc_acc_soc_book_remove');

?>

<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<h3>Social Bookmarks</h3>
<fieldset>
<legend><strong>Which ones to include</strong></legend>
<p>
<?php 

foreach ($bookmarks as $bookmark) {
?>
<input type="checkbox" name="<?php echo $fieldPrefix.'link_'.$bookmark[0]; ?>" id="<?php echo $fieldPrefix.'link_'.$bookmark[0]; ?>" value="<?php echo $bookmark[0]; ?>"<?php echo $bookmark[1] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix.'link_'.$bookmark[0]; ?>"><?php echo $bookmark[0]; ?></label>
<br/>

<?php 
}
?>
</p>
</fieldset>

<h3>Placement</h3>

<fieldset>
<legend><strong>Where to show the bookmarks</strong></legend>
<p>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>type_post" id="<?php echo $fieldPrefix; ?>type_post" value="post"<?php echo $postTypes['post'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>type_post">On posts</label>
<br/>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>type_page" id="<?php echo $fieldPrefix; ?>type_page" value="page"<?php echo $postTypes['page'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>type_page">On pages</label>
<br/>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>type_front" id="<?php echo $fieldPrefix; ?>type_front" value="front"<?php echo $postTypes['front'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>type_front">On front page (if you have a opted for a static front page)</label>
<br/>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>type_home" id="<?php echo $fieldPrefix; ?>type_home" value="home"<?php echo $postTypes['home'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>type_home">On your blog page (if you have one)</label>
<br/>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>type_archive" id="<?php echo $fieldPrefix; ?>type_archive" value="archive"<?php echo $postTypes['archive'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>type_archive">On archive pages (categories, tags, archives etc)</label>
</p>
<p><label for="<?php echo $fieldPrefix; ?>exclude">Pages and Posts to exclude (comma separated list of page or post ids)</label><br/>
<input type="text" name="<?php echo $fieldPrefix; ?>exclude" id="<?php echo $fieldPrefix; ?>exclude" value="<?php echo htmlspecialchars(get_option('cc_acc_soc_book_exclude'));  ?>" size="30">
</p>
</fieldset>

<fieldset>
<legend><strong>Where to place the bookmarks</strong></legend>
<p>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>before" id="<?php echo $fieldPrefix; ?>before" value="before"<?php echo $position['before'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>before">Before the content</label>
<br/>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>after" id="<?php echo $fieldPrefix; ?>after" value="after"<?php echo $position['after'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>after">After the content</label>
</p>
</fieldset>

<h3>Appearance</h3>
<fieldset>
<legend><strong>Prefix paragraph</strong></legend>
<p>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>prefix_req" id="<?php echo $fieldPrefix; ?>prefix_req" value="prefix_req"<?php echo $prefix['reqd'] ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>prefix_req">Prefix required</label>
</p>
<p>
<label for="<?php echo $fieldPrefix; ?>prefix_text">Prefix text</label><br/>
<input type="text" name="<?php echo $fieldPrefix; ?>prefix_text" id="<?php echo $fieldPrefix; ?>prefix_text" value="<?php echo htmlspecialchars($prefix['text']);  ?>" size="40">
</p>
</fieldset>

<fieldset>
<legend><strong>Highlight colour for hover and focus</strong></legend>
<p><label for="<?php echo $fieldPrefix; ?>highlight">Highlight colour (hex value - #nnnnnn)</label><br/>
<input type="text" name="<?php echo $fieldPrefix; ?>highlight" id="<?php echo $fieldPrefix; ?>highlight" value="<?php echo htmlspecialchars(get_option('cc_acc_soc_book_highlight'));  ?>" size="20">
</p>
</fieldset>
<h3>Behaviour</h3>
<fieldset>
<legend><strong>Opening new windows and tabs</strong></legend>
<p>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>open_window" id="<?php echo $fieldPrefix; ?>open_window" value="open_window"<?php echo $openWin ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>open_window">Open bookmarking links in new window or tab. (Note: appropriate accessibility warnings will be added for opening windows)</label>
</p>
</fieldset>
<h3>Deactivation</h3>
<fieldset>
<legend><strong>What to do if you deactivate this plugin</strong></legend>
<p>
<input type="checkbox" name="<?php echo $fieldPrefix; ?>remove" id="<?php echo $fieldPrefix; ?>remove" value="remove"<?php echo $remove ? ' checked="checked"':'' ?>>
<label for="<?php echo $fieldPrefix; ?>remove">Remove options from database</label>
</p>
</fieldset>

<hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
</p>

</form>
</div>

<?php
 
}


/* EOF */
