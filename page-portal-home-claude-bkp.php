<?php
/**
 * Template Name: Portal home mr claude bkp
 * Description: A custom template for displaying the influencer HQ.
 *
 * @package Avantage_Baccarat
 */

get_header();
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

<style>
:root{color-scheme:dark;--gl:#F0C93A;--gd:#7A5A1A;--scarlet:#9B1515;--bg:#000000;--bg2:#0E0E0C;--bg3:#161612;--text:#FFFFFF;--warm:#EAD9B0;--soft:#C9A84C;--white:#FFFFFF}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;background:#000!important}
body{background:#000!important;color:var(--text);font-family:'Be Vietnam Pro',sans-serif;font-weight:300;line-height:1.8;overflow-x:hidden;-webkit-font-smoothing:antialiased}

/* NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;justify-content:space-between;align-items:center;padding:18px 24px;background:rgba(0,0,0,.98)}
.logo{font-family:'Cinzel',serif;font-size:1.1rem;letter-spacing:.3em;color:var(--gl);text-decoration:none;text-transform:uppercase}
.nav-cta{font-family:'Be Vietnam Pro',sans-serif;font-size:.62rem;letter-spacing:.15em;text-transform:uppercase;color:var(--gl);border:1px solid var(--gl);padding:9px 14px;background:transparent;cursor:pointer;font-weight:600;opacity:0;pointer-events:none;transition:opacity .6s ease}
.nav-cta.visible{opacity:1;pointer-events:auto}
.nav-cta:active{background:var(--gl);color:#000}

/* HERO */
.hero{min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:110px 32px 80px;position:relative;overflow:hidden}
.hero-glow{position:absolute;top:35%;left:50%;transform:translate(-50%,-50%);width:400px;height:400px;background:radial-gradient(circle,rgba(240,201,58,.12) 0%,transparent 70%);pointer-events:none;animation:heroGlow 4s ease-in-out infinite}
.hero-lines{position:absolute;inset:0;background:repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(240,201,58,.04) 40px)}
.hero-eye{font-size:.72rem;letter-spacing:.6em;text-transform:uppercase;color:var(--gl);margin-bottom:20px;animation:fadeUp .7s .1s both;position:relative;z-index:1;font-weight:600}
.hero h1{font-family:'Cinzel',serif;font-size:clamp(2.1rem,9.1vw,3.8rem);font-weight:700;line-height:1.04;color:var(--white);margin-bottom:32px;animation:fadeUp .9s .4s both;position:relative;z-index:1}
.hero h1 em{font-style:italic;color:var(--gl)}
.hero-sub{font-size:1.21rem;letter-spacing:.06em;color:var(--warm);max-width:320px;margin:0 auto 52px;line-height:2.1;animation:fadeUp .9s .6s both;position:relative;z-index:1;font-family:'Be Vietnam Pro',sans-serif;font-style:italic}
.hero-btns{display:flex;flex-direction:column;gap:18px;align-items:center;animation:fadeUp .9s .85s both;position:relative;z-index:1;margin-bottom:48px}
.scroll-hint{position:absolute;bottom:36px;left:0;right:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;animation:fadeUp .9s 1.4s both;text-align:center}
.scroll-hint span{font-size:.72rem;letter-spacing:.55em;color:var(--gl);text-transform:uppercase;font-weight:700}
.scroll-line{width:2px;height:52px;background:linear-gradient(to bottom,var(--gl),transparent);animation:pulse 1.8s ease-in-out infinite}

/* BUTTONS */
.btn-gold{background:var(--gl);color:#000;border:none;padding:20px 24px;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;cursor:pointer;width:300px;animation:glowPulse 3s ease-in-out 2s infinite}
.btn-gold:active{background:#FFD700}
.btn-scarlet{background:var(--scarlet);color:#fff;border:none;padding:20px 24px;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;cursor:pointer;width:300px}
.btn-scarlet:active{background:#700000}

/* ACCORDION */
.acc-wrap{position:relative;z-index:1}
.tap-hint{text-align:center;padding:18px 32px 14px;background:var(--bg2);border-bottom:1px solid rgba(240,201,58,.15);transition:opacity .5s,max-height .5s;max-height:60px;overflow:hidden}
.tap-hint span{font-size:.75rem;letter-spacing:.35em;text-transform:uppercase;color:var(--gl);font-weight:600}
.tap-hint.gone{opacity:0;max-height:0;padding:0;pointer-events:none}
.acc-sec{border-bottom:1px solid rgba(240,201,58,.15)}
.acc-sec:first-of-type{border-top:1px solid rgba(240,201,58,.15)}
.acc-sec:nth-child(odd){background:var(--bg2)}
.acc-sec:nth-child(even){background:var(--bg3)}
.acc-hd{width:100%;padding:30px 32px 20px;background:transparent;border:none;cursor:pointer;text-align:left;-webkit-tap-highlight-color:transparent;user-select:none;-webkit-user-select:none}
.acc-hd:active{background:rgba(240,201,58,.06)}
.acc-eye{font-size:.88rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);display:block;margin-bottom:10px;font-weight:700}
.acc-title{letter-spacing:.04em;font-family:'Cinzel',serif;font-size:1.75rem;font-weight:600;color:var(--white);line-height:1.2;display:block;margin-bottom:16px}
.acc-title em{font-style:italic;color:var(--gl)}
.acc-chev{display:flex;justify-content:center;width:100%}
.acc-chev svg{width:28px;height:28px;stroke:var(--gl);stroke-width:2.5;fill:none;transition:transform .35s ease}
.acc-sec.open .acc-chev svg{transform:rotate(180deg)}
.acc-body{max-height:0;overflow:hidden;transition:max-height .5s cubic-bezier(.4,0,.2,1)}
.acc-sec.open .acc-body{max-height:9999px}
.acc-inner{padding:4px 32px 44px}

/* CONTENT */
.sec-p{font-family:'Be Vietnam Pro',sans-serif;font-size:1.1rem;font-weight:300;letter-spacing:.02em;color:var(--warm);line-height:1.9;margin-bottom:24px}
.beliefs-intro{font-family:'Cinzel',serif;font-size:1.8rem;font-weight:400;font-style:italic;color:var(--white);line-height:1.4;text-align:center;margin-bottom:32px;border-bottom:1px solid rgba(240,201,58,.2);padding-bottom:32px}
.belief{display:flex;gap:22px;align-items:flex-start;padding:22px 0;border-bottom:1px solid rgba(240,201,58,.12)}
.belief:last-of-type{border-bottom:none}
.b-num{font-family:'Cinzel',serif;font-size:1rem;color:var(--gl);min-width:24px;margin-top:4px;font-weight:600}
.b-text{font-family:'Be Vietnam Pro',sans-serif;font-size:1.2rem;font-weight:300;color:var(--white);line-height:1.65}

/* HISTORY CARDS */
.h-card{margin-bottom:28px;background:rgba(0,0,0,.4);position:relative;overflow:hidden}
.h-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:3px;background:linear-gradient(to right,var(--gl),transparent)}
.ph{height:180px;background:linear-gradient(135deg,rgba(240,201,58,.08),rgba(0,0,0,.9));display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;border-bottom:1px solid rgba(240,201,58,.15)}
.ph-icon{font-size:1.8rem;color:var(--gl);opacity:.7}
.ph-lbl{font-size:.65rem;letter-spacing:.4em;text-transform:uppercase;color:var(--warm);text-align:center;line-height:2;font-weight:600}
.ph-sub{font-size:.55rem;letter-spacing:.2em;color:var(--gd);text-transform:uppercase}
.h-body{padding:28px 24px}
.h-name{font-family:'Cinzel',serif;font-size:1.6rem;font-style:italic;color:var(--gl);margin-bottom:14px}
.h-text{font-family:'Be Vietnam Pro',sans-serif;font-size:1.05rem;font-weight:300;color:var(--warm);line-height:1.9}
.h-result{margin-top:20px;font-family:'Cinzel',serif;font-size:1.2rem;font-style:italic;font-weight:400;color:var(--gl);border-top:1px solid rgba(240,201,58,.25);padding-top:16px}

/* PILLARS */
.pillars{display:flex;flex-wrap:wrap;justify-content:center;margin:28px 0}
.pillar{padding:14px 18px;border:1px solid rgba(240,201,58,.2);font-size:.75rem;letter-spacing:.25em;text-transform:uppercase;color:var(--warm);margin:-1px}

/* QUOTE */
.quote{border-left:3px solid var(--gl);padding:28px 24px;margin:28px 0;background:rgba(240,201,58,.04)}
.quote p{font-family:'Cinzel',serif;font-size:1.4rem;font-weight:400;font-style:italic;color:var(--white);line-height:1.6}
.quote cite{display:block;margin-top:18px;font-family:'Be Vietnam Pro',sans-serif;font-size:1.1rem;font-weight:300;color:var(--warm);font-style:normal;line-height:1.8}

/* IMAGE PH */
.img-ph{height:200px;background:linear-gradient(135deg,rgba(240,201,58,.06),rgba(0,0,0,.9));border:1px solid rgba(240,201,58,.2);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;margin-bottom:28px}

/* GENIUS */
.genius-box{border:2px solid var(--gl);padding:36px 26px;position:relative;margin:36px 0 28px;background:linear-gradient(135deg,rgba(240,201,58,.06) 0%,transparent 60%)}
.genius-box::before{content:'GENIUS';position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:var(--bg2);padding:0 20px;font-family:'Be Vietnam Pro',sans-serif;font-size:.65rem;letter-spacing:.5em;color:var(--gl);font-weight:700}
.g-item{display:flex;gap:18px;align-items:flex-start;padding:18px 0;border-bottom:1px solid rgba(240,201,58,.1)}
.g-item:last-child{border-bottom:none;padding-bottom:0}
.g-icon{font-size:1.4rem;color:var(--gl);flex-shrink:0;margin-top:4px}
.g-text{font-family:'Be Vietnam Pro',sans-serif;font-size:1.05rem;font-weight:300;color:var(--warm);line-height:1.8}

/* PATH CARDS */
.path-card{background:rgba(0,0,0,.4);border:2px solid rgba(240,201,58,.3);padding:36px 28px;margin-bottom:18px}
.path-tag{font-size:.65rem;letter-spacing:.45em;text-transform:uppercase;color:var(--gl);display:block;margin-bottom:18px;font-weight:700}
.path-title{font-family:'Cinzel',serif;font-size:1.65rem;font-weight:600;color:var(--white);line-height:1.35;margin-bottom:22px}
.path-desc{font-family:'Be Vietnam Pro',sans-serif;font-size:1.05rem;font-weight:300;color:var(--warm);line-height:2.1}

/* CTA */
.cta-block{margin:36px 0 10px}
.cta-line{width:48px;height:2px;background:var(--gl);margin:0 auto 22px}
.cta-btn{display:block;width:100%;padding:22px;background:transparent;border:2px solid var(--gl);font-family:'Cinzel',serif;font-size:1.3rem;font-style:italic;color:var(--gl);cursor:pointer;text-align:center;line-height:1.4}
.cta-btn:active{background:rgba(240,201,58,.1)}
.cta-done{text-align:center;padding:22px 0;font-family:'Cinzel',serif;font-size:1.25rem;font-style:italic;color:var(--gl);animation:fadeUp .5s both}

/* FINAL BAND */
.final-band{background:linear-gradient(135deg,var(--bg3) 0%,var(--bg2) 100%);border-top:2px solid rgba(240,201,58,.3);text-align:center;padding:80px 32px;position:relative;z-index:1}
.final-band h2{font-family:'Cinzel',serif;font-size:clamp(2rem,8vw,2.8rem);font-weight:600;line-height:1.2;color:var(--white);margin-bottom:18px}
.final-band h2 em{font-style:italic;color:var(--gl)}
.final-band p{font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;font-weight:300;color:var(--warm);line-height:2;margin-bottom:44px}
footer{padding:40px 32px;text-align:center;border-top:1px solid rgba(240,201,58,.12);position:relative;z-index:1}
footer p{font-size:.65rem;letter-spacing:.2em;color:var(--soft);text-transform:uppercase}

/* CALENDAR PICKER */
.cal-wrap{margin:20px 0 28px}
.cal-label{font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.3em;text-transform:uppercase;color:var(--gl);display:block;margin-bottom:12px;font-weight:700}
.cal-input{width:100%;padding:18px 20px;background:rgba(240,201,58,.05);border:2px solid rgba(240,201,58,.3);font-family:'Cinzel',serif;font-size:1.2rem;color:var(--white);cursor:pointer;appearance:none;-webkit-appearance:none}
.cal-input:focus{outline:none;border-color:var(--gl)}

/* MODAL */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:200;align-items:flex-end;justify-content:center}
.overlay.open{display:flex;animation:fadeIn .25s}
.modal{display:block;background:var(--bg2);border:2px solid rgba(240,201,58,.3);border-bottom:none;width:100%;max-height:90vh;overflow-y:auto;padding:48px 28px 56px;border-radius:24px 24px 0 0;animation:slideUp .35s;position:relative}
.modal-x{position:absolute;top:20px;right:24px;background:none;border:none;color:var(--warm);font-size:1.4rem;cursor:pointer;line-height:1}
.m-eye{font-size:.65rem;letter-spacing:.5em;text-transform:uppercase;color:var(--gl);display:block;margin-bottom:14px;font-weight:700}
.m-title{font-family:'Cinzel',serif;font-size:2rem;font-weight:600;color:var(--white);margin-bottom:12px;line-height:1.2}
.m-sub{font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;font-weight:300;color:var(--warm);line-height:1.9;margin-bottom:36px}
.m-ctx{font-family:'Cinzel',serif;font-style:italic;font-size:1.2rem;color:var(--gl);margin-bottom:28px;line-height:1.6}
.mstep{display:none}
.mstep.on{display:block;animation:fadeUp .35s both}
.ch-list{display:flex;flex-direction:column;gap:12px;margin-bottom:18px}
.ch-icon{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.ch-name{font-size:.82rem;letter-spacing:.15em;text-transform:uppercase;color:var(--white);font-weight:600}
.ch-lbl{font-size:.68rem;color:var(--warm);display:block;margin-top:4px}
.m-note{font-family:'Be Vietnam Pro',sans-serif;font-size:.9rem;color:var(--warm);line-height:1.8;text-align:center;margin-top:22px;border-top:1px solid rgba(240,201,58,.12);padding-top:20px}
.comp-list{display:flex;flex-direction:column;gap:14px;margin-bottom:28px}
.comp-card{display:flex;gap:20px;align-items:flex-start;padding:26px 22px;background:rgba(240,201,58,.05);border:2px solid rgba(240,201,58,.15);cursor:pointer;transition:all .2s}
.comp-card.sel{background:rgba(240,201,58,.12);border-color:var(--gl)}
.comp-chk{width:26px;height:26px;border:2px solid var(--gd);border-radius:4px;flex-shrink:0;margin-top:4px;display:flex;align-items:center;justify-content:center;transition:all .2s}
.comp-card.sel .comp-chk{background:var(--gl);border-color:var(--gl)}
.chk-in{font-size:.8rem;color:#000;opacity:0;transition:opacity .2s;font-weight:700}
.comp-card.sel .chk-in{opacity:1}
.comp-body{flex:1}
.comp-tag{font-size:.6rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);display:block;margin-bottom:8px;font-weight:700}
.comp-title{font-family:'Cinzel',serif;font-size:1.4rem;font-weight:600;color:var(--white);margin-bottom:10px;line-height:1.3}
.comp-desc{font-family:'Be Vietnam Pro',sans-serif;font-size:.95rem;font-weight:300;color:var(--warm);line-height:1.85}
.send-btn{width:100%;padding:22px;background:var(--gl);border:none;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:#000;cursor:pointer;margin-bottom:14px}
.send-btn:active{background:#FFD700}
.send-btn:disabled{opacity:.3;cursor:default}
.back-btn{background:none;border:none;color:var(--warm);font-size:.68rem;letter-spacing:.2em;text-transform:uppercase;cursor:pointer;font-family:'Be Vietnam Pro',sans-serif;padding:8px 0;display:block}
.confirm-wrap{text-align:center;padding:24px 0 14px}
.confirm-icon{font-size:3rem;margin-bottom:24px;color:var(--gl)}
.confirm-title{font-family:'Cinzel',serif;font-size:2rem;font-weight:600;color:var(--white);margin-bottom:14px;line-height:1.2}
.confirm-sub{font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;font-weight:300;color:var(--warm);line-height:2;margin-bottom:36px}

/* CHANNEL SELECTOR (modal step 1) */
.ch-sel{display:flex;align-items:center;gap:16px;padding:18px 20px;background:rgba(240,201,58,.05);border:1px solid rgba(240,201,58,.15);cursor:pointer;transition:all .2s;-webkit-tap-highlight-color:transparent}
.ch-sel.selected{background:rgba(240,201,58,.12);border-color:var(--gl)}
.ch-sel:active{background:rgba(240,201,58,.1)}
.ch-chk{width:22px;height:22px;border:2px solid rgba(240,201,58,.3);border-radius:4px;flex-shrink:0;transition:all .2s;background:transparent;position:relative}
.ch-sel.selected .ch-chk{background:var(--gl);border-color:var(--gl)}
.ch-sel.selected .ch-chk::after{content:'✓';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.7rem;color:#000;font-weight:700}
.ch-info{flex:1}
.ch-entry{padding:0 20px 14px;background:rgba(240,201,58,.05);border:1px solid rgba(240,201,58,.15);border-top:none;animation:fadeUp .25s both}
.ch-input{width:100%;padding:14px 16px;background:rgba(0,0,0,.6);border:2px solid rgba(240,201,58,.6);font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--white);outline:none}
.ch-input:focus{border-color:var(--gl)}
.ch-input::placeholder{color:rgba(234,217,176,.65)}
.m-benefit{font-family:'Be Vietnam Pro',sans-serif;font-size:.95rem;font-weight:300;color:var(--warm);line-height:1.8;margin:-8px 0 20px;font-style:italic}
.ms2-quote{font-family:'Cinzel',serif;font-size:1.1rem;font-style:italic;color:var(--warm);line-height:1.8;border-left:3px solid var(--gl);padding:16px 20px;margin:0 0 24px;background:rgba(240,201,58,.04)}

/* IMAGE/CARD WRAPPERS */
.card-img-wrap{overflow:hidden}
.card-img-wrap img{transition:transform .3s ease}
.card-img-wrap img:hover{transform:scale(1.02)}
.dual-appearance-wrap{display:flex;gap:12px;margin:20px 0 28px}
.appearance-ph{flex:1;height:200px;background:linear-gradient(135deg,rgba(240,201,58,.06),rgba(0,0,0,.9));border:1px solid rgba(240,201,58,.2);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;position:relative}
.appearance-ph::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(to right,var(--gl),transparent)}
.sailboat-wrap img{filter:brightness(.85)}
.we-believe{font-family:'Cinzel',serif;font-size:1.1rem;font-weight:700;letter-spacing:.5em;text-transform:uppercase;color:var(--gl);text-align:center;margin:28px 0 20px;display:block}

/* SCROLL ANIMATION */
.drift-on-scroll{opacity:0;transform:translateY(32px);transition:opacity .8s cubic-bezier(.2,0,.2,1),transform .8s cubic-bezier(.2,0,.2,1)}
.drift-on-scroll.visible{opacity:1;transform:translateY(0)}
.drift-up{animation:driftUp .8s cubic-bezier(.2,0,.2,1) both}

/* STAGE VISUAL */
.stage-visual{width:100%;height:280px;background:radial-gradient(ellipse at 50% 60%,#1A1200 0%,#000000 70%);position:relative;overflow:hidden;margin-bottom:28px;border:1px solid rgba(240,201,58,.15)}
.stage-rings{position:absolute;top:50%;left:50%;transform:translate(-50%,-55%);display:flex;gap:12px;align-items:center}
.stage-ring{border-radius:50%;border:2px solid rgba(240,201,58,.35);box-shadow:0 0 12px rgba(240,201,58,.15),inset 0 0 12px rgba(240,201,58,.08);animation:ringPulse 3s ease-in-out infinite}
.stage-ring.r1{width:56px;height:56px;animation-delay:0s}
.stage-ring.r2{width:72px;height:72px;animation-delay:.4s;border-color:rgba(240,201,58,.25)}
.stage-ring.r3{width:56px;height:56px;animation-delay:.8s}
.stage-label{position:absolute;bottom:22px;left:0;right:0;text-align:center}
.stage-label span{font-family:'Be Vietnam Pro',sans-serif;font-size:.6rem;letter-spacing:.55em;text-transform:uppercase;color:rgba(240,201,58,.5);font-weight:400}

/* TABLET — 768px */
@media(min-width:768px){
  nav{padding:22px 48px}
  .logo{font-size:1.3rem}
  .hero{padding:180px 64px 110px}
  .hero h1{font-size:clamp(2.8rem,8vw,5rem)}
  .hero-sub{font-size:1.35rem;max-width:480px}
  .btn-gold,.btn-scarlet{width:360px;padding:22px 28px}
  .acc-hd{padding:36px 64px 24px}
  .acc-eye{font-size:1rem}
  .acc-title{font-size:2.2rem}
  .acc-inner{padding:8px 64px 56px}
  .sec-p{font-size:1.3rem;line-height:2}
  .beliefs-intro{font-size:2.2rem}
  .b-text{font-size:1.45rem}
  .quote p{font-size:1.65rem}
  .quote cite{font-size:1.3rem}
  .h-name{font-size:2rem}
  .h-text{font-size:1.3rem}
  .path-title{font-size:2rem}
  .path-desc{font-size:1.25rem}
  .final-band{padding:100px 48px}
  .modal{padding:52px 40px 60px}
  .m-title{font-size:2.4rem}
  .m-sub{font-size:1.2rem}
  .tap-hint{padding:22px 48px 18px}
  .appearance-ph{height:260px}
  .sailboat-wrap img{height:360px}
  .card-img-wrap img{height:300px}
}

/* DESKTOP — 1024px */
@media(min-width:1024px){
  nav{padding:28px 80px}
  .logo{font-size:1.4rem;letter-spacing:.4em}
  .hero{padding:220px 120px 140px}
  .hero h1{font-size:clamp(3rem,6vw,5.5rem)}
  .hero-sub{font-size:1.4rem;max-width:560px}
  .hero-btns{flex-direction:row;gap:24px}
  .btn-gold,.btn-scarlet{width:auto;padding:22px 48px}
  .acc-hd{padding:44px 120px 28px}
  .acc-title{font-size:2.6rem}
  .acc-inner{padding:10px 120px 72px}
  .sec-p{font-size:1.5rem;max-width:800px}
  .beliefs-intro{font-size:2.6rem;max-width:800px;margin-left:auto;margin-right:auto}
  .b-text{font-size:1.65rem}
  .quote p{font-size:1.9rem}
  .quote cite{font-size:1.45rem}
  .h-name{font-size:2.4rem}
  .h-text{font-size:1.45rem}
  .h-result{font-size:1.45rem}
  .path-title{font-size:2.4rem}
  .path-desc{font-size:1.4rem}
  .final-band{padding:120px 120px}
  .final-band p{font-size:1.4rem;max-width:600px;margin:0 auto 52px}
  .cta-block{max-width:580px}
  .modal{max-width:620px;margin:0 auto}
  .m-title{font-size:2.8rem}
  .tap-hint{padding:28px 120px 22px}
  .acc-wrap{padding-bottom:100vh}
  .appearance-ph{height:320px}
  .dual-appearance-wrap{gap:20px}
  .sailboat-wrap img{height:460px;max-width:100%}
  .card-img-wrap img{height:360px}
}

/* LARGE DESKTOP — 1400px */
@media(min-width:1400px){
  .hero{padding:240px 160px 160px}
  .acc-hd{padding:48px 160px 32px}
  .acc-inner{padding:12px 160px 80px}
  .final-band{padding:140px 160px}
  .tap-hint{padding:32px 160px 26px}
}

@keyframes ringPulse{0%,100%{opacity:.5;transform:scale(1)}50%{opacity:1;transform:scale(1.06)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(60px);opacity:0}to{transform:none;opacity:1}}
@keyframes pulse{0%,100%{opacity:.3}50%{opacity:1}}
@keyframes driftUp{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:translateY(0)}}
@keyframes glowPulse{0%,100%{box-shadow:0 0 20px rgba(240,201,58,0);transform:scale(1)}50%{box-shadow:0 0 40px rgba(240,201,58,.25);transform:scale(1.02)}}
@keyframes heroGlow{0%,100%{opacity:.08}50%{opacity:.18}}
</style>

<main id="primary" class="site-main">

<nav>
  <a class="logo" href="#"><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;color:var(--gl)">influencer</span><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;color:var(--gl)">HQ</span></a>
  <button class="nav-cta" id="navCta" onclick="openModal()">Yes — Let's Talk</button>
</nav>

<section class="hero">
  <div class="hero-lines"></div>
  <div class="hero-glow"></div>
  <span class="hero-eye">Influencer Headquarters</span>
  <h1>Influence.<br><em>Compete.</em><br>Own the Future.</h1>
  <p class="hero-sub">We believe those who drive the energy deserve to share in what they help build.</p>
  <div class="hero-btns">
    <button class="btn-gold" onclick="openModal()">Yes — Let's Start the Conversation</button>
  </div>
  <div class="scroll-hint"><span>Discover</span><div class="scroll-line"></div></div>
</section>

<div class="acc-wrap">
  <div class="tap-hint gone" id="tapHint"><span>Tap any heading to explore</span></div>

  <div class="acc-sec" id="s1">
    <div class="acc-hd" onclick="tog('s1')">
      <span class="acc-eye">Who We Are</span>
      <span class="acc-title">Our Beliefs</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="beliefs-intro">People don't buy what you do — They buy what you believe.</p>
      <p class="sec-p">The beliefs below guide every opportunity at Influencer Headquarters where influencers partner with global platforms that value long-term contribution, not one-time campaigns.</p>
      <span class="we-believe">WE BELIEVE</span>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Influence deserves more than short-term payouts.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Your voice should create long-term value.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Those who drive the energy deserve to share in what they help build.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">When your influence grows a platform, you should share in the value created by that growth.</span></div>
    </div></div>
  </div>

  <div class="acc-sec" id="s2">
    <div class="acc-hd" onclick="tog('s2')">
      <span class="acc-eye">The Opportunity</span>
      <span class="acc-title">The Future of the Influencer Marketplace</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">Brands everywhere rely on influencers to shape culture, spark engagement, and build communities. But traditional partnerships often end sooner than the value you provide.</p>
      <p class="sec-p">Our partners' platforms reward ongoing contribution — giving influencers a meaningful stake in the success they help build. As these platforms expand across new markets, the value of your influence grows with them.</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s3">
    <div class="acc-hd" onclick="tog('s3')">
      <span class="acc-eye">A Lesson from History</span>
      <span class="acc-title"><em>Ownership</em> Has Always Been the Difference</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">We believe that Influencers deserve the opportunity to share in ownership.</p>
      <div class="h-card">
        <div class="card-img-wrap magic-img" style="height:240px;background:rgba(240,201,58,.08);border:1px solid rgba(240,201,58,.2);display:flex;align-items:center;justify-content:center"><span style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;letter-spacing:.2em;color:rgba(240,201,58,.4);text-transform:uppercase">Magic Johnson — Licensed Photo</span></div>
        <div class="h-body">
          <div class="h-name">Magic Johnson</div>
          <div class="h-text">The biggest missed equity-for-influence deal in history was Nike's 11 cent stock deal offered to NBA superstar Magic Johnson. Magic chose Adidas' $100,000 cash instead.<br><br>Today's value of the Nike stock Magic turned down? $5.4 billion.</div>
          <div class="h-result">— and that's why equity-for-influence changes everything.</div>
        </div>
      </div>
      <div class="h-card">
        <div class="card-img-wrap" style="height:240px;background:rgba(240,201,58,.08);border:1px solid rgba(240,201,58,.2);display:flex;align-items:center;justify-content:center"><span style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;letter-spacing:.2em;color:rgba(240,201,58,.4);text-transform:uppercase">Alix Earle — Licensed Photo</span></div>
        <div class="h-body">
          <div class="h-name">Alix Earle</div>
          <div class="h-text">American Influencer Alix Earle chose an ownership-based partnership with Poppi instead of traditional cash. When Poppi was acquired by PepsiCo for well over a billion dollars, her choice became a defining moment in the creator economy.</div>
          <div class="h-result">Ownership matters — and influence deserves the chance to share in it.</div>
        </div>
      </div>
    </div></div>
  </div>

  <div class="acc-sec" id="s4">
    <div class="acc-hd" onclick="tog('s4')">
      <span class="acc-eye">The Foundation</span>
      <span class="acc-title">Competition: The Oldest Language in the World</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">It began in ancient Greece, where the first Olympic Games ignited national pride. It filled the arenas of Rome. It lives in Asia's legendary traditions — games of mastery, precision, rhythm, and strategy celebrated for centuries.</p>
      <div class="pillars">
        <div class="pillar">Global Sports</div><div class="pillar">Music Battles</div><div class="pillar">Esports Arenas</div>
        <div class="pillar">Creator Challenges</div><div class="pillar">Digital Tournaments</div><div class="pillar">Moments We Share</div>
      </div>
      <div class="quote">
        <p>Competition is Universal<br>Competition is Emotional<br>Competition is Human</p>
        <cite>Throughout history, competition has always needed leaders who elevate every moment, ignite anticipation, and unite communities. Today, those leaders are influencers.</cite>
      </div>
    </div></div>
  </div>

  <div class="acc-sec" id="s5">
    <div class="acc-hd" onclick="tog('s5')">
      <span class="acc-eye">The Stage</span>
      <span class="acc-title">Elegance. Prestige. <em>Global Legacy.</em></span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="sailboat-wrap" style="height:320px;background:rgba(240,201,58,.05);border:1px solid rgba(240,201,58,.15);display:flex;align-items:center;justify-content:center;margin-left:-32px;margin-right:-32px"><span style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;letter-spacing:.2em;color:rgba(240,201,58,.4);text-transform:uppercase">Elegance Photo — Licensed</span></div>
      <p class="sec-p">The stages influencers step onto carry the same timeless elements found in the world's greatest competitions. Elegance that never goes out of style. Prestige that spans centuries.</p>
      <p class="sec-p">A global following that crosses borders and cultures. Intensity and emotion in every moment. Influence deserves a stage worthy of that legacy — and now, it has one.</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s6">
    <div class="acc-hd" onclick="tog('s6')">
      <span class="acc-eye">The Technology</span>
      <span class="acc-title"><em>Equity You Can Count On</em></span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">Every follower you invite, every level of participation they generate, and every moment of play they create is tracked and verified by <strong>GENIUS</strong> — a best-in-class technology set up expressly for this purpose.</p>
      <div class="genius-box">
        <div class="g-item"><span class="g-icon">◈</span><span class="g-text">Every share recorded at the exact moment it is earned</span></div>
        <div class="g-item"><span class="g-icon">◈</span><span class="g-text">Nothing is pooled, estimated, or assumed</span></div>
      </div>
      <p class="sec-p" style="text-align:center;color:var(--gl);font-weight:400">When your followers participate, Genius records it. When your network grows, Genius tracks it. Your equity is protected.</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s7">
    <div class="acc-hd" onclick="tog('s7')">
      <span class="acc-eye">The Global Stage</span>
      <span class="acc-title">International Competition Series</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="dual-appearance-wrap">
        <div class="appearance-ph"><span class="ph-icon">◈</span><div class="ph-lbl">Influencer Appearance</div><span class="ph-sub">Licensed photo</span></div>
        <div class="appearance-ph"><span class="ph-icon">◈</span><div class="ph-lbl">Influencer Appearance</div><span class="ph-sub">Licensed photo</span></div>
      </div>
      <p class="sec-p">Influencers are automatically invited to appear on our partners' International Competition Series — a 24-hour global competition stage. Head-to-head competition features two Influencers from their own locations streaming reactions, commentary, and competitive energy in real time.</p>
      <p class="sec-p" style="color:var(--gl)">English · Mandarin · Cantonese · Korean · Japanese · Thai · Vietnamese</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s8">
    <div class="acc-hd" onclick="tog('s8')">
      <span class="acc-eye">Your Invitation</span>
      <span class="acc-title">The Competitions</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="ms2-quote" style="margin-bottom:28px">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
      <p class="sec-p" style="text-align:center;color:var(--white);margin-bottom:8px;font-family:'Cinzel',serif;font-size:1.4rem">Two competitions. One stage. All yours.</p>
      <p class="sec-p" style="text-align:center;color:var(--warm);margin-bottom:32px;font-size:1rem">Choose the one that feels right for you right now. You can always do both.</p>

      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);font-weight:700;margin-bottom:12px">Start Here — Community Competition</p>
      <div class="path-card">
        <div class="path-title">One Week. Your Community.</div>
        <p class="path-desc">A one week get acquainted competition between your followers who choose to participate with you at the helm cheering them all on to victory.<br><br>Welcome to the stage that was built for you.</p>
      </div>

      <div style="height:1px;background:rgba(240,201,58,.2);margin:32px 0"></div>

      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);font-weight:700;margin-bottom:12px">Go Big — World Competition</p>
      <div class="path-card">
        <span class="path-tag">This Weekend · Thursday Night through Sunday Night</span>
        <div class="path-title"><div style="text-align:center;line-height:1.9">
          <span style="display:block">You and your followers</span>
          <span style="display:block;font-family:'Be Vietnam Pro',sans-serif;font-size:1.1rem;letter-spacing:.4em;color:var(--gl);font-weight:700;margin:6px 0">VS</span>
          <span style="display:block">Other Influencers and their followers</span>
          <span style="display:block;margin-top:12px;color:var(--gl)">One global stage!</span>
        </div></div>
        <div style="width:40px;height:2px;background:var(--gl);margin:18px 0"></div>
        <p class="path-desc">Join creators and their followers from across the world. Compete anytime Thursday night through Sunday night.</p>
      </div>

      <p style="font-family:'Cinzel',serif;font-size:1.2rem;font-style:italic;color:var(--warm);text-align:center;margin:32px 0 24px;line-height:1.6">The stage is set. The competition is waiting.</p>
      <div style="text-align:center;margin-bottom:16px">
        <button class="btn-scarlet" id="competeBtn" onclick="openCompetition()" style="margin:0 auto;display:block;opacity:0">Yes — I'm Ready to Compete</button>
      </div>
    </div></div>
  </div>

</div><!-- .acc-wrap -->

<div class="final-band">
  <div class="cta-line" style="margin-bottom:28px"></div>
  <h2>Your influence is<br><em>worth more than a campaign.</em></h2>
  <p>We're ready when you are.<br>Reach us through the channel that works best for you.</p>
  <div id="cfinal">
    <button class="btn-gold drift-on-scroll" id="finalBtn" onclick="openModal('cfinal')" style="margin:0 auto;display:block">Yes — Let's Start the Conversation</button>
  </div>
</div>

<footer><p>© <?php echo esc_html(date('Y')); ?> InfluencerHQ · All Rights Reserved</p></footer>

<!-- Contact / Competition Modal -->
<div class="overlay" id="mainModal">
  <div class="modal">
    <button class="modal-x" onclick="closeModal()">✕</button>
    <div class="mstep on" id="ms1">
      <span class="m-eye">Get in Touch</span>
      <h3 class="m-title">Let's Start the Conversation</h3>
      <p class="m-sub">Select your favorite communication methods. Choose as many as you like.</p>
      <p class="m-benefit">We believe that communication is the foundation of all business relationships and all success.</p>
      <div class="ch-list">
        <div class="ch-sel" id="sel-email" onclick="toggleCh('email')">
          <div class="ch-chk" id="chk-email"></div>
          <div class="ch-icon" style="background:rgba(240,201,58,.15)">✉</div>
          <div class="ch-info"><div class="ch-name">Email</div><span class="ch-lbl">We'll reach you by email</span></div>
        </div>
        <div class="ch-entry" id="entry-email" style="display:none"><input class="ch-input" type="email" placeholder="Your email address"></div>

        <div class="ch-sel" id="sel-wa" onclick="toggleCh('wa')">
          <div class="ch-chk" id="chk-wa"></div>
          <div class="ch-icon" style="background:rgba(37,211,102,.15);color:#25D366">💬</div>
          <div class="ch-info"><div class="ch-name">WhatsApp</div><span class="ch-lbl">Message us on WhatsApp</span></div>
        </div>
        <div class="ch-entry" id="entry-wa" style="display:none"><input class="ch-input" type="tel" placeholder="Your WhatsApp number"></div>

        <div class="ch-sel" id="sel-kakao" onclick="toggleCh('kakao')">
          <div class="ch-chk" id="chk-kakao"></div>
          <div class="ch-icon" style="background:rgba(255,232,18,.15);color:#FEE418">💛</div>
          <div class="ch-info"><div class="ch-name">KakaoTalk</div><span class="ch-lbl">Your KakaoTalk ID</span></div>
        </div>
        <div class="ch-entry" id="entry-kakao" style="display:none"><input class="ch-input" type="text" placeholder="KakaoTalk ID"></div>

        <div class="ch-sel" id="sel-line" onclick="toggleCh('line')">
          <div class="ch-chk" id="chk-line"></div>
          <div class="ch-icon" style="background:rgba(0,195,0,.15);color:#00C300">✔</div>
          <div class="ch-info"><div class="ch-name">LINE</div><span class="ch-lbl">Your LINE ID</span></div>
        </div>
        <div class="ch-entry" id="entry-line" style="display:none"><input class="ch-input" type="text" placeholder="LINE ID"></div>

        <div class="ch-sel" id="sel-wechat" onclick="toggleCh('wechat')">
          <div class="ch-chk" id="chk-wechat"></div>
          <div class="ch-icon" style="background:rgba(9,187,7,.15);color:#09BB07">💬</div>
          <div class="ch-info"><div class="ch-name">WeChat</div><span class="ch-lbl">Your WeChat ID</span></div>
        </div>
        <div class="ch-entry" id="entry-wechat" style="display:none"><input class="ch-input" type="text" placeholder="WeChat ID"></div>

        <div class="ch-sel" id="sel-telegram" onclick="toggleCh('telegram')">
          <div class="ch-chk" id="chk-telegram"></div>
          <div class="ch-icon" style="background:rgba(0,136,204,.15);color:#0088CC">✈</div>
          <div class="ch-info"><div class="ch-name">Telegram</div><span class="ch-lbl">Your Telegram username</span></div>
        </div>
        <div class="ch-entry" id="entry-telegram" style="display:none"><input class="ch-input" type="text" placeholder="@username"></div>
      </div>

      <div style="margin:20px 0 4px">
        <div style="font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gl);font-weight:600;margin-bottom:10px">Favorite Social Media Handle</div>
        <input style="width:100%;padding:14px 16px;background:rgba(0,0,0,.6);border:2px solid rgba(240,201,58,.6);font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:#fff;outline:none" type="text" placeholder="@yourhandle" id="socialHandle">
      </div>

      <button onclick="chosen()" style="margin-top:16px;width:100%;padding:20px;background:var(--gl);border:none;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:#000;cursor:pointer;opacity:.35;transition:opacity .3s" id="ch-continue" disabled>Continue →</button>

      <p class="m-note">All conversations are private and confidential.<br>We operate across time zones. Expect a reply within minutes.</p>
    </div>
    <div class="mstep" id="ms2">
      <span class="m-eye">One More Thing</span>
      <h3 class="m-title">Glory is earned one weekend at a time.</h3>
      <p class="m-ctx">Yours starts now. Choose your competition and we'll send you and your followers everything you need to get started.</p>
      <div class="ms2-quote">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
      <div class="comp-list">
        <div class="comp-card" id="cw" onclick="pickComp('cw')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Global Stage</span><div class="comp-title">Influencer World Competition</div><div class="comp-desc">Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.</div></div></div>
        <div class="comp-card" id="cp" onclick="pickComp('cp')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Community Competition</span><div class="comp-title">Community Competition</div><div class="comp-desc">A one week get acquainted competition between your followers who choose to participate with you at the helm cheering them all on to victory.</div></div></div>
      </div>
      <button class="send-btn" id="sendbtn" onclick="submit()" disabled>Send Me the Details</button>
      <button class="back-btn" onclick="goBack()">← Back</button>
    </div>
    <div class="mstep" id="ms3">
      <div class="confirm-wrap">
        <div class="confirm-icon">◈</div>
        <h3 class="confirm-title">You're on your way.</h3>
        <p class="confirm-sub">Details coming shortly. You and your followers are on the road to glory.</p>
        <button class="btn-gold" onclick="closeModal()" style="margin:0 auto;display:block">Close</button>
      </div>
    </div>
  </div>
</div>

</main><!-- #primary -->

<script>
var started = false;
var src = null;

window.addEventListener('scroll', function() {
  var hero = document.querySelector('.hero');
  var navCta = document.getElementById('navCta');
  if (window.pageYOffset > hero.offsetHeight * 0.7) navCta.classList.add('visible');
  else navCta.classList.remove('visible');
});

function tog(id) {
  var sec = document.getElementById(id);
  var isOpen = sec.classList.contains('open');
  var hint = document.getElementById('tapHint');
  if (hint) hint.classList.add('gone');
  document.querySelectorAll('.acc-sec').forEach(function(s) { s.classList.remove('open'); });
  var cb = document.getElementById('competeBtn');
  if (cb) { cb.classList.remove('drift-up'); cb.style.opacity = '0'; }
  if (!isOpen) {
    sec.classList.add('open');
    var top = sec.getBoundingClientRect().top + window.pageYOffset - 70;
    window.scrollTo({ top: top, behavior: 'smooth' });
    setTimeout(function() {
      window.scrollTo({ top: sec.getBoundingClientRect().top + window.pageYOffset - 70, behavior: 'smooth' });
    }, 400);
    setTimeout(function() {
      window.scrollTo({ top: sec.getBoundingClientRect().top + window.pageYOffset - 70, behavior: 'smooth' });
    }, 600);
    if (id === 's8') {
      setTimeout(function() {
        var btn = document.getElementById('competeBtn');
        if (btn) { btn.style.opacity = '0'; btn.classList.add('drift-up'); }
      }, 800);
    }
  }
}

function openModal(ctaId) {
  src = ctaId || null;
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms1');
}

function openCompetition() {
  src = null;
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms2');
}

function closeModal() {
  document.getElementById('mainModal').classList.remove('open');
  document.body.style.overflow = '';
}

function show(id) {
  document.querySelectorAll('.mstep').forEach(function(s) { s.classList.remove('on'); });
  document.getElementById(id).classList.add('on');
}

function toggleCh(id) {
  var sel = document.getElementById('sel-' + id);
  var entry = document.getElementById('entry-' + id);
  if (sel.classList.contains('selected')) {
    sel.classList.remove('selected');
    entry.style.display = 'none';
  } else {
    sel.classList.add('selected');
    entry.style.display = 'block';
  }
  var anySelected = document.querySelectorAll('.ch-sel.selected').length > 0;
  var btn = document.getElementById('ch-continue');
  if (btn) {
    btn.disabled = !anySelected;
    btn.style.opacity = anySelected ? '1' : '.35';
    btn.style.cursor = anySelected ? 'pointer' : 'default';
  }
}

function chosen() {
  started = true;
  document.querySelectorAll('.cta-block').forEach(function(block) {
    block.innerHTML = '<p class="cta-done">Connected. Details coming shortly.</p>';
  });
  var cfinal = document.getElementById('cfinal');
  if (cfinal) cfinal.innerHTML = '<p class="cta-done">Details coming your way shortly.</p>';
  show('ms2');
}

function pickComp(id) {
  document.getElementById(id).classList.toggle('sel');
  var any = document.querySelectorAll('.comp-card.sel').length > 0;
  document.getElementById('sendbtn').disabled = !any;
}

function submit() { show('ms3'); }
function goBack() { show('ms1'); }

document.getElementById('mainModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Drift-in animation for final band button on scroll
var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.3 });
var finalBtn = document.getElementById('finalBtn');
if (finalBtn) observer.observe(finalBtn);
</script>


<?php get_footer(); ?>
