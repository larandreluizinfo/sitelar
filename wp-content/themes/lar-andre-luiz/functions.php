<?php
/**
 * Funções do tema Lar André Luiz.
 *
 * Princípio do tema: cada seção do site antigo é uma Página do WordPress
 * com URL própria. Não existe mais roteamento por hash (#) nem navegação
 * dependente de JavaScript — todo link de menu é um <a href> real.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LAR_THEME_VERSION', '1.0.0' );

/* -------------------------------------------------------------------------
 * 1. Setup do tema
 * ---------------------------------------------------------------------- */

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );

		register_nav_menus(
			array(
				'principal' => __( 'Menu principal', 'lar-andre-luiz' ),
			)
		);

		add_editor_style( 'assets/css/style.css' );
	}
);

add_action(
	'after_switch_theme',
	function () {
		update_option( 'lar_andre_luiz_versao', LAR_THEME_VERSION );
	}
);

/* -------------------------------------------------------------------------
 * 2. Estilos e scripts
 * ---------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		$css = get_template_directory() . '/assets/css/style.css';
		$js  = get_template_directory() . '/assets/js/main.js';

		wp_enqueue_style(
			'lar-base',
			get_template_directory_uri() . '/assets/css/style.css',
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : LAR_THEME_VERSION
		);

		wp_enqueue_style(
			'lar-theme',
			get_stylesheet_uri(),
			array( 'lar-base' ),
			LAR_THEME_VERSION
		);

		wp_enqueue_script(
			'lar-main',
			get_template_directory_uri() . '/assets/js/main.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : LAR_THEME_VERSION,
			true
		);

		wp_localize_script(
			'lar-main',
			'larData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => esc_url_raw( rest_url( 'lar/v1/forms' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'strings' => array(
					'menuOpen'  => __( 'Abrir menu', 'lar-andre-luiz' ),
					'menuClose' => __( 'Fechar menu', 'lar-andre-luiz' ),
				),
			)
		);
	}
);

/* -------------------------------------------------------------------------
 * 3. Limpezas do core que não são usadas neste site
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
	}
);

/* -------------------------------------------------------------------------
 * 4. Mapa slug antigo -> slug do WordPress
 *
 *    Usado para converter links históricos do template em permalinks reais.
 * ---------------------------------------------------------------------- */

/**
 * Slugs canônicos de cada página do site.
 *
 * @return array<string,string> id antigo => slug
 */
function lar_page_slugs() {
	return array(
		'inicio'        => '',
		'quem'          => 'quem-somos',
		'atividades'    => 'atividades',
		'esportes-base' => 'esportes-de-base',
		'galeria'       => 'galeria',
		'noticias'      => 'noticias',
		'eventos'       => 'eventos',
		'transparencia' => 'transparencia',
		'fale'          => 'fale-conosco',
		'trabalhe'      => 'trabalhe-conosco',
		'inscricao'     => 'pre-inscricao',
		'quadra'        => 'quadra-dos-sonhos',
		'aces'          => 'instituto-aces',
	);
}

/**
 * URL de uma página interna a partir do id legado do template.
 *
 * @param string $id    Id legado (ex.: 'atividades').
 * @param string $anchor Âncora opcional (ex.: '#esporte-futebol').
 * @return string URL absoluto.
 */
function lar_page_url( $id, $anchor = '' ) {
	$slugs = lar_page_slugs();

	if ( ! isset( $slugs[ $id ] ) ) {
		return home_url( '/' . ltrim( $id, '/' ) );
	}

	if ( '' === $slugs[ $id ] ) {
		return home_url( '/' );
	}

	$url = home_url( '/' . $slugs[ $id ] . '/' );

	if ( '' !== $anchor ) {
		$url .= '#' . ltrim( $anchor, '#' );
	}

	return $url;
}

/**
 * Slug da página com o id legado.
 *
 * @param string $id Id legado.
 * @return string Slug (string vazia para a home).
 */
function lar_page_slug( $id ) {
	$slugs = lar_page_slugs();

	return isset( $slugs[ $id ] ) ? $slugs[ $id ] : $id;
}

