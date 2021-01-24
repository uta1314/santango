<?php
    session_start();
    // データベース接続を確立する関数(getDb())
    require_once '../assets/php/parts/DbManager.php';

    // 変数の初期化
    $error_message = [];
    $name = null;
    $password = null;
    $db = null;
    $sql = null;
    $stmt = null;
    $row = null;

    // データが送信されたかを確認
    if(!empty($_POST['login'])) {
        // データが空でないかを確認
        if(isset($_POST['name']) && $_POST['name'] !== '' &&
            isset($_POST['password']) && $_POST['password'] !== '') {

            $name = $_POST['name'];
            $password = $_POST['password'];
            try {
                $db = getDb();
                $sql = 'SELECT * FROM users WHERE username = ?';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(1, $name);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                // 指定したハッシュがパスワードにマッチしているかチェック
                if(password_verify($password, $row['password'])) {
                    // データベースのユーザー情報をセションに保存
                    $_SESSION['name'] = $row['username'];
                    // boardへ移動
                    header('Location: ../board/');
                    exit;
                } else {
                    $error_message[] = 'お名前もしくはパスワードが間違っています。';
                }
            } catch (PDOException $e) {
                $error_message[] = $e->getMessage();
            }
        } else {
            $error_message[] = 'お名前とパスワードを入力してください。';
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン画面</title>
    <!-- cssへのリンク -->
    <link rel="stylesheet" type="text/css" href="../assets/css/reset.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/common.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/login.css">
</head>
<body>
    <h2 class="sub-title">ログイン画面</h2>
    <div class="container">
        <!-- ログインに失敗した場合 -->
        <?php if(!empty($error_message)): ?>
            <p class="error-message message"><?php echo implode(' ', $error_message); ?></p>
        <?php endif; ?>
        <form class="form" action="" method="post">
            <label for="name">お名前</label>
            <input type="text" id="name" name="name" value="" />
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" value="" />
            <input type="submit" name="login" value="ログイン" />
        </form>
    </div>
    <div class="link home">
        <a href="../index.html">ホームに戻る</a>
    </div>
</body>
</html>