<?php
    session_start();

    // ログアウトの押下
    if(!empty($_GET['logout'])) {
        $_SESSION = [];
        // セションクッキーが存在する場合
        if(isset($_COOKIE[session_name()])) {
            // セションクッキーの情報を取得
            $cparam = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $cparam['path'], $cparam['domain'],
                $cparam['secure'], $cparam['httponly']);
            // セションを破棄
            session_destroy();
        }
        header('Location: ../');
    }
    // エスケープ処理する関数(e())
    require_once '../assets/php/parts/Encode.php';
    // データベース接続を確立する関数(getDb())
    require_once '../assets/php/parts/DbManager.php';
    // 入力された文字列を検証するクラス
    require_once '../assets/php/parts/MyValidator.php';

    // タイムゾーン設定
    date_default_timezone_set('Asia/Tokyo');

    // 変数の初期化
    $error_message = [];
    $message_array = [];
    $message = [];
    $name = null;
    $url = null;
    $xml = null;
    $theme = null;
    $words = [];
    $now_date = null;
    $db = null;
    $sql = null;
    $stmt = null;
    $row = null;

    // セションのnameが空ではなかった場合
    if(!empty($_SESSION['name'])) {
        $name = e($_SESSION['name']);
    } else {
    // ログインしていない場合
        // ログインページへリダイレクト
        header('Location: ../login/');
    }

    try {
        $db = getDb();
        $sql = 'SELECT date FROM words GROUP BY date';
        $stmt = $db->query($sql);
        // 日付がデータベースに存在する場合
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 24時間が経過していた場合
            if(strtotime($row['date'] . '+1 day') <= strtotime('now')) {
                // ランダムにWikipediaから3つの記事を取得
                $url = 'http://ja.wikipedia.org/w/api.php?format=xml&action=query&list=random&rnnamespace=0&rnlimit=3';

                // XMLエラーを有効にする
                libxml_use_internal_errors(true);

                // 応答読み込み
                $xml = @simplexml_load_file($url);

                // Wikipedia API接続失敗
                if(count(libxml_get_errors()) > 0) {
                    $error_message[] = '単語を取得できませんでした。';
                } else if(isset($xml->query->random->page)) {
                // 検索結果あり
                    // すべてのflagをFALSEにする
                    $sql = 'UPDATE themes SET flag=FALSE';
                    $stmt = $db->query($sql);
                    // 1~最大idまでのあいだでランダムに値を生成し、その値と一致するレコードのflagをTRUEにする
                    $sql = ' UPDATE themes AS t INNER JOIN (SELECT (FLOOR(RAND() * (SELECT MAX(id) FROM themes)) + 1) AS id) AS tmp ON t.id = tmp.id SET t.flag=TRUE';
                    $stmt = $db->query($sql);

                    $i = 1;
                    $today = date('Y-m-d');
                    foreach($xml->query->random->page as $page) {
                        // 単語、日付を更新
                        $sql = 'UPDATE words SET word = ?, date = ? WHERE id = ?';
                        $stmt = $db->prepare($sql);
                        $stmt->bindValue(1, $page['title']);
                        $stmt->bindValue(2, $today);
                        $stmt->bindValue(3, $i);
                        $stmt->execute();
                        $i++;
                    }
                } else {
                // 検索結果なし
                    $error_message[] = '単語が見つかりませんでした。';
                }
            }
        } else {
        // 単語がデータベースに存在しない場合
            // ランダムにWikipediaから3つの記事を取得
            $url = 'http://ja.wikipedia.org/w/api.php?format=xml&action=query&list=random&rnnamespace=0&rnlimit=3';

            // XMLエラーを有効にする
            libxml_use_internal_errors(true);

            // 応答読み込み
            $xml = @simplexml_load_file($url);

            // Wikipedia API接続失敗
            if(count(libxml_get_errors()) > 0) {
                $error_message[] = '単語を取得できませんでした。';
            } else if(isset($xml->query->random->page)) {
            // 検索結果あり
                // すべてのflagをFALSEにする
                $sql = 'UPDATE themes SET flag=FALSE';
                $stmt = $db->query($sql);
                // 1~最大idまでのあいだでランダムに値を生成し、その値と一致するレコードのflagをTRUEにする
                $sql = ' UPDATE themes AS t INNER JOIN (SELECT (FLOOR(RAND() * (SELECT MAX(id) FROM themes)) + 1) AS id) AS tmp ON t.id = tmp.id SET t.flag=TRUE';
                $stmt = $db->query($sql);

                $i = 1;
                $today = date('Y-m-d');
                foreach($xml->query->random->page as $page) {
                    // idと単語、日付を追加
                    $sql = 'INSERT INTO words (id, word, date) VALUES (?, ?, ?)';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(1, $i);
                    $stmt->bindValue(2, $page['title']);
                    $stmt->bindValue(3, $today);
                    $stmt->execute();
                    $i++;
                }
            } else {
            // 検索結果なし
                $error_message[] = '単語が見つかりませんでした。';
            }
        }
    } catch (PDOException $e) {
        $error_message[] = '接続に失敗しました。';
        $error_message[] = $e->getMessage();
    }

    try {
        $db = getDb();
        // flagがTRUEのレコードを取得
        $sql = 'SELECT theme FROM themes WHERE flag=TRUE';
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $theme = $row['theme'];

        // データベースから3つの単語を取得
        $sql = 'SELECT word FROM words';
        $stmt = $db->query($sql);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $words[] = $row['word'];
        }
    } catch (PDOException $e) {
        $error_message[] = '接続に失敗しました。';
        $error_message[] = $e->getMessage();
    }

    // データが送信されたかを確認
    if(!empty($_POST['board'])) {
        // メッセージの入力チェック
        if(empty($_POST['message'])) {
            $error_message[] = 'メッセージを入力してください。';
        }
        // MyValidatorクラスのインスタンス化
        $v = new MyValidator();
        // 文字数チェック
        $v->lengthCheckMax($_POST['message'], 'メッセージ', 200);
        // MyValidatorクラスでエラーを検知した場合
        if($v()) {
            $error_message[] = implode(' ', $v());
        }
        // エラーがなかった場合
        if(empty($error_message)) {
            try {
                $db = getDb();
                // 書き込み日時を取得
                $now_date = date('Y-m-d H:i:s');
                // データ登録のSQL
                $sql = 'INSERT INTO message (name, message, post_date) VALUES (?, ?, ?)';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(1, $_SESSION['name']);
                $stmt->bindValue(2, $_POST['message']);
                $stmt->bindValue(3, $now_date);
                $stmt->execute();

                $_SESSION['success_message'] = 'メッセージを書き込めました。';
                // 多重送信対策
                header('Location: ./');
            } catch (PDOException $e) {
                $error_message[] = '書き込みに失敗しました。';
                $error_message[] = $e->getMessage();
            }
        }
    }

    try {
        $db = getDb();
        // データ取得のSQL
        $sql = 'SELECT name, message, post_date FROM message ORDER BY post_date DESC';
        $stmt = $db->query($sql);
        // データを取り出す
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = [
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
    <title>投稿画面</title>
    <!-- cssへのリンク -->
    <link rel="stylesheet" type="text/css" href="../assets/css/reset.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/common.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/board.css">
</head>
<body>
    <h2 class="sub-title">今日のお題</h2>
    <!-- お題の表示 -->
    <p class="theme"><?php echo $theme ?></p>
    <!-- 単語の表示 -->
    <ul class="words">
        <?php foreach($words as $word): ?>
        <li class="word"><?php echo $word ?></li>
        <?php endforeach;?>
    </ul>
    <div class="container">
        <!-- 投稿が成功した場合 -->
        <?php if(empty($_POST['board']) && !empty($_SESSION['success_message'])): ?>
            <p class="success-message message"><?php echo $_SESSION['success_message']; ?></p>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <!-- 投稿に失敗した場合 -->
        <?php if(!empty($error_message)): ?>
            <p class="error-message message"><?php echo implode(' ', $error_message); ?></p>
        <?php endif; ?>
        <div class="name">
            名前: <span><?php if(!empty($name)) {echo $name;} ?></span>
        </div>
        <form class="form" action="" method="post">
            <label for="message">メッセージ</label>
            <textarea id="message" name="message"></textarea>
            <input type="submit" name="board" value="書き込む">
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
                    </div>
                    <p class="show-message"><?php echo $value['message']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <nav class="nav">
        <ul class="nav-list">
            <?php if($name === 'root'): ?>
                <li class="nav-item admin"><a href="../admin/">管理者画面</a></li>
            <?php endif; ?>
            <li class="nav-item logout">
                <form class="form" method="get" action="">
                    <input type="submit" name="logout" value="ログアウト" />
                </form>
            </li>
        </ul>
    </nav>
</body>
</html>