<?php
session_start();
require_once 'db.php';

$flash_type = $_SESSION['flash_type'] ?? '';
$flash_msg  = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_type'], $_SESSION['flash_msg']);

$loggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';

$games = [
    'arcade' => [
        ['id'=>'snake',    'name'=>'Snake',         'desc'=>'Eat food, grow longer, don\'t hit the walls!',    'icon'=>'🐍', 'difficulty'=>'Easy'],
        ['id'=>'breakout', 'name'=>'Breakout',       'desc'=>'Break all bricks with a bouncing ball.',          'icon'=>'🧱', 'difficulty'=>'Medium'],
        ['id'=>'flappy',   'name'=>'Flappy Bird',    'desc'=>'Tap to fly through pipes without crashing.',      'icon'=>'🐦', 'difficulty'=>'Hard'],
        ['id'=>'asteroids','name'=>'Space Shooter',  'desc'=>'Blast asteroids before they destroy you.',        'icon'=>'🚀', 'difficulty'=>'Medium'],
    ],
    'puzzle' => [
        ['id'=>'2048',     'name'=>'2048',           'desc'=>'Slide tiles to reach the 2048 tile.',             'icon'=>'🔢', 'difficulty'=>'Medium'],
        ['id'=>'memory',   'name'=>'Memory Cards',   'desc'=>'Match all pairs with the fewest flips.',          'icon'=>'🃏', 'difficulty'=>'Easy'],
        ['id'=>'minesweeper','name'=>'Minesweeper',  'desc'=>'Reveal cells without hitting any mines.',         'icon'=>'💣', 'difficulty'=>'Hard'],
        ['id'=>'sliding',  'name'=>'Sliding Puzzle', 'desc'=>'Arrange numbered tiles in order.',                'icon'=>'🔲', 'difficulty'=>'Medium'],
    ],
    'strategy' => [
        ['id'=>'tictactoe','name'=>'Tic Tac Toe',    'desc'=>'Outsmart the AI in the classic X & O game.',      'icon'=>'❌', 'difficulty'=>'Easy'],
        ['id'=>'connect4', 'name'=>'Connect Four',   'desc'=>'Drop discs to get four in a row.',                'icon'=>'🟡', 'difficulty'=>'Medium'],
        ['id'=>'chess',    'name'=>'Chess Lite',      'desc'=>'Play chess against a basic AI opponent.',         'icon'=>'♟️', 'difficulty'=>'Hard'],
    ],
    'word' => [
        ['id'=>'wordle',   'name'=>'Wordle',          'desc'=>'Guess the hidden 5-letter word in 6 tries.',     'icon'=>'📝', 'difficulty'=>'Medium'],
        ['id'=>'hangman',  'name'=>'Hangman',         'desc'=>'Guess letters to reveal the hidden word.',       'icon'=>'🎭', 'difficulty'=>'Easy'],
        ['id'=>'typeracer','name'=>'Type Racer',      'desc'=>'Type as fast as you can to beat the clock.',     'icon'=>'⌨️', 'difficulty'=>'Medium'],
    ],
    'sports' => [
        ['id'=>'pong',     'name'=>'Pong',            'desc'=>'Classic table tennis arcade game.',              'icon'=>'🏓', 'difficulty'=>'Easy'],
        ['id'=>'basketball','name'=>'Basket Ball',    'desc'=>'Shoot hoops and score as many as you can.',      'icon'=>'🏀', 'difficulty'=>'Medium'],
        ['id'=>'tetris',   'name'=>'Tetris',          'desc'=>'Stack falling blocks to clear lines.',           'icon'=>'🟦', 'difficulty'=>'Medium'],
    ],
];

$categoryLabels = [
    'arcade'   => ['label'=>'Arcade',   'icon'=>'🕹️'],
    'puzzle'   => ['label'=>'Puzzle',   'icon'=>'🧩'],
    'strategy' => ['label'=>'Strategy', 'icon'=>'♟️'],
    'word'     => ['label'=>'Word',     'icon'=>'📖'],
    'sports'   => ['label'=>'Sports',   'icon'=>'🏆'],
];

$activeCategory = $_GET['cat'] ?? 'all';
$search = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeonPlay — Mini Games Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg:        #050510;
    --surface:   #0d0d2b;
    --card:      #0f1035;
    --border:    #1a1a4e;
    --cyan:      #00f5ff;
    --pink:      #ff0080;
    --purple:    #9b59b6;
    --green:     #00ff88;
    --yellow:    #ffd700;
    --text:      #e8e8ff;
    --muted:     #7878aa;
    --glow-c:    0 0 20px rgba(0,245,255,.4);
    --glow-p:    0 0 20px rgba(255,0,128,.4);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    font-family:'Exo 2',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* ── Canvas BG ── */
