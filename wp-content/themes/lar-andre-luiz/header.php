<?php
/**
 * Cabeçalho do site.
 *
 * O menu é renderizado por wp_nav_menu() com o walker LAR_Nav_Walker:
 * cada item é um <a href> com permalink real do WordPress.
 * Não há mais onclick="showPage(...)" nem href="#".
 *
 * @package LarAndreLuiz
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#conteudo"><?php esc_html_e( 'Pular para o conteúdo', 'lar-andre-luiz' ); ?></a>

<header>
	<div class="nav">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			$logo_id = get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				echo wp_get_attachment_image( $logo_id, 'full', false, array( 'alt' => esc_attr( get_bloginfo( 'name' ) ) ) );
			} else {
				echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/images/logo.svg' ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
			}
			?>
			<small><?php esc_html_e( 'DESDE 1959', 'lar-andre-luiz' ); ?></small>
		</a>

		<?php
		$mobile_label   = __( 'Abrir menu', 'lar-andre-luiz' );
		$mobile_menu_id = 'menu';
		?>
		<button class="burger" type="button"
			aria-label="<?php echo esc_attr( $mobile_label ); ?>"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $mobile_menu_id ); ?>"
			data-lar-burger><i class="fas fa-bars" aria-hidden="true"></i></button>

		<nav id="<?php echo esc_attr( $mobile_menu_id ); ?>" aria-label="<?php esc_attr_e( 'Menu principal', 'lar-andre-luiz' ); ?>">
			<?php
			if ( has_nav_menu( 'principal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'principal',
						'container'      => false,
						'items_wrap'     => '<ul id="menu-list" class="menu-list">%3$s</ul>',
						'depth'          => 2,
						'walker'         => new LAR_Nav_Walker(),
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul id="menu-list" class="menu-list">';
				foreach ( lar_page_slugs() as $id => $slug ) {
					$page = '' === $slug ? null : get_page_by_path( $slug );
					$title = $page ? get_the_title( $page ) : ucfirst( $id );
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( home_url( $slug ? '/' . $slug . '/' : '/' ) ),
						esc_html( $title )
					);
				}
				echo '</ul>';
			}
			?>
		</nav>

		<?php echo lar_donate_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</header>

<main id="conteudo">