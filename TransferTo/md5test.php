<?php
$login='client';
$password='pass99';
$key=5;
$longLine='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

echo md5("$login$password$key$longLine"),"\n";

?>
