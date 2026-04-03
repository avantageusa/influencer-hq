<?php
/**
 * Template Name: Portal Test API
 * Description: Interactive AJAX API tester for all InfluencerHQ gateway routes.
 *
 * @package Avantage_Baccarat
 */
get_header();
?>
<main id="primary" class="site-main ihq-api-tester">
<div class="iat-layout">

  <!-- SIDEBAR -->
  <aside class="iat-sidebar">
    <div class="iat-sidebar-header">
      <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="InfluencerHQ" class="iat-logo">
      <h2>API Tester</h2>
      <input type="text" id="iat-search" placeholder="Search endpoints…" autocomplete="off">
    </div>
    <nav class="iat-nav" id="iat-nav"></nav>
  </aside>

  <!-- MAIN -->
  <div class="iat-main">
    <div class="iat-detail" id="iat-detail">
      <div class="iat-placeholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="1.5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
        <p>Select an endpoint from the sidebar</p>
      </div>
    </div>

    <div class="iat-response" id="iat-response" style="display:none;">
      <div class="iat-response-header">
        <span class="iat-response-title">Response</span>
        <span id="iat-status-badge" class="iat-badge"></span>
        <span id="iat-response-url" class="iat-response-url"></span>
        <button class="iat-btn-ghost" id="iat-copy-btn">⎘ Copy</button>
        <button class="iat-btn-ghost" id="iat-collapse-btn">▾</button>
      </div>
      <div id="iat-response-body-wrap">
        <div class="iat-tabs">
          <button class="iat-tab active" data-tab="body">Body</button>
          <button class="iat-tab" data-tab="headers">Headers</button>
          <button class="iat-tab" data-tab="request">Request</button>
        </div>
        <pre id="iat-body-pre"    class="iat-pre"><code></code></pre>
        <pre id="iat-headers-pre" class="iat-pre" style="display:none;"><code></code></pre>
        <pre id="iat-request-pre" class="iat-pre" style="display:none;"><code></code></pre>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="iat-nonce"    value="<?php echo wp_create_nonce('ihq_api_test'); ?>">
<input type="hidden" id="iat-ajax-url" value="<?php echo admin_url('admin-ajax.php'); ?>">
</main>

