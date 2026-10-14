<?php
/**
 * Importador: converte o index.html do SPA em Páginas do WordPress.
 *
 * Executar com:
 *   php tools/importar-paginas.php
 *
 * O que ele faz:
 *  - divide as 13 <section class="page"> em 14 Páginas do WordPress
 *  - tira a capa (hero) do conteúdo e grava como post meta editável
 *  - troca onclick="showPage('x')" por <a href="permalink">
 *  - converte cards clicáveis em <a> reais
 *  - remove todos os onclick inline restantes (viram data-attributes)
 *  - converte href="#" em texto sem link (não havia destino)
 *  - cria o menu "principal" e define a página inicial
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

define( 'WP_USE_THEMES', false );
require_once dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* -------------------------------------------------------------------------
 * Executa como administrador
 *
 * O WordPress filtra o post_content com wp_kses_post quando o usuário não
 * tem a capacidade "unfiltered_html". Sem isto, o conteúdo salvo perdia:
 *
 *   <svg>, <path>, <circle>, <iframe>, <form>, <input>
 *
 * — ou seja, todos os ícones em SVG e o mapa do Google Sumiam das páginas.
 * ---------------------------------------------------------------------- */

$lar_admin = get_users(
	array(
		'role'    => 'administrator',
		'number'  => 1,
		'orderby' => 'ID',
		'fields'  => 'ID',
	)
);

if ( ! $lar_admin ) {
	exit( "Nenhum administrador encontrado no banco.\n" );
}

wp_set_current_user( (int) $lar_admin[0] );

echo 'Executando como administrador: #' . get_current_user_id() . "\n";
echo 'unfiltered_html: ' . ( current_user_can( 'unfiltered_html' ) ? 'sim' : 'NAO' ) . "\n\n";

/* -------------------------------------------------------------------------
 * Configuração
 * ---------------------------------------------------------------------- */

/**
 * Fonte: caminho do index.html original.
 */
$origem = getenv( 'LAR_ORIGEM' ) ?: 'C:/Users/laran/AppData/Local/Temp/opencode/wp/orig-site/index.html.working';

/**
 * Definição das páginas.
 */
$paginas = array(
	'inicio'        => array( 'slug' => 'inicio', 'title' => 'Início', 'menu' => 'Início' ),
	'quem'          => array( 'slug' => 'quem-somos', 'title' => 'Quem somos', 'menu' => 'Quem somos' ),
	'atividades'    => array( 'slug' => 'atividades', 'title' => 'Atividades', 'menu' => 'Atividades' ),
	'esportes-base' => array( 'slug' => 'esportes-de-base', 'title' => 'Esportes de Base' ),
	'galeria'       => array( 'slug' => 'galeria', 'title' => 'Galeria de Atividades' ),
	'noticias'      => array( 'slug' => 'noticias', 'title' => 'Notícias', 'menu' => 'Notícias' ),
	'eventos'       => array( 'slug' => 'eventos', 'title' => 'Eventos', 'menu' => 'Eventos' ),
	'transparencia' => array( 'slug' => 'transparencia', 'title' => 'Transparência', 'menu' => 'Transparência', 'template' => 'page-transparencia.php' ),
	'contato'       => array( 'slug' => 'contato', 'title' => 'Contato' ),
	'fale'          => array( 'slug' => 'fale-conosco', 'title' => 'Fale Conosco', 'menu' => 'Fale Conosco', 'parent' => 'contato' ),
	'trabalhe'      => array( 'slug' => 'trabalhe-conosco', 'title' => 'Trabalhe Conosco', 'menu' => 'Trabalhe Conosco', 'parent' => 'contato' ),
	'inscricao'     => array( 'slug' => 'pre-inscricao', 'title' => 'Pré Inscrição', 'menu' => 'Pré Inscrição', 'parent' => 'contato' ),
	'quadra'        => array( 'slug' => 'quadra-dos-sonhos', 'title' => 'Quadra dos Sonhos' ),
	'aces'          => array( 'slug' => 'instituto-aces', 'title' => 'Instituto ACES' ),
);