#stars{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}

/* ── Layout ── */
.wrap{position:relative;z-index:1;max-width:1400px;margin:0 auto;padding:0 24px}

/* ── NAV ── */
nav{
    position:sticky;top:0;z-index:100;
    background:rgba(5,5,16,.85);
    backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
    padding:0 24px;
}
.nav-inner{
    max-width:1400px;margin:0 auto;
    display:flex;align-items:center;gap:16px;height:64px;
}
.logo{
    font-family:'Orbitron',sans-serif;
    font-size:1.4rem;font-weight:900;
    background:linear-gradient(90deg,var(--cyan),var(--pink));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    text-decoration:none;white-space:nowrap;
}
.logo span{color:var(--pink)}
.nav-search{
    flex:1;max-width:320px;
    background:var(--surface);border:1px solid var(--border);
    border-radius:999px;padding:8px 16px;
    color:var(--text);font-family:'Exo 2',sans-serif;font-size:.9rem;
    outline:none;transition:.2s;
}
.nav-search:focus{border-color:var(--cyan);box-shadow:0 0 12px rgba(0,245,255,.2)}
.nav-spacer{flex:1}
.nav-actions{display:flex;align-items:center;gap:10px}
.btn{
    font-family:'Orbitron',sans-serif;font-size:.75rem;font-weight:600;
    padding:8px 18px;border-radius:6px;cursor:pointer;
    text-decoration:none;border:none;transition:.2s;letter-spacing:.05em;
}
.btn-ghost{
    background:transparent;border:1px solid var(--cyan);color:var(--cyan);
}
.btn-ghost:hover{background:var(--cyan);color:var(--bg)}
.btn-primary{
    background:linear-gradient(135deg,var(--cyan),var(--purple));
    color:#fff;border:none;
}
.btn-primary:hover{transform:translateY(-1px);box-shadow:var(--glow-c)}
.btn-danger{background:var(--pink);color:#fff}
.btn-danger:hover{opacity:.85}
.user-badge{
    display:flex;align-items:center;gap:8px;
    font-size:.85rem;color:var(--cyan);
}
.avatar-sm{
    width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,var(--cyan),var(--purple));
    display:flex;align-items:center;justify-content:center;
    font-family:'Orbitron',sans-serif;font-size:.8rem;font-weight:700;color:#fff;
}