<style>
:root{--gold:#E6CFA0;--gold-dim:#C4A46D;--bg:#0a0a0a;--bg2:#111;--bg3:#1a1a1a;--border:rgba(230,207,160,.18);--text:#e8e8e8;--dim:#888;--green:#3fb950;--red:#f85149;--blue:#58a6ff;--orange:#d29922;--sw:300px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:'Source Sans Pro',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
html{margin-top:0!important;}#wpadminbar{display:none!important;}
.ihq-api-tester{min-height:100vh;}
.iat-layout{display:flex;height:100vh;overflow:hidden;}
.iat-sidebar{width:var(--sw);min-width:var(--sw);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;}
.iat-sidebar-header{padding:16px 14px 10px;border-bottom:1px solid var(--border);flex-shrink:0;}
.iat-logo{max-height:32px;margin-bottom:10px;display:block;}
.iat-sidebar-header h2{font-size:.9rem;font-weight:700;color:var(--gold);letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px;}
#iat-search{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:6px 10px;color:var(--text);font-size:.82rem;outline:none;}
#iat-search:focus{border-color:var(--gold);}
.iat-nav{overflow-y:auto;flex:1;padding-bottom:20px;}
.iat-nav::-webkit-scrollbar{width:4px;}
.iat-nav::-webkit-scrollbar-thumb{background:var(--border);}
.iat-group-label{font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--dim);padding:14px 14px 4px;}
.iat-nav-item{display:flex;align-items:center;gap:8px;padding:7px 14px;cursor:pointer;font-size:.8rem;border-left:3px solid transparent;transition:background .15s,border-color .15s;}
.iat-nav-item:hover{background:var(--bg3);}
.iat-nav-item.active{background:var(--bg3);border-left-color:var(--gold);color:var(--gold);}
.iat-mb{font-size:.6rem;font-weight:800;padding:2px 5px;border-radius:3px;min-width:48px;text-align:center;flex-shrink:0;}
.bg-get{background:rgba(63,185,80,.15);color:var(--green);}
.bg-post{background:rgba(88,166,255,.15);color:var(--blue);}
.bg-patch{background:rgba(210,153,34,.15);color:var(--orange);}
.bg-delete{background:rgba(248,81,73,.15);color:var(--red);}
.iat-main{flex:1;overflow:hidden;display:flex;flex-direction:column;}
.iat-detail{flex:1;overflow-y:auto;padding:28px 32px;}
.iat-detail::-webkit-scrollbar{width:5px;}
.iat-detail::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.iat-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;color:var(--dim);font-size:.9rem;}
.ep-header{display:flex;align-items:center;gap:12px;margin-bottom:6px;}
.ep-path{font-family:monospace;font-size:1rem;}
.ep-desc{font-size:.85rem;color:var(--dim);margin-bottom:20px;}
.sec-title{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--dim);margin:18px 0 8px;}
.fields{display:flex;flex-direction:column;gap:8px;}
.field{display:flex;flex-direction:column;gap:4px;}
.field label{font-size:.75rem;color:var(--dim);}
.field input,.field select,.field textarea{background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:7px 10px;color:var(--text);font-size:.82rem;outline:none;font-family:inherit;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--gold);}
.field textarea{resize:vertical;min-height:130px;font-family:monospace;font-size:.78rem;}
.field select option{background:var(--bg3);}
.auth-row{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:.78rem;color:var(--dim);background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:8px 12px;}
.auth-row strong{color:var(--gold);}
.send-btn{margin-top:18px;padding:9px 28px;background:var(--gold);color:#000;border:none;border-radius:6px;font-weight:700;font-size:.88rem;cursor:pointer;transition:background .2s,transform .1s;}
.send-btn:hover{background:var(--gold-dim);}
.send-btn:active{transform:scale(.98);}
.send-btn:disabled{opacity:.5;cursor:not-allowed;}
.iat-response{border-top:1px solid var(--border);background:var(--bg2);flex-shrink:0;}
.iat-response-header{display:flex;align-items:center;gap:10px;padding:9px 18px;border-bottom:1px solid var(--border);background:var(--bg3);}
.iat-response-title{font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dim);}
.iat-badge{font-size:.72rem;font-weight:800;padding:2px 8px;border-radius:4px;}
.iat-badge.ok{background:rgba(63,185,80,.2);color:var(--green);}
.iat-badge.err{background:rgba(248,81,73,.2);color:var(--red);}
.iat-badge.pend{background:rgba(230,207,160,.1);color:var(--gold);}
.iat-response-url{font-family:monospace;font-size:.72rem;color:var(--dim);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.iat-btn-ghost{background:none;border:1px solid var(--border);border-radius:4px;color:var(--dim);padding:2px 8px;font-size:.72rem;cursor:pointer;}
.iat-btn-ghost:hover{border-color:var(--gold);color:var(--gold);}
.iat-tabs{display:flex;border-bottom:1px solid var(--border);padding:0 18px;}
.iat-tab{padding:7px 14px;font-size:.78rem;cursor:pointer;background:none;border:none;color:var(--dim);border-bottom:2px solid transparent;margin-bottom:-1px;}
.iat-tab:hover{color:var(--text);}
.iat-tab.active{color:var(--gold);border-bottom-color:var(--gold);}
.iat-pre{padding:16px 18px;font-family:monospace;font-size:.78rem;overflow:auto;max-height:380px;color:#f8f8f2;line-height:1.55;white-space:pre-wrap;word-break:break-all;}
.iat-pre::-webkit-scrollbar{width:5px;height:5px;}
.iat-pre::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.jk{color:#79c0ff;}.js{color:#a5d6ff;}.jn{color:#f2cc60;}.jb{color:#ff7b72;}.jz{color:#8b949e;}
</style>

<script>
(function(){
const BASE='https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc';
const NOW=new Date().toISOString();
const DATE=NOW.slice(0,10);

const EPS=[
  /* ── RANKINGS ── */
  {g:'Rankings',m:'POST',p:'/rankings/getRankings',d:'Get individual rankings (flat list or tiers)',
   body:JSON.stringify({game:'AllGames',gameType:'overall',week:NOW,geographicLevel:'world',geographicValue:'',cumulative:true,flatten:true,forceGenerate:false,mergeResults:true,limit:10},null,2)},
  {g:'Rankings',m:'POST',p:'/rankings/getRankingsForPlayer',d:'Get rankings subtier for a specific player',
   body:JSON.stringify({game:'AllGames',gameType:'overall',playerId:'',tierFilter:'diamond',week:NOW,geographicLevel:'world'},null,2)},
  {g:'Rankings',m:'POST',p:'/rankings/getRankingsSummaryForPlayer',d:'Get rankings summary across all geographic levels',
   body:JSON.stringify({game:'AllGames',gameType:'overall',playerId:'',week:NOW},null,2)},
  {g:'Rankings',m:'GET',p:'/rankings/getLeaguesData',d:'Returns leagues data in JSON or RSS format',
   q:[{n:'dataView',l:'dataView',o:['all','teams'],def:'all'},{n:'format',l:'format',o:['json','rss'],def:'json'},{n:'game',l:'game',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagues/{game}',d:'Get leagues for a specific game',
   pp:[{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueTeams',d:'Get league teams',
   q:[{n:'game',l:'game',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueTeams/{game}',d:'Get league teams for a specific game',
   pp:[{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueTeams/{game}/{league}',d:'Get league teams for a game + league',
   pp:[{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeaguePreferences/{playerId}',d:'Get league preferences for a player',
   pp:[{n:'playerId',l:'playerId *',t:'text'}],q:[{n:'game',l:'game',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeaguePreferences/{playerId}/{game}',d:'Get league preferences for player + game',
   pp:[{n:'playerId',l:'playerId *',t:'text'},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']}],q:[{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeaguePreferences/{playerId}/{game}/{league}',d:'Get specific league preference',
   pp:[{n:'playerId',l:'playerId *',t:'text'},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'}]},
  {g:'Rankings',m:'POST',p:'/rankings/setLeaguePreference/{playerId}/{game}/{league}/{preference}',d:'Set league preference for a player',
   pp:[{n:'playerId',l:'playerId *',t:'text'},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'},{n:'preference',l:'preference *',t:'text'}],
   body:JSON.stringify({playerId:'',game:'Sportsbook',league:'Sports',preference:''},null,2)},
  {g:'Rankings',m:'POST',p:'/rankings/deleteLeaguePreference/{playerId}/{game}/{league}',d:'Delete league preference',
   pp:[{n:'playerId',l:'playerId *',t:'text'},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'}],
   body:JSON.stringify({playerId:'',game:'Sportsbook',league:'Sports'},null,2)},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueIndividualRankings/{playerId}/{game}/{league}/{week}',d:'Get league individual rankings for a player',
   pp:[{n:'playerId',l:'playerId *',t:'text'},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'},{n:'week',l:'week * (date)',t:'date',def:DATE}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueIndividualRankingsForTeam/{game}/{league}/{teamName}/{week}',d:'Get league individual rankings for a team',
   pp:[{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'},{n:'teamName',l:'teamName *',t:'text'},{n:'week',l:'week * (date)',t:'date',def:DATE}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueScores',d:'Get league scores',
   q:[{n:'week',l:'week (date)',t:'date'},{n:'game',l:'game',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueScores/{week}',d:'Get league scores for a specific week',
   pp:[{n:'week',l:'week * (date)',t:'date',def:DATE}],q:[{n:'game',l:'game',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueScores/{week}/{game}',d:'Get league scores for a week + game',
   pp:[{n:'week',l:'week * (date)',t:'date',def:DATE},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']}],q:[{n:'league',l:'league',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getLeagueScores/{week}/{game}/{league}',d:'Get league scores for week + game + league',
   pp:[{n:'week',l:'week * (date)',t:'date',def:DATE},{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'league',l:'league *',t:'text'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getWeeklyResultsForTeam/{game}/{leagueName}/{teamName}',d:'Get weekly results for a team',
   pp:[{n:'game',l:'game *',o:['Sportsbook','Avantage Baccarat']},{n:'leagueName',l:'leagueName *',t:'text'},{n:'teamName',l:'teamName *',t:'text'}]},
  {g:'Rankings',m:'POST',p:'/rankings/createChallenge',d:'Create a challenge',
   body:JSON.stringify({authenticatedUser:'',authenticatedEmail:'',authenticatedFirstName:'',authenticatedLastName:'',name:'',scheduledStartDateTime:NOW,durationHours:168,minNumberOfHands:100,maxNumberOfHands:0,challengedPlayers:[]},null,2)},
  {g:'Rankings',m:'GET',p:'/rankings/getChallengesForPlayer',d:'Get challenges for a player',
   q:[{n:'authenticatedUser',l:'authenticatedUser *',t:'text'},{n:'statusFilter',l:'statusFilter',o:['active','completed','archived'],def:'active'},{n:'lastDaysFilter',l:'lastDaysFilter (int)',t:'number'}]},
  {g:'Rankings',m:'GET',p:'/rankings/getChallengeDetails/{challengeId}',d:'Get challenge details',
   pp:[{n:'challengeId',l:'challengeId *',t:'text'}]},
  {g:'Rankings',m:'POST',p:'/rankings/joinChallenges',d:'Join multiple challenges',
   body:JSON.stringify({authenticatedUser:'',challenges:[{challengeId:'',teamName:''}]},null,2)},

  /* ── PLAYERS ── */
  {g:'Players',m:'GET',  p:'/account/players/me',d:'Get current player profile'},
  {g:'Players',m:'GET',  p:'/account/players/token',d:'Get player token'},
  {g:'Players',m:'GET',  p:'/account/players/onboarding/check/postalcode',d:'Check onboarding postal code'},
  {g:'Players',m:'PATCH',p:'/account/players/signup-completion',d:'Set UTM tags and update emailConfirmed',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/onboarding/service-notification',d:'Set user service notification',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/onboarding/contest-winnings-notification',d:'Create player contest & winnings notification',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/onboarding/cashprize-leaderboard',d:'Create player cash prize & leaderboard',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/onboarding/landing-info',d:'Update player name, address and phone',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/fullname',d:'Change user first and last name',body:JSON.stringify({firstName:'',lastName:''},null,2)},
  {g:'Players',m:'PATCH',p:'/account/players/address',d:'Change user address',body:JSON.stringify({postCode:'',countryIso:'US'},null,2)},
  {g:'Players',m:'PATCH',p:'/account/players/avatar',d:'Update provided user avatar',body:'{}'},
  {g:'Players',m:'DELETE',p:'/account/players/avatar',d:'Delete player avatar'},
  {g:'Players',m:'PATCH',p:'/account/players/avatar/upload',d:'Upload and update user avatar',body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/language',d:'Change preferred language',body:JSON.stringify({language:'en'},null,2)},
  {g:'Players',m:'PATCH',p:'/account/players/missing-email',d:'Add email when not set (OAuth)',body:JSON.stringify({email:''},null,2)},
  {g:'Players',m:'GET',  p:'/account/players/avatar/provided',d:'Get provided avatar options'},
  {g:'Players',m:'POST', p:'/account/players/forgot-password',d:'Check if email exists (forgot password)',body:JSON.stringify({email:''},null,2)},
  {g:'Players',m:'PATCH',p:'/account/players/geo-location',d:'Update geo-location',
   eh:[{n:'username',l:'username * (header)',t:'text'}],body:'{}'},
  {g:'Players',m:'PATCH',p:'/account/players/claim-account',d:'Guest users claim account',
   eh:[{n:'username',l:'username * (header)',t:'text'}],
   body:JSON.stringify({email:'',isMarketingNotificationsEmailActive:false,firstName:'',lastName:'',postCode:'',countryIso:'US',password:''},null,2)},
  {g:'Players',m:'POST', p:'/account/players/validate-docs',d:'Validate player documents',
   eh:[{n:'username',l:'username * (header)',t:'text'}],body:'{}'},
  {g:'Players',m:'POST', p:'/account/players/webhook',d:'Process complycube webhook',body:'{}'},
  {g:'Players',m:'GET',  p:'/account/players/{playerId}/doc/verified',d:'Check if player document is verified',
   pp:[{n:'playerId',l:'playerId *',t:'text'}]},
  {g:'Players',m:'PATCH',p:'/account/players/acknowledgment',d:'Player accepted terms and conditions',body:'{}'},
  {g:'Players',m:'POST', p:'/account/players/saveExperiment',d:'Save experiment data',body:'{}'},
  {g:'Players',m:'POST', p:'/account/players/update-vip-status',d:'Update VIP status',body:'{}'},

  /* ── OAUTH ── */
  {g:'OAuth',m:'POST',p:'/account/oauth/start-session',d:'Start session for OAuth player (Telegram, Google, Facebook…)',
   bf:[
     {n:'oauthLoginType',l:'Login Type',o:['InfluencerHq','Telegram','Google','Facebook'],def:'InfluencerHq'},
     /* InfluencerHq fields */
     {n:'ihq_id',         l:'ID (UUID)',    t:'text',  grp:'ihq'},
     {n:'ihq_firstName',  l:'First Name',  t:'text',  grp:'ihq'},
     {n:'ihq_lastName',   l:'Last Name',   t:'text',  grp:'ihq'},
     {n:'ihq_email',      l:'Email',       t:'email', grp:'ihq'},
     /* Telegram fields */
     {n:'tg_first_name',  l:'First Name',  t:'text',   grp:'tg'},
     {n:'tg_last_name',   l:'Last Name',   t:'text',   grp:'tg'},
     {n:'tg_id',          l:'User ID (int)', t:'number', grp:'tg', def:'0'},
     {n:'tg_hash',        l:'Hash',        t:'text',   grp:'tg'},
     {n:'tg_auth_date',   l:'Auth Date (unix)', t:'number', grp:'tg', def:'0'},
     {n:'tg_photo_url',   l:'Photo URL',   t:'text',   grp:'tg'},
   ],
   body:''},

  /* ── REFERRALS ── */
  {g:'Referrals',m:'POST',p:'/referral/user',d:'Create a referral user',
   body:JSON.stringify({userId:'',firstName:'',lastName:'',email:''},null,2)},

  /* ── STATUS ── */
  {g:'Status',m:'GET',p:'/referral/health',d:'Referral service health check'},
];

/* helpers */
function mc(m){return 'bg-'+m.toLowerCase();}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function buildPath(tpl,vals){return tpl.replace(/\{(\w+)\}/g,(_,k)=>encodeURIComponent(vals[k]||'{'+k+'}'));}
function highlight(json){
  if(typeof json!=='string') json=JSON.stringify(json,null,2);
  return json.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,m=>{
      let c='jn';
      if(/^"/.test(m)){c=/:$/.test(m)?'jk':'js';}
      else if(/true|false/.test(m)){c='jb';}
      else if(/null/.test(m)){c='jz';}
      return '<span class="'+c+'">'+m+'</span>';
    });
}

/* sidebar */
const nav=document.getElementById('iat-nav');
function renderNav(f){
  f=(f||'').toLowerCase(); nav.innerHTML='';
  const groups={};
  EPS.forEach((ep,i)=>{
    if(f&&!(ep.p.toLowerCase().includes(f)||ep.m.toLowerCase().includes(f)||ep.g.toLowerCase().includes(f))) return;
    (groups[ep.g]=groups[ep.g]||[]).push({ep,i});
  });
  Object.keys(groups).forEach(g=>{
    const lbl=document.createElement('div');lbl.className='iat-group-label';lbl.textContent=g;nav.appendChild(lbl);
    groups[g].forEach(({ep,i})=>{
      const item=document.createElement('div');item.className='iat-nav-item';item.dataset.idx=i;
      item.innerHTML='<span class="iat-mb '+mc(ep.m)+'">'+ep.m+'</span><span>'+ep.p.replace(/\{[^}]+\}/g,'<em style="opacity:.45">…</em>')+'</span>';
      item.addEventListener('click',()=>{selectEp(i);setActive(item);});
      nav.appendChild(item);
    });
  });
}
renderNav();
function setActive(el){document.querySelectorAll('.iat-nav-item').forEach(n=>n.classList.remove('active'));el.classList.add('active');}
document.getElementById('iat-search').addEventListener('input',e=>renderNav(e.target.value));

/* detail panel */
let curEp=null;
const detail=document.getElementById('iat-detail');
function selectEp(idx){
  curEp=EPS[idx];
  let h='<div class="ep-header"><span class="iat-mb '+mc(curEp.m)+'" style="font-size:.8rem;padding:4px 10px">'+curEp.m+'</span><span class="ep-path">'+esc(curEp.p)+'</span></div>'
     +'<p class="ep-desc">'+esc(curEp.d||'')+'</p>'
     +'<div class="auth-row">��� Auth: <strong>milos_testing</strong> &nbsp;|&nbsp; Base: <code style="color:var(--gold-dim);font-size:.78rem">'+BASE+'</code></div>';

  if(curEp.pp&&curEp.pp.length){
    h+='<div class="sec-title">Path Parameters</div><div class="fields">';
    curEp.pp.forEach(p=>{
      h+='<div class="field"><label>'+esc(p.l)+'</label>';
      h+=p.o?'<select data-pp="'+p.n+'">'+p.o.map(o=>'<option>'+esc(o)+'</option>').join('')+'</select>'
             :'<input type="'+(p.t||'text')+'" data-pp="'+p.n+'" placeholder="'+esc(p.n)+'" value="'+esc(p.def||'')+'">';
      h+='</div>';
    });h+='</div>';
  }
  if(curEp.q&&curEp.q.length){
    h+='<div class="sec-title">Query Parameters</div><div class="fields">';
    curEp.q.forEach(p=>{
      h+='<div class="field"><label>'+esc(p.l)+'</label>';
      h+=p.o?'<select data-qp="'+p.n+'"><option value=""></option>'+p.o.map(o=>'<option'+(o===p.def?' selected':'')+'>'+esc(o)+'</option>').join('')+'</select>'
             :'<input type="'+(p.t||'text')+'" data-qp="'+p.n+'" placeholder="'+esc(p.n)+'" value="'+esc(p.def||'')+'">';
      h+='</div>';
    });h+='</div>';
  }
  if(curEp.eh&&curEp.eh.length){
    h+='<div class="sec-title">Extra Headers</div><div class="fields">';
    curEp.eh.forEach(p=>{h+='<div class="field"><label>'+esc(p.l)+'</label><input type="'+(p.t||'text')+'" data-hh="'+p.n+'" placeholder="'+esc(p.n)+'" value="'+(p.def||'')+'"></div>';});
    h+='</div>';
  }
  if(curEp.bf&&curEp.bf.length){
    h+='<div class="sec-title">OAuth Payload Fields</div><div class="fields">';
    curEp.bf.forEach(p=>{
      const grpAttr=p.grp?' data-ogrp="'+p.grp+'"':'';
      const hide=p.grp&&p.grp!=='ihq'?' style="display:none"':'';
      h+='<div class="field"'+grpAttr+hide+'><label>'+esc(p.l)+'</label>';
      h+=p.o?'<select data-bf="'+p.n+'">'+p.o.map(o=>'<option'+(o===p.def?' selected':'')+'>'+esc(o)+'</option>').join('')+'</select>'
             :'<input type="'+(p.t||'text')+'" data-bf="'+p.n+'" placeholder="'+esc(p.n)+'" value="'+esc(p.def||'')+'">';
      h+='</div>';
    });
    h+='</div>';
  }
  if(curEp.body!==undefined){
    h+='<div class="sec-title">Request Body <small style="font-weight:400;opacity:.7">(JSON — auto-filled from fields above)</small></div>'
      +'<div class="field"><textarea id="iat-body-ta" spellcheck="false">'+esc(curEp.body||'')+'</textarea></div>';
  }
  h+='<button class="send-btn" id="send-btn">&#9654; Send Request</button>';
  detail.innerHTML=h;
  document.getElementById('send-btn').addEventListener('click',fire);
  if(curEp.bf&&curEp.bf.length){
    function buildOAuthBody(){
      const v=n=>document.querySelector('[data-bf="'+n+'"]')?.value||'';
      const lt=v('oauthLoginType')||'InfluencerHq';
      let payload;
      if(lt==='InfluencerHq'){
        payload={id:v('ihq_id'),firstName:v('ihq_firstName'),lastName:v('ihq_lastName'),email:v('ihq_email')};
      } else {
        payload={auth_date:parseInt(v('tg_auth_date')||'0',10),first_name:v('tg_first_name'),hash:v('tg_hash'),id:parseInt(v('tg_id')||'0',10),last_name:v('tg_last_name'),photo_url:v('tg_photo_url')};
      }
      const ta=document.getElementById('iat-body-ta');
      if(ta) ta.value=JSON.stringify({oauthLoginType:lt,payload},null,2);
    }
    function updateOAuthGroups(){
      const lt=document.querySelector('[data-bf="oauthLoginType"]')?.value||'InfluencerHq';
      const grp=lt==='InfluencerHq'?'ihq':'tg';
      document.querySelectorAll('[data-ogrp]').forEach(el=>{
        el.style.display=el.dataset.ogrp===grp?'':'none';
      });
      buildOAuthBody();
    }
    buildOAuthBody();
    document.querySelectorAll('[data-bf]').forEach(el=>el.addEventListener('input',()=>{
      if(el.dataset.bf==='oauthLoginType') updateOAuthGroups(); else buildOAuthBody();
    }));
  }
}

/* fire request */
function fire(){
  if(!curEp) return;
  const ppV={};document.querySelectorAll('[data-pp]').forEach(el=>ppV[el.dataset.pp]=el.value);
  const path=buildPath(curEp.p,ppV);
  const qp=[];document.querySelectorAll('[data-qp]').forEach(el=>{if(el.value)qp.push(encodeURIComponent(el.dataset.qp)+'='+encodeURIComponent(el.value));});
  const fullPath=path+(qp.length?'?'+qp.join('&'):'');
  const eh={};document.querySelectorAll('[data-hh]').forEach(el=>{if(el.value)eh[el.dataset.hh]=el.value;});
  const bodyEl=document.getElementById('iat-body-ta');
  const body=bodyEl?bodyEl.value.trim():null;
  if(body&&body!=='{}'){try{JSON.parse(body);}catch(e){showResp({error:true,message:'Invalid JSON: '+e.message});return;}}
  const btn=document.getElementById('send-btn');
  btn.disabled=true;btn.textContent='Sending…';
  showLoading(curEp.m,BASE+fullPath);
  const fd=new FormData();
  fd.append('action','ihq_api_proxy');
  fd.append('nonce',document.getElementById('iat-nonce').value);
  fd.append('endpoint',fullPath);
  fd.append('method',curEp.m);
  if(body) fd.append('body',body);
  fd.append('extra_headers',JSON.stringify(eh));
  fetch(document.getElementById('iat-ajax-url').value,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{btn.disabled=false;btn.textContent='▶ Send Request';data.success?showResp(data.data):showResp({error:true,message:data.data?JSON.stringify(data.data):'Unknown error',url:BASE+fullPath,method:curEp.m});})
    .catch(e=>{btn.disabled=false;btn.textContent='▶ Send Request';showResp({error:true,message:e.message});});
}

/* response */
const rPanel=document.getElementById('iat-response');
const rBadge=document.getElementById('iat-status-badge');
const rUrl=document.getElementById('iat-response-url');
const bPre=document.getElementById('iat-body-pre');
const hPre=document.getElementById('iat-headers-pre');
const rqPre=document.getElementById('iat-request-pre');

function showLoading(m,url){
  rPanel.style.display='';rBadge.className='iat-badge pend';rBadge.textContent='…';rUrl.textContent=m+' '+url;
  bPre.innerHTML='<code style="color:var(--gold)">⏳ Waiting for response…</code>';
  hPre.innerHTML='<code></code>';rqPre.innerHTML='<code></code>';activateTab('body');
}
function showResp(d){
  rPanel.style.display='';
  if(d.error){rBadge.className='iat-badge err';rBadge.textContent='ERROR';rUrl.textContent=d.url||'';bPre.innerHTML='<code style="color:var(--red)">'+esc(d.message)+'</code>';return;}
  const ok=d.status>=200&&d.status<300;
  rBadge.className='iat-badge '+(ok?'ok':'err');rBadge.textContent=d.status+' '+d.status_message;rUrl.textContent=d.method+' '+d.url;
  try{bPre.innerHTML='<code>'+highlight(JSON.stringify(JSON.parse(d.body),null,2))+'</code>';}catch(e){bPre.innerHTML='<code>'+esc(d.body)+'</code>';}
  hPre.innerHTML='<code>'+highlight(JSON.stringify(d.headers,null,2))+'</code>';
  rqPre.innerHTML='<code>'+highlight(JSON.stringify({method:d.method,url:d.url,headers:{Authorization:'milos_testing','Content-Type':'application/json'}},null,2))+'</code>';
}

/* tabs */
function activateTab(n){
  document.querySelectorAll('.iat-tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===n));
  bPre.style.display=n==='body'?'block':'none';
  hPre.style.display=n==='headers'?'block':'none';
  rqPre.style.display=n==='request'?'block':'none';
}
document.querySelectorAll('.iat-tab').forEach(t=>t.addEventListener('click',()=>activateTab(t.dataset.tab)));

/* copy */
document.getElementById('iat-copy-btn').addEventListener('click',()=>{
  const el=bPre.querySelector('code');
  navigator.clipboard.writeText(el?el.innerText:'').then(()=>{const b=document.getElementById('iat-copy-btn');b.textContent='✓ Copied';setTimeout(()=>b.textContent='⎘ Copy',2000);});
});

/* collapse */
let col=false;
document.getElementById('iat-collapse-btn').addEventListener('click',()=>{
  col=!col;document.getElementById('iat-response-body-wrap').style.display=col?'none':'';
  document.getElementById('iat-collapse-btn').textContent=col?'▸':'▾';
});
})();
</script>

<?php get_footer(); ?>
