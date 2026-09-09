const pages=[...document.querySelectorAll('.page')];
const titles={quem:'Quem somos',atividades:'Atividades',galeria:'Galeria de Atividades',noticias:'Notícias',eventos:'Eventos',transparencia:'Transparência',fale:'Fale Conosco',trabalhe:'Trabalhe Conosco',inscricao:'Pré Inscrição',quadra:'Quadra dos sonhos',aces:'Instituto ACES'};
let navStack=[];
let pageTimer=null;
function showPage(id){
  const target=document.getElementById(id);
  if(!target)return;
  const cur=document.querySelector('.page.active');
  if(cur&&cur!==target&&!cur.classList.contains('leaving')){
    if(pageTimer)clearTimeout(pageTimer);
    cur.classList.add('leaving');
    pageTimer=setTimeout(()=>doShow(id),200);
  }else{
    doShow(id);
  }
}
function doShow(id){
  pages.forEach(p=>p.classList.remove('active','leaving'));
  const target=document.getElementById(id);
  if(target)target.classList.add('active');
  if(navStack[navStack.length-1]!==id)navStack.push(id);
  renderCrumb(id);
  window.scrollTo({top:0,behavior:'smooth'});
  history.replaceState(null,'','#'+id);
  const hvid=document.querySelector('#'+id+' .hero video');
  if(hvid){hvid.muted=true;hvid.play().catch(()=>{})};
  const cta=document.querySelector('.cta-doe');
  if(cta)cta.style.display=(id==='trabalhe'||id==='inscricao')?'none':'';
  if(id==='atividades'&&window.refreshVideoCarousel)window.refreshVideoCarousel();
}
function renderCrumb(id){
  document.querySelectorAll('.crumb-row').forEach(c=>c.remove());
  if(id==='inicio')return;
  const sec=document.getElementById(id);if(!sec)return;
  const row=document.createElement('div');row.className='crumb-row';
  const back=document.createElement('a');back.className='crumb-back';back.href='#';back.innerHTML='<i class="fas fa-arrow-left"></i>Voltar';back.onclick=e=>{e.preventDefault();navStack.pop();showPage(navStack.pop()||'inicio')};
  const crumb=document.createElement('nav');crumb.className='breadcrumb';
  crumb.innerHTML='<span class="sep">|</span><a href="#inicio" onclick="showPage(\'inicio\')">Início</a><i class="fas fa-chevron-right sep"></i><span class="current">'+(titles[id]||id)+'</span>';
  row.appendChild(back);row.appendChild(crumb);
  const hero=sec.querySelector('.hero');
  if(hero){const inner=hero.querySelector('.hero-inner');hero.classList.add('has-crumb');(inner||hero).insertBefore(row,inner?inner.firstChild:hero.firstChild);row.classList.add('on-hero');}
  else{sec.insertBefore(row,sec);}
}
function openDonation(){document.getElementById('donation').classList.add('open')}
function closeDonation(){document.getElementById('donation').classList.remove('open')}
function toggleMenu(){document.getElementById('menu').classList.toggle('open')}
document.getElementById('menu').addEventListener('click',e=>{if(e.target.closest('button'))document.getElementById('menu').classList.remove('open')});
function openTransModal(btn){
  const card=btn.closest('.trans-card');
  if(!card)return;
  document.getElementById('transMonth').textContent=card.dataset.month;
  const titleEl=document.getElementById('transTitle');
  if(titleEl)titleEl.textContent=card.dataset.title||('Balancete '+card.dataset.month);
  document.getElementById('transIn').textContent=card.dataset.in;
  document.getElementById('transOut').textContent=card.dataset.out;
  fillList(document.getElementById('transInItems'),(card.dataset.inItems||'').split(';'),'up');
  fillList(document.getElementById('transOutItems'),(card.dataset.outItems||'').split(';'),'down');
  const filesEl=document.getElementById('transFiles');
  filesEl.innerHTML='';
  (card.dataset.files||'').split(';').forEach(f=>{
    if(!f.trim())return;
    const [href,name]=f.split('|');
    const li=document.createElement('li');
    const a=document.createElement('a');a.href=href.trim();a.download='';a.innerHTML='<i class="fas fa-file-pdf"></i>'+(name||href.trim());
    li.appendChild(a);filesEl.appendChild(li);
  });
  document.getElementById('transModal').classList.add('open');
}
function fillList(ul,items,cls){
  ul.innerHTML='';
  items.forEach(it=>{
    if(!it.trim())return;
    const i=it.indexOf(':');
    const name=it.slice(0,i).trim();
    const val=it.slice(i+1).trim();
    const li=document.createElement('li');
    li.innerHTML='<span>'+name+'</span><strong class="'+(cls||'')+'">'+val+'</strong>';
    ul.appendChild(li);
  });
}
function closeTransModal(){document.getElementById('transModal').classList.remove('open')}
document.addEventListener('click',e=>{if(e.target.id==='transModal')closeTransModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeTransModal()});
let galImages=[],galIndex=0;
function visibleImg(ph){let b=null,bop=-1;ph.querySelectorAll('img').forEach(im=>{const op=parseFloat(getComputedStyle(im).opacity);if(op>bop){bop=op;b=im}});return b}
let galFade=null;
function renderImg(){
  const g=galImages[galIndex];
  if(!g)return;
  const img=document.getElementById('imgModalImg');
  const cap=document.getElementById('imgModalCap');
  img.style.opacity='0';
  cap.style.opacity='0';
  clearTimeout(galFade);
  galFade=setTimeout(()=>{
    img.src=g.src;
    cap.textContent=g.cap;
    img.style.opacity='1';
    cap.style.opacity='1';
  },180);
  const dots=document.getElementById('galDots');
  dots.innerHTML='';
  galImages.forEach((_,i)=>{const b=document.createElement('button');b.setAttribute('aria-label','Imagem '+(i+1));b.className=(i===galIndex)?'active':'';b.onclick=()=>{galIndex=i;renderImg()};dots.appendChild(b)});
}
function openImgGallery(index){
  const m=document.getElementById('imgModal');
  if(!m||!galImages.length)return;
  galIndex=(index+galImages.length)%galImages.length;
  renderImg();
  m.classList.add('open');
}
function imgGalleryPrev(){galIndex=(galIndex-1+galImages.length)%galImages.length;renderImg()}
function imgGalleryNext(){galIndex=(galIndex+1)%galImages.length;renderImg()}
function closeImgModal(){document.getElementById('imgModal').classList.remove('open')}
document.addEventListener('click',e=>{if(e.target.id==='imgModal')closeImgModal()});
document.addEventListener('keydown',e=>{
  if(e.key==='Escape')closeImgModal();
  const im=document.getElementById('imgModal');
  if(im&&im.classList.contains('open')){
    if(e.key==='ArrowLeft')imgGalleryPrev();
    if(e.key==='ArrowRight')imgGalleryNext();
  }
});
document.querySelectorAll('.mosaic .photo').forEach(ph=>{
  ph.addEventListener('click',()=>{
    const best=visibleImg(ph);
    if(!best)return;
    galImages=[...document.querySelectorAll('.mosaic .photo')].map(p=>{
      const b=visibleImg(p);
      return b?{src:b.src,cap:b.dataset.cap||b.alt||''}:null;
    }).filter(Boolean);
    let idx=galImages.findIndex(g=>g.src===best.src);
    openImgGallery(idx>=0?idx:0);
  });
});
function sendForm(e){e.preventDefault();alert('Obrigado! Este formulário é um modelo. Para receber mensagens de verdade, conecte-o a um serviço de formulário ou backend.');e.target.reset()}
document.getElementById('year').textContent=new Date().getFullYear();
const hv=document.querySelector('.hero video');if(hv){hv.muted=true;const p=()=>hv.play().catch(()=>{});p();document.addEventListener('click',p,{once:true});window.addEventListener('load',()=>{setTimeout(p,500)})}
const initial=location.hash.replace('#',''); if(initial && document.getElementById(initial)) showPage(initial);
document.getElementById('donation').addEventListener('click',e=>{if(e.target.id==='donation')closeDonation()});