/* ── HERO ── */
.hero{
    text-align:center;padding:80px 24px 48px;
}
.hero-tag{
    display:inline-block;
    background:rgba(0,245,255,.1);border:1px solid rgba(0,245,255,.3);
    color:var(--cyan);border-radius:999px;
    padding:4px 16px;font-size:.8rem;letter-spacing:.15em;
    font-family:'Orbitron',sans-serif;margin-bottom:24px;
}
.hero h1{
    font-family:'Orbitron',sans-serif;
    font-size:clamp(2.2rem,6vw,5rem);font-weight:900;
    line-height:1.1;margin-bottom:16px;
}
.hero h1 .c{color:var(--cyan)}
.hero h1 .p{color:var(--pink)}
.hero p{
    font-size:1.1rem;color:var(--muted);max-width:500px;margin:0 auto 32px;
}
.hero-stats{
    display:flex;justify-content:center;gap:48px;flex-wrap:wrap;
}
.stat{text-align:center}
.stat-val{
    font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:800;
    background:linear-gradient(135deg,var(--cyan),var(--green));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.stat-lbl{font-size:.8rem;color:var(--muted);letter-spacing:.1em}

/* ── CATEGORY TABS ── */
.cat-bar{
    display:flex;align-items:center;gap:10px;
    padding:24px 0 8px;flex-wrap:wrap;
}
.cat-btn{
    font-family:'Orbitron',sans-serif;font-size:.72rem;font-weight:600;
    padding:8px 18px;border-radius:999px;cursor:pointer;
    border:1px solid var(--border);background:var(--surface);color:var(--muted);
    text-decoration:none;transition:.2s;letter-spacing:.08em;
}
.cat-btn:hover,.cat-btn.active{
    border-color:var(--cyan);color:var(--cyan);
    background:rgba(0,245,255,.08);
}

/* ── SECTION HEADER ── */
.section-title{
    font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:700;
    color:var(--cyan);letter-spacing:.12em;
    display:flex;align-items:center;gap:12px;margin-bottom:20px;
}
.section-title::after{
    content:'';flex:1;height:1px;
    background:linear-gradient(90deg,var(--border),transparent);
}

/* ── GAME GRID ── */
.game-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
    gap:20px;margin-bottom:56px;
}
.game-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:16px;overflow:hidden;
    transition:.3s;cursor:pointer;
    text-decoration:none;color:inherit;
    position:relative;
    display:flex;flex-direction:column;
}
.game-card:hover{
    transform:translateY(-4px);
    border-color:var(--cyan);
    box-shadow:0 12px 40px rgba(0,245,255,.15);
}
.game-card-top{
    height:120px;
    display:flex;align-items:center;justify-content:center;
    font-size:3.5rem;
    position:relative;overflow:hidden;
}
.game-card-top::before{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(0,245,255,.05),rgba(155,89,182,.05));
}
.game-card-top .glow-orb{
    position:absolute;width:80px;height:80px;border-radius:50%;
    background:radial-gradient(circle,rgba(0,245,255,.15),transparent 70%);
    top:50%;left:50%;transform:translate(-50%,-50%);
}
.game-card-body{padding:16px}
.game-card-name{
    font-family:'Orbitron',sans-serif;font-size:.9rem;font-weight:700;
    margin-bottom:6px;color:#fff;
}
.game-card-desc{
    font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:12px;
}
.game-card-footer{
    display:flex;align-items:center;justify-content:space-between;
    margin-top:auto;
}
.difficulty{
    font-size:.7rem;font-family:'Orbitron',sans-serif;
    padding:3px 10px;border-radius:999px;
}
.diff-Easy  {background:rgba(0,255,136,.1);color:var(--green);border:1px solid rgba(0,255,136,.3)}
.diff-Medium{background:rgba(255,215,0,.1);color:var(--yellow);border:1px solid rgba(255,215,0,.3)}
.diff-Hard  {background:rgba(255,0,128,.1);color:var(--pink);border:1px solid rgba(255,0,128,.3)}
.play-arrow{
    width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,var(--cyan),var(--purple));
    display:flex;align-items:center;justify-content:center;
    font-size:1rem;color:#fff;
    transition:.2s;
}
.game-card:hover .play-arrow{transform:scale(1.15);box-shadow:var(--glow-c)}

/* ── FLASH ── */
.flash{
    margin:16px 0;padding:12px 20px;border-radius:8px;
    font-size:.9rem;display:flex;align-items:center;gap:10px;
}
.flash-success{background:rgba(0,255,136,.1);border:1px solid rgba(0,255,136,.3);color:var(--green)}
.flash-error  {background:rgba(255,0,128,.1);border:1px solid rgba(255,0,128,.3);color:var(--pink)}

/* ── MODAL ── */
.modal-overlay{
    display:none;position:fixed;inset:0;z-index:500;
    background:rgba(0,0,0,.75);backdrop-filter:blur(8px);
    align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex}
.modal{
    background:var(--surface);border:1px solid var(--border);
    border-radius:20px;padding:40px;width:100%;max-width:440px;
    position:relative;animation:modalIn .3s ease;
}
@keyframes modalIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-close{
    position:absolute;top:16px;right:16px;
    background:none;border:none;color:var(--muted);
    font-size:1.4rem;cursor:pointer;
}
.modal-close:hover{color:var(--pink)}
.modal h2{
    font-family:'Orbitron',sans-serif;font-size:1.3rem;font-weight:700;
    margin-bottom:8px;color:var(--cyan);
}
.modal p.sub{font-size:.85rem;color:var(--muted);margin-bottom:28px}
.tab-switch{
    display:flex;background:var(--card);border-radius:8px;
    padding:4px;margin-bottom:24px;
}
.tab-switch button{
    flex:1;padding:8px;border:none;background:none;
    color:var(--muted);font-family:'Exo 2',sans-serif;font-size:.9rem;
    cursor:pointer;border-radius:6px;transition:.2s;
}
.tab-switch button.active{background:var(--cyan);color:var(--bg);font-weight:600}
.field{margin-bottom:16px}
.field label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:6px;letter-spacing:.05em}
.field input{
    width:100%;background:var(--card);border:1px solid var(--border);
    border-radius:8px;padding:10px 14px;color:var(--text);
    font-family:'Exo 2',sans-serif;font-size:.95rem;outline:none;transition:.2s;
}
.field input:focus{border-color:var(--cyan);box-shadow:0 0 12px rgba(0,245,255,.15)}
.btn-full{width:100%;padding:12px;font-size:.85rem;border-radius:8px;margin-top:8px}
.modal-footer{text-align:center;margin-top:16px;font-size:.82rem;color:var(--muted)}
.modal-footer a{color:var(--cyan);cursor:pointer;text-decoration:none}

