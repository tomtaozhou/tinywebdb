<?php
setlocale(LC_TIME, "ja_JP");
date_default_timezone_set('Asia/Tokyo');

// WordPress API 配置
define('WP_API_URL', 'https://example.com/wp-json/wp/v2/posts');
define('WP_USERNAME', 'example_user');
define('WP_PASSWORD', 'example_password');

// 本地状态文件，用于记录最后同步的标签数据
define('SYNC_STATE_FILE', 'sync_state.json');

// 发送数据到 WordPress 函数
function send_to_wordpress($title, $content) {
    $url = WP_API_URL;

    $auth = base64_encode(WP_USERNAME . ':' . WP_PASSWORD);

    $data = array(
        'title'   => $title,
        'content' => $content,
        'status'  => 'publish', // 发布文章状态
    );

    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Basic $auth\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ),
    );

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        die('Error posting to WordPress');
    }

    return $result;
}

// 加载上次同步状态
function load_sync_state() {
    if (file_exists(SYNC_STATE_FILE)) {
        return json_decode(file_get_contents(SYNC_STATE_FILE), true);
    }
    return array();
}

// 保存同步状态
function save_sync_state($state) {
    file_put_contents(SYNC_STATE_FILE, json_encode($state));
}

// 检查数据是否变化
function has_changed($tagName, $currentData, $syncState) {
    if (!isset($syncState[$tagName])) {
        return true; // 如果标签未同步过，认为有变化
    }
    return $syncState[$tagName] !== $currentData; // 比较当前数据和已同步数据
}

// 遍历文件，生成表格内容并检测变化
$listTxt = array();
if ($handler = opendir("./")) {
    while (($sub = readdir($handler)) !== FALSE) {
        if (substr($sub, -4, 4) == ".txt") {
            $listTxt[] = $sub;
        }
    }
    closedir($handler);
}

// 加载同步状态
$syncState = load_sync_state();
$content = "<h3>TinyWebDB Tags</h3>";
$content .= "<table border=1>";
$content .= "<thead><tr>";
$content .= "<th>Tag Name</th><th>Size</th><th>Ver</th><th>localIP</th>";
$content .= "<th>Temp</th><th>Pres</th><th>Bright</th><th>Clients</th><th>Aps</th><th>localTime</th>";
$content .= "</tr></thead>";

if ($listTxt) {
    sort($listTxt);
    foreach ($listTxt as $sub) {
        $tagName = substr($sub, 0, -4);
        $tagValue = file_get_contents($sub);
        $obj = json_decode($tagValue);

        $currentData = array(
            'Size'      => filesize("./" . $sub),
            'Ver'       => $obj->{'Ver'},
            'localIP'   => $obj->{'localIP'},
            'Temp'      => $obj->{'temperature'},
            'Pres'      => $obj->{'pressure_hpa'},
            'Bright'    => $obj->{'bright_lux'},
            'Clients'   => $obj->{'clients'},
            'Aps'       => $obj->{'aps'},
            'localTime' => $obj->{'localTime'},
        );

        // 检查是否有变化
        if (has_changed($tagName, $currentData, $syncState)) {
            // 如果有变化，构造 WordPress 内容并发送
            $postContent = "<table border=1>";
            $postContent .= "<tr><td>Tag Name</td><td>$tagName</td></tr>";
            foreach ($currentData as $key => $value) {
                $postContent .= "<tr><td>$key</td><td>$value</td></tr>";
            }
            $postContent .= "</table>";
            send_to_wordpress("Tag Updated: $tagName", $postContent);

            // 更新同步状态
            $syncState[$tagName] = $currentData;
        }

        // 构造 HTML 表格
        $content .= "<tr>";
        $content .= "<td>$tagName</td>";
        $content .= "<td>" . $currentData['Size'] . "</td>";
        $content .= "<td>" . $currentData['Ver'] . "</td>";
        $content .= "<td>" . $currentData['localIP'] . "</td>";
        $content .= "<td>" . $currentData['Temp'] . "</td>";
        $content .= "<td>" . $currentData['Pres'] . "</td>";
        $content .= "<td>" . $currentData['Bright'] . "</td>";
        $content .= "<td>" . $currentData['Clients'] . "</td>";
        $content .= "<td>" . $currentData['Aps'] . "</td>";
        $content .= "<td>" . strftime("%D %T", (int)$currentData['localTime']) . "</td>";
        $content .= "</tr>";
    }
}

// 保存同步状态
save_sync_state($syncState);

$content .= "</table>";

// 手动同步逻辑
if (isset($_POST['sync_to_wp'])) {
    // 将数据发送到 WordPress
    $response = send_to_wordpress("Manual Sync: TinyWebDB Tags", $content);
    echo "<p>Data synchronized to WordPress manually!</p>";
}

// HTML 输出
echo "<h1>TinyWebDB API and Sniffing Log Compare</h1>";
echo "<h2><a href=index.php>HOME</a>  <a href=tags.php>TAGS</a></h2>";

// 手动同步按钮
echo "<form method='POST' action=''>";
echo "<button type='submit' name='sync_to_wp'>Sync to UPOD</button>";
echo "</form>";

echo $content;
?>