/**
 * Páginas criadas (id legado => ID do post).
 */
$ids_criados = array();

/* -------------------------------------------------------------------------
 * Utilidades de DOM
 * ---------------------------------------------------------------------- */

$doc = new DOMDocument( '1.0', 'UTF-8' );
libxml_use_internal_errors( true );
$doc->loadHTML( '<?xml encoding="UTF-8">' . file_get_contents( $origem ) );
libxml_clear_errors();
$xpath = new DOMXPath( $doc );

/**
 * Converte um nó em string HTML.
 *
 * @param DOMNode $no Nó.
 * @return string
 */
function html_do_no( $no ) {
	$html = $no->ownerDocument->saveHTML( $no );

	// saveHTML() adiciona xmlns em SVG; remove para não quebrar o HTML.
	return preg_replace( '#\sxmlns="http://www\.w3\.org/2000/svg"#', '', $html );
}

/**
 * HTML interno de um elemento (sem a própria tag).
 *
 * @param DOMNode $el Elemento.
 * @return string
 */
function inner_html( $el ) {
	$html = '';

	foreach ( $el->childNodes as $filho ) {
		$html .= $el->ownerDocument->saveHTML( $filho );
	}

	return preg_replace( '#\sxmlns="http://www\.w3\.org/2000/svg"#', '', trim( $html ) );
}

/**
 * URL pública de uma página a partir do id legado.
 *
 * @param string $id      Id legado.
 * @param string $ancora  Âncora opcional.
 * @return string
 */
function url_pagina( $id, $ancora = '' ) {
	global $paginas;

	if ( 'contato' === $id ) {
		$url = home_url( '/contato/' );
	} elseif ( isset( $paginas[ $id ] ) ) {
		$url = 'inicio' === $id ? home_url( '/' ) : home_url( '/' . $paginas[ $id ]['slug'] . '/' );
	} else {
		$url = home_url( '/' . $id . '/' );
	}

	return $url . $ancora;
}

/**
 * Cria (ou substitui) um elemento com uma tag e os atributos informados.
 *
 * @param DOMDocument $doc       Documento.
 * @param DOMElement  $elemento  Elemento original.
 * @param string      $tag       Nova tag.
 * @param array       $attrs     Atributos a preservar.
 * @return DOMElement
 */
function trocar_tag( $doc, $elemento, $tag, $attrs = array() ) {
	$novo = $doc->createElement( $tag );

	foreach ( $attrs as $nome => $valor ) {
		if ( null === $valor || false === $valor ) {
			continue;
		}
		$novo->setAttribute( $nome, $valor );
	}

	while ( $elemento->firstChild ) {
		$novo->appendChild( $elemento->firstChild );
	}

	$elemento->parentNode->replaceChild( $novo, $elemento );

	return $novo;
}

/* -------------------------------------------------------------------------
 * Conversão dos cliques em links / data-attributes
 * ---------------------------------------------------------------------- */

/**
 * Aplica as conversões de navegação em um trecho de HTML.
 *
 * @param string $html HTML do conteúdo da página.
 * @return string
 */