/* ── FOOTER ── */
footer{
    border-top:1px solid var(--border);padding:32px 24px;text-align:center;
    color:var(--muted);font-size:.82rem;
}
footer strong{color:var(--cyan);font-family:'Orbitron',sans-serif}

/* ── RESPONSIVE ── */
@media(max-width:600px){
    .hero{padding:48px 16px 32px}
    .hero-stats{gap:24px}
    .nav-search{display:none}
}
</style>
</head>
<body>

<canvas id="stars"></canvas>

<!-- NAV -->
<nav>
  <div class="nav-inner">
    <a href="index.php" class="logo">NEON<span>PLAY</span></a>
    <form method="GET" action="index.php" style="flex:1;max-width:320px">
      <input type="text" name="q" class="nav-search" placeholder="🔍  Search games…" value="<?=htmlspecialchars($search)?>">
    </form>
    <div class="nav-spacer"></div>
    <div class="nav-actions">
      <?php if ($loggedIn): ?>
        <a href="profile.php" class="user-badge" style="text-decoration:none">
          <div class="avatar-sm"><?=strtoupper(substr($username,0,1))?></div>
          <span><?=htmlspecialchars($username)?></span>
        </a>
        <form method="POST" action="auth.php">
          <input type="hidden" name="action" value="logout">
          <button class="btn btn-danger">Logout</button>
        </form>
      <?php else: ?>
        <button class="btn btn-ghost" onclick="openModal('login')">Login</button>
        <button class="btn btn-primary" onclick="openModal('register')">Sign Up</button>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="wrap">
  <?php if ($flash_msg): ?>
    <div class="flash flash-<?=$flash_type?>"><?=$flash_msg?></div>
  <?php endif; ?>

  <div class="hero">
    <div class="hero-tag">🎮 THE ULTIMATE MINI GAMES HUB</div>
    <h1>Play <span class="c">Epic</span> Mini<br>Games <span class="p">Free</span></h1>
    <p>17 handcrafted mini-games across 5 categories. No downloads needed — just play!</p>
    <div class="hero-stats">
      <div class="stat"><div class="stat-val">17</div><div class="stat-lbl">GAMES</div></div>
      <div class="stat"><div class="stat-val">5</div><div class="stat-lbl">CATEGORIES</div></div>
      <div class="stat"><div class="stat-val">∞</div><div class="stat-lbl">FUN</div></div>
    </div>
  </div>

  <!-- CATEGORY BAR -->
  <div class="cat-bar">
    <a href="index.php" class="cat-btn <?=$activeCategory==='all'?'active':''?>">🎮 All Games</a>
    <?php foreach ($categoryLabels as $key=>$c): ?>
      <a href="index.php?cat=<?=$key?>" class="cat-btn <?=$activeCategory===$key?'active':''?>"><?=$c['icon']?> <?=$c['label']?></a>
    <?php endforeach; ?>
  </div>

  <!-- GAME LISTINGS -->
  <?php
  $showAll = ($activeCategory === 'all' && !$search);
  foreach ($games as $cat => $list):
    // filter by category
    if ($activeCategory !== 'all' && $activeCategory !== $cat) continue;
    // filter by search
    $filtered = $search
      ? array_filter($list, fn($g) => stripos($g['name'],$search)!==false || stripos($g['desc'],$search)!==false)
      : $list;
    if (empty($filtered)) continue;
    $c = $categoryLabels[$cat];
  ?>
  <div class="section-title"><?=$c['icon']?> <?=$c['label']?></div>
  <div class="game-grid">
    <?php foreach ($filtered as $g): ?>
    <a href="game.php?id=<?=$g['id']?>" class="game-card">
      <div class="game-card-top">
        <div class="glow-orb"></div>
        <?=$g['icon']?>
      </div>
      <div class="game-card-body">
        <div class="game-card-name"><?=htmlspecialchars($g['name'])?></div>
        <div class="game-card-desc"><?=htmlspecialchars($g['desc'])?></div>
        <div class="game-card-footer">
          <span class="difficulty diff-<?=$g['difficulty']?>"><?=$g['difficulty']?></span>
          <div class="play-arrow">▶</div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php if ($search && !array_sum(array_map(fn($l)=>count(array_filter($l,fn($g)=>stripos($g['name'],$search)!==false||stripos($g['desc'],$search)!==false)),$games))): ?>
    <div style="text-align:center;padding:80px;color:var(--muted)">
      <div style="font-size:3rem">🔍</div>
      <div style="font-family:'Orbitron',sans-serif;margin-top:16px">No games found for "<?=htmlspecialchars($search)?>"</div>
    </div>
  <?php endif; ?>
