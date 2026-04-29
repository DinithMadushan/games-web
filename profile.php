<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare("SELECT game_id, COUNT(*) as plays, MAX(score) as best FROM game_history WHERE user_id=? GROUP BY game_id ORDER BY plays DESC");
$stmt2->execute([$userId]);
$gameStats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $db->prepare("SELECT * FROM game_history WHERE user_id=? ORDER BY played_at DESC LIMIT 30");
$stmt3->execute([$userId]);
$recentHistory = $stmt3->fetchAll(PDO::FETCH_ASSOC);

$totalPlays = array_sum(array_column($gameStats,'plays'));
$totalGames = count($gameStats);

$gameNames = ['snake'=>'🐍 Snake','breakout'=>'🧱 Breakout','flappy'=>'🐦 Flappy Bird','asteroids'=>'🚀 Space Shooter','2048'=>'🔢 2048','memory'=>'🃏 Memory','minesweeper'=>'💣 Minesweeper','sliding'=>'🔲 Sliding','tictactoe'=>'❌ Tic Tac Toe','connect4'=>'🟡 Connect Four','chess'=>'♟️ Chess Lite','wordle'=>'📝 Wordle','hangman'=>'🎭 Hangman','typeracer'=>'⌨️ Type Racer','pong'=>'🏓 Pong','basketball'=>'🏀 Basketball','tetris'=>'🟦 Tetris'];

function timeAgo($dt){$n=new DateTime();$a=new DateTime($dt);$d=$n->diff($a);if($d->d>0)return $d->d.'d ago';if($d->h>0)return $d->h.'h ago';return $d->i.'m ago';}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile — <?=htmlspecialchars($username)?></title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#050510;--surface:#0d0d2b;--card:#0f1035;--border:#1a1a4e;--cyan:#00f5ff;--pink:#ff0080;--purple:#9b59b6;--green:#00ff88;--yellow:#ffd700;--text:#e8e8ff;--muted:#7878aa}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Exo 2',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
#stars{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}
.wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 24px 64px}
nav{position:sticky;top:0;z-index:100;background:rgba(5,5,16,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 24px}
.nav-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:16px;height:64px}
.logo{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:900;background:linear-gradient(90deg,var(--cyan),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.btn{font-family:'Orbitron',sans-serif;font-size:.75rem;font-weight:600;padding:8px 18px;border-radius:6px;cursor:pointer;text-decoration:none;border:none;transition:.2s}
.btn-ghost{background:transparent;border:1px solid var(--cyan);color:var(--cyan)}
.btn-ghost:hover{background:var(--cyan);color:var(--bg)}
.btn-danger{background:var(--pink);color:#fff}
.spacer{flex:1}
/* Profile Hero */
.profile-hero{
  background:linear-gradient(135deg,rgba(0,245,255,.05),rgba(155,89,182,.05));
  border:1px solid var(--border);border-radius:20px;
  padding:40px;display:flex;align-items:center;gap:32px;margin:32px 0;flex-wrap:wrap;
}
.avatar-lg{
  width:80px;height:80px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--cyan),var(--purple));
  display:flex;align-items:center;justify-content:center;
  font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:900;color:#fff;
  box-shadow:0 0 30px rgba(0,245,255,.3);
}
.profile-info h1{font-family:'Orbitron',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px}
.profile-info p{font-size:.9rem;color:var(--muted);margin-bottom:12px}
.member-since{font-size:.75rem;color:var(--muted);background:var(--surface);border:1px solid var(--border);padding:4px 12px;border-radius:999px;display:inline-block}
.profile-stats{display:flex;gap:32px;margin-left:auto;flex-wrap:wrap}
.pstat{text-align:center}
.pstat-val{font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--cyan),var(--green));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.pstat-lbl{font-size:.75rem;color:var(--muted);letter-spacing:.1em}
/* Grid */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}
@media(max-width:700px){.two-col{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:24px}
.card-title{font-family:'Orbitron',sans-serif;font-size:.85rem;font-weight:700;color:var(--cyan);margin-bottom:16px;letter-spacing:.08em}
/* Game stat rows */
.game-stat-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.game-stat-row:last-child{border-bottom:none}
.game-stat-name{flex:1;font-size:.88rem}
.game-stat-plays{font-size:.8rem;color:var(--muted);min-width:60px;text-align:center}
.game-stat-best{font-family:'Orbitron',sans-serif;font-size:.95rem;color:var(--cyan);min-width:60px;text-align:right}
/* History */
.hist-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border)}
.hist-item:last-child{border-bottom:none}
.hist-score{font-family:'Orbitron',sans-serif;font-size:.95rem;color:var(--cyan);min-width:55px}
.hist-name{flex:1;font-size:.82rem;color:var(--text)}
.hist-result{font-size:.75rem;padding:2px 8px;border-radius:999px}
.result-win{background:rgba(0,255,136,.1);color:var(--green)}
.result-lose{background:rgba(255,0,128,.1);color:var(--pink)}
.result-played{background:rgba(120,120,170,.1);color:var(--muted)}
.hist-time{font-size:.72rem;color:var(--border);white-space:nowrap}
.empty{text-align:center;padding:32px;color:var(--muted);font-size:.85rem}
.empty .e{font-size:2.5rem;margin-bottom:8px}
</style>
</head>
<body>
<canvas id="stars"></canvas>
<nav>
  <div class="nav-inner">
    <a href="index.php" class="logo">NEONPLAY</a>
    <div class="spacer"></div>
    <a href="index.php" class="btn btn-ghost">🎮 Games</a>
    <form method="POST" action="auth.php" style="margin-left:8px">
      <input type="hidden" name="action" value="logout">
      <button class="btn btn-danger">Logout</button>
    </form>
  </div>
