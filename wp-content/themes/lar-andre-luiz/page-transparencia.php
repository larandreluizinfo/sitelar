<?php
/**
 * Template da página Transparência.
 *
 * Substitui a tabela que era gerada com Math.random() no JavaScript.
 * Os dados vêm do post type "prestacao", cadastrados no admin.
 *
 * @package LarAndreLuiz
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

<div class="page-wrap">
		<?php lar_render_hero(); ?>

<div style="max-width:1200px;margin:auto;padding:64px 24px;display:grid;gap:48px">
			<?php
			/*
			 * O texto de introdução (eyebrow, título e subtítulo) fica no
			 * editor, dentro do post_content. O container de largura máxima
			 * acima é do template, para que a lista de balancetes fique
			 * dentro dele.
			 */
			the_content();

			get_template_part( 'template-parts/transparencia' );
			?>
		</div>
	</div>

	<?php
endwhile;

get_footer();