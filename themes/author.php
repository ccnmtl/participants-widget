<?php get_header(); 

Plugin Name: Participants Widget
Description: A widget which displays blog participant info - number of posts and comments, and links to profile page
Author: Jonah Bossewitch <jonah at ccnmtl dot columbia.edu, CCNMTL 
Licence: GPL2, GNU Public Licence,  http://www.gnu.org/copyleft/gpl.html

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

?>

<div id="container"><?php get_sidebar(); ?>
 <div id="content" role="main">

<!-- create custom profile page, with posts and comments from http://blogmum.com/2009/06/how-to-make-a-wordpress-profile-page/ --> 
<?php
$thisauthor = get_userdata(intval($author));

// set nopaging=true so we query for all posts and comments
query_posts("&nopaging=true&author=$author");

?>

<? if(function_exists('get_avatar')) { echo get_avatar($thisauthor->user_email, 96, "" ); } ?> 

<h2><?php echo $thisauthor->first_name . " " . $thisauthor->last_name; ?> (<?php echo $thisauthor->display_name; ?>)</h2>
<p>

<?php if ($thisauthor->user_email) { ?>
Email: <a href="mailto:<? echo $thisauthor->user_email; ?>"><? echo $thisauthor->user_email ?></a><br />
<?php } ?>

<?php if ($thisauthor->user_url) { ?>
Website: <a href="<? echo $thisauthor->user_url; ?>"><? echo $thisauthor->user_url; ?></a><br />
<?php } ?>

<?php if ($thisauthor->description) { ?>
Bio: <?php echo $thisauthor->description; ?><br />
<?php } ?>

			  

<?php if (have_posts()) : ?>
<?php $author_archive_view_url = get_bloginfo('url').'/author_archive_view?author='.$thisauthor->user_nicename;  ?>
<h2>Posts (<?php echo "<a href='$author_archive_view_url'>details</a>)"  ?></h2>
<ul>
<? while (have_posts()) :  the_post(); ?>
<li><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a> (<?php the_time('M d, Y'); ?>)</li>
<?php endwhile; else: ?>
<li>This user hasn't published any posts. </li>
<?php endif;  ?>
</ul>
<?

 $comments_array = participants_get_comments_by_user($thisauthor->ID);
 if ($comments_array): ?> 
   <h2>Comments </h2>
   <ul>
<? foreach ($comments_array as $comment):
setup_postdata($comment);
echo "<li><a href='". $comment['url'] ."'>Comment on ". $comment['post_title'] ."</a> (" . date('M d, Y', strtotime($comment['comment_date'])) . ")</li>";
endforeach; ?>
</ul>
<? endif; ?>

	
	<?php posts_nav_link(' &#8212; ', __('&laquo; Previous'), __('Next &raquo;')); ?>
  </div>
</div>
	

<!-- The main column ends  -->

<?php get_footer(); ?>
