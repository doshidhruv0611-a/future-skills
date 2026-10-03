<?php require_once 'includes/db.php';
if(isset($_POST['login'])){if(getenv('ADMIN_PASSWORD')&&hash_equals(getenv('ADMIN_PASSWORD'),$_POST['pw']??'')){setcookie('adm',tok(),['expires'=>time()+86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);header('Location: admin.php');exit;}$err='Wrong password, or ADMIN_PASSWORD not set.';}
if(isset($_GET['logout'])){setcookie('adm','',time()-3600,'/');header('Location: admin.php');exit;}
if(is_admin()&&$_SERVER['REQUEST_METHOD']=='POST'){
if(isset($_POST['save'])){foreach(array_keys(defaults()) as $k)if(isset($_POST[$k]))db()->prepare('REPLACE INTO content(k,v) VALUES(?,?)')->execute([$k,$_POST[$k]]);}
if(isset($_POST['reply']))db()->prepare("UPDATE messages SET reply=?,status='Replied' WHERE id=?")->execute([$_POST['reply'],(int)$_POST['id']]);
if(isset($_POST['del']))db()->prepare('DELETE FROM messages WHERE id=?')->execute([(int)$_POST['id']]);
header('Location: admin.php');exit;}
require 'includes/header.php';?><div class="wrap"><h1>Admin Panel</h1>
<?php if(!is_admin()){?><?=isset($err)?"<p>".e($err)."</p>":''?><form method="post"><label>Password</label><input type="password" name="pw"><button name="login">Login</button></form>
<?php }else{?><p><a href="?logout=1">Logout</a></p><h2>Content Management</h2><form method="post"><?php foreach(defaults() as $k=>$v){?><label><?=$k?></label><textarea name="<?=$k?>" rows="2"><?=e(c($k))?></textarea><?php }?><button name="save">Save Content</button></form>
<h2>Inquiries</h2><table><tr><th>Date</th><th>From</th><th>Message</th><th>Status</th><th>Reply</th></tr><?php foreach(db()->query('SELECT * FROM messages ORDER BY id DESC') as $m){?><tr><td><?=e($m['created'])?></td><td><?=e($m['name'])?><br><?=e($m['email'])?><br><?=e($m['phone'])?></td><td><?=nl2br(e($m['message']))?></td><td><?=e($m['status'])?></td><td><form method="post"><input type="hidden" name="id" value="<?=$m['id']?>"><textarea name="reply" rows="2"><?=e($m['reply']??'')?></textarea><button>Mark Replied</button> <button name="del" value="1" style="background:#b91c1c" onclick="return confirm('Delete?')">Delete</button></form></td></tr><?php }?></table><?php }?></div><?php require 'includes/footer.php';?>
