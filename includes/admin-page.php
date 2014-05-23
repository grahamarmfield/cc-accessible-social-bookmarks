<?php 
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
   	echo '<div class="wrap"><h2>Accessible Social Bookmarks Configuration</h2>';
   	echo '<p>Here is where the form would go if I actually had options.</p>';
   	echo '</div>';
}
/* EOF */
