<?php
function getDb() {
    $dsn = 'mysql:dbname=randomwords; host=127.0.0.1; charset=utf8';
    $usr = 'yuta';
    $passwd = 'kiseki';

    // データーベースへの接続を確立
    $db = new PDO($dsn, $usr, $passwd, [PDO::ATTR_PERSISTENT => true]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}