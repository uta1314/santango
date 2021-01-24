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
    $name = null;
    $message_id = null;
    $message_data = [];
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

    // GETパラメータに値が入っていて、POSTパラメータが空の場合
    if(!empty($_GET['message_id']) && empty($_POST['message_id'])) {
        $message_id = (int)$_GET['message_id'];
        try {
            $db = getDb();
            $sql = 'SELECT * FROM message WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $message_id, PDO::PARAM_INT);
            $stmt->execute();
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $message_data = [
                    'id' => e($row['id']),
                    'name' => e($row['name']),
                    'message' => (e($row['message'])),
                    'post_date' => e($row['post_date'])
                ];
            } else {
                // データが読み込めなかったら一覧に戻る
                header('Location: ../admin/');
            }
        } catch (PDOException $e) {
            $error_message[] = 'データベースの接続に失敗しました。';
            $error_message[] = $e->getMessage();
        }
    } else if(!empty($_POST['message_id'])) {
        // POSTパラメータが入っている場合
        $message_id = (int)$_POST['message_id'];
        $message_data['name'] = $_POST['name'];

        if(empty($_POST['message'])) {
            $error_message[] = 'メッセージを入力してください。';
        } else {
            $message_data['message'] = $_POST['message'];
        }
        // エラーがなかった場合
        if(empty($error_message)) {
            try {
                $db = getDb();
                $sql = 'UPDATE message SET message = ? WHERE id = ?';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(1, $message_data['message']);
                $stmt->bindValue(2, $message_id, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->rowCount()) {
                    header('Location: ../admin/');
                }
            } catch (PDOException $e) {
                $error_message[] = 'データベースの接続に失敗しました。';
                $error_message[] = $e->getMessage();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編集画面</title>
    <!-- cssへのリンク -->
    <link rel="stylesheet" type="text/css" href="../assets/css/reset.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/common.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/edit.css">
</head>
<body>
    <h2 class="sub-title">編集画面</h2>
    <div class="container">
        <!-- エラーが発生した場合 -->
        <?php if(!empty($error_message)): ?>
            <p class="error-message message"><?php echo implode(' ', $error_message); ?></p>
        <?php endif; ?>
        <div class="name">
            名前: <span><?php if(!empty($message_data['name'])) {echo $message_data['name'];} ?></span>
        </div>
        <form class="form" action="" method="post">
            <input type="hidden" name="name" value="<?php echo $message_data['name'] ?>">
            <label for="message">ひとことメッセージ</label>
            <textarea id="message" name="message"><?php if(!empty($message_data['message'])) {echo $message_data['message'];}?></textarea>
            <div class="button-box">
                <div class="button cancel">
                    <a href="../admin/">キャンセル</a>
                </div>
                <input type="submit" name="submit" value="更新">
                <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
            </div>
        </form>
    </div>
</body>
</html>