const techTrack=document.querySelector('.tech-track');
if(techTrack){
  const originals=[...techTrack.querySelectorAll('.tech-card')];
  const step=()=>originals[0]?originals[0].offsetWidth+20:310;
  const columns=()=>originals.length/2;
  const perPage=()=>Math.max(1,Math.round((techTrack.parentElement.clientWidth||window.innerWidth)/step()));
  const totalPages=()=>Math.max(1,Math.ceil(columns()/perPage()));
  let page=0;
  const apply=()=>techTrack.style.transform='translateX(-'+(page*perPage()*step())+'px)';
  const next=()=>{page=(page+1)%totalPages();apply();updateDots('tech')};
  const prev=()=>{page=(page-1+totalPages())%totalPages();apply();updateDots('tech')};
  if(originals.length>=2){originals.forEach(c=>{techTrack.appendChild(c.cloneNode(true))})}
  const prevBtn=document.querySelector('.tech-prev');const nextBtn=document.querySelector('.tech-next');
  if(prevBtn)prevBtn.addEventListener('click',()=>{prev();updateDots('tech')});
  if(nextBtn)nextBtn.addEventListener('click',()=>{next();updateDots('tech')});
  setInterval(()=>{next();updateDots('tech')},3000);
  const dotsBox=document.getElementById('techDots');
  if(dotsBox){
    for(let i=0;i<totalPages();i++){const b=document.createElement('button');b.setAttribute('aria-label','Página '+(i+1));b.onclick=()=>{page=i;apply();updateDots('tech')};dotsBox.appendChild(b)}
    updateDots('tech');
  }
  let startX=0,delta=0,dragging=false;
  techTrack.addEventListener('pointerdown',e=>{dragging=true;startX=e.clientX;delta=0;techTrack.style.transition='none'});
  window.addEventListener('pointermove',e=>{if(!dragging)return;delta=e.clientX-startX;techTrack.style.transform='translateX('+(-(page*perPage()*step())+delta)+'px)'});
  window.addEventListener('pointerup',()=>{if(!dragging)return;dragging=false;techTrack.style.transition='transform .6s ease';if(Math.abs(delta)>50){delta>0?prev():next()}else{apply()}updateDots('tech')});
}
function updateDots(kind){
  const box=document.getElementById(kind==='tech'?'techDots':'logoDots');
  if(!box)return;
  const track=document.querySelector(kind==='tech'?'.tech-track':'.logo-track');
  if(!track)return;
  const items=[...track.querySelectorAll(kind==='tech'?'.tech-card':'.logo-item')];
  if(kind==='tech'){
    const step=items[0]?items[0].offsetWidth+20:0;
    const columns=items.length/4;
    const per=Math.max(1,Math.round((track.parentElement.clientWidth||window.innerWidth)/step));
    const total=Math.max(1,Math.ceil(columns/per));
    const pos=Math.abs(parseFloat(getComputedStyle(track).transform.split(',')[4])||0);
    const idx=Math.min(Math.round(pos/(step*per)),total-1);
    [...box.children].forEach((b,i)=>b.classList.toggle('active',i===idx));
  }else{
    const total=items.length/2;
    const step=items[0]?items[0].offsetWidth+44:0;
    const pos=Math.abs(parseFloat(getComputedStyle(track).transform.split(',')[4])||0);
    const idx=Math.min(Math.floor(pos/step),total-1);
    [...box.children].forEach((b,i)=>b.classList.toggle('active',i===idx));
  }
}
const videoTrack=document.querySelector('.video-track');
if(videoTrack){
  const originals=[...videoTrack.querySelectorAll('.video-card')];
  const step=()=>originals[0]?originals[0].offsetWidth+20:360;
  const perPage=()=>Math.max(1,Math.round((videoTrack.parentElement.clientWidth||window.innerWidth)/step()));
  const totalPages=()=>Math.max(1,Math.ceil(originals.length/perPage()));
  const halfW=()=>originals.length*step();
  let vpos=0;
  const vapply=()=>videoTrack.style.transform='translateX(-'+(vpos%halfW())+'px)';
  const vnext=()=>{vpos+=perPage()*step();vapply();updateVideoDots()};
  const vprev=()=>{vpos-=perPage()*step();if(vpos<0)vpos+=halfW();vapply();updateVideoDots()};
  if(originals.length>=2){originals.forEach(c=>{videoTrack.appendChild(c.cloneNode(true))})}
  const vprevBtn=document.querySelector('.video-prev');
  const vnextBtn=document.querySelector('.video-next');
  if(vprevBtn)vprevBtn.addEventListener('click',vprev);
  if(vnextBtn)vnextBtn.addEventListener('click',vnext);
  setInterval(vnext,4000);
  function renderVideoDots(){
    const dotsBox=document.getElementById('videoDots');
    if(!dotsBox)return;
    const total=totalPages();
    if(dotsBox.children.length===total)return;
    dotsBox.innerHTML='';
    for(let i=0;i<total;i++){const b=document.createElement('button');b.setAttribute('aria-label','Página '+(i+1));b.onclick=()=>{vpos=i*perPage()*step();vapply();updateVideoDots()};dotsBox.appendChild(b)}
  }
  renderVideoDots();
  window.addEventListener('resize',renderVideoDots);
  window.refreshVideoCarousel=()=>{requestAnimationFrame(()=>{renderVideoDots();updateVideoDots()})};
  let vstart=0,vdelta=0,vdrag=false;
  videoTrack.addEventListener('pointerdown',e=>{vdrag=true;vstart=e.clientX;vdelta=0;videoTrack.style.transition='none'});
  window.addEventListener('pointermove',e=>{if(!vdrag)return;vdelta=e.clientX-vstart;videoTrack.style.transform='translateX('+(-(vpos%halfW())+vdelta)+'px)'});
  window.addEventListener('pointerup',()=>{if(!vdrag)return;vdrag=false;videoTrack.style.transition='transform .6s ease';if(Math.abs(vdelta)>50){vdelta>0?vprev():vnext()}else{vapply()}updateVideoDots()});
}
function updateVideoDots(){
  const box=document.getElementById('videoDots');
  if(!box)return;
  const track=document.querySelector('.video-track');
  if(!track)return;
  const cards=[...track.querySelectorAll('.video-card')];
  const n=cards.length/2;
  const step=cards[0]?cards[0].offsetWidth+20:0;
  const per=Math.max(1,Math.round((track.parentElement.clientWidth||window.innerWidth)/step));
  const total=Math.max(1,Math.ceil(n/per));
  const pos=Math.abs(parseFloat(getComputedStyle(track).transform.split(',')[4])||0);
  const idx=Math.min(Math.floor(pos/(step*per)),total-1);
  [...box.children].forEach((b,i)=>b.classList.toggle('active',i===idx));
}
let childCount=0;
const childTemplate=()=>{
  childCount++;
  const card=document.createElement('div');card.className='child-card';
  card.innerHTML='<button type="button" class="remove-child" onclick="this.closest(\'.child-card\').remove()">×</button>'
    +'<input required placeholder="Nome da criança"><div class="form-row">'
    +'<input required type="number" min="1" max="18" placeholder="Idade"><input required placeholder="Escola que estuda"></div>'
    +'<div class="form-row"><input required placeholder="Série"><select required><option value="" disabled selected>Período que estuda</option><option>Manhã</option><option>Tarde</option><option>Integral</option></select></div>';
  return card;
};
function addChild(){
  const list=document.getElementById('childrenList');
  if(list.childElementCount>=5){alert('Você já adicionou o máximo de crianças permitido.');return}
  list.appendChild(childTemplate());
}
document.querySelectorAll('.file-input').forEach(fi=>{
  const inp=fi.querySelector('input[type="file"]');
  const txt=fi.querySelector('.file-text');
  inp.addEventListener('change',()=>{
    fi.classList.add('has-file');
    if(txt)txt.textContent=inp.files[0]?inp.files[0].name:'Clique para anexar seu currículo';
  });
});
const preForm=document.getElementById('preForm');
if(preForm)preForm.addEventListener('submit',e=>{
  const list=document.getElementById('childrenList');
  if(list.childElementCount===0){e.preventDefault();alert('Adicione ao menos uma criança para enviar a pré-inscrição.');return}
});

