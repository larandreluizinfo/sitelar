<?php
/**
 * Template da página inicial.
 *
 * @package LarAndreLuiz
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<div class="page-wrap">
		<?php lar_render_hero(); ?>
		<?php the_content(); ?>
	</div>

	<?php
endwhile;

get_footer();