/* -------------------------------------------------------------------------
 * 4b. Kses que aceita SVG
 *
 * O design system usa ícones em SVG inline (estatísticas da capa, logos dos
 * parceiros). O wp_kses_post padrão remove <svg>, <path>, <circle> etc.,
 * então usar kses no conteúdo da capa apagaria todos os ícones.
 * Esta função roda o kses mantendo as tags de SVG.
 * ---------------------------------------------------------------------- */

/**
 * Filtra HTML de layout permitindo SVG inline.
 *
 * @param string $html HTML.
 * @return string
 */
function lar_kses_layout( $html ) {
	static $permitidas = null;

	if ( null === $permitidas ) {
		$permitidas = wp_kses_allowed_html( 'post' );

		$svg = array(
			'svg'           => array(
				'class' => true, 'id' => true, 'style' => true, 'role' => true,
				'viewbox' => true, 'width' => true, 'height' => true,
				'xmlns' => true, 'fill' => true, 'stroke' => true,
				'stroke-width' => true, 'aria-hidden' => true, 'aria-label' => true,
				'preserveaspectratio' => true, 'focusable' => true,
			),
			'path'          => array(
				'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'class' => true, 'style' => true, 'opacity' => true,
			),
			'circle'        => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'class' => true, 'style' => true ),
			'rect'          => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'rx' => true, 'class' => true, 'style' => true ),
			'g'             => array( 'class' => true, 'id' => true, 'style' => true, 'fill' => true, 'transform' => true ),
			'line'          => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'class' => true, 'style' => true ),
			'polyline'      => array( 'points' => true, 'fill' => true, 'stroke' => true, 'class' => true, 'style' => true ),
			'polygon'       => array( 'points' => true, 'fill' => true, 'stroke' => true, 'class' => true, 'style' => true ),
			'ellipse'       => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'class' => true, 'style' => true ),
			'defs'          => array( 'class' => true, 'id' => true ),
			'lineargradient' => array( 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true ),
			'stop'          => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ),
			'title'         => array( 'class' => true ),
			'text'          => array( 'class' => true, 'x' => true, 'y' => true, 'fill' => true, 'font-size' => true ),
		);

		$permitidas = array_merge( $permitidas, $svg );
	}

	return wp_kses( $html, $permitidas );
}

/* -------------------------------------------------------------------------
 * 5. Conteúdo das páginas: caminhos de assets
 *
 *    O conteúdo das páginas é escrito com caminhos relativos
 *    "assets/...". Aqui eles viram URLs absolutas do tema.
 * ---------------------------------------------------------------------- */

/**
 * Converte caminhos relativos "assets/..." em URLs absolutas do tema.
 *
 * @param string $html HTML.
 * @return string
 */
function lar_resolve_assets( $html ) {
	$base = get_template_directory_uri() . '/';

	// Atributos com caminho relativo para a pasta assets/ do tema.
	$html = preg_replace(
		'#(\s(?:src|href|poster|data-src)=")(?!\#|https?:|//|/)(assets/[^"]+)#i',
		'$1' . $base . '$2',
		$html
	);

	// url(assets/...) dentro de style="" ou background inline.
	$html = preg_replace(
		'#url\(\s*[\'"]?(?!\#|https?:|//|/)(assets/[^)\'"]+)[\'"]?\s*\)#i',
		'url(' . $base . '$1)',
		$html
	);

	return $html;
}

add_filter(
	'the_content',
	function ( $content ) {
		if ( is_admin() || ! is_singular() ) {
			return $content;
		}

		return lar_resolve_assets( $content );
	}
);

/* -------------------------------------------------------------------------
 * 5b. Desliga a formatação automática do conteúdo
 *
 * O conteúdo das páginas deste site é HTML de layout (cards, grids,
 * mosaicos, carrosséis), não texto corrido. O wpautop do core não
 * entende esses blocos e insere marcação no meio do markup:
 *
 *   <div class="card activity"><img ...></p><div>…</div></div>
 *                                          ↑ </p> órfão
 *
 * Esses </p> e <br /> quebram o grid, porque o CSS depende de o <img>
 * e a <div> seguinte serem irmãos diretos. Como as páginas são compostas
 * por blocos já formatados, desligamos o autop.
 * ---------------------------------------------------------------------- */