const logoTrack=document.querySelector('.logo-track');
if(logoTrack){
  const items=[...logoTrack.querySelectorAll('.logo-item')];
  items.forEach(it=>{logoTrack.appendChild(it.cloneNode(true))});
  const step=()=>items[0]?items[0].offsetWidth+44:110;
  const half=()=>items.length*step();
  let lpos=0;
  const lapply=()=>logoTrack.style.transform='translateX(-'+(lpos%half())+'px)';
  const lnext=()=>{lpos+=step();lapply();updateDots('logo')};
  const lprev=()=>{lpos-=step();if(lpos<0)lpos+=half();lapply();updateDots('logo')};
  const lprevBtn=document.querySelector('.logo-prev');const lnextBtn=document.querySelector('.logo-next');
  if(lprevBtn)lprevBtn.addEventListener('click',lprev);
  if(lnextBtn)lnextBtn.addEventListener('click',lnext);
  setInterval(lnext,2500);
  const dotsBox=document.getElementById('logoDots');
  if(dotsBox){
    items.forEach((_,i)=>{const b=document.createElement('button');b.setAttribute('aria-label','Slide '+(i+1));b.onclick=()=>{lpos=Math.round((i*step())/half()*half())%half();lapply();updateDots('logo')};dotsBox.appendChild(b)});
    updateDots('logo');
  }
}
function setupPagination(container,perPage,firstOnly){
  if(!container)return;
  const items=[...container.children];
  if(items.length<=perPage)return;
  const totalPages=Math.ceil(items.length/perPage);
  const wrap=document.createElement('div');wrap.className='pagination';
  container.after(wrap);
  let current=1;
  function go(p,scroll){
    current=Math.min(Math.max(1,p),totalPages);
    items.forEach((it,i)=>it.style.display=(Math.floor(i/perPage)+1===current)?'':'none');
    if(firstOnly)firstOnly.forEach(el=>{if(el)el.style.display=(current===1)?'':'none'});
    wrap.innerHTML='';
    const prev=document.createElement('button');prev.innerHTML='<i class="fas fa-arrow-left"></i>Anterior';prev.disabled=current===1;prev.onclick=()=>go(current-1,true);
    wrap.appendChild(prev);
    const info=document.createElement('span');info.className='pg-info';info.textContent='Página '+current+' de '+totalPages;
    wrap.appendChild(info);
    const next=document.createElement('button');next.innerHTML='Próximo<i class="fas fa-arrow-right"></i>';next.disabled=current===totalPages;next.onclick=()=>go(current+1,true);
    wrap.appendChild(next);
    if(scroll)container.scrollIntoView({behavior:'smooth',block:'start'});
  }
  go(1,false);
}
setupPagination(document.querySelector('#galeria .gallery-mosaic'),12);
setupPagination(document.querySelector('.news-grid'),9,[document.querySelector('#noticias .news-hero')]);