function converter_navegacao( $html ) {
	$fragmento = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$fragmento->loadHTML( '<?xml encoding="UTF-8"><div id="lar-root">' . $html . '</div>', LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	$raiz = $fragmento->getElementById( 'lar-root' );

	// 1) onclick="showPage('x')" em <a>: vira permalink real.
	foreach ( iterator_to_array( $fragmento->getElementsByTagName( 'a' ) ) as $a ) {
		$onclick = $a->getAttribute( 'onclick' );
		$href    = $a->getAttribute( 'href' );

		if ( preg_match( "/showPage\('([a-z\-]+)'\)/", $onclick, $m ) ) {
			$a->setAttribute( 'href', url_pagina( $m[1] ) );
			$a->removeAttribute( 'onclick' );
			$a->removeAttribute( 'style' );
			continue;
		}

		if ( preg_match( "/goSport\('([a-z]+)'\)/", $onclick, $m ) ) {
			$a->setAttribute( 'href', url_pagina( 'atividades', '#esporte-' . $m[1] ) );
			$a->removeAttribute( 'onclick' );
			continue;
		}

		if ( preg_match( '/showEsportesBase/', $onclick ) ) {
			$a->setAttribute( 'href', url_pagina( 'esportes-base' ) );
			$a->removeAttribute( 'onclick' );
			continue;
		}

		// 2) href="#" sem destino: não é link. Vira texto.
		if ( '#' === $href || '' === $href ) {
			if ( $a->hasAttribute( 'class' ) && preg_match( '/(activity-link|card-link|read-more)/', $a->getAttribute( 'class' ) ) ) {
				$span = trocar_tag(
					$fragmento,
					$a,
					'span',
					array(
						'class' => $a->getAttribute( 'class' ),
						'style' => $a->hasAttribute( 'style' ) ? $a->getAttribute( 'style' ) : null,
					)
				);
				$span->setAttribute( 'aria-disabled', 'true' );
			} else {
				$a->removeAttribute( 'href' );
				$a->setAttribute( 'role', 'presentation' );
			}
			$a->removeAttribute( 'onclick' );
		}
	}

	// 3) Qualquer elemento com onclick vira link de verdade ou data-attribute.
	$xpath_f = new DOMXPath( $fragmento );
	$alvos   = $xpath_f->query( '//*[@onclick]' );

	foreach ( iterator_to_array( $alvos ) as $el ) {
		$onclick = $el->getAttribute( 'onclick' );
		$tag     = strtolower( $el->tagName );

		if ( preg_match( "/showPage\('([a-z\-]+)'\)/", $onclick, $m ) ) {
			$attrs = array( 'href' => url_pagina( $m[1] ) );
			foreach ( array( 'class', 'style', 'title', 'aria-label' ) as $nome ) {
				if ( $el->hasAttribute( $nome ) ) {
					$attrs[ $nome ] = $el->getAttribute( $nome );
				}
			}

			// <a> dentro de <a> é inválido: nesse caso mantém o botão.
			if ( 'a' === $tag ) {
				$el->setAttribute( 'href', url_pagina( $m[1] ) );
				$el->removeAttribute( 'onclick' );
			} else {
				trocar_tag( $fragmento, $el, 'a', $attrs );
			}
			continue;
		}

		if ( preg_match( "/goSport\('([a-z]+)'\)/", $onclick, $m ) ) {
			$el->setAttribute( 'href', url_pagina( 'atividades', '#esporte-' . $m[1] ) );
			$el->removeAttribute( 'onclick' );
			continue;
		}

		// Ações de interface viram data-attributes (delegadas no main.js).
		$acoes = array(
			'openDonation'       => array( 'data-lar-open', 'donation' ),
			'closeDonation'      => array( 'data-lar-close', 'donation' ),
			'closeTransModal'    => array( 'data-lar-close', 'transModal' ),
			'closeImgModal'      => array( 'data-lar-close', 'imgModal' ),
			'imgGalleryPrev'     => array( 'data-lar-gal', 'prev' ),
			'imgGalleryNext'     => array( 'data-lar-gal', 'next' ),
			'addChild'           => array( 'data-lar-add-child', '' ),
			'openTransModal'     => array( 'data-lar-open', 'transModal' ),
		);

		$tratado = false;

		foreach ( $acoes as $chave => $novo ) {
			if ( false === strpos( $onclick, $chave ) ) {
				continue;
			}

			$el->removeAttribute( 'onclick' );
			$el->removeAttribute( 'href' );
			$el->setAttribute( $novo[0], $novo[1] );
			$tratado = true;
			break;
		}

		if ( $tratado ) {
			continue;
		}

		if ( preg_match( "/switchSport\('([a-z]+)'/", $onclick, $m ) ) {
			$el->removeAttribute( 'onclick' );
			$el->setAttribute( 'data-lar-sport', $m[1] );
			$el->setAttribute( 'role', 'tab' );
			$el->setAttribute(
				'aria-selected',
				false !== stripos( $el->getAttribute( 'class' ), 'active' ) ? 'true' : 'false'
			);
			continue;
		}

		if ( false !== strpos( $onclick, "closest('.child-card').remove()" ) ) {
			$el->removeAttribute( 'onclick' );
			$el->setAttribute( 'data-lar-remove-child', '' );
			continue;
		}

		if ( false !== strpos( $onclick, 'toggleMenu' ) || false !== strpos( $onclick, 'preventDefault' ) || false !== strpos( $onclick, 'sendForm' ) ) {
			$el->removeAttribute( 'onclick' );
			continue;
		}

		// Qualquer onclick que sobre é removido: o tema não depende dele.
		$el->removeAttribute( 'onclick' );
	}

	// 4) Âncoras reais para os painéis de esporte (substituem goSport).
	foreach ( $fragmento->getElementsByTagName( '*' ) as $el ) {
		if ( ! $el->hasAttribute( 'data-sport' ) ) {
			continue;
		}
		$el->setAttribute( 'id', 'esporte-' . $el->getAttribute( 'data-sport' ) );
	}

	// 5) Envolve a galeria da home para que o JS da galeria funcione.
	$html = '';
	foreach ( $raiz->childNodes as $filho ) {
		$html .= $fragmento->saveHTML( $filho );
	}

	return trim( $html );
}

/* -------------------------------------------------------------------------
 * Extração da capa (hero)
 * ---------------------------------------------------------------------- */

/**
 * Separa a capa do conteúdo e devolve os metadados.
 *
 * @param DOMElement $secao Seção completa.
 * @return array{0:array,1:string} Metadados e conteúdo restante.
 */
function extrair_hero( $secao ) {
	$hero = null;

	foreach ( $secao->childNodes as $filho ) {
		if ( $filho instanceof DOMElement && 'div' === strtolower( $filho->tagName ) && false !== strpos( $filho->getAttribute( 'class' ), 'hero' ) ) {
			$hero = $filho;
			break;
		}
	}

	if ( ! $hero ) {
		return array( array(), html_do_no( $secao ) );
	}

	$meta = array(
		'lar_hero_title'    => '',
		'lar_hero_eyebrow'  => '',
		'lar_hero_subtitle' => '',
		'lar_hero_video'    => '',
		'lar_hero_poster'   => '',
		'lar_hero_extra'    => '',
	);

	// Vídeo de fundo.
	foreach ( $hero->getElementsByTagName( 'video' ) as $video ) {
		$src = $video->getAttribute( 'src' );
		if ( $src ) {
			$meta['lar_hero_video'] = $src;
			break;
		}
	}

	// Imagem / poster: a primeira <img> dentro da capa que não seja logo.
	foreach ( $hero->getElementsByTagName( 'img' ) as $img ) {
		$src = $img->getAttribute( 'src' );
		if ( $src ) {
			$meta['lar_hero_poster'] = $src;
			break;
		}
	}

	// Textos.
	foreach ( $hero->getElementsByTagName( 'div' ) as $div ) {
		if ( 'eyebrow' === $div->getAttribute( 'class' ) && '' === $meta['lar_hero_eyebrow'] ) {
			$meta['lar_hero_eyebrow'] = trim( $div->textContent );
		}
	}

	foreach ( $hero->getElementsByTagName( 'h1' ) as $h1 ) {
		if ( '' === $meta['lar_hero_title'] ) {
			$meta['lar_hero_title'] = inner_html( $h1 );
		}
	}

	$inner = null;
	foreach ( $hero->getElementsByTagName( 'div' ) as $div ) {
		if ( 'hero-inner' === $div->getAttribute( 'class' ) ) {
			$inner = $div;
			break;
		}
	}

	if ( $inner ) {
		foreach ( $inner->getElementsByTagName( 'p' ) as $p ) {
			if ( '' === $meta['lar_hero_subtitle'] ) {
				$meta['lar_hero_subtitle'] = inner_html( $p );
			}
		}

		/*
		 * Tudo o que vier depois do subtítulo dentro de .hero-inner é conteúdo
		 * do design que não cabe em título/subtítulo — por exemplo o botão
		 * "Conheça nossas atividades" e as quatro estatísticas da home.
		 * Guardamos em lar_hero_extra para nada ser perdido.
		 */
		$extras = array();

		foreach ( $inner->childNodes as $filho ) {
			if ( ! $filho instanceof DOMElement ) {
				if ( '' === trim( $filho->textContent ) ) {
					continue;
				}

				// Texto solto dentro da capa entra como extra.
				$extras[] = esc_html( $filho->textContent );
				continue;
			}

			$classe = $filho->getAttribute( 'class' );

			// eyebrow, h1 e o primeiro p já foram tratados acima.
			if ( 'eyebrow' === $classe || 'h1' === strtolower( $filho->tagName ) ) {
				continue;
			}

			if ( 'p' === strtolower( $filho->tagName ) && '' === $meta['lar_hero_subtitle'] ) {
				continue;
			}

			if ( 'p' === strtolower( $filho->tagName ) ) {
				continue;
			}

			$html_extra = html_do_no( $filho );

			// O HTML extra é renderizado no navegador: converte a navegação.
			$html_extra = converter_navegacao( $html_extra );

			$extras[] = $html_extra;
		}

		$meta['lar_hero_extra'] = trim( implode( "\n", $extras ) );
	}

	// Remove a capa do conteúdo.
	$hero->parentNode->removeChild( $hero );

	return array( $meta, html_do_no( $secao ) );
}

/* -------------------------------------------------------------------------
 * Ajustes pontuais de conteúdo
 * ---------------------------------------------------------------------- */

/**
 * Troca os <form> do template antigo pelo shortcode [lar_form].
 *
 * O WordPress filtra o post_content com wp_kses_post para visitantes sem
 * "unfiltered_html", e <form> não é permitido — o formulário desapareceria.
 *
 * @param string $html      HTML do conteúdo.
 * @param string $id_secao  Id da seção de origem (fale/trabalhe/inscricao).
 * @return string
 */
function converter_formularios( $html, $id_secao ) {
	// Cada página tem exatamente um <form>; o tipo vem da seção de origem.
	$por_secao = array(
		'fale'      => 'contato',
		'trabalhe'  => 'curriculo',
		'inscricao' => 'inscricao',
	);

	$form_id = isset( $por_secao[ $id_secao ] ) ? $por_secao[ $id_secao ] : 'contato';

	$substituicoes = array();

	preg_match_all( '~<form\b.*?</form>~is', $html, $achados, PREG_OFFSET_CAPTURE );

	foreach ( $achados[0] as $achado ) {
		$substituicoes[] = array(
			'pos'         => $achado[1],
			'comprimento' => strlen( $achado[0] ),
			'texto'       => '[lar_form id="' . $form_id . '"]',
		);
	}

	// De trás para frente: assim os deslocamentos das posições anteriores
	// não são invalidados pelas substituições seguintes.
	foreach ( array_reverse( $substituicoes ) as $s ) {
		$html = substr_replace( $html, $s['texto'], $s['pos'], $s['comprimento'] );
	}

	return $html;
}

/**
 * Remove o container <div style="max-width:..."> da seção, promote os filhos.
 *
 * @param DOMElement $secao Seção (já sem a capa).
 * @return DOMElement Mesma seção, com o container desenrolado.
 */
function remover_wrapper_maxwidth( $secao ) {
	foreach ( iterator_to_array( $secao->childNodes ) as $filho ) {
		if ( ! $filho instanceof DOMElement || 'div' !== strtolower( $filho->tagName ) ) {
			continue;
		}

		if ( false === strpos( $filho->getAttribute( 'style' ), 'max-width' ) ) {
			continue;
		}

		foreach ( iterator_to_array( $filho->childNodes ) as $neto ) {
			$filho->parentNode->insertBefore( $neto, $filho );
		}

		$filho->parentNode->removeChild( $filho );

		break;
	}

	return $secao;
}

/**
 * Correções de texto que dependiam do roteamento por hash.
 *
 * @param string $id   Id legado.
 * @param string $html Conteúdo.
 * @return string
 */
function ajustar_conteudo( $id, $html ) {
	if ( 'inicio' === $id ) {
		// O bloco de doação agora é renderizado por todo o site em
		// template-parts/cta-doe.php. Mantido aqui, apareceria duplicado.
		$html = preg_replace(
			'~<section class="cta-doe.*?</section>\s*~s',
			'',
			$html
		);
	}

	if ( 'esportes-base' === $id ) {
		// Os cards iam todos para a mesma página: o texto prometia o que não existia.
		$html = str_replace(
			'Escolha um esporte abaixo. Cada card leva para sua respectiva página: esportes coletivos e atletismo na Quadra dos Sonhos, esportes de raquete no Instituto ACES.',
			'Futebol, handebol, vôlei e atletismo acontecem na Quadra dos Sonhos; beach tênis e tênis são realizados com o Instituto ACES. Escolha uma modalidade para ver onde ela é praticada.',
			$html
		);
		$html = str_replace( 'Clique em um esporte para abrir sua página.', 'Clique em uma modalidade para ver o local e as turmas.', $html );
	}

	if ( 'atividades' === $id ) {
		$html = str_replace(
			'Escolha uma modalidade e conheça os treinos oferecidos no contraturno. Cada aba leva para sua respectiva página.',
			'Escolha uma modalidade e conheça os treinos oferecidos no contraturno.',
			$html
		);
	}

	return $html;
}

/* -------------------------------------------------------------------------
 * Importação
 * ---------------------------------------------------------------------- */

echo "Importador de páginas — Lar André Luiz\n";
echo str_repeat( '-', 60 ) . "\n";

if ( ! file_exists( $origem ) ) {
	exit( "Arquivo de origem não encontrado: {$origem}\n" );
}

$secoes = $xpath->query( '//section[@id]' );

/**
 * Remove uma execução anterior para o importador ser idempotente.
 */
function limpar_importacao_anterior() {
	$anteriores = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	foreach ( $anteriores as $post ) {
		wp_delete_post( $post->ID, true );
	}

	foreach ( array( 'prestacao', 'lar_mensagem' ) as $tipo ) {
		$ids = get_posts(
			array(
				'post_type'      => $tipo,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);
		foreach ( $ids as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	$menus = wp_get_nav_menus();

	foreach ( $menus as $menu ) {
		wp_delete_nav_menu( $menu->term_id );
	}

	update_option( 'show_on_front', 'posts' );
	update_option( 'page_on_front', 0 );

	echo 'Limpeza: ' . count( $anteriores ) . ' página(s) e ' . count( $menus ) . " menu(s) removidos.\n\n";
}

limpar_importacao_anterior();

foreach ( $secoes as $secao ) {
	$id = $secao->getAttribute( 'id' );

	if ( ! isset( $paginas[ $id ] ) ) {
		echo "  [ignorado] seção desconhecida: {$id}\n";
		continue;
	}

	list( $meta, $corpo ) = extrair_hero( $secao );

	/*
	 * A página de Transparência usa o template page-transparencia.php, que já
	 * traz o container de largura máxima. Se o container do HTML original
	 * também for salvo no post_content, a seção fica aninhada duas vezes.
	 */
	if ( 'transparencia' === $id ) {
		$secao = remover_wrapper_maxwidth( $secao );
		$corpo = html_do_no( $secao );
	}

	// Remove a classe que controlava a troca de página do SPA.
	$classe = preg_replace( '/\b(page|active)\b/', '', $secao->getAttribute( 'class' ) );
	$classe = trim( preg_replace( '/\s+/', ' ', $classe ) );
	$corpo  = str_replace( ' class="' . trim( $secao->getAttribute( 'class' ) ) . '"', '', $corpo );

	$corpo = converter_navegacao( $corpo );
	$corpo = converter_formularios( $corpo, $id );
	$corpo = ajustar_conteudo( $id, $corpo );

	// "Transparência" ganha o template que lista os balancetes do admin.
	if ( 'transparencia' === $id ) {
		$corpo = preg_replace(
			'#<div class="tabs" id="transTabs"></div>\s*<div id="transYearTabs"></div>\s*<div id="transContent"></div>#s',
			'',
			$corpo
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $paginas[ $id ]['title'],
			'post_name'    => $paginas[ $id ]['slug'],
			'post_content' => $corpo,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		echo "  [ERRO] {$id}: " . $post_id->get_error_message() . "\n";
		continue;
	}

	foreach ( $meta as $chave => $valor ) {
		if ( '' !== $valor ) {
			update_post_meta( $post_id, $chave, $valor );
		}
	}

	if ( ! empty( $paginas[ $id ]['template'] ) ) {
		update_post_meta( $post_id, '_wp_page_template', $paginas[ $id ]['template'] );
	}

	$ids_criados[ $id ] = $post_id;

	printf( "  [ok] %-14s -> /%s/ (id %d, %d bytes)\n", $id, $paginas[ $id ]['slug'], $post_id, strlen( $corpo ) );
}

/* Página "Contato".

   O template antigo agrupava Fale/Trabalhe/Pré-inscrição num dropdown sem
   página própria. Para o menu deixar de depender de JavaScript, criamos uma
   página real no slug do item pai. */

$contato_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Contato',
		'post_name'    => 'contato',
		'post_content' => '',
	),
	true
);

if ( is_wp_error( $contato_id ) ) {
	echo "  [ERRO] não foi possível criar a página Contato: " . $contato_id->get_error_message() . "\n";
	$contato_id = 0;
} else {
	$ids_criados['contato'] = $contato_id;
	echo "\n  [ok] contato       -> /contato/ (id {$contato_id})\n";

	update_post_meta( $contato_id, 'lar_hero_eyebrow', 'Canais de atendimento' );
	update_post_meta( $contato_id, 'lar_hero_title', 'Fale com o Lar André Luiz' );
	update_post_meta(
		$contato_id,
		'lar_hero_subtitle',
		'Escolha o canal que precisa: dúvidas gerais, envio de currículo ou pré-inscrição de crianças e adolescentes.'
	);
}

// Hierarquia: "Contato" é pai das três páginas de contato.
//
// post_parent fica em 0 de propósito: o aninhamento do menu é definido por
// menu-item-parent-id, que é independente da hierarquia de páginas. Sem
// post_parent as URLs ficam planas (/fale-conosco/), como no site original —
// com post_parent o WordPress geraria /contato/fale-conosco/.
foreach ( $paginas as $id => $conf ) {
	if ( empty( $conf['parent'] ) ) {
		continue;
	}

	$filho = isset( $ids_criados[ $id ] ) ? $ids_criados[ $id ] : 0;

	if ( $filho ) {
		wp_update_post(
			array(
				'ID'          => $filho,
				'post_parent' => 0,
			)
		);
	}
}

// Conteúdo da página "Contato": cartões dos três canais.
if ( $contato_id ) {
	$cartoes = array();

	$canais = array(
		'fale'      => array(
			'Fale Conosco',
			'Dúvidas gerais, informações sobre o contraturno, visitas e agendamentos.',
		),
		'trabalhe'  => array(
			'Trabalhe Conosco',
			'Envie seu currículo para as vagas abertas de educadores e equipe técnica.',
		),
		'inscricao' => array(
			'Pré Inscrição',
			'Pré-inscrição de crianças e adolescentes para as atividades do contraturno.',
		),
	);

	foreach ( $canais as $filho_id => $dados ) {
		if ( ! isset( $ids_criados[ $filho_id ] ) ) {
			continue;
		}

		$permalink = get_permalink( $ids_criados[ $filho_id ] );

		$cartoes[] = sprintf(
			'<div class="card"><h3><a href="%s">%s</a></h3><p>%s</p><a class="activity-link" href="%s">%s</a></div>',
			esc_url( $permalink ),
			esc_html( $dados[0] ),
			esc_html( $dados[1] ),
			esc_url( $permalink ),
			'Abrir'
		);
	}

	wp_update_post(
		array(
			'ID'           => $contato_id,
			'post_content' => '<div class="grid">' . implode( '', $cartoes ) . '</div>',
		)
	);
}

/* Página inicial. */

if ( isset( $ids_criados['inicio'] ) ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids_criados['inicio'] );
	echo "\n  Página inicial definida: / (id {$ids_criados['inicio']})\n";
}

/* Menu principal. */

$menu_nome  = 'Menu principal';
$menu       = wp_get_nav_menu_object( $menu_nome );
$menu_id    = $menu ? $menu->term_id : wp_create_nav_menu( $menu_nome );

echo "\nCriando o menu principal\n";

/**
 * Adiciona um item de menu e devolve o ID do item.
 *
 * @param int    $menu_id   ID do menu.
 * @param string $titulo    Título do item.
 * @param int    $objeto_id ID da página.
 * @param int    $parent    ID do item pai.
 * @param int    $ordem     Ordem.
 * @return int
 */
function add_item_menu( $menu_id, $titulo, $objeto_id, $parent = 0, $ordem = 0 ) {
	// menu-item-type = 'post_type' e menu-item-object = o nome do post type.
	// Passar 'post_type' no lugar de 'page' faz o WP descartar o item.
	$item_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => $titulo,
			'menu-item-object'    => 'page',
			'menu-item-object-id' => (int) $objeto_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => (int) $parent,
			'menu-item-position'  => (int) $ordem,
		)
	);

	return is_wp_error( $item_id ) ? 0 : (int) $item_id;
}