</div>

<footer>
  <strong>NEONPLAY</strong> &mdash; Built with PHP &amp; JavaScript &bull; <?=date('Y')?><br>
  <span style="margin-top:6px;display:block">17 games · 5 categories · Unlimited fun</span>
</footer>

<!-- AUTH MODAL -->
<div class="modal-overlay" id="authModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h2 id="modalTitle">Welcome Back</h2>
    <p class="sub" id="modalSub">Login to track your scores and history</p>
    <div class="tab-switch">
      <button id="tabLogin" class="active" onclick="switchTab('login')">Login</button>
      <button id="tabRegister" onclick="switchTab('register')">Register</button>
    </div>

    <!-- LOGIN FORM -->
    <form method="POST" action="auth.php" id="loginForm">
      <input type="hidden" name="action" value="login">
      <div class="field"><label>EMAIL</label><input type="email" name="email" placeholder="your@email.com" required></div>
      <div class="field"><label>PASSWORD</label><input type="password" name="password" placeholder="••••••••" required></div>
      <button class="btn btn-primary btn-full">Login →</button>
    </form>

    <!-- REGISTER FORM -->
    <form method="POST" action="auth.php" id="registerForm" style="display:none">
      <input type="hidden" name="action" value="register">
      <div class="field"><label>USERNAME</label><input type="text" name="username" placeholder="GamerTag" required></div>
      <div class="field"><label>EMAIL</label><input type="email" name="email" placeholder="your@email.com" required></div>
      <div class="field"><label>PASSWORD</label><input type="password" name="password" placeholder="Min. 6 chars" required minlength="6"></div>
      <button class="btn btn-primary btn-full">Create Account →</button>
    </form>
  </div>
</div>

<script>
// ── Stars BG ──
const canvas = document.getElementById('stars');
const ctx = canvas.getContext('2d');
let stars = [];
function resize() {
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;
  stars = Array.from({length:120}, () => ({
    x: Math.random()*canvas.width,
    y: Math.random()*canvas.height,
    r: Math.random()*1.5+.3,
    speed: Math.random()*.4+.1,
    pulse: Math.random()*Math.PI*2
  }));
}
resize();
window.addEventListener('resize', resize);

function animateStars() {
  ctx.clearRect(0,0,canvas.width,canvas.height);
  const t = Date.now()/1000;
  stars.forEach(s => {
    s.pulse += .02;
    const alpha = (.4+Math.sin(s.pulse)*.3);
    ctx.beginPath();
    ctx.arc(s.x, s.y, s.r, 0, Math.PI*2);
    ctx.fillStyle = `rgba(0,245,255,${alpha})`;
    ctx.fill();
    s.y -= s.speed;
    if (s.y < 0) { s.y = canvas.height; s.x = Math.random()*canvas.width; }
  });
  requestAnimationFrame(animateStars);
}
animateStars();

// ── Modal ──
function openModal(tab) {
  document.getElementById('authModal').classList.add('open');
  switchTab(tab);
}
function closeModal() {
  document.getElementById('authModal').classList.remove('open');
}
function switchTab(tab) {
  const isLogin = tab === 'login';
  document.getElementById('tabLogin').classList.toggle('active', isLogin);
  document.getElementById('tabRegister').classList.toggle('active', !isLogin);
  document.getElementById('loginForm').style.display    = isLogin ? '' : 'none';
  document.getElementById('registerForm').style.display = isLogin ? 'none' : '';
  document.getElementById('modalTitle').textContent = isLogin ? 'Welcome Back' : 'Create Account';
  document.getElementById('modalSub').textContent   = isLogin ? 'Login to track your scores' : 'Join to save your game history';
}
document.getElementById('authModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

<?php if ($flash_type === 'error'): ?>
document.addEventListener('DOMContentLoaded', () => openModal('login'));
<?php endif; ?>
</script>
</body>
</html>
