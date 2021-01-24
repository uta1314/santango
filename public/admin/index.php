<?php
    session_start();
    // エスケープ処理する関数(e())
    require_once '../assets/php/parts/Encode.php';
    // データベース接続を確立する関数(getDb())
    require_once '../assets/php/parts/DbManager.php';

    // タイムゾーン設定
    date_default_timezone_set('Asia/Tokyo');

    // 変数の初期化
    $error_message = [];
    $message_array = [];
    $message = [];
    $name = null;
    $db = null;
    $sql = null;
    $stmt = null;
    $row = null;

    // セションのnameが空ではなかった場合
    if(!empty($_SESSION['name'])) {
        $name = e($_SESSION['name']);
    }

    //管理者ではなかった場合
    if(empty($name) || $name !== 'root') {
        // ログインページへリダイレクト
        header('Location: ../login/');
    }

    try {
        $db = getDb();
        // データ取得のSQL
        $sql = 'SELECT id, name, message, post_date FROM message ORDER BY post_date DESC';
        $stmt = $db->query($sql);
        // データを取り出す
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = [
                'id' => e($row['id']),
                'name' => e($row['name']),
                'message' => nl2br(e($row['message'])),
                'post_date' => e($row['post_date'])
            ];
            array_push($message_array, $message);
        }
    } catch (PDOException $e) {
        $error_message[] = 'データの読み込みに失敗しました。';
        $error_message[] = $e->getMessage();
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>菅理者画面</title>
    <!-- cssへのリンク -->
    <link rel="stylesheet" type="text/css" href="../assets/css/reset.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/common.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/admin.css">
</head>
<body>
    <h2 class="sub-title">管理者ページ</h2>
    <div class="container">
        <!-- エラーが発生した場合 -->
        <?php if(!empty($error_message)): ?>
            <p class="error-message message"><?php echo implode(' ', $error_message); ?></p>
        <?php endif; ?>
        <form class="form" method="get" action="../assets/php/Download.php">
            <select name="limit">
                <option value="">すべて</option>
                <option value="10">10件</option>
                <option value="30">30件</option>
            </select>
            <input type="submit" name="download" value="ダウンロード" />
        </form>
        <hr>
        <!-- 投稿情報 -->
        <?php if(!empty($message_array)): ?>
            <div class="post-box">
                <?php foreach($message_array as $value): ?>
                    <div class="post-item <?php if($name === $value['name']) {echo 'self';} ?>">
                        <div class="info">
                            <h2 class="show-name"><?php echo $value['name']; ?></h2>
                            <time class="show-date"><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
                            <p class="show-link">
                                <a href="../edit/?message_id=<?php echo $value['id']; ?>">編集</a><!--
                            --><a href="../delete/?message_id=<?php echo $value['id']; ?>">削除</a>
                            </p>
                        </div>
                        <p class="show-message"><?php echo $value['message']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="button back">
        <a href="../board/">戻る</a>
    </div>
</body>
</html>