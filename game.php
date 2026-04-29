<?php
session_start();
require_once 'db.php';

$gameId  = $_GET['id'] ?? '';
$loggedIn = isset($_SESSION['user_id']);
$userId   = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';

$allGames = [
    'snake'      => ['name'=>'Snake',         'icon'=>'🐍','cat'=>'Arcade',  'difficulty'=>'Easy',  'desc'=>'Control a growing snake to eat food. Avoid hitting walls or yourself. The longer you get, the harder it becomes!', 'controls'=>'Arrow Keys or WASD to move'],
    'breakout'   => ['name'=>'Breakout',       'icon'=>'🧱','cat'=>'Arcade',  'difficulty'=>'Medium','desc'=>'Use a paddle to bounce a ball and break all the bricks. Don\'t let the ball fall off the bottom!',                 'controls'=>'Mouse or Arrow Keys to move paddle'],
    'flappy'     => ['name'=>'Flappy Bird',    'icon'=>'🐦','cat'=>'Arcade',  'difficulty'=>'Hard',  'desc'=>'Tap or press Space to make the bird flap its wings and fly through pipes. How far can you go?',                  'controls'=>'Space or Click to flap'],
    'asteroids'  => ['name'=>'Space Shooter',  'icon'=>'🚀','cat'=>'Arcade',  'difficulty'=>'Medium','desc'=>'Fly your spaceship and blast incoming asteroids. Don\'t let them hit you!',                                      'controls'=>'Arrow Keys to move, Space to shoot'],
    '2048'       => ['name'=>'2048',           'icon'=>'🔢','cat'=>'Puzzle',  'difficulty'=>'Medium','desc'=>'Slide tiles on a 4×4 grid. When two tiles with the same number touch, they merge! Reach 2048!',               'controls'=>'Arrow Keys to slide tiles'],
    'memory'     => ['name'=>'Memory Cards',   'icon'=>'🃏','cat'=>'Puzzle',  'difficulty'=>'Easy',  'desc'=>'Flip cards two at a time and try to find matching pairs. Complete the board with fewest flips!',               'controls'=>'Click cards to flip them'],
    'minesweeper'=> ['name'=>'Minesweeper',    'icon'=>'💣','cat'=>'Puzzle',  'difficulty'=>'Hard',  'desc'=>'Click cells to reveal them. Numbers show how many adjacent mines exist. Right-click to flag mines.',           'controls'=>'Left click reveal, Right click flag'],
    'sliding'    => ['name'=>'Sliding Puzzle', 'icon'=>'🔲','cat'=>'Puzzle',  'difficulty'=>'Medium','desc'=>'Slide numbered tiles into the correct order (1–15) with the empty space in the bottom right.',                 'controls'=>'Click a tile adjacent to the blank to slide it'],
    'tictactoe'  => ['name'=>'Tic Tac Toe',    'icon'=>'❌','cat'=>'Strategy','difficulty'=>'Easy',  'desc'=>'Play X vs the AI. Get three in a row — horizontally, vertically or diagonally — to win!',                    'controls'=>'Click a cell to place your mark'],
    'connect4'   => ['name'=>'Connect Four',   'icon'=>'🟡','cat'=>'Strategy','difficulty'=>'Medium','desc'=>'Drop colored discs into a 7-column grid. Connect four of your discs in a row to win!',                        'controls'=>'Click a column to drop your disc'],
    'chess'      => ['name'=>'Chess Lite',     'icon'=>'♟️','cat'=>'Strategy','difficulty'=>'Hard',  'desc'=>'Play a simplified chess game against an AI. Capture the king to win!',                                       'controls'=>'Click piece then click destination'],
    'wordle'     => ['name'=>'Wordle',         'icon'=>'📝','cat'=>'Word',    'difficulty'=>'Medium','desc'=>'Guess the secret 5-letter word in 6 tries. Green = correct, Yellow = wrong position, Gray = not in word.',    'controls'=>'Type letters, Enter to submit, Backspace to delete'],
    'hangman'    => ['name'=>'Hangman',        'icon'=>'🎭','cat'=>'Word',    'difficulty'=>'Easy',  'desc'=>'Guess the hidden word one letter at a time. Too many wrong guesses and the man is hanged!',                   'controls'=>'Click letters or type on keyboard'],
    'typeracer'  => ['name'=>'Type Racer',     'icon'=>'⌨️','cat'=>'Word',    'difficulty'=>'Medium','desc'=>'Type the displayed text as fast and accurately as possible. Your WPM score is recorded!',                     'controls'=>'Type in the input field'],
    'pong'       => ['name'=>'Pong',           'icon'=>'🏓','cat'=>'Sports',  'difficulty'=>'Easy',  'desc'=>'Classic table tennis! Move your paddle to return the ball. First to 7 points wins!',                         'controls'=>'Mouse or W/S keys to move paddle'],
    'basketball' => ['name'=>'Basketball',     'icon'=>'🏀','cat'=>'Sports',  'difficulty'=>'Medium','desc'=>'Aim and shoot the basketball through the hoop. Perfect your angle and power for maximum score!',             'controls'=>'Click and drag to aim, release to shoot'],
    'tetris'     => ['name'=>'Tetris',         'icon'=>'🟦','cat'=>'Sports',  'difficulty'=>'Medium','desc'=>'Rotate and drop falling Tetromino pieces to fill complete lines. Lines clear for points!',                   'controls'=>'Arrow keys to move/rotate, Down to drop faster'],
];