/* ========== TRANSPARÊNCIA - ABAS POR CATEGORIA/ANO/MÊS ========== */
(function(){
  const tabsEl=document.getElementById('transTabs');
  const yearTabsEl=document.getElementById('transYearTabs');
  const contentEl=document.getElementById('transContent');
  if(!tabsEl||!yearTabsEl||!contentEl)return;

  const cats=[
    {id:'institucional',label:'Institucional',color:'var(--green-primary)'},
    {id:'cmdca',label:'CMDCA',color:'var(--blue)'},
    {id:'smdhcj',label:'SMDHCJ',color:'var(--red)'},
    {id:'emendas',label:'Emendas Parlamentares',color:'var(--yellow)'}
  ];
  const years=[2025,2024,2023];
  const months=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

  function rand(base){return Math.round((base+Math.random()*base*.3-base*.15)/100)*100}
  const data={};
  cats.forEach(c=>{data[c.id]={};years.forEach(y=>{data[c.id][y]=months.map(m=>{
    const baseIn=c.id==='institucional'?34000:c.id==='cmdca'?10000:c.id==='smdhcj'?8000:12000;
    const baseOut=baseIn*(.85+Math.random()*.1);
    const inp=rand(baseIn);const outp=rand(baseOut);
    return{month:m+' de '+y,in:inp,out:outp,balance:inp-outp,
      inItems:c.id==='institucional'?`Telemarketing: R$ 8.000;Contraturno e doações: R$ ${(inp-8000).toLocaleString('pt-BR')}`
        :c.id==='cmdca'?`Projeto CMDCA: R$ ${inp.toLocaleString('pt-BR')}`
        :c.id==='smdhcj'?`SMDHCJ: R$ ${inp.toLocaleString('pt-BR')}`
        :`Emenda Parlamentar: R$ ${inp.toLocaleString('pt-BR')}`,
      outItems:c.id==='institucional'?`Alimentação: R$ ${(outp*.37).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Educadores: R$ ${(outp*.44).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Materiais: R$ ${(outp*.1).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Despesas fixas: R$ ${(outp*.09).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}`
        :c.id==='cmdca'?`Materiais pedagógicos: R$ ${(outp*.32).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Atividades culturais: R$ ${(outp*.38).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Transporte: R$ ${(outp*.3).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}`
        :c.id==='smdhcj'?`Alimentação: R$ ${(outp*.37).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Educadores: R$ ${(outp*.35).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Materiais: R$ ${(outp*.28).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}`
        :`Manutenção: R$ ${(outp*.38).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Materiais: R$ ${(outp*.32).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')};Equipamentos: R$ ${(outp*.3).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}`,
      file:c.id+'/'+m.toLowerCase().replace(/ã/g,'a').replace(/ç/g,'c')+'-'+y+'.pdf',
      badgeColor:c.color
    };
  })})});

  let activeCat='institucional',activeYear=2025;

  function fmt(v){return(v>=0?'+ ':'− ')+'R$ '+Math.abs(v).toLocaleString('pt-BR')}

  function renderTabs(){
    tabsEl.innerHTML=`<div class="trans-dropdowns"><select class="trans-select" onchange="window._transSwitch(this.value)">${cats.map(c=>`<option value="${c.id}" ${c.id===activeCat?'selected':''}>${c.label}</option>`).join('')}</select><select class="trans-select" onchange="window._transYearSwitch(+this.value)">${years.map(y=>`<option value="${y}" ${y===activeYear?'selected':''}>${y}</option>`).join('')}</select></div>`;
  }
  function renderYearTabs(){yearTabsEl.innerHTML=''}
  function renderContent(){
    const m=data[activeCat][activeYear];
    const catLabel=(cats.find(c=>c.id===activeCat)||{}).label||activeCat;
    contentEl.innerHTML=`<div class="trans-months">${m.map((d,i)=>{
      const titulo=`Balancete ${catLabel} - ${d.month}`;
      const filePath=`assets/prestacao-contas/${d.file}`;
      const fileName=`Balancete ${d.month}.pdf`;
      return`<div class="card trans-card" data-title="${titulo}" data-month="${d.month}" data-in="${fmt(d.in)}" data-out="${fmt(d.out)}" data-in-items="${d.inItems}" data-out-items="${d.outItems}" data-files="${filePath}|${titulo}"><span class="badge" style="background:${d.badgeColor};color:${d.badgeColor==='var(--yellow)'?'var(--ink)':'#fff'}">${d.month.split(' ')[0]}</span><h3>${titulo}</h3><div class="trans-meta"><span>Arquivo:</span><a class="trans-file-link" href="${filePath}" download onclick="event.stopPropagation()"><i class="fas fa-file-pdf"></i>${fileName}</a></div></div>`;
    }).join('')}</div>`;
  }

  window._transSwitch=function(cat){activeCat=cat;renderTabs();renderYearTabs();renderContent()};
  window._transYearSwitch=function(y){activeYear=y;renderYearTabs();renderContent()};

  renderTabs();renderYearTabs();renderContent();
})();

/* ========== ANIMAÇÕES DE SCROLL ========== */
(function(){
  const els=document.querySelectorAll('.fade-up,.fade-in,.scale-in');
  if(!els.length)return;
  const obs=new IntersectionObserver((entries)=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target)}});
  },{threshold:0.12,rootMargin:'0px 0px -40px 0px'});
  els.forEach(el=>obs.observe(el));
})();
