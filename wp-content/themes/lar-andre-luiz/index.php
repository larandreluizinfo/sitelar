<?php
/**
 * Fallback do tema (blog / arquivo).
 *
 * @package LarAndreLuiz
 */

get_header();
?>

<div class="page-wrap">
	<div class="hero">
		<div class="overlay"></div>
		<div class="hero-inner">
			<h1><?php echo esc_html( wp_get_document_title() ); ?></h1>
			<?php if ( function_exists( 'lar_breadcrumb' ) ) : ?>
				<?php $lar_crumb = lar_breadcrumb(); ?>
				<?php if ( $lar_crumb ) : ?>
					<div class="crumb-row on-hero"><?php echo $lar_crumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="sec">
		<?php if ( have_posts() ) : ?>
			<div class="cards4">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
						<?php endif; ?>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
						<a class="activity-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Saiba mais', 'lar-andre-luiz' ); ?></a>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="lar-empty">
				<strong><?php esc_html_e( 'Nada por aqui', 'lar-andre-luiz' ); ?></strong>
				<?php esc_html_e( 'Nenhum conteúdo foi encontrado nesta página.', 'lar-andre-luiz' ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();