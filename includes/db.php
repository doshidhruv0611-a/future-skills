<?php
function db(){static $p;if($p)return $p;
$u=getenv('MYSQL_URL')?:getenv('MYSQL_PUBLIC_URL');if(!$u)die('Set MYSQL_URL');
$x=parse_url($u);
$p=new PDO("mysql:host={$x['host']};port=".($x['port']??3306).";dbname=".ltrim($x['path'],'/').";charset=utf8mb4",$x['user'],$x['pass']??'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach(array_filter(array_map('trim',explode(';',file_get_contents(__DIR__.'/../database/schema.sql')))) as $q)$p->exec($q);
return $p;}
function defaults(){return[
'hero_title'=>"Empowering Young Minds for Tomorrow's World",
'hero_sub'=>'Robotics & AI education for schools, Grades 1-12.',
'f1'=>'Zero Cost Lab Setup|Schools invest nothing to set up a robotics lab.',
'f2'=>'Grades 1-12 Program|A structured path from basics to advanced AI.',
'f3'=>'40+ Innovative Projects|Arduino Nano/Uno, IoT and AI projects.',
'scope_intro'=>'A 10-month curriculum with 8 sessions every month.',
'skills_intro'=>'We help students move from technology users to technology creators, building cognitive foundations, critical thinking and problem-solving through design.',
'q1'=>'Is there really zero investment for schools?','a1'=>'Yes. We set up the lab at no cost to the school.',
'q2'=>'What is the student fee?','a2'=>'Rs. 200 per student per month.',
'q3'=>'How is the hardware set up?','a3'=>'Our team installs kits and trains the staff. (Edit this text.)',
'footer_note'=>'Simple & Reliable'];}
function c($k){static $m;if($m===null){$m=defaults();foreach(db()->query('SELECT k,v FROM content') as $r)$m[$r['k']]=$r['v'];}return $m[$k]??'';}
function e($s){return htmlspecialchars($s,ENT_QUOTES);}
function tok(){return hash_hmac('sha256','admin',getenv('ADMIN_PASSWORD')?:'x');}
function is_admin(){return getenv('ADMIN_PASSWORD')&&hash_equals(tok(),$_COOKIE['adm']??'');}