add_action(
	'wp',
	function () {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'shortcode_unautop' );
		remove_filter( 'the_content', 'convert_smilies' );
		remove_filter( 'the_content', 'convert_chars' );

		// wptexturize é mantido: só troca aspas por aspas tipográficas.
	}
);

/* -------------------------------------------------------------------------
 * 6. Navegação
 * ---------------------------------------------------------------------- */

/**
 * Menu principal com submenu "Contato" como dropdown.
 */
class LAR_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Abre um submenu.
	 *
	 * Usa <ul> porque o walker emite <li>: um <div> contendo <li> é HTML inválido.
	 *
	 * @param string   $output Buffer.
	 * @param int      $depth  Profundidade atual.
	 * @param stdClass $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= "\n<ul class=\"dd-menu\">\n";
	}

	/**
	 * Fecha um submenu.
	 *
	 * @param string   $output Buffer.
	 * @param int      $depth  Profundidade atual.
	 * @param stdClass $args   Args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= "</ul>\n";
	}

	/**
	 * Aplica a classe "dd" nos itens que têm filhos.
	 *
	 * @param string   $output Buffer.
	 * @param WP_Post  $item   Item do menu.
	 * @param int      $depth  Profundidade.
	 * @param stdClass $args   Args.
	 * @param int      $id     ID do item.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$has_children = in_array( 'menu-item-has-children', $classes, true );

		if ( $has_children ) {
			$classes[] = 'dd';
		}

		$class_names = implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id_attr = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
		$id_attr = $id_attr ? ' id="' . esc_attr( $id_attr ) . '"' : '';

		$output .= str_repeat( "\t", $depth ) . '<li' . $id_attr . $class_names . '>';

		$atts           = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']     = ! empty( $item->xfn ) ? $item->xfn : '';
		$atts['href']    = ! empty( $item->url ) ? $item->url : '';

		if ( $has_children ) {
			$atts['class']   = 'dd-btn';
			$atts['aria-haspopup'] = 'true';
			$atts['data-lar-dropdown'] = 'true';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attr_html = '';
		foreach ( $atts as $key => $value ) {
			if ( '' === $value || is_null( $value ) ) {
				continue;
			}
			$value       = ( 'href' === $key ) ? esc_url( $value ) : esc_attr( $value );
			$attr_html  .= ' ' . esc_attr( $key ) . '="' . $value . '"';
		}

		$chevron = $has_children ? ' <i class="fas fa-chevron-down" aria-hidden="true"></i>' : '';

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$output .= '<a' . $attr_html . '><span class="dd-label">' . $title . '</span>' . $chevron . '</a>';
	}
}

/* -------------------------------------------------------------------------
 * 7. Breadcrumb
 * ---------------------------------------------------------------------- */

/**
 * Breadcrumb da página atual (substitui o "crumb-row" montado por JS).
 *
 * @return string|null HTML da breadcrumb ou null na home.
 */