if (!isset($allGames[$gameId])) {
    header('Location: index.php');
    exit;
}

$game = $allGames[$gameId];

// Load history
$history = [];
$bestScore = 0;
$totalPlays = 0;
if ($loggedIn) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM game_history WHERE user_id=? AND game_id=? ORDER BY played_at DESC LIMIT 20");
        $stmt->execute([$userId, $gameId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $db->prepare("SELECT MAX(score) as best, COUNT(*) as total FROM game_history WHERE user_id=? AND game_id=?");
        $stmt2->execute([$userId, $gameId]);
        $stats = $stmt2->fetch(PDO::FETCH_ASSOC);
        $bestScore  = $stats['best'] ?? 0;
        $totalPlays = $stats['total'] ?? 0;
    } catch(Exception $e) {}
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    return $diff->i . 'm ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($game['name'])?> — NeonPlay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#050510;--surface:#0d0d2b;--card:#0f1035;--border:#1a1a4e;
  --cyan:#00f5ff;--pink:#ff0080;--purple:#9b59b6;--green:#00ff88;--yellow:#ffd700;
  --text:#e8e8ff;--muted:#7878aa;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Exo 2',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
#stars{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}
.wrap{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 24px}

nav{position:sticky;top:0;z-index:100;background:rgba(5,5,16,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 24px}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;height:64px}
.logo{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:900;background:linear-gradient(90deg,var(--cyan),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.back-btn{font-family:'Orbitron',sans-serif;font-size:.75rem;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:6px;border:1px solid var(--border);padding:6px 14px;border-radius:999px;transition:.2s}
.back-btn:hover{border-color:var(--cyan);color:var(--cyan)}
.spacer{flex:1}
.user-badge{display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--cyan);text-decoration:none}
.avatar-sm{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:.8rem;font-weight:700;color:#fff}
.btn{font-family:'Orbitron',sans-serif;font-size:.75rem;font-weight:600;padding:8px 18px;border-radius:6px;cursor:pointer;text-decoration:none;border:none;transition:.2s;letter-spacing:.05em}
.btn-ghost{background:transparent;border:1px solid var(--cyan);color:var(--cyan)}
.btn-ghost:hover{background:var(--cyan);color:var(--bg)}
.btn-primary{background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff}
.btn-danger{background:var(--pink);color:#fff}

/* PAGE LAYOUT */
.page{display:grid;grid-template-columns:1fr 340px;gap:32px;padding:32px 0 64px}
@media(max-width:900px){.page{grid-template-columns:1fr}}

/* GAME SECTION */
.game-header{margin-bottom:20px}
.breadcrumb{font-size:.8rem;color:var(--muted);margin-bottom:12px}
.breadcrumb a{color:var(--cyan);text-decoration:none}
.game-title{font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:900;display:flex;align-items:center;gap:16px;margin-bottom:8px}
.cat-tag{font-size:.75rem;font-family:'Orbitron',sans-serif;padding:4px 12px;border-radius:999px;background:rgba(0,245,255,.1);border:1px solid rgba(0,245,255,.3);color:var(--cyan)}
.diff-tag{font-size:.75rem;font-family:'Orbitron',sans-serif;padding:4px 12px;border-radius:999px}
.diff-Easy  {background:rgba(0,255,136,.1);color:var(--green);border:1px solid rgba(0,255,136,.3)}
.diff-Medium{background:rgba(255,215,0,.1);color:var(--yellow);border:1px solid rgba(255,215,0,.3)}
.diff-Hard  {background:rgba(255,0,128,.1);color:var(--pink);border:1px solid rgba(255,0,128,.3)}

.game-canvas-wrap{
  background:var(--card);border:1px solid var(--border);border-radius:16px;
  overflow:hidden;position:relative;min-height:520px;
  display:flex;align-items:center;justify-content:center;
}
#gameFrame{width:100%;height:560px;border:none;background:#000}

.game-meta{display:flex;gap:16px;margin-top:16px;flex-wrap:wrap}
.meta-chip{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:.82rem;color:var(--muted)}
.meta-chip strong{color:var(--text);display:block;margin-bottom:2px;font-family:'Orbitron',sans-serif;font-size:.75rem}

/* SIDEBAR */
.sidebar{}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:20px}
.card-title{font-family:'Orbitron',sans-serif;font-size:.85rem;font-weight:700;color:var(--cyan);margin-bottom:16px;letter-spacing:.08em}

.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.stat-box{background:var(--surface);border-radius:10px;padding:14px;text-align:center}
.stat-num{font-family:'Orbitron',sans-serif;font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,var(--cyan),var(--green));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-lbl{font-size:.72rem;color:var(--muted);margin-top:2px}

.history-list{display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto}
.history-list::-webkit-scrollbar{width:4px}
.history-list::-webkit-scrollbar-track{background:var(--surface)}
.history-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.hist-item{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px}
.hist-score{font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:700;color:var(--cyan);min-width:60px}
.hist-detail{flex:1;font-size:.78rem;color:var(--muted)}
.hist-time{font-size:.7rem;color:var(--border);white-space:nowrap}
.result-win{color:var(--green)}
.result-lose{color:var(--pink)}
.result-played{color:var(--muted)}

.no-login-msg{text-align:center;padding:24px;color:var(--muted);font-size:.85rem}
.no-login-msg .icon{font-size:2.5rem;margin-bottom:8px}
.login-link{color:var(--cyan);cursor:pointer;font-weight:600}

.info-row{display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;font-size:.85rem}
.info-row .label{color:var(--muted);min-width:80px;flex-shrink:0}
.info-row .value{color:var(--text)}

.not-logged-banner{
  background:rgba(0,245,255,.06);border:1px solid rgba(0,245,255,.2);
  border-radius:10px;padding:12px 16px;font-size:.82rem;color:var(--muted);
  margin-bottom:16px;text-align:center;
}
.not-logged-banner a{color:var(--cyan);cursor:pointer;font-weight:600}
</style>
</head>
<body>
<canvas id="stars"></canvas>

<nav>
  <div class="nav-inner">
    <a href="index.php" class="logo">NEONPLAY</a>
    <a href="index.php" class="back-btn">← All Games</a>
    <div class="spacer"></div>
    <?php if ($loggedIn): ?>
      <a href="profile.php" class="user-badge">
        <div class="avatar-sm"><?=strtoupper(substr($username,0,1))?></div>
        <span><?=htmlspecialchars($username)?></span>
      </a>
      <form method="POST" action="auth.php" style="margin-left:8px">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-danger">Logout</button>
      </form>
    <?php else: ?>
      <a href="index.php" class="btn btn-ghost">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="wrap">
  <div class="page">
    <!-- GAME MAIN -->
    <div>
      <div class="game-header">
        <div class="breadcrumb">
          <a href="index.php">Home</a> › <a href="index.php?cat=<?=strtolower($game['cat'])?>"><?=$game['cat']?></a> › <?=htmlspecialchars($game['name'])?>
        </div>
        <div class="game-title">
          <span><?=$game['icon']?></span>
          <?=htmlspecialchars($game['name'])?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <span class="cat-tag"><?=$game['cat']?></span>
          <span class="diff-tag diff-<?=$game['difficulty']?>"><?=$game['difficulty']?></span>
        </div>
      </div>

      <?php if (!$loggedIn): ?>
      <div class="not-logged-banner">
        🏆 <a href="index.php">Login or Register</a> to save your scores and track your history!
      </div>
      <?php endif; ?>

      <div class="game-canvas-wrap">
        <iframe id="gameFrame" src="games/<?=$gameId?>.php?user=<?=urlencode($username)?>&uid=<?=$userId?>" allowfullscreen></iframe>
      </div>

      <div class="game-meta">
        <div class="meta-chip"><strong>CONTROLS</strong><?=$game['controls']?></div>
        <div class="meta-chip"><strong>CATEGORY</strong><?=$game['cat']?></div>
        <div class="meta-chip"><strong>DIFFICULTY</strong><?=$game['difficulty']?></div>
      </div>
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar">
      <!-- Game Info -->
      <div class="card">
        <div class="card-title">📋 ABOUT THIS GAME</div>
        <p style="font-size:.88rem;color:var(--muted);line-height:1.65;margin-bottom:16px"><?=htmlspecialchars($game['desc'])?></p>
        <div class="info-row"><span class="label">Controls</span><span class="value"><?=$game['controls']?></span></div>
        <div class="info-row"><span class="label">Category</span><span class="value"><?=$game['cat']?></span></div>
        <div class="info-row"><span class="label">Difficulty</span><span class="value"><?=$game['difficulty']?></span></div>
      </div>

      <?php if ($loggedIn): ?>
      <!-- Stats -->
      <div class="card">
        <div class="card-title">📊 YOUR STATS</div>
        <div class="stat-grid">
          <div class="stat-box"><div class="stat-num"><?=$totalPlays?></div><div class="stat-lbl">PLAYS</div></div>
          <div class="stat-box"><div class="stat-num"><?=$bestScore?></div><div class="stat-lbl">BEST SCORE</div></div>
        </div>
      </div>

      <!-- History -->
      <div class="card">
        <div class="card-title">🕹️ GAME HISTORY</div>
        <?php if (empty($history)): ?>
          <div style="text-align:center;padding:20px;color:var(--muted);font-size:.85rem">
            <div style="font-size:2rem;margin-bottom:8px">🎮</div>
            No plays yet — start playing!
          </div>
        <?php else: ?>
        <div class="history-list">
          <?php foreach ($history as $h): ?>
          <div class="hist-item">
            <div class="hist-score"><?=$h['score']?></div>
            <div class="hist-detail">
              <span class="result-<?=$h['result']?>"><?=ucfirst($h['result'])?></span>
              <?php if ($h['duration']>0): ?> · <?=gmdate('i:s',$h['duration'])?><?php endif; ?>
            </div>
            <div class="hist-time"><?=timeAgo($h['played_at'])?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php else: ?>
      <div class="card">
        <div class="card-title">🏆 TRACK YOUR PROGRESS</div>
        <div class="no-login-msg">
          <div class="icon">🔐</div>
          <p><span class="login-link" onclick="window.location='index.php'">Login or create an account</span> to save your scores, track play history, and compete!</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const canvas = document.getElementById('stars');
const ctx = canvas.getContext('2d');
let stars=[];
function resize(){canvas.width=window.innerWidth;canvas.height=window.innerHeight;stars=Array.from({length:80},()=>({x:Math.random()*canvas.width,y:Math.random()*canvas.height,r:Math.random()*1.2+.2,speed:Math.random()*.3+.1,pulse:Math.random()*Math.PI*2}))}
resize();window.addEventListener('resize',resize);
function animate(){ctx.clearRect(0,0,canvas.width,canvas.height);stars.forEach(s=>{s.pulse+=.02;const a=.3+Math.sin(s.pulse)*.25;ctx.beginPath();ctx.arc(s.x,s.y,s.r,0,Math.PI*2);ctx.fillStyle=`rgba(0,245,255,${a})`;ctx.fill();s.y-=s.speed;if(s.y<0){s.y=canvas.height;s.x=Math.random()*canvas.width}});requestAnimationFrame(animate)}
animate();

// Receive score from iframe
window.addEventListener('message', e => {
  const d = e.data;
  if (d && d.type === 'GAME_SCORE') {
    <?php if ($loggedIn): ?>
    fetch('../api/score.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({game_id:'<?=$gameId?>',score:d.score,duration:d.duration||0,result:d.result||'played'})
    }).then(()=>{ setTimeout(()=>location.reload(),800) });
    <?php endif; ?>
  }
});
</script>
</body>
</html>
