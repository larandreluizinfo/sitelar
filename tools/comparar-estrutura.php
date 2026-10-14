<?php
/**
 * Compara a estrutura do HTML original (index.html do SPA) com o HTML
 * renderizado pelo tema.
 *
 * É esta comparação que revela por que a página "ficou feia": se uma
 * classe ou um elemento estrutural do design system se perdeu na
 * conversão, o CSS deixa de casar e o layout quebra.
 *
 * Para cada página:
 *   - extrai o "esqueleto" (tag + classe) do HTML original
 *   - extrai o mesmo esqueleto do HTML renderizado
 *   - reporta elementos faltando, sobrando ou com classe diferente
 *
 * Uso: php tools/comparar-estrutura.php [rota]
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

$base   = getenv( 'LAR_URL' ) ?: 'http://sitelar.test';
$origem = getenv( 'LAR_ORIGEM' ) ?: 'C:/Users/laran/AppData/Local/Temp/opencode/wp/orig-site/index.html.working';

/**
 * Mapa seção original => rota renderizada.
 */
$rotas = array(
	'inicio'        => '/',
	'quem'          => '/quem-somos/',
	'atividades'    => '/atividades/',
	'esportes-base' => '/esportes-de-base/',
	'galeria'       => '/galeria/',
	'noticias'      => '/noticias/',
	'eventos'       => '/eventos/',
	'transparencia' => '/transparencia/',
	'fale'          => '/fale-conosco/',
	'trabalhe'      => '/trabalhe-conosco/',
	'inscricao'     => '/pre-inscricao/',
	'quadra'        => '/quadra-dos-sonhos/',
	'aces'          => '/instituto-aces/',
);

/**
 * Classes que o tema/adiciona e que não existem no original.
 * Não contam como "classe trocada".
 */
$ignoradas = array(
	'page-wrap',
	'has-crumb',
	'crumb-row',
	'on-hero',
	'breadcrumb',
	'current-menu-item',
	'lar-transparencia',
	'lar-empty',
	'screen-reader-text',
	'skip-link',
	'menu-list',
	'dd',
	'modern-form',
	'lar-notice',
);

/**
 * Elementos que não fazem parte do conteúdo da página.
 */
// 'button' NÃO é ignorado: botões fora de <form> viram <a> e precisam
	// ser contados para a comparação. Botões dentro de form não são
	// visitados porque a walk para no próprio <form>.
	$ignorar_tags = array(
		'form',
		'input',
		'textarea',
		'select',
		'option',
		'label',
		'script',
		'style',
		'video',
		'source',
	);

/**
 * Conversões feitas de propósito durante a migração para o WordPress.
 *
 *original -> novo O comparador desconta estas diferenças para que reste
 * apenas o que realmente quebrou.
 */
$conversoes = array(
	// A <section class="page"> do SPA virou a <section id="slug"> da página.
	'section.active.page' => 'section',
	'section.page'        => 'section',

	// Cards que eram <div onclick> viraram link de verdade.
	'div.card.spot'    => 'a.card.spot',
	'div.activity.card' => 'a.activity.card',
	'div.news-hero'    => 'a.news-hero',

	// Botões de ação dentro do hero viraram link real.
	'button.cta' => 'a.cta',

	// Links sem destino: viraram texto, não link.
	'a.activity-link' => 'span.activity-link',
	'a.card-link'     => 'span.card-link',
);

/**
 * Elementos que a página de transparência não usa mais.
 *
 * As abas eram montadas por JavaScript com números aleatórios; agora vêm
 * do post type "prestacao". O bloco antigo foi retirado de propósito.
 */
$removido_de_propósito = array(
	'transparencia' => array( 'div.tabs', 'div.lar-transparencia' ),
);

/**
 * Aplica as conversões esperadas ao esqueleto original.
 *
 * @param array  $contador     Contador de tokens do original.
 * @param string $rota         Rota.
 * @return array
 */
function aplicar_conversoes( array $contador, $rota ) {
	global $conversoes, $removido_de_propósito;

	$saida = array();

	foreach ( $contador as $token => $qtd ) {
		$destino = isset( $conversoes[ $token ] ) ? $conversoes[ $token ] : $token;

		// Elementos que a página abandonou de propósito.
		if ( in_array( $token, $removido_de_propósito[ $rota ] ?? array(), true ) ) {
			continue;
		}

		$saida[ $destino ] = isset( $saida[ $destino ] ) ? $saida[ $destino ] + $qtd : $qtd;
	}

	ksort( $saida );

	return $saida;
}

/**
 * Baixa uma URL.
 *
 * @param string $url URL.
 * @return string
 */
function html_de( $url ) {
	$ch = curl_init( $url );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT        => 40,
		)
	);
	$body = curl_exec( $ch );
	curl_close( $ch );

	return (string) $body;
}

/**
 * Carrega um HTML no DOM.
 *
 * @param string $html   HTML.
 * @param string $wrapper Tag que envolve o conteúdo.
 * @return DOMXPath
 */