function lar_breadcrumb() {
	if ( is_front_page() ) {
		return null;
	}

	$items = array(
		array(
			'label' => __( 'Início', 'lar-andre-luiz' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'page' ) ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_singular( 'post' ) ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		if ( $blog_id ) {
			$items[] = array(
				'label' => get_the_title( $blog_id ),
				'url'   => get_permalink( $blog_id ),
			);
		}
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} else {
		$items[] = array(
			'label' => wp_get_document_title(),
			'url'   => '',
		);
	}

	$html  = '<nav class="breadcrumb" aria-label="' . esc_attr__( 'Você está em', 'lar-andre-luiz' ) . '">';
	$html .= '<span class="sep" aria-hidden="true">|</span>';

	$last = count( $items ) - 1;

	foreach ( $items as $i => $item ) {
		if ( $i === $last ) {
			$html .= '<span class="current" aria-current="page">' . esc_html( $item['label'] ) . '</span>';
			break;
		}

		$html .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		$html .= '<i class="fas fa-chevron-right sep" aria-hidden="true"></i>';
	}

	$html .= '</nav>';

	return $html;
}

/* -------------------------------------------------------------------------
 * 8. Capa (hero) das páginas — metabox
 * ---------------------------------------------------------------------- */

require_once get_template_directory() . '/inc/meta-boxes.php';

/* -------------------------------------------------------------------------
 * 9. Tipos de conteúdo e formulários
 * ---------------------------------------------------------------------- */

require_once get_template_directory() . '/inc/cpt-prestacao.php';
require_once get_template_directory() . '/inc/prestacao-fields.php';
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/form-shortcode.php';

/* -------------------------------------------------------------------------
 * 10. Utilidades de template
 * ---------------------------------------------------------------------- */

/**
 * Renderiza a capa de uma página a partir dos metadados.
 *
 * @param int|null $post_id ID da página.
 */
function lar_render_hero( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$hero    = function_exists( 'lar_get_hero' ) ? lar_get_hero( $post_id ) : array();

	// Caminhos "assets/..." gravados no admin viram URLs do tema.
	$video  = function_exists( 'lar_asset_meta_url' ) ? lar_asset_meta_url( $hero['video'] ?? '' ) : ( $hero['video'] ?? '' );
	$poster = function_exists( 'lar_asset_meta_url' ) ? lar_asset_meta_url( $hero['poster'] ?? '' ) : ( $hero['poster'] ?? '' );

	$title = ! empty( $hero['title'] ) ? $hero['title'] : get_the_title( $post_id );
	$crumb = lar_breadcrumb();
	?>
	<div class="hero<?php echo $crumb ? ' has-crumb' : ''; ?>">
		<?php if ( $video ) : ?>
			<video autoplay muted loop playsinline preload="metadata"
				<?php if ( $poster ) : ?>
					poster="<?php echo esc_url( $poster ); ?>"
				<?php endif; ?>
				src="<?php echo esc_url( $video ); ?>"></video>
		<?php endif; ?>

		<?php if ( $poster ) : ?>
			<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $title ); ?>">
		<?php endif; ?>

		<div class="overlay"></div>

		<div class="hero-inner">
			<?php if ( $crumb ) : ?>
				<div class="crumb-row on-hero">
					<a class="crumb-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<i class="fas fa-arrow-left" aria-hidden="true"></i>
						<?php esc_html_e( 'Voltar', 'lar-andre-luiz' ); ?>
					</a>
					<?php echo $crumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $hero['eyebrow'] ) ) : ?>
				<div class="eyebrow" style="background:var(--yellow-light);color:var(--ink);"><?php echo esc_html( $hero['eyebrow'] ); ?></div>
			<?php endif; ?>

			<h1><?php echo lar_kses_layout( $title ); ?></h1>

			<?php if ( ! empty( $hero['subtitle'] ) ) : ?>
				<p><?php echo lar_kses_layout( $hero['subtitle'] ); ?></p>
			<?php endif; ?>

			<?php
			/*
			 * Botões, estatísticas e demais elementos do design que ficavam
			 * dentro da capa no template original.
			 */
			if ( ! empty( $hero['extra'] ) ) {
				echo lar_kses_layout( lar_resolve_assets( $hero['extra'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Link do botão "Doe aqui" (a donation modal fica no footer).
 *
 * @param array $atts Atributos.
 * @return string
 */
function lar_donate_button( $atts = array() ) {
	$atts = wp_parse_args(
		$atts,
		array(
			'class' => 'doe',
			'label' => __( 'Doe aqui', 'lar-andre-luiz' ),
		)
	);

return sprintf(
		'<button type="button" class="%1$s" data-lar-open="donation">%2$s</button>',
		esc_attr( $atts['class'] ),
		esc_html( $atts['label'] )
	);
}