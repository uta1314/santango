<?php

class MyValidator {
    // エラーメッセージを格納するためのプライベート変数
    private $_errors;

    // コンストラクタ
    public function __construct(string $encoding = 'UTF-8') {
        // プライベート変数$_errorsを初期化
        $_errors = [];
        // 内部文字コードを設定
        mb_internal_encoding($encoding);
        // $_GET,$_POST,$_COOKIEの文字エンコーディングをチェック
        $this->checkEncoding($_GET);
        $this->checkEncoding($_POST);
        $this->checkEncoding($_COOKIE);
        // $_GET,$_POST,$_COOKIEのnullバイトをチェック
        $this->checkNull($_GET);
        $this->checkNull($_POST);
        $this->checkNull($_COOKIE);
    }

    // 配列要素に含まれる文字エンコーディングをチェック
    private function checkEncoding(array $data) {
        foreach ($data as $key => $value) {
            if (!mb_check_encoding($value)) {
                $this->_errors[] = "{$key}は不正な文字コードです。";
            }
        }
    }

    // 配列要素に含まれるnullバイトをチェック
    private function checkNull(array $data) {
        foreach ($data as $key => $value) {
            if (preg_match('/\0/', $value)) {
                $this->_errors[] = "{$key}は不正な文字を含んでいます。";
            }
        }
    }

    // 文字列長検証（$len文字以上であるか）
    public function lengthCheckMin(string $value, string $name, int $len) {
        if (trim($value) !== '') {
            if (mb_strlen($value) < $len) {
                $this->_errors[] = "{$name}は{$len}文字以上で入力してください。";
            }
        }
    }

    // 文字列長検証（$len文字以内であるか）
    public function lengthCheckMax(string $value, string $name, int $len) {
        if (trim($value) !== '') {
            if (mb_strlen($value) > $len) {
                $this->_errors[] = "{$name}は{$len}文字以内で入力してください。";
            }
        }
    }

    // 正規表現パターン検証（パターン$patternに合致するか）
    public function regexCheck(string $value, string $name, string $pattern) {
        if (trim($value) !== '') {
            if (!preg_match($pattern, $value)) {
                $this->_errors[] = "{$name}は正しい形式で入力してください。";
            }
        }
    }

    // プライベート変数_errorsにエラー情報が含まれる場合には結合してjsに戻す
    public function __invoke() {
        if (is_array($this->_errors)) {
            if (count($this->_errors) > 0) {
                // エラーメッセージを表示する
                return $this->_errors;
            }
        }
    }
}