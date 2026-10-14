/**
 * Lar André Luiz — scripts do tema.
 *
 * IMPORTANTE: este arquivo NÃO navega entre páginas.
 * A navegação é feita por links reais do WordPress (/atividades/,
 * /transparencia/, ...). Não existe mais showPage(), history.replaceState
 * nem window.location.hash.
 *
 * O JavaScript cuida apenas do que não é markup: menu mobile, carrosséis,
 * abas de esportes, modais, galeria de imagens e os campos dinâmicos
 * do formulário de pré-inscrição.
 */
( function () {
	'use strict';

	/* ===================================================================
	 * 1. Helpers
	 * ================================================================ */

	const $  = ( sel, ctx ) => ( ctx || document ).querySelector( sel );
	const $$ = ( sel, ctx ) => Array.from( ( ctx || document ).querySelectorAll( sel ) );

	/**
	 * Delegação de eventos: um único listener por tipo de evento,
	 * funcionando em conteúdo inserido dinamicamente.
	 *
	 * @param {string}   type     Tipo do evento.
	 * @param {string}   selector Seletor do alvo.
	 * @param {Function} handler  Callback.
	 */
	function on( type, selector, handler ) {
		document.addEventListener( type, function ( e ) {
			const alvo = e.target.closest( selector );
			if ( alvo ) {
				handler( e, alvo );
			}
		} );
	}

	/* ===================================================================
	 * 2. Menu mobile
	 * ================================================================ */

	const menu   = $( '#menu' );
	const burger = $( '[data-lar-burger]' );

	if ( menu && burger ) {
		burger.addEventListener( 'click', function () {
			const aberto = menu.classList.toggle( 'open' );
			burger.setAttribute( 'aria-expanded', aberto ? 'true' : 'false' );
		} );

		// Fecha ao clicar em qualquer link do menu.
		menu.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( 'a' ) ) {
				menu.classList.remove( 'open' );
				burger.setAttribute( 'aria-expanded', 'false' );
			}
		} );

		// No desktop o submenu abre por CSS (:hover / :focus-within),
		// então nenhuma lógica de menu é necessária aqui.
	}

	// O item de menu da página atual já vem marcado pelo WordPress
	// (current-menu-item / current_page_item), sem JavaScript.

	/* ===================================================================
	 * 3. Modais (doação, galeria de imagens, balancete)
	 * ================================================================ */

	let elementoFocadoAntes = null;

	/**
	 * Abre um modal.
	 *
	 * @param {string} id ID do modal.
	 */
	function abrirModal( id ) {
		const modal = document.getElementById( id );
		if ( ! modal ) {
			return;
		}

		elementoFocadoAntes = document.activeElement;
		modal.hidden = false;
		modal.classList.add( 'open' );
		document.body.style.overflow = 'hidden';

		const foco = $(
			'button, [href], input, select, textarea',
			modal
		);
		if ( foco ) {
			foco.focus();
		}
	}

	/**
	 * Fecha um modal.
	 *
	 * @param {string} id ID do modal.
	 */
	function fecharModal( id ) {
		const modal = document.getElementById( id );
		if ( ! modal ) {
			return;
		}

		modal.classList.remove( 'open' );
		modal.hidden = true;
		document.body.style.overflow = '';

		if ( elementoFocadoAntes && typeof elementoFocadoAntes.focus === 'function' ) {
			elementoFocadoAntes.focus();
		}
	}

	on( 'click', '[data-lar-open]', function ( e, alvo ) {
		e.preventDefault();
		abrirModal( alvo.getAttribute( 'data-lar-open' ) );
	} );

	on( 'click', '[data-lar-close]', function ( e, alvo ) {
		e.preventDefault();
		fecharModal( alvo.getAttribute( 'data-lar-close' ) );
	} );

	// Clique no fundo (fora da caixa) fecha.
	$$( '.modal' ).forEach( function ( modal ) {
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) {
				fecharModal( modal.id );
			}
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}

		const aberto = $( '.modal.open' );
		if ( aberto ) {
			fecharModal( aberto.id );
		}
	} );

	/* ===================================================================
	 * 4. Vídeo da capa
	 * ================================================================ */

	( function videoHero() {
		$$( '.hero video' ).forEach( function ( video ) {
			video.muted = true;
			const tocar = () => {
				const p = video.play();
				if ( p && p.catch ) {
					p.catch( function () {} );
				}
			};
			tocar();
			document.addEventListener( 'click', tocar, { once: true } );
			window.addEventListener( 'load', function () {
				setTimeout( tocar, 300 );
			} );
		} );
	} )();

	/* ===================================================================
	 * 5. Abas de esportes
	 * ================================================================ */

	on( 'click', '[data-lar-sport]', function ( e, alvo ) {
		const id   = alvo.getAttribute( 'data-lar-sport' );
		const raiz = alvo.closest( '[data-lar-sport-tabs]' ) || document;

		$$( '[data-lar-sport]', raiz ).forEach( function ( b ) {
			const ativo = b === alvo;
			b.classList.toggle( 'active', ativo );
			b.setAttribute( 'aria-selected', ativo ? 'true' : 'false' );
		} );

		$$( '.sport-panel', raiz ).forEach( function ( p ) {
			p.style.display = p.getAttribute( 'data-sport' ) === id ? '' : 'none';
		} );
	} );

	/* ===================================================================
	 * 6. Galeria de imagens (mosaico da home)
	 * ================================================================ */

	let galImagens = [];
	let galIndice  = 0;

	/**
	 * Descobre qual imagem está visível dentro de um mosaico (a CSS anima
	 * as imagens, por isso comparamos a opacidade computada).
	 *
	 * @param {HTMLElement} bloco Bloco do mosaico.
	 * @return {HTMLImageElement|null}
	 */
	function imagemVisivel( bloco ) {
		let melhor = null;
		let maior  = -1;

		$$( 'img', bloco ).forEach( function ( img ) {
			const op = parseFloat( window.getComputedStyle( img ).opacity );
			if ( op > maior ) {
				maior = op;
				melhor = img;
			}
		} );

		return melhor;
	}

	/**
	 * Desenha a imagem atual da galeria.
	 */
	function desenharGaleria() {
		const item = galImagens[ galIndice ];
		const img  = document.getElementById( 'imgModalImg' );
		const cap  = document.getElementById( 'imgModalCap' );
		const dots = document.getElementById( 'galDots' );

		if ( ! item || ! img || ! cap ) {
			return;
		}

		img.style.opacity = '0';
		cap.style.opacity = '0';

		window.setTimeout( function () {
			img.src = item.src;
			img.alt = item.cap || '';
			cap.textContent = item.cap;
			img.style.opacity = '1';
			cap.style.opacity = '1';
		}, 160 );

		if ( dots ) {
			dots.innerHTML = '';
			galImagens.forEach( function ( _, i ) {
				const b = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'aria-label', 'Imagem ' + ( i + 1 ) );
				b.classList.toggle( 'active', i === galIndice );
				b.addEventListener( 'click', function () {
					galIndice = i;
					desenharGaleria();
				} );
				dots.appendChild( b );
			} );
		}
	}

	/**
	 * Avança/retrocede na galeria.
	 *
	 * @param {number} passo 1 ou -1.
	 */
	function moverGaleria( passo ) {
		if ( ! galImagens.length ) {
			return;
		}
		galIndice = ( galIndice + passo + galImagens.length ) % galImagens.length;
		desenharGaleria();
	}

	$$( '.mosaic .photo' ).forEach( function ( bloco ) {
		bloco.addEventListener( 'click', function () {
			const atual = imagemVisivel( bloco );
			if ( ! atual ) {
				return;
			}

			galImagens = $$( '.mosaic .photo' )
				.map( function ( p ) {
					const img = imagemVisivel( p );
					return img
						? { src: img.src, cap: img.getAttribute( 'data-cap' ) || img.alt || '' }
						: null;
				} )
				.filter( Boolean );

			galIndice = galImagens.findIndex( function ( g ) {
				return g.src === atual.src;
			} );

			desenharGaleria();
			abrirModal( 'imgModal' );
		} );
	} );

	on( 'click', '[data-lar-gal]', function ( e, alvo ) {
		moverGaleria( 'prev' === alvo.getAttribute( 'data-lar-gal' ) ? -1 : 1 );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		const modal = document.getElementById( 'imgModal' );

		if ( ! modal || ! modal.classList.contains( 'open' ) ) {
			return;
		}

		if ( 'ArrowLeft' === e.key ) {
			moverGaleria( -1 );
		}

		if ( 'ArrowRight' === e.key ) {
			moverGaleria( 1 );
		}
	} );

	/* ===================================================================
	 * 7. Modal do balancete (transparência)
	 * ================================================================ */

	on( 'click', '[data-lar-balancete]', function ( e, alvo ) {
		if ( e.target.closest( 'a' ) ) {
			return;
		}

		const dados = alvo.dataset;

		document.getElementById( 'transTitle' ).textContent = dados.titulo || '';
		document.getElementById( 'transMonth' ).textContent = dados.mes || '';

		const brl = function ( v ) {
			const n = parseFloat( v || '0' );
			return ( n >= 0 ? '+ ' : '− ' ) + 'R$ ' +
				Math.abs( n ).toLocaleString( 'pt-BR', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				} );
		};

		document.getElementById( 'transIn' ).textContent  = brl( dados.entradas );
		document.getElementById( 'transOut' ).textContent = brl( dados.saidas );

		/**
		 * Preenche uma lista de "Nome: valor".
		 *
		 * @param {string} id  ID da <ul>.
		 * @param {string} raw JSON com as linhas.
		 * @param {string} cls Classe do <strong>.
		 */
		const preencher = function ( id, raw, cls ) {
			const ul = document.getElementById( id );
			ul.innerHTML = '';

			let linhas = [];
			try {
				linhas = JSON.parse( raw || '[]' );
			} catch ( err ) {
				linhas = [];
			}

			linhas.forEach( function ( linha ) {
				const i = linha.indexOf( ':' );
				const nome = -1 === i ? linha : linha.slice( 0, i );
				const valor = -1 === i ? '' : linha.slice( i + 1 );

				const li = document.createElement( 'li' );
				const span = document.createElement( 'span' );
				const strong = document.createElement( 'strong' );

				span.textContent = nome.trim();
				strong.className = cls;
				strong.textContent = valor.trim();

				li.appendChild( span );
				li.appendChild( strong );
				ul.appendChild( li );
			} );
		};

		preencher( 'transInItems', dados.inItens, 'up' );
		preencher( 'transOutItems', dados.outItens, 'down' );

		const arquivos = document.getElementById( 'transFiles' );
		arquivos.innerHTML = '';

		if ( dados.arquivo ) {
			const li  = document.createElement( 'li' );
			const a   = document.createElement( 'a' );
			a.href = dados.arquivo;
			a.setAttribute( 'download', '' );
			a.textContent = dados.arquivo.split( '/' ).pop();
			li.appendChild( a );
			arquivos.appendChild( li );
		}

		abrirModal( 'transModal' );
	} );

	/* ===================================================================
	 * 8. Filtros da transparência (enviam por GET, funcionam sem JS)
	 * ================================================================ */

	$$( '.lar-trans-filters select' ).forEach( function ( sel ) {
		sel.addEventListener( 'change', function () {
			const form = sel.closest( 'form' );
			if ( form ) {
				form.submit();
			}
		} );
	} );

	/* ===================================================================
	 * 9. Carrosséis (tech, vídeos e logos)
	 * ================================================================ */

	/**
	 * Calcula o deslocamento de um passo a partir da largura do primeiro item.
	 *
	 * @param {HTMLElement} track     Trilho.
	 * @param {number}      gap       Espaço entre itens.
	 * @return {number} Largura do passo.
	 */
	function passoDoTrilho( track, gap ) {
		const primeiro = track.children[ 0 ];
		return primeiro ? primeiro.offsetWidth + gap : 300;
	}

	/**
	 * Monta um carrossel com arraste, setas e indicators.
	 *
	 * @param {Object} cfg Configuração.
	 */
	function criarCarrossel( cfg ) {
		const track = $( cfg.track );
		if ( ! track ) {
			return;
		}

		const originais = Array.from( track.children );
		if ( originais.length < 2 ) {
			return;
		}

		const gap   = cfg.gap || 20;
		const passo = passoDoTrilho( track, gap );
		const clonar = cfg.clonar !== false;

		if ( clonar ) {
			originais.forEach( function ( c ) {
				track.appendChild( c.cloneNode( true ) );
			} );
		}

		const totalItens = track.children.length;
		const passoTotal = passo * ( clonar ? originais.length : totalItens );

		let pos      = 0;
		let arrastando = false;
		let inicioX   = 0;
		let delta     = 0;

		const itensPorPagina = function () {
			const largura = track.parentElement ? track.parentElement.clientWidth : window.innerWidth;
			return Math.max( 1, Math.round( largura / passo ) );
		};

		const totalPaginas = function () {
			const colunas = clonar ? originais.length : totalItens;
			return Math.max( 1, Math.ceil( colunas / itensPorPagina() ) );
		};

		const aplicar = function () {
			let deslocamento = pos;

			if ( clonar && deslocamento >= passoTotal ) {
				deslocamento = deslocamento % passoTotal;
			}

			track.style.transform = 'translateX(-' + deslocamento + 'px)';
			pontos( deslocamento );
		};

		const proximo = function () {
			pos += itensPorPagina() * passo;
			if ( pos >= passoTotal ) {
				pos = 0;
			}
			aplicar();
		};

		const anterior = function () {
			pos -= itensPorPagina() * passo;
			if ( pos < 0 ) {
				pos = pos + passoTotal;
			}
			aplicar();
		};

		const dotsBox = cfg.dots ? $( cfg.dots ) : null;
		const pontos  = function ( deslocamento ) {
			if ( ! dotsBox || dotsBox.children.length !== totalPaginas() ) {
				montarPontos();
			}

			const porPagina = itensPorPagina();
			const idx = Math.min(
				Math.floor( deslocamento / ( passo * porPagina ) ),
				totalPaginas() - 1
			);

			Array.from( dotsBox.children ).forEach( function ( b, i ) {
				b.classList.toggle( 'active', i === idx );
			} );
		};

		function montarPontos() {
			if ( ! dotsBox ) {
				return;
			}

			dotsBox.innerHTML = '';

			for ( let i = 0; i < totalPaginas(); i++ ) {
				const b = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'aria-label', 'Página ' + ( i + 1 ) );
				b.addEventListener( 'click', function () {
					pos = i * itensPorPagina() * passo;
					aplicar();
				} );
				dotsBox.appendChild( b );
			}
		}

		if ( cfg.prev ) {
			const b = $( cfg.prev );
			if ( b ) {
				b.addEventListener( 'click', anterior );
			}
		}

		if ( cfg.next ) {
			const b = $( cfg.next );
			if ( b ) {
				b.addEventListener( 'click', proximo );
			}
		}

		if ( cfg.auto ) {
			window.setInterval( proximo, cfg.auto );
		}

		// Arraste / swipe.
		track.addEventListener( 'pointerdown', function ( e ) {
			arrastando = true;
			inicioX     = e.clientX;
			delta       = 0;
			track.style.transition = 'none';
		} );

		window.addEventListener( 'pointermove', function ( e ) {
			if ( ! arrastando ) {
				return;
			}
			delta = e.clientX - inicioX;
			track.style.transform =
				'translateX(' + ( -pos + delta ) + 'px)';
		} );

		window.addEventListener( 'pointerup', function () {
			if ( ! arrastando ) {
				return;
			}
			arrastando = false;
			track.style.transition = 'transform .6s ease';

			if ( Math.abs( delta ) > 50 ) {
				delta > 0 ? anterior() : proximo();
			} else {
				aplicar();
			}
		} );

		window.addEventListener( 'resize', function () {
			montarPontos();
			aplicar();
		} );

		montarPontos();
		aplicar();
	}

	criarCarrossel( {
		track: '.tech-track',
		dots: '#techDots',
		prev: '.tech-prev',
		next: '.tech-next',
		gap: 20,
		auto: 3000
	} );

	criarCarrossel( {
		track: '.video-track',
		dots: '#videoDots',
		prev: '.video-prev',
		next: '.video-next',
		gap: 20,
		auto: 4000
	} );

	criarCarrossel( {
		track: '.logo-track',
		dots: '#logoDots',
		prev: '.logo-prev',
		next: '.logo-next',
		gap: 44,
		auto: 2500,
		clonar: true
	} );

	/* ===================================================================
	 * 10. Paginação de listas (galeria e notícias)
	 * ================================================================ */

	/**
	 * Pagina uma lista de itens em blocos de "porPagina".
	 *
	 * @param {string} seletor    Container dos itens.
	 * @param {number} porPagina Itens por página.
	 * @param {string[]} ocultar Seletores de blocos exibidos só na pág. 1.
	 */
	function paginar( seletor, porPagina, ocultar ) {
		const container = $( seletor );
		if ( ! container ) {
			return;
		}

		const itens = Array.from( container.children ).filter( function ( el ) {
			return ! el.matches( '.pagination' );
		} );

		const totalPaginas = Math.ceil( itens.length / porPagina );

		if ( totalPaginas < 2 ) {
			return;
		}

		const wrap = document.createElement( 'div' );
		wrap.className = 'pagination';
		container.after( wrap );

		const extras = ( ocultar || [] ).map( function ( sel ) { return $( sel ); } ).filter( Boolean );

		let atual = 1;

		function ir( pagina, rolar ) {
			atual = Math.min( Math.max( 1, pagina ), totalPaginas );

			itens.forEach( function ( el, i ) {
				const bloco = Math.floor( i / porPagina ) + 1;
				el.style.display = bloco === atual ? '' : 'none';
			} );

			extras.forEach( function ( el ) {
				el.style.display = atual === 1 ? '' : 'none';
			} );

			wrap.innerHTML = '';

			const anterior = document.createElement( 'button' );
			anterior.type = 'button';
			anterior.innerHTML = '<i class="fas fa-arrow-left" aria-hidden="true"></i> Anterior';
			anterior.disabled = atual === 1;
			anterior.addEventListener( 'click', function () {
				ir( atual - 1, true );
			} );

			const info = document.createElement( 'span' );
			info.className = 'pg-info';
			info.textContent = 'Página ' + atual + ' de ' + totalPaginas;

			const proximo = document.createElement( 'button' );
			proximo.type = 'button';
			proximo.innerHTML = 'Próximo <i class="fas fa-arrow-right" aria-hidden="true"></i>';
			proximo.disabled = atual === totalPaginas;
			proximo.addEventListener( 'click', function () {
				ir( atual + 1, true );
			} );

			wrap.appendChild( anterior );
			wrap.appendChild( info );
			wrap.appendChild( proximo );

			if ( rolar ) {
				container.scrollIntoView( {
					behavior: 'smooth',
					block: 'start'
				} );
			}
		}

		ir( 1, false );
	}

	paginar( '.gallery-mosaic', 12 );
	paginar( '.news-grid', 9, [ '.news-hero' ] );

	/* ===================================================================
	 * 11. Formulários
	 * ================================================================ */

	/**
	 * Campos dinâmicos da pré-inscrição (adicionar/remover crianças).
	 */
	( function camposPreInscricao() {
		const lista = document.getElementById( 'childrenList' );
		const botao = document.querySelector( '[data-lar-add-child]' );

		if ( ! lista || ! botao ) {
			return;
		}

		const MAXIMO = 5;

		/**
		 * Cria um bloco de criança.
		 *
		 * @return {HTMLElement}
		 */
		function novoCartao() {
			const card = document.createElement( 'div' );
			card.className = 'child-card';
			card.innerHTML =
				'<button type="button" class="remove-child" data-lar-remove-child aria-label="Remover">' +
				'&times;</button>' +
				'<input required placeholder="Nome da criança">' +
				'<div class="form-row">' +
				'<input required type="number" min="1" max="18" placeholder="Idade">' +
				'<input required placeholder="Escola que estuda">' +
				'</div>' +
				'<div class="form-row">' +
				'<input required placeholder="Série">' +
				'<select required><option value="" disabled selected>Período que estuda</option>' +
				'<option>Manhã</option><option>Tarde</option><option>Integral</option></select>' +
				'</div>';
			return card;
		}

		botao.addEventListener( 'click', function () {
			if ( lista.childElementCount >= MAXIMO ) {
				alert( 'Você já adicionou o máximo de ' + MAXIMO + ' crianças permitido.' );
				return;
			}
			lista.appendChild( novoCartao() );
		} );

		document.addEventListener( 'click', function ( e ) {
			const btn = e.target.closest( '[data-lar-remove-child]' );
			if ( btn ) {
				btn.closest( '.child-card' ).remove();
			}
		} );
	} )();

	// Campos de arquivo.
	$$( '.file-input' ).forEach( function ( campo ) {
		const input = $( 'input[type="file"]', campo );
		const texto = $( '.file-text', campo );

		if ( ! input ) {
			return;
		}

		input.addEventListener( 'change', function () {
			campo.classList.add( 'has-file' );
			if ( texto ) {
				texto.textContent = input.files[ 0 ]
					? input.files[ 0 ].name
					: texto.getAttribute( 'data-placeholder' ) || 'Clique para anexar';
			}
		} );
	} );

	/* ===================================================================
	 * 12. Animações de scroll
	 * ================================================================ */

	( function animacoesScroll() {
		const alvos = $$( '.fade-up, .fade-in, .scale-in' );

		if ( ! alvos.length || ! ( 'IntersectionObserver' in window ) ) {
			alvos.forEach( function ( el ) {
				el.classList.add( 'visible' );
			} );
			return;
		}

		const obs = new IntersectionObserver(
			function ( entradas ) {
				entradas.forEach( function ( e ) {
					if ( e.isIntersecting ) {
						e.target.classList.add( 'visible' );
						obs.unobserve( e.target );
					}
				} );
			},
			{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
		);

		alvos.forEach( function ( el ) {
			obs.observe( el );
		} );
	} )();
} )();