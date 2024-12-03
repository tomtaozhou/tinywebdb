<?php
setlocale(LC_TIME, "ja_JP");
date_default_timezone_set('Asia/Tokyo');

// 定义本地状态文件和 UPOD Connection Account 配置文件
define('SYNC_STATE_FILE', 'sync_state.json'); // 记录同步状态
define('UPOD_CONNECTION_FILE', 'upod_connection_accounts.json'); // UPOD 连接账户配置文件

// 加载同步状态
function load_sync_state() {
    if (file_exists(SYNC_STATE_FILE)) {
        return json_decode(file_get_contents(SYNC_STATE_FILE), true);
    }
    return [];
}

// 保存同步状态
function save_sync_state($state) {
    file_put_contents(SYNC_STATE_FILE, json_encode($state));
}

// 加载 UPOD Connection Account
function load_upod_connections() {
    if (file_exists(UPOD_CONNECTION_FILE)) {
        return json_decode(file_get_contents(UPOD_CONNECTION_FILE), true);
    }
    return [];
}

// 保存 UPOD Connection Account
function save_upod_connections($connections) {
    file_put_contents(UPOD_CONNECTION_FILE, json_encode($connections));
}

// 检查数据是否变化
function has_changed($tagName, $currentData, $syncState) {
    if (!isset($syncState[$tagName])) {
        return true; // 如果标签未同步过，认为有变化
    }
    return $syncState[$tagName] !== $currentData; // 比较当前数据和已同步数据
}

// 发送数据到 WordPress
function send_to_wordpress($upod, $title, $content) {
    $url = $upod['url'];
    $auth = base64_encode($upod['username'] . ':' . $upod['password']);

    $data = [
        'title'   => $title,
        'content' => $content,
        'status'  => 'publish', // 发布文章状态
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Basic $auth\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        echo "<p>Error posting to WordPress: $url</p>";
        return false;
    }
    return true;
}

// 初始化变量
$syncState = load_sync_state();
$upodConnections = load_upod_connections();
$listTxt = array_diff(scandir("./"), ['.', '..']); // 获取所有文件

// 遍历文件并检测变化
foreach ($listTxt as $file) {
    if (substr($file, -4) !== ".txt") continue; // 跳过非 .txt 文件

    $tagName = substr($file, 0, -4);
    $tagValue = file_get_contents($file);
    $obj = json_decode($tagValue);

    // 构建当前数据
    $currentData = [
        'Size'      => filesize($file),
        'Ver'       => $obj->Ver ?? '',
        'localIP'   => $obj->localIP ?? '',
        'Temp'      => $obj->temperature ?? '',
        'Pres'      => $obj->pressure_hpa ?? '',
        'Bright'    => $obj->bright_lux ?? '',
        'Clients'   => $obj->clients ?? '',
        'Aps'       => $obj->aps ?? '',
        'localTime' => $obj->localTime ?? '',
    ];

    // 检测数据是否变化
    if (has_changed($tagName, $currentData, $syncState)) {
        echo "<p>Data changed for Tag: $tagName</p>";

        // 构造 WordPress 内容
        $postContent = "<table border=1>";
        $postContent .= "<tr><td>Tag Name</td><td>$tagName</td></tr>";
        foreach ($currentData as $key => $value) {
            $postContent .= "<tr><td>$key</td><td>$value</td></tr>";
        }
        $postContent .= "</table>";

        // 发送数据到 WordPress
        if (isset($upodConnections[$tagName])) {
            $upod = $upodConnections[$tagName];
            send_to_wordpress($upod, "Tag Updated: $tagName", $postContent);
        } else {
            echo "<p>No UPOD Connection Account for Tag: $tagName</p>";
        }

        // 更新同步状态
        $syncState[$tagName] = $currentData;
    }
}

// 保存同步状态
save_sync_state($syncState);

// UPOD Connection Account 表单
if (isset($_GET['connect_tag'])) {
    $tagName = $_GET['connect_tag'];
    $existingConnection = $upodConnections[$tagName] ?? ['url' => '', 'username' => '', 'password' => ''];

    echo "<h3>UPOD Connection Account for Tag: $tagName</h3>";
    echo "<form method='POST' action='tags.php'>";
    echo "<input type='hidden' name='tagName' value='$tagName'>";
    echo "<p>UPOD URL: <input type='text' name='url' value='{$existingConnection['url']}'></p>";
    echo "<p>Username: <input type='text' name='username' value='{$existingConnection['username']}'></p>";
    echo "<p>Password: <input type='password' name='password' value='{$existingConnection['password']}'></p>";
    echo "<button type='submit' name='save_upod'>Save</button>";
    echo "<button type='submit' name='remove_upod'>Remove</button>"; // 删除按钮
    echo "</form>";
    exit;
}

// 保存新的 UPOD Connection Account
if (isset($_POST['save_upod'])) {
    $tagName = $_POST['tagName'];
    $upodConnections[$tagName] = [
        'url'      => $_POST['url'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
    ];
    save_upod_connections($upodConnections);
    header("Location: tags.php");
    exit;
}

// 删除 UPOD Connection Account
if (isset($_POST['remove_upod'])) {
    $tagName = $_POST['tagName'];
    unset($upodConnections[$tagName]);
    save_upod_connections($upodConnections);
    header("Location: tags.php");
    exit;
}

// 手动同步逻辑
if (isset($_POST['sync_all_to_wp'])) {
    // 将所有数据发送到 WordPress
    foreach ($syncState as $tagName => $data) {
        $postContent = "<table border=1>";
        $postContent .= "<tr><td>Tag Name</td><td>$tagName</td></tr>";
        foreach ($data as $key => $value) {
            $postContent .= "<tr><td>$key</td><td>$value</td></tr>";
        }
        $postContent .= "</table>";

        if (isset($upodConnections[$tagName])) {
            $upod = $upodConnections[$tagName];
            send_to_wordpress($upod, "Manual Sync: $tagName", $postContent);
        }
    }
    echo "<p>All data synchronized to WordPress manually!</p>";
}

// 手动同步某个 Tag
if (isset($_POST['sync_tag_to_wp'])) {
    $tagName = $_POST['sync_tag_to_wp'];
    $data = $syncState[$tagName];
    $postContent = "<table border=1>";
    $postContent .= "<tr><td>Tag Name</td><td>$tagName</td></tr>";
    foreach ($data as $key => $value) {
        $postContent .= "<tr><td>$key</td><td>$value</td></tr>";
    }
    $postContent .= "</table>";

    if (isset($upodConnections[$tagName])) {
        $upod = $upodConnections[$tagName];
        send_to_wordpress($upod, "Manual Sync: $tagName", $postContent);
    }
    echo "<p>Data for $tagName synchronized to WordPress manually!</p>";
}

// 显示所有 Tag 数据
echo "<h1>TinyWebDB</h1>";
echo "<h2><a href=index.php>HOME</a>  <a href=tags.php>TAGS</a></h2>";

echo "<form method='POST' action='tags.php'>";
echo "<button type='submit' name='sync_all_to_wp'>Sync All Tags to UPOD</button>";
echo "</form>";

echo "<table border=1>";
echo "<thead><tr>";
echo "<th>Tag Name</th><th>Size</th><th>Ver</th><th>localIP</th>";
echo "<th>Temp</th><th>Pres</th><th>Bright</th><th>Clients</th><th>Aps</th><th>localTime</th><th>UPOD Connection Account</th><th>Action</th>";
echo "</tr></thead>";

foreach ($syncState as $tagName => $data) {
    echo "<tr>";
    echo "<td>$tagName</td>";
    foreach ($data as $key => $value) {
        echo "<td>$value</td>";
    }
    if (isset($upodConnections[$tagName])) {
        echo "<td>Connected to: {$upodConnections[$tagName]['url']}</td>";
        echo "<td><a href='tags.php?connect_tag=$tagName'>Edit</a></td>";
    } else {
        echo "<td><a href='tags.php?connect_tag=$tagName'>Add UPOD Connection Account</a></td>";
        echo "<td></td>";
    }
    echo "</tr>";
}

echo "</table>";
?>
