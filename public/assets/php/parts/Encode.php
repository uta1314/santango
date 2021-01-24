<?php
function e(string $str, string $charset = 'UTF-8'): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, $charset);
}