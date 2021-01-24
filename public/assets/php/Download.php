<?php
    session_start();

    // データベース接続を確立する関数(getDb())
    require_once './parts/DbManager.php';

    // 変数の初期化
    $message_array = [];
    $name = null;
    $csv_data = null;
    $db = null;
    $sql = null;
    $stmt = null;
    $row = null;
    $limit = null;

    // 取得件数
    if(!empty($_GET['limit'])) {
        if($_GET['limit'] === "10") {
            $limit = 10;
        } else if($_GET['limit'] === "30") {
            $limit = 30;
        }
    }

    // セションのnameが空ではなかった場合
    if(!empty($_SESSION['name'])) {
        $name = $_SESSION['name'];
    }

    //管理者であった場合
    if(!empty($name) && $name === 'root') {
        // 出力の設定
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=メッセージデータ.csv');
        try {
            $db = getDb();
            if(!empty($limit)) {
                $sql = 'SELECT * FROM message ORDER BY post_date ASC LIMIT ?';
            } else {
                $sql = 'SELECT * FROM message ORDER BY post_date ASC';
            }
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            // データを取り出す
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $message_array[] = $row;
            }
        } catch(PDOException $e) {
            $e->getMessage();
        }

        // CSVデータを作成
        if(!empty($message_array)) {
            // 1行目のラベル作成
            $csv_data .= '"ID","表示名","メッセージ","投稿日時"'."\n";

            foreach($message_array as $value) {
                // データを1行ずつCSVファイルに書き込む
                $csv_data .= '"' . $value['id'] . '","' . $value['name'] . '","' . $value['message'] . '","' . $value['post_date'] . "\"\n";
            }
        }
        // Excelで開くようにSJISにして出力
        echo mb_convert_encoding($csv_data, "SJIS", "UTF-8");
    } else {
        // ログインページへリダイレクト
        header('Location: ../login/');
    }

    return;