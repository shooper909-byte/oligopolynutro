const { chromium } = require('/opt/node22/lib/node_modules/playwright');
const URL='http://127.0.0.1:8899/research-stacks.html';
const pass=[],fail=[];
const t=(n,c,d='')=>{ (c?pass:fail).push(n+(d?' — '+d:'')); };

(async()=>{
 const b=await chromium.launch();

 // ---------- layout across breakpoints ----------
 for(const [name,w,h] of [['desktop',1440,900],['tablet',900,900],['mobile',390,844]]){
  const p=await b.newPage({viewport:{width:w,height:h}});
  await p.goto(URL,{waitUntil:'load'});
  const r=await p.evaluate(()=>({
    hScroll: document.documentElement.scrollWidth > window.innerWidth+1,
    h1: document.querySelectorAll('h1').length,
    h1text: (document.querySelector('h1')||{}).textContent.replace(/\s+/g,' ').trim(),
    cards: document.querySelectorAll('#oplrs-cards input[type=checkbox]').length,
    curated: document.querySelectorAll('.oplrs-stack').length,
    trust: document.querySelectorAll('.oplrs-trust div').length,
    heroTop: Math.round(document.querySelector('.oplrs-hero').getBoundingClientRect().top),
    ctaVisible: document.querySelector('.oplrs-cta .oplrs-btn').getBoundingClientRect().bottom < window.innerHeight,
    dockShown: getComputedStyle(document.querySelector('[data-dock]')).display !== 'none',
  }));
  t(`[${name}] no horizontal scroll`, !r.hScroll);
  t(`[${name}] exactly one H1`, r.h1===1, `found ${r.h1}`);
  t(`[${name}] H1 text correct`, r.h1text==='Build Your Own Research Stack', r.h1text);
  t(`[${name}] 8 compound cards`, r.cards===8, String(r.cards));
  t(`[${name}] 4 curated cards`, r.curated===4, String(r.curated));
  t(`[${name}] 4 trust items`, r.trust===4, String(r.trust));
  if(name==='desktop') t('[desktop] hero CTA above the fold', r.ctaVisible);
  t(`[${name}] dock hidden while nothing is selected`, !r.dockShown);
  if(name==='mobile'){
    await p.locator('#oplrs-cards .oplrs-card').nth(0).locator('label.box').click();
    const shown=await p.evaluate(()=>{const d=document.querySelector('[data-dock]');
      return getComputedStyle(d).display!=='none' && !d.hidden;});
    t('[mobile] dock appears once a compound is selected', shown);
    await p.locator('#oplrs-cards .oplrs-card').nth(0).locator('label.box').click();
    const rehidden=await p.evaluate(()=>{const d=document.querySelector('[data-dock]');
      return getComputedStyle(d).display==='none';});
    t('[mobile] dock hides again when deselected', rehidden);
  }
  await p.screenshot({path:`rs-after-${name}.png`, fullPage:name!=='desktop'});
  await p.close();
 }

 // ---------- functional ----------
 const p=await b.newPage({viewport:{width:1440,height:900}});
 const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
 p.on('pageerror',e=>errs.push(String(e)));
 await p.goto(URL,{waitUntil:'load'});

 const card=(i)=>p.locator('#oplrs-cards .oplrs-card').nth(i-1).locator('label.box');
 const cbInput=(i)=>p.locator('#oplrs-cards input[type=checkbox]').nth(i-1);
 const state=()=>p.evaluate(()=>({
   count: document.querySelector('[data-count]').textContent,
   msg: document.querySelector('[data-msg]').textContent,
   live: document.getElementById('oplrs-live').textContent,
   disabled: document.querySelector('[data-submit]').getAttribute('aria-disabled'),
   picked: document.querySelectorAll('[data-picked] li').length,
   emptyShown: !document.querySelector('[data-empty]').hidden,
   step: [...document.querySelectorAll('.oplrs-steps li')].map(l=>l.getAttribute('data-on')).join(''),
 }));

 let s=await state();
 t('empty state: 0 items', s.count==='0 items', s.count);
 t('empty state: submit disabled', s.disabled==='true');
 t('empty state: placeholder shown', s.emptyShown);

 await card(1).click(); s=await state();
 t('1 selected: count updates', s.count==='1 item', s.count);
 t('1 selected: submit still disabled', s.disabled==='true');
 t('1 selected: guidance names the gap', /Select 2 more to reach a 3-vial bundle/.test(s.msg), s.msg);
 t('1 selected: step 2 active', s.step==='110', s.step);

 await card(2).click(); await card(3).click(); s=await state();
 t('3 selected: submit enabled', s.disabled==='false');
 t('3 selected: ready message', /Ready: 3-vial bundle/.test(s.msg), s.msg);
 t('3 selected: summary lists 3', s.picked===3, String(s.picked));
 t('3 selected: step 3 active', s.step==='111', s.step);
 t('3 selected: live region announces', /3 items selected/.test(s.live), s.live);

 await card(4).click(); s=await state();
 t('4 selected: submit re-disabled', s.disabled==='true');
 t('4 selected: guidance to 6', /Select 2 more to reach a 6-vial bundle/.test(s.msg), s.msg);

 // remove control
 await p.click('[data-picked] li:first-child button'); s=await state();
 t('remove control works', s.picked===3 && s.disabled==='false', `${s.picked}/${s.disabled}`);

 // duplicate prevention: clicking same card twice toggles, never duplicates
 await card(1).click(); await card(1).click(); s=await state();
 const dupes=await p.evaluate(()=>{const n=[...document.querySelectorAll('[data-picked] .t')].map(x=>x.textContent);
   return n.length-new Set(n).size;});
 t('no duplicate entries in summary', dupes===0, String(dupes));

 // clear all
 await p.click('[data-clear]'); s=await state();
 t('clear all resets to 0', s.count==='0 items' && s.picked===0 && s.emptyShown);

 // submit wiring — intercept the POST
 for(const i of [1,2,3]) await card(i).click();
 const posted=await p.evaluate(()=>{
   const f=document.querySelector('[data-form]');
   let captured=null;
   f.addEventListener('submit',ev=>{ev.preventDefault();
     captured={action:f.action,
       fields:[...f.querySelectorAll('input[data-gen]')].map(i=>i.name+'='+i.value)};},{once:true});
   f.requestSubmit ? f.requestSubmit() : f.dispatchEvent(new Event('submit',{cancelable:true}));
   return captured;});
 t('submit targets the 3-vial container', /build-your-research-bundle-3-vials/.test(posted?.action||''), posted?.action);
 t('submit sends 3 mnm_quantity fields',
   (posted?.fields||[]).filter(f=>/^mnm_quantity\[\d+\]=1$/.test(f)).length===3,
   JSON.stringify(posted?.fields));
 t('submit sends size for server-side container mapping',
   (posted?.fields||[]).some(f=>f==='oplrs_size=3'), JSON.stringify(posted?.fields));

 t('no console errors', errs.length===0, errs.join(' | '));
 await p.close();

 // ---------- keyboard ----------
 const k=await b.newPage({viewport:{width:1440,height:900}});
 await k.goto(URL,{waitUntil:'load'});
 await k.evaluate(()=>document.querySelector('#oplrs-cards input').focus());
 await k.keyboard.press('Space');
 const kres=await k.evaluate(()=>({
   checked: document.querySelector('#oplrs-cards input').checked,
   count: document.querySelector('[data-count]').textContent,
   focusVisible: !!document.activeElement.closest('.oplrs-card'),
 }));
 t('keyboard: Space selects a compound', kres.checked && kres.count==='1 item');
 t('keyboard: focus stays on the control', kres.focusVisible);
 const tabbable=await k.evaluate(()=>document.querySelectorAll(
   '#oplrs a[href],#oplrs button,#oplrs input:not([type=hidden])').length);
 t('keyboard: all controls reachable', tabbable>=15, String(tabbable));
 await k.close();

 // ---------- reduced motion ----------
 const rm=await b.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
 await rm.goto(URL,{waitUntil:'load'});
 const anim=await rm.evaluate(()=>{
   const el=[...document.querySelectorAll('#oplrs *')];
   return el.filter(e=>{const c=getComputedStyle(e);
     return (c.animationName&&c.animationName!=='none')||
            (c.transitionDuration&&parseFloat(c.transitionDuration)>0);}).length;});
 t('reduced motion: no animations or transitions', anim===0, String(anim));
 await rm.close();

 await b.close();
 console.log('PASS ('+pass.length+')'); pass.forEach(x=>console.log('  ok  '+x));
 if(fail.length){console.log('\nFAIL ('+fail.length+')'); fail.forEach(x=>console.log('  XX  '+x));}
 process.exit(fail.length?1:0);
})();
