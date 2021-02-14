<?php
function getDb() {
    $dsn = 'mysql:dbname=webfantasy_santango; host=157.112.187.125; charset=utf8';
    $usr = 'webfantasy_uta';
    $passwd = 'kiseki1537';

    // データーベースへの接続を確立
    $db = new PDO($dsn, $usr, $passwd, [PDO::ATTR_PERSISTENT => true]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}