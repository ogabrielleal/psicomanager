(() => {
  const sidebar=document.querySelector('#sidebar');
  document.querySelector('[data-sidebar]')?.addEventListener('click',()=>sidebar?.classList.toggle('open'));
  const privacy=document.querySelector('[data-privacy]');
  if(localStorage.getItem('privacyMode')==='1')document.body.classList.add('privacy-mode');
  privacy?.addEventListener('click',()=>{
    document.body.classList.toggle('privacy-mode');
    localStorage.setItem('privacyMode',document.body.classList.contains('privacy-mode')?'1':'0');
  });
  document.querySelector('#print-document')?.addEventListener('click',()=>window.print());

  const search=document.querySelector('#global-search');
  document.addEventListener('keydown',e=>{
    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();search?.focus();}
  });
  search?.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&search.value.trim()){
      const base=document.querySelector('link[href*="assets/css/app.css"]')?.href.replace(/assets\/css\/app\.css.*$/,'')||'/';
      window.location.href=base+'patients/index.php?q='+encodeURIComponent(search.value.trim());
    }
  });

  // Relatório técnico de erros do navegador. Não captura tela nem conteúdo clínico.
  // Screenshot automático é deliberadamente evitado para não copiar prontuários/PII para logs.
  const reportClientError=(event)=>{
    try{
      const base=document.querySelector('link[href*="assets/css/app.css"]')?.href.replace(/assets\/css\/app\.css.*$/,'')||'/';
      const requestId=document.querySelector('meta[name="request-id"]')?.content||'';
      const payload=JSON.stringify({...event,request_id:requestId});
      if(navigator.sendBeacon){navigator.sendBeacon(base+'errors/client.php',new Blob([payload],{type:'application/json'}));}
      else fetch(base+'errors/client.php',{method:'POST',headers:{'Content-Type':'application/json'},body:payload,keepalive:true,credentials:'same-origin'}).catch(()=>{});
    }catch(_e){}
  };
  window.addEventListener('error',e=>reportClientError({message:String(e.message||'JavaScript error'),source:String(e.filename||''),line:e.lineno||0,column:e.colno||0}));
  window.addEventListener('unhandledrejection',e=>reportClientError({message:'Unhandled promise rejection: '+String(e.reason?.message||e.reason||'unknown')}));

  // SESSION_IDLE_CLIENT: bloqueio visual por inatividade. O servidor também valida o timeout.
  if(document.body.classList.contains('app-body')){
    const idleMs=15*60*1000; let idleTimer;
    const base=document.querySelector('link[href*="assets/css/app.css"]')?.href.replace(/assets\/css\/app\.css.*$/,'')||'/';
    const reset=()=>{clearTimeout(idleTimer);idleTimer=setTimeout(()=>{window.location.href=base+'lock.php';},idleMs);};
    ['mousemove','mousedown','keydown','touchstart','scroll'].forEach(ev=>window.addEventListener(ev,reset,{passive:true}));
    reset();
  }
})();
