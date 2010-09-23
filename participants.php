<?php
/*
Plugin Name: Participants Widget
Description: A widget which displays blog participant info - number of posts and comments, and links to profile page
Author: Jonah Bossewitch, CCNMTL 
Licence: GPL3, GNU Public Licence,  http://www.gnu.org/copyleft/gpl.html
*/

/**
 * Display recent comments widget. *
 * @param array $args Widget arguments.
*/


/* add_action('widgets_init', 'wp_widget_participants_init'); */
add_action('plugins_loaded', 'wp_widget_participants_init'); 


/*
 * participants_get_comments_by_user
 * @param user_id
 * @return array of how many comments a user has made, keyed off of user ids
 */ 
function participants_get_comments_by_user($user_id, $limit = 0) {
  global $wpdb;
  
  $querystr = "
    SELECT comment_ID, comment_post_ID, post_title
    FROM $wpdb->comments, $wpdb->posts
    WHERE user_id = $user_id
    AND comment_post_id = ID
    AND comment_approved = 1
    ORDER BY comment_ID DESC";

  if ($limit > 0) {
    $querystr .= " LIMIT $limit";
  }

  // $comments_array = $wpdb->get_results($querystr, OBJECT);
 $comments_array = $wpdb->get_results($querystr, ARRAY_A);

 // setup the url to the comment, for convinience
 foreach ($comments_array as &$comment) {
   $comment['url'] = get_bloginfo('url') ."/?p=".$comment['comment_post_ID']."/#comment-". $comment['comment_ID'];;
 }

 return $comments_array;

}

/*
 * participants_get_comment_counts
 * returns an array of how many comments a user has made, keyed off of user ids
 */ 
function participants_get_comment_counts() {
  global $wpdb;
  
  $author_count = array();

  foreach ((array) $wpdb->get_results("SELECT DISTINCT user_id, COUNT(comment_ID) AS count FROM $wpdb->comments WHERE comment_approved = 1 GROUP BY user_id") as $row) {
    $author_count[$row->user_id] = $row->count;
  }

 return $author_count;

}

/**
 * List all the authors of the blog, with several options available.
 *
 * <ul>
 * <li>optioncount (boolean) (false): Show the count in parenthesis next to the
 * author's name.</li>
 * <li>exclude_admin (boolean) (true): Exclude the 'admin' user that is
 * installed bydefault.</li>
 * <li>show_fullname (boolean) (false): Show their full names.</li>
 * <li>hide_empty (boolean) (true): Don't show authors without any posts.</li>
 * <li>feed (string) (''): If isn't empty, show links to author's feeds.</li>
 * <li>feed_image (string) (''): If isn't empty, use this image to link to
 * feeds.</li>
 * <li>echo (boolean) (true): Set to false to return the output, instead of
 * echoing.</li>
 * <li>style (string) ('list'): Whether to display list of authors in list form
 * or as a string.</li>
 * <li>html (bool) (true): Whether to list the items in html for or plaintext.
 * </li>
 * </ul>
 *
 * @link http://codex.wordpress.org/Template_Tags/wp_list_authors
 * @since 1.2.0
 * @param array $args The argument array.
 * @return null|string The output, if echo is set to false.
 */
