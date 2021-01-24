<?php
    // データベース接続を確立する関数(getDb())
    require_once '../assets/php/parts/DbManager.php';
    // 入力された文字列を検証するクラス
    require_once '../assets/php/parts/MyValidator.php';

    // 変数の初期化
    $success_message = null;
    $error_message = [];
    $link = null;
    $name = null;
    $password = null;
    $mail = null;
    $db = null;
    $sql = null;
    $stmt = null;

    // データが送信されたかを確認
    if(!empty($_POST['signup'])) {
        // データが空でないかを確認
        if(isset($_POST['name']) && trim($_POST['name']) !== '' &&
            isset($_POST['password']) && trim($_POST['password']) !== '' &&
            isset($_POST['mail']) && trim($_POST['mail']) !== '') {
            // MyValidatorクラスのインスタンス化
            $v = new MyValidator();
            // 文字数チェック
            $v->lengthCheckMax($_POST['name'], 'お名前', 11);
            $v->lengthCheckMin($_POST['password'], 'パスワード', 8);
            $v->lengthCheckMax($_POST['password'], 'パスワード', 128);
            $v->lengthCheckMax($_POST['mail'], 'メールアドレス', 32);
            // メールアドレスが正しい形式化をチェック
            $v->regexCheck($_POST['mail'], 'メールアドレス', '/^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@{1}[A-Za-z0-9_.-]{1,}\.[A-Za-z0-9]{1,}$/');
            // MyValidatorクラスでエラーを検知した場合
            if($v()) {
                $error_message[] = implode(' ', $v());
            }
            // エラーがなかった場合
            if(empty($error_message)) {

                $name = $_POST['name'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $mail = $_POST['mail'];
                try {
                    $db = getDb();
                    // 入力された名前または、メールアドレスが登録されているか調べる
                    $sql = 'SELECT * FROM users WHERE username = ? OR mail = ?';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(1, $name);
                    $stmt->bindValue(2, $mail);
                    $stmt->execute();
                    // 名前または、メールアドレスが登録されているか確認
                    if($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // 同じ名前が登録されていた場合
                        if($member['username'] === $name) {
                            $error_message[] = '同じ名前が存在します。';
                        }
                        // 同じメールアドレスが登録されていた場合
                        if($member['mail'] === $mail) {
                            $error_message[] = '同じメールアドレスが存在します。';
                        }
                        // 該当行が最大2行存在するためすべて検査するための繰り返し
                        while($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // 同じ名前が登録されていた場合
                            if($member['username'] === $name) {
                                $error_message[] = '同じ名前が存在します。';
                            }
                            // 同じメールアドレスが登録されていた場合
                            if($member['mail'] === $mail) {
                                $error_message[] = '同じメールアドレスが存在します。';
                            }
                        }
                    } else {
                        // 入力された内容をデーターベースに追加
                        $sql = 'INSERT INTO users(username, password, mail) VALUES(?, ?, ?)';
                        $stmt = $db->prepare($sql);
                        $stmt->bindValue(1, $name);
                        $stmt->bindValue(2, $password);
                        $stmt->bindValue(3, $mail);
                        $stmt->execute();
                        $success_message = '登録が完了しました。';
                        $link = '<div class="link login"><a href="../login/">ログインページへ</a></div>';
                    }
                } catch (PDOException $e) {
                    $error_message[] = $e->getMessage();
                }
            }
        } else {
            $error_message[] = '登録に失敗しました。';
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録画面</title>
    <!-- cssへのリンク -->
    <link rel="stylesheet" type="text/css" href="../assets/css/reset.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/common.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/signup.css">
</head>
<body>
    <h2 class="sub-title">新規登録画面</h2>
    <div class="container">
        <!-- 登録に成功した場合 -->
        <?php if(!empty($success_message)): ?>
            <p class="success-message message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <!-- 登録に失敗した場合 -->
        <?php if(!empty($error_message)): ?>
            <p class="error-message message"><?php echo implode(' ', $error_message); ?></p>
        <?php endif; ?>
        <form class="form" action="" method="post">
            <label for="name">お名前<span class="notes">*</span></label>
            <input type="text" id="name" name="name" value="" />
            <label for="password">パスワード<span class="notes">*</span></label>
            <input type="password" id="password" name="password" value="" />
            <label for="mail">メールアドレス<span class="notes">*</span></label>
            <input type="email" id="mail" name="mail" value="" />
            <input type="submit" name="signup" value="新規登録" />
        </form>
        <div class="link home">
            <a href="../index.html">ホームに戻る</a>
        </div><!--
     --><?php
            // リンク先の表示
            if(!empty($link)) {
                echo $link;
            }
        ?>
    </div>
</body>
</html>