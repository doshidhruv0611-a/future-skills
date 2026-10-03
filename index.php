<?php // Vercel router: maps /page.php -> root page file
$p=preg_replace('/\.php$/','',trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'))?:'index';
if(!in_array($p,['index','scope','skills','faq','contact','admin'],true))$p='index';
chdir(__DIR__.'/..');require __DIR__.'/../'.$p.'.php';