function participants_list_authors($args = '') {
	global $wpdb;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'feed_type' => '', 'echo' => true,
		'style' => 'list', 'html' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);
	$return = '';

	/** @todo Move select to get_authors(). */
 	$users = get_users_of_blog(); 
	$author_ids = array(); 
	foreach ( (array) $users as $user ) { 
		$author_ids[] = $user->user_id; 
	} 
	if ( count($author_ids) > 0  ) { 
		$author_ids=implode(',', $author_ids ); 
		//$authors  = $wpdb->get_results( "SELECT ID, user_nicename from $wpdb->users WHERE ID IN($author_ids) " . ($exclude_admin ? "AND user_login <> 'admin' " : '') . "ORDER BY display_name" ); 
		$authors = $wpdb->get_results( "SELECT users.ID, users.user_nicename from $wpdb->users users, $wpdb->usermeta usermeta WHERE users.ID = usermeta.user_id AND usermeta.meta_key = 'last_name' AND users.ID IN($author_ids) " . ($exclude_admin ? "AND users.user_login <> 'admin' " : '') . "ORDER BY usermeta.meta_value" ); 
	} else { 
		$authors = array(); 
	} 

	$author_count = array();
	foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
		$author_count[$row->post_author] = $row->count;
	}

	// added by jsb to support comments
	$author_comment_count = participants_get_comment_counts();

	foreach ( (array) $authors as $author ) {

		$link = '';

		$author = get_userdata( $author->ID );
		$posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
		$comments = (isset($author_comment_count[$author->ID])) ? $author_comment_count[$author->ID] : 0;
		$name = $author->display_name;

		if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
			$name = "$author->first_name $author->last_name";

		if( !$html ) {
			if ( $posts == 0 && $comments == 0) {
				if ( ! $hide_empty )
					$return .= $name . ', ';
			} else
				$return .= $name . ', ';

			// No need to go further to process HTML.
			continue;
		}

		if ( !($posts == 0 && $comments == 0 && $hide_empty) && 'list' == $style )
			$return .= '<li>';
		if ( $posts == 0 && $comments == 0) {
			if ( ! $hide_empty )
				$link = $name;
		} else {
			$link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $author->display_name) ) . '">' . $name . '</a>';

			if ( (! empty($feed_image)) || (! empty($feed)) ) {
				$link .= ' ';
				if (empty($feed_image))
					$link .= '(';
				$link .= '<a href="' . get_author_feed_link($author->ID) . '"';

				if ( !empty($feed) ) {
					$title = ' title="' . esc_attr($feed) . '"';
					$alt = ' alt="' . esc_attr($feed) . '"';
					$name = $feed;
					$link .= $title;
				}

				$link .= '>';

				if ( !empty($feed_image) )
					$link .= "<img src=\"" . esc_url($feed_image) . "\" style=\"border: none;\"$alt$title" . ' />';
				else
					$link .= $name;

				$link .= '</a>';

				if ( empty($feed_image) )
					$link .= ')';
			}

			if ( $optioncount )
			  // $link .= ' ('. $posts . ')';
			  $link .= ' ('. $posts . '/' . $comments . ')';

		}

		if ( !($posts == 0 && $comments == 0 && $hide_empty) && 'list' == $style )
			$return .= $link . '</li>';
		else if ( ! $hide_empty )
			$return .= $link . ', ';
	}

	$return = trim($return, ', ');

	if ( ! $echo )
		return $return;
	echo $return;
}

// create a way to view the authors posts by archive 
function author_archive_view () {

  // return if this URL does not contain with "/author_archive_veiw"
  $re = '/\/author_archive_view(.*)$/';
  if (!preg_match( $re, $_SERVER["REQUEST_URI"])) {
    return;
  }

  global $wp_query;
  if (isset($wp_query->query_vars['author'])) {
      query_posts("&nopaging=true&author_name=".$wp_query->query_vars['author']);
    }
  include(TEMPLATEPATH . '/archive.php');
  exit;
}


function wp_widget_participants($args) {
    //global $wpdb, $comments, $comment;

    extract($args);
    $options = get_option('widget_participants');
    $title = $options['title'];
    // $title = "Authors' Posts";

    $authors = participants_list_authors('show_fullname=1&optioncount=1&exclude_admin=0&hide_empty=1&echo=0');
    //$authors = wp_list_authors('show_fullname=1&optioncount=1&exclude_admin=0&hide_empty=0&echo=0');
    if ($authors) {
      echo $before_widget . $before_title . $title . $after_title;
      echo '<ul id="list-authors" class="widget widget_pages">';
      //echo '<ul>';
      echo $authors;
      echo '</ul>';
      echo '</li>';
      echo $after_widget;
    }
}

function wp_widget_participants_control() {
  $options = get_option('widget_participants');
  if ( !is_array($options) )
    $options = array(
		     'title'=>'Participants',
		     );
  if ( $_POST['authors-posts-submit'] ) {
    $options['title'] = strip_tags(stripslashes($_POST['authors-posts-title']));
    update_option('widget_participants', $options);
  }
  
  $title = htmlspecialchars($options['title'], ENT_QUOTES);
  
  echo '<p style="text-align:right;"><label for="authors-posts-title">Title: <input style="width: 200px;" id="authors-posts-title" name="authors-posts-title" type="text" value="'.$title.'" /></label></p>';
  echo '<input type="hidden" id="authors-posts-submit" name="authors-posts-submit" value="1" />';
}

function wp_widget_participants_init() {
  // error_log("entering wp_widget_participants_init");

  if (!function_exists('wp_register_sidebar_widget')) 
    return;

  // error_log("wp_widget_participants_init: registering sidebar widget");

  $widget_ops = array('classname' => 'widget_participants', 'description' => 'Participants Contributions'  );
  wp_register_sidebar_widget("participants", "Participants", 'wp_widget_participants', $widget_ops);
  wp_register_widget_control("participants", "participants", 'wp_widget_participants_control');
  
  // create a view that preserves the author's archive page view (posts and teasers)
  add_action('template_redirect', 'author_archive_view');
  
}


?>