</nav>

<div class="wrap">
  <div class="profile-hero">
    <div class="avatar-lg"><?=strtoupper(substr($username,0,1))?></div>
    <div class="profile-info">
      <h1><?=htmlspecialchars($username)?></h1>
      <p><?=htmlspecialchars($user['email'])?></p>
      <span class="member-since">Member since <?=date('M Y',strtotime($user['created_at']))?></span>
    </div>
    <div class="profile-stats">
      <div class="pstat"><div class="pstat-val"><?=$totalPlays?></div><div class="pstat-lbl">TOTAL PLAYS</div></div>
      <div class="pstat"><div class="pstat-val"><?=$totalGames?></div><div class="pstat-lbl">GAMES PLAYED</div></div>
    </div>
  </div>

  <div class="two-col">
    <!-- Game Stats -->
    <div class="card">
      <div class="card-title">🎮 GAMES PLAYED</div>
      <?php if (empty($gameStats)): ?>
        <div class="empty"><div class="e">🎮</div>No games played yet.<br><a href="index.php" style="color:var(--cyan)">Start playing now!</a></div>
      <?php else: ?>
        <?php foreach ($gameStats as $gs): ?>
        <div class="game-stat-row">
          <a href="game.php?id=<?=$gs['game_id']?>" style="text-decoration:none;color:inherit;flex:1">
            <div class="game-stat-name"><?=$gameNames[$gs['game_id']]??$gs['game_id']?></div>
          </a>
          <div class="game-stat-plays"><?=$gs['plays']?> plays</div>
          <div class="game-stat-best"><?=$gs['best']?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Recent History -->
    <div class="card">
      <div class="card-title">🕐 RECENT ACTIVITY</div>
      <?php if (empty($recentHistory)): ?>
        <div class="empty"><div class="e">📭</div>No history yet!</div>
      <?php else: ?>
        <?php foreach ($recentHistory as $h): ?>
        <div class="hist-item">
          <div class="hist-score"><?=$h['score']?></div>
          <div class="hist-name"><?=$gameNames[$h['game_id']]??$h['game_id']?></div>
          <span class="hist-result result-<?=$h['result']?>"><?=ucfirst($h['result'])?></span>
          <div class="hist-time"><?=timeAgo($h['played_at'])?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const canvas=document.getElementById('stars'),ctx=canvas.getContext('2d');let stars=[];
function resize(){canvas.width=window.innerWidth;canvas.height=window.innerHeight;stars=Array.from({length:80},()=>({x:Math.random()*canvas.width,y:Math.random()*canvas.height,r:Math.random()*1.2+.2,speed:Math.random()*.3+.1,pulse:Math.random()*Math.PI*2}))}
resize();window.addEventListener('resize',resize);
function animate(){ctx.clearRect(0,0,canvas.width,canvas.height);stars.forEach(s=>{s.pulse+=.02;const a=.3+Math.sin(s.pulse)*.25;ctx.beginPath();ctx.arc(s.x,s.y,s.r,0,Math.PI*2);ctx.fillStyle=`rgba(0,245,255,${a})`;ctx.fill();s.y-=s.speed;if(s.y<0){s.y=canvas.height;s.x=Math.random()*canvas.width}});requestAnimationFrame(animate)}
animate();
</script>
</body>
</html>
