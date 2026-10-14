<?php
/**
 * Template padrão de página interna.
 *
 * Estrutura idêntica à antiga <section class="page"> do SPA, mas com URL
 * própria (/atividades/, /transparencia/, ...). A capa vem dos metadados
 * da página e o corpo vem do editor do WordPress (post_content).
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