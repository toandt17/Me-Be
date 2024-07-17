<?php
/**
 * The blog template file.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.16.0
 */

get_header();

?>

<div id="content" class="blog-wrapper blog-archive page-wrapper">
		<?php get_template_part( 'template-parts/posts/layout', get_theme_mod('blog_layout','right-sidebar') ); 
		if (function_exists('pvc_get_post_views')) {
			echo '<p>Lượt xem: ' . pvc_get_post_views(get_the_ID()) . '</p>';
		}
	?>
	
</div>

<?php get_footer(); ?>