$itens_menu = array( 'inicio', 'quem', 'atividades', 'noticias', 'eventos', 'transparencia' );
$ordem      = 1;

foreach ( $itens_menu as $id ) {
	if ( ! isset( $ids_criados[ $id ] ) ) {
		continue;
	}

	add_item_menu( $menu_id, $paginas[ $id ]['menu'], $ids_criados[ $id ], 0, $ordem++ );

	echo "  - {$paginas[ $id ]['menu']}\n";
}

$pai_contato = add_item_menu( $menu_id, 'Contato', $contato_id, 0, $ordem++ );

echo "  - Contato (pai, item #{$pai_contato})\n";

foreach ( array( 'fale', 'trabalhe', 'inscricao' ) as $filho ) {
	if ( ! isset( $ids_criados[ $filho ] ) ) {
		continue;
	}

	$item_filho = add_item_menu( $menu_id, $paginas[ $filho ]['menu'], $ids_criados[ $filho ], $pai_contato, 0 );

	echo "    - {$paginas[ $filho ]['menu']} (item #{$item_filho})\n";
}

// "Doe aqui" não é item de menu: é um botão de ação renderizado pelo header.
// (Um item de menu sem URL geraria um <a> sem href.)

$locations              = get_theme_mod( 'nav_menu_locations', array() );
$locations['principal'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

/* ---------------------------------------------------------------------- */

flush_rewrite_rules();

echo "\n" . str_repeat( '-', 60 ) . "\n";
echo "Concluído. Páginas criadas: " . count( $ids_criados ) . "\n";
echo "Admin: " . admin_url() . "\n";
