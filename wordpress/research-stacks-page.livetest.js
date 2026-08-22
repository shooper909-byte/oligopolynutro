const { chromium } = require('/opt/node22/lib/node_modules/playwright');
const URL='http://127.0.0.1:8899/rs-live.html';
const pass=[],fail=[]; const t=(n,c,d='')=>{(c?pass:fail).push(n+(d?' — '+d:''));};
const strip=async p=>{ await p.addStyleTag({content:'*{visibility:visible!important}'}); return p.evaluate(()=>{[...document.querySelectorAll('body *')].forEach(e=>{const s=getComputedStyle(e);
  if((s.position==='fixed'||s.position==='absolute')&&parseInt(s.zIndex,10)>=9000)e.remove();});
  // the age gate also styles <body> itself; drop its stylesheet and any full-viewport cover
  ['opb-age-gate-css'].forEach(id=>{const e=document.getElementById(id); if(e) e.remove();});
  [...document.querySelectorAll('body *')].forEach(e=>{const s=getComputedStyle(e);
    const r=e.getBoundingClientRect();
    if(s.position==='fixed' && r.width>=window.innerWidth*0.95 && r.height>=window.innerHeight*0.95) e.remove();});
  document.body.style.position='static';
  document.documentElement.style.overflow='auto';document.body.style.overflow='auto';});};
(async()=>{const b=await chromium.launch();
 for(const [n,w,h] of [['desktop',1440,900],['mobile',390,844]]){
  const p=await b.newPage({viewport:{width:w,height:h}});
  await p.route('**/*',r=>r.request().url().startsWith('http://127.0.0.1:8899')?r.continue():r.abort());
  await p.goto(URL,{waitUntil:'domcontentloaded'}); await strip(p);
  const r=await p.evaluate(()=>({hs:document.documentElement.scrollWidth>window.innerWidth+1,
    h1:document.querySelectorAll('h1').length, cards:document.querySelectorAll('#oplrs-cards input').length,
    curated:document.querySelectorAll('.oplrs-stack').length}));
  t(`[live ${n}] no horizontal scroll`,!r.hs);
  t(`[live ${n}] one H1`,r.h1===1,String(r.h1));
  t(`[live ${n}] 8 compounds`,r.cards===8,String(r.cards));
  t(`[live ${n}] 4 curated`,r.curated===4,String(r.curated));
  await p.close();}
 const p=await b.newPage({viewport:{width:1440,height:900}});
 const errs=[]; p.on('pageerror',e=>errs.push(String(e)));
 await p.route('**/*',r=>r.request().url().startsWith('http://127.0.0.1:8899')?r.continue():r.abort());
 await p.goto(URL,{waitUntil:'domcontentloaded'}); await strip(p);
 const card=i=>p.locator('#oplrs-cards .oplrs-card').nth(i-1).locator('label.box');
 const st=()=>p.evaluate(()=>({c:document.querySelector('[data-count]').textContent,
   m:document.querySelector('[data-msg]').textContent,
   d:document.querySelector('[data-submit]').getAttribute('aria-disabled'),
   n:document.querySelectorAll('[data-picked] li').length}));
 let s=await st(); t('[live] starts empty',s.c==='0 items'&&s.d==='true',JSON.stringify(s));
 await card(1).click(); s=await st();
 t('[live] 1 selected blocks submit',s.d==='true'&&/Select 2 more/.test(s.m),s.m);
 await card(2).click(); await card(3).click(); s=await st();
 t('[live] 3 selected enables submit',s.d==='false'&&s.n===3,JSON.stringify(s));
 const posted=await p.evaluate(()=>{const f=document.querySelector('[data-form]');let c=null;
   f.addEventListener('submit',e=>{e.preventDefault();c={a:f.action,
     f:[...f.querySelectorAll('input[data-gen]')].map(i=>i.name+'='+i.value)};},{once:true});
   f.requestSubmit();return c;});
 t('[live] posts to 3-vial container',/build-your-research-bundle-3-vials/.test(posted?.a||''),posted?.a);
 t('[live] sends 3 mnm_quantity fields',(posted?.f||[]).filter(x=>/mnm_quantity/.test(x)).length===3,JSON.stringify(posted?.f));
 await p.click('[data-clear]'); s=await st();
 t('[live] clear all works',s.c==='0 items'&&s.n===0);
 t('[live] no page errors',errs.length===0,errs.join('|'));
 await p.evaluate(()=>document.querySelectorAll('img[loading=lazy]').forEach(i=>i.loading='eager'));
 await p.waitForTimeout(1200);
 await p.screenshot({path:'rs-live-desktop.png'});
 await p.close(); await b.close();
 console.log('PASS ('+pass.length+')'); pass.forEach(x=>console.log('  ok  '+x));
 if(fail.length){console.log('FAIL ('+fail.length+')');fail.forEach(x=>console.log('  XX  '+x));}
})();
