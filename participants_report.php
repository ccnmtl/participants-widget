<?php
//$LINE_BREAK = "<br />";
$LINE_BREAK = "\n";
echo ('"uni","display name","entry type","date","post title","url","categories"' . "$LINE_BREAK");
global $post;
$args = array('numberposts' => -1);
$myposts = get_posts( $args );
foreach( $myposts as $post ) : setup_postdata($post); ?>
<?php
  // pluck out the post's categories
  $cat_array = array_map( create_function('$cat', 'return $cat->cat_name;'), get_the_category());
  sort($cat_array);
  $cat_string = implode(', ', $cat_array);
  $cat_string = str_replace('"', '""', $cat_string);
?>
"<?php the_author_login() ?>","<?php the_author(); ?>","post","<?php the_time('Y-m-d') ?>","<?php the_title(); ?>","<?php the_permalink(); ?>","<?php echo $cat_string ?><?php echo ("\"$LINE_BREAK"); ?>
<?php endforeach; ?>
<?php

$users = get_users_of_blog(); 
// $user_posts = array();
$user_comments = array();

foreach ( (array) $users as $user ) { 
 
  // $thisauthor = get_userdata(intval($author));
 $comments_array = participants_get_comments_by_user($user->user_id);
 foreach ( (array) $comments_array as $comment) {
   // print_r ($comment);
   echo("\"$user->user_login\",\"$user->display_name\",\"comment\",\""); 
   echo(date('Y-m-d', strtotime($comment['comment_date'])));
   echo("\",\"");
   echo($comment['post_title']);
   echo("\",\"");
   echo($comment['url']);
   echo("\"");
   echo("$LINE_BREAK");
 }
}
?>
