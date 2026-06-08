<?php

header('Access-Control-Allow-Origin: *');



// 手动解析查询字符串，获取所有c参数

$query = $_SERVER['QUERY_STRING'];

$cats = [];



// 方法1：直接正则匹配所有 c= 后面的值

preg_match_all('/[?&]c=([^&]+)/', '?' . $query, $matches);

if (!empty($matches[1])) {

    $cats = $matches[1];

}



// 如果没有找到，尝试其他格式

if (empty($cats)) {

    // 处理逗号分隔格式 ?c=a,b,c

    if (isset($_GET['c']) && strpos($_GET['c'], ',') !== false) {

        $cats = explode(',', $_GET['c']);

    } 

    // 处理单个分类

    elseif (isset($_GET['c'])) {

        $cats = [$_GET['c']];

    }

}



$encode = $_GET['encode'] ?? 'json';

$charset = $_GET['charset'] ?? 'utf-8';

$callback = $_GET['callback'] ?? '';

$selector = $_GET['select'] ?? '.hitokoto';

$min_length = intval($_GET['min_length'] ?? 0);

$max_length = intval($_GET['max_length'] ?? 30);



// 远程配置

$remote_base = 'https://github.com/pzwboy/xytxplayersnote/raw/refs/heads/main/notes/';

$available_types = ['a'];



// 如果没有指定分类，使用所有

if (empty($cats)) $cats = $available_types;



// 收集句子

$all_sentences = [];



foreach ($cats as $cat) {

    $cat = trim($cat);

    if (!in_array($cat, $available_types)) continue;

    

    $url = $remote_base . "{$cat}.json";

    $json = @file_get_contents($url);

    

    if ($json === false) continue;

    

    $sentences = json_decode($json, true);

    if (!is_array($sentences)) continue;

    

    foreach ($sentences as $s) {

        $len = mb_strlen($s['hitokoto'], 'utf-8');

        if ($len >= $min_length && $len <= $max_length) {

            $s['length'] = $len;

            $s['reviewer'] = $s['reviewer'] ?? 0;

            $s['commit_from'] = $s['commit_from'] ?? 'api';

            $s['creator_uid'] = intval($s['creator_uid'] ?? 0);

            $s['from_who'] = $s['from_who'] ?? null;

            $all_sentences[] = $s;

        }

    }

}



// 随机返回

if (empty($all_sentences)) {

    $result = ['error' => 'No sentences match'];

} else {

    $result = $all_sentences[array_rand($all_sentences)];

}



// 处理编码

if ($charset === 'gbk') {

    $result = mb_convert_encoding($result, 'gbk', 'utf-8');

    header('Content-Type: text/html; charset=gbk');

}



// 根据encode格式输出

switch ($encode) {

    case 'text':

        header('Content-Type: text/plain; charset=' . $charset);

        echo is_array($result) ? $result['hitokoto'] : $result;

        break;

        

    case 'js':

        header('Content-Type: application/javascript; charset=' . $charset);

        $text = is_array($result) ? $result['hitokoto'] : $result;

        $text = addslashes($text);

        echo "function hitokoto(){document.write('{$text}');}";

        if ($selector !== '.hitokoto') {

            echo "\ndocument.querySelector('{$selector}').innerHTML = '{$text}';";

        }

        break;

        

    case 'json':

    default:

        header('Content-Type: application/json; charset=' . $charset);

        $json = json_encode($result, JSON_UNESCAPED_UNICODE);

        

        if ($callback) {

            header('Content-Type: application/javascript; charset=' . $charset);

            echo "{$callback}({$json});";

        } else {

            echo $json;

        }

        break;

}