function dom_de( $html, $wrapper = 'body' ) {
	$doc = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="UTF-8"><' . $wrapper . '>' . $html . '</' . $wrapper . '>' );
	libxml_clear_errors();

	return new DOMXPath( $doc );
}

/**
 * Normaliza um nó do HTML original para a forma que o tema deveria gerar.
 *
 * As conversões são deduzidas dos próprios atributos do original, e não de
 * uma lista fixa: assim "metade dos links virou span" continua correto.
 *
 * @param DOMElement $el    Nó.
 * @param string     $tag   Tag original.
 * @param array      $classes Classes originais.
 * @param string     $rota   Rota da página.
 * @return string|null Token normalizado, ou null para ignorar o nó.
 */
function normalizar_original( $el, $tag, array $classes, $rota ) {
	$href    = $el->getAttribute( 'href' );
	$onclick = $el->getAttribute( 'onclick' );

	// <section class="page"> do SPA virou <section id="slug"> da página.
	if ( in_array( 'page', $classes, true ) ) {
		$classes = array_diff( $classes, array( 'page', 'active' ) );
	}

	// Link sem destino: o tema troca por <span aria-disabled="true">.
	if ( 'a' === $tag && ( '#' === $href || '' === $href ) ) {
		$tag = 'span';
	}

	/*
	 * Bloco clicável: <div onclick> vira <a href>. Mas <button> só vira link
	 * quando o onclick navegava (showPage/goSport); ações de interface como
	 * switchSport e openDonation viram data-attribute e continuam button.
	 */
	$navegava = (bool) preg_match( '~showPage|goSport|showEsportesBase~', $onclick );

	if ( 'div' === $tag || 'section' === $tag || 'article' === $tag || ( 'button' === $tag && $navegava ) ) {
		if ( '' !== $onclick ) {
			$tag = 'a';
		}
	}

	/*
	 * A transparência trocou as abas em JS pelo post type "prestacao".
	 * Os três contêineres que o JavaScript preenchia não existem mais.
	 */
	if ( '/transparencia/' === $rota ) {
		$ids_abas = array( 'transTabs', 'transYearTabs', 'transContent' );

		if ( in_array( 'tabs', $classes, true ) || in_array( $el->getAttribute( 'id' ), $ids_abas, true ) ) {
			return null;
		}
	}

	sort( $classes );

	return $tag . ( $classes ? '.' . implode( '.', $classes ) : '' );
}

/**
 * Extrai o esqueleto tag.classe de uma árvore.
 *
 * @param DOMNode $no            Nó raiz.
 * @param array   $ignoradas     Classes ignoradas.
 * @param array   $ignorar_tags  Tags ignoradas.
 * @param bool    $pular_raiz    Não emitir o token do próprio nó raiz.
 * @param string  $rota          Rota, usada na normalização do original.
 * @return string[]
 */
