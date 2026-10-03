<?php require 'includes/header.php';$ok=false;
if($_SERVER['REQUEST_METHOD']=='POST'&&trim($_POST['name']??'')&&trim($_POST['message']??'')){
db()->prepare('INSERT INTO messages(name,email,phone,message) VALUES(?,?,?,?)')->execute([substr($_POST['name'],0,150),substr($_POST['email']??'',0,150),substr($_POST['phone']??'',0,30),substr($_POST['message'],0,3000)]);$ok=true;}?>
<div class="wrap"><h1>Contact Us</h1><?php if($ok){?><div class="ok">Thank you! We will contact you soon.</div><?php }?>
<form method="post"><label>Name</label><input name="name" required><label>Email</label><input type="email" name="email"><label>Phone</label><input name="phone"><label>Message</label><textarea name="message" rows="5" required></textarea><button>Send</button></form></div><?php require 'includes/footer.php';?>