function esqueleto( $no, array $ignoradas, array $ignorar_tags, $pular_raiz = false, $rota = '' ) {
	$tokens = array();

	/**
 * Blocos que são novos de propósito: o comparador não desce dentro deles.
 *
 * A lista de balancetes não existia no HTML original (era montada por JS a
 * partir de números aleatórios), então comparar os filhos não faz sentido.
 */
$pular_subarvore = array(
	'lar-transparencia',
	'lar-empty',
);

$andar = function ( $el ) use ( &$andar, &$tokens, $ignoradas, $ignorar_tags, $rota, $pular_subarvore ) {
		if ( ! $el instanceof DOMElement ) {
			return;
		}

		$tag = strtolower( $el->tagName );

		if ( in_array( $tag, $ignorar_tags, true ) ) {
			return;
		}

		$lista_classes = preg_split( '/\s+/', trim( $el->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );

		foreach ( $pular_subarvore as $classe ) {
			if ( in_array( $classe, (array) $lista_classes, true ) ) {
				return;
			}
		}

		$classes = array_filter(
			$lista_classes,
			function ( $c ) use ( $ignoradas ) {
				return ! in_array( $c, $ignoradas, true );
			}
		);

		sort( $classes );

		$token = $rota
			? normalizar_original( $el, $tag, $classes, $rota )
			: ( $tag . ( $classes ? '.' . implode( '.', $classes ) : '' ) );

		if ( null !== $token ) {
			$tokens[] = $token;
		}

		foreach ( $el->childNodes as $filho ) {
			$andar( $filho );
		}
	};

	// O nó raiz pode ser apenas um container: nesse caso não entra na contagem.
	if ( $pular_raiz ) {
		foreach ( $no->childNodes as $filho ) {
			$andar( $filho );
		}
	} else {
		$andar( $no );
	}

	return $tokens;
}

/**
 * Conta ocorrências de cada token.
 *
 * @param string[] $tokens Tokens.
 * @return array<string,int>
 */
function contar( array $tokens ) {
	$c = array();

	foreach ( $tokens as $t ) {
		$c[ $t ] = isset( $c[ $t ] ) ? $c[ $t ] + 1 : 1;
	}

	ksort( $c );

	return $c;
}

/**
 * Imprime a diferença entre dois contadores.
 *
 * @param array    $esperado Original.
 * @param array    $obtido   Renderizado.
 * @param string   $contexto Contexto da seção.
 * @param int      $max      Máximo de linhas por bloco.
 */
function mostrar_diferencas( array $esperado, array $obtido, $contexto, $max = 12 ) {
	$faltando = array();
	$sobrando = array();

	foreach ( $esperado as $token => $qtd ) {
		$diff = $qtd - ( isset( $obtido[ $token ] ) ? $obtido[ $token ] : 0 );

		if ( $diff > 0 ) {
			$faltando[ $token ] = $diff;
		}
	}

	foreach ( $obtido as $token => $qtd ) {
		$diff = $qtd - ( isset( $esperado[ $token ] ) ? $esperado[ $token ] : 0 );

		if ( $diff > 0 ) {
			$sobrando[ $token ] = $diff;
		}
	}

	arsort( $faltando );
	arsort( $sobrando );

	if ( ! $faltando && ! $sobrando ) {
		printf( "  %-22s OK — %d nós conferem\n", $contexto, array_sum( $esperado ) );

		return 0;
	}

	printf( "  %-22s %d ausentes / %d extras\n", $contexto, array_sum( $faltando ), array_sum( $sobrando ) );

	$i = 0;
	foreach ( $faltando as $token => $qtd ) {
		printf( "      - %-40s x%d\n", $token, $qtd );
		if ( ++$i >= $max ) {
			echo "      ... (mais ausentes)\n";
			break;
		}
	}

	$i = 0;
	foreach ( $sobrando as $token => $qtd ) {
		printf( "      + %-40s x%d\n", $token, $qtd );
		if ( ++$i >= $max ) {
			echo "      ... (mais extras)\n";
			break;
		}
	}

	return array_sum( $faltando ) + array_sum( $sobrando );
}

echo str_repeat( '=', 74 ) . "\n";
echo "Estrutura: HTML original x HTML renderizado\n";
echo str_repeat( '=', 74 ) . "\n\n";

if ( ! file_exists( $origem ) ) {
	exit( "HTML original não encontrado: {$origem}\n" );
}

$doc_original = dom_de( file_get_contents( $origem ) );

$sections = $doc_original->query( '//section[@id]' );

$paginas_html = array();
$total        = 0;

foreach ( $sections as $secao ) {
	$id = $secao->getAttribute( 'id' );

	if ( ! isset( $rotas[ $id ] ) ) {
		continue;
	}

	$rota  = $rotas[ $id ];
	$html  = html_de( $base . $rota );
	$xpath = dom_de( $html );

	$wrap = $xpath->query( '//div[contains(@class,"page-wrap")]' )->item( 0 );

	if ( ! $wrap ) {
		printf( "  %-22s [FALHA] .page-wrap não encontrado\n", $rota );
		$total++;
		continue;
	}

	/*
	 * Original: a <section> inteira.
	 * Renderizado: a capa vem do template (fora da section) e o corpo
	 * continua na <section>, então comparamos só o corpo contra a section
	 * original sem a capa.
	 */
	$clones = $secao->cloneNode( true );

	// Remove a capa dos dois lados para comparar só o corpo.
	foreach ( $clones->childNodes as $filho ) {
		if ( $filho instanceof DOMElement && false !== strpos( $filho->getAttribute( 'class' ), 'hero' ) ) {
			$clones->removeChild( $filho );
			break;
		}
	}

	foreach ( $wrap->childNodes as $filho ) {
		if ( $filho instanceof DOMElement && false !== strpos( $filho->getAttribute( 'class' ), 'hero' ) ) {
			$wrap->removeChild( $filho );
			break;
		}
	}

	// O bloco de doação saiu do conteúdo e passou para o rodapé.
	foreach ( array( $clones, $wrap ) as $alvo ) {
		foreach ( iterator_to_array( $alvo->childNodes ) as $filho ) {
			if ( $filho instanceof DOMElement && false !== strpos( $filho->getAttribute( 'class' ), 'cta-doe' ) ) {
				$alvo->removeChild( $filho );
			}
		}
	}

$esperado = contar( esqueleto( $clones, $ignoradas, $ignorar_tags, false, $rota ) );
$obtido = contar( esqueleto( $wrap, $ignoradas, $ignorar_tags, true ) );

	$paginas_html[ $rota ] = mostrar_diferencas( $esperado, $obtido, $rota );

	$total += $paginas_html[ $rota ];

	echo "\n";
}

echo str_repeat( '-', 74 ) . "\n";
printf( "Diferenças estruturais totais: %d\n", $total );

exit( $total ? 1 : 0 );