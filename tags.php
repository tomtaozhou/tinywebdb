<h1>TinyWebDB API and sniffing log compare</h1>
<h2> <a href=index.php>HOME</a>  <a href=tags.php>TAGS</a></h2>
<form method="POST" action="">
<?php
setlocale(LC_TIME, "ja_JP");
date_default_timezone_set('Asia/Tokyo');
$listLog = array();
$listTxt = array();
if ($handler = opendir("./")) {
    while (($sub = readdir($handler)) !== FALSE) {
        if (substr($sub, -4, 4) == ".txt") {
            $listTxt[] = $sub;
        } elseif (substr($sub, 0, 10) == "tinywebdb_") {
            $listLog[] = $sub;
        }
    }
    closedir($handler);
}

echo "<h3>TinyWebDB Tags</h3>";
echo "<table border=1>";
echo "<thead><tr>";
echo "<th> </th>";
echo "<th> Tag Name </th>";
echo "<th> Size </th>";
echo "<th> Ver </th>";
echo "<th> localIP </th>";
echo "<th> Temp </th>";
echo "<th> Pres </th>";
echo "<th> Bright </th>";
echo "<th> Clients </th>";
echo "<th> Aps </th>";
echo "<th> localTime </th>";
echo "</tr></thead>\n";
if ($listTxt) {
    $now = time();
    sort($listTxt);
    foreach ($listTxt as $sub) {
	$tagValue = file_get_contents($sub);
	$obj = json_decode($tagValue);
	$tim_stmp = $obj->{'localTime'} - 9*3600;
	if(($now-$tim_stmp) > 7*24*3600){
	    echo "<tr bgcolor=#AAAAAA>";
	} else if(($now-$tim_stmp) > 24*3600){
	    echo "<tr bgcolor=#FFFFAA>";
	} else if(($now-$tim_stmp) > 900){
            echo "<tr bgcolor=#FFAAAA>";
	} else {
	    echo "<tr>";
	}

        echo "<td> <input type=checkbox name='tagList[]' value=" . substr($sub, 0, -4) . "></td>\n";
        echo "<td><a href=tags.php?tag=" . substr($sub, 0, -4) . ">" .substr($sub, 0, -4) . "</a></td>\n";
        echo "<td>" . filesize("./" . $sub) . "</td>\n";
        echo "<td>" . $obj->{'Ver'} . "</td>\n";
        echo "<td>" . $obj->{'localIP'} . "</td>\n";
        echo "<td>" . $obj->{'temperature'} . "</td>\n";
        echo "<td>" . $obj->{'pressure_hpa'} . "</td>\n";
        echo "<td>" . $obj->{'bright_lux'} . "</td>\n";
        echo "<td>" . $obj->{'clients'} . "</td>\n";
        echo "<td>" . $obj->{'aps'} . "</td>\n";
        echo "<td>" . strftime("%D %T", (int)$tim_stmp) . "</td>\n";
        echo "</tr>";
    }
}
echo "</table>";
echo "<input type=submit value=submit>";
echo "</form>";

$macdb = json_decode(file_get_contents("mac.db"));

if (isset($_POST['macName'])) {
    $mac = $_POST['macAddr'];
    $macdb->{$mac} = $_POST['macName'];
    $fh = fopen("mac.db", "w") or die("check file write permission.");
    fwrite($fh, json_encode($macdb));
    fclose($fh);
} elseif (isset($_GET['mac'])) {
    $mac = $_GET['mac'];
    echo "<h3>MAC db form</h3>";
    echo "<form method='POST' action=''>";
    echo "<input type='hidden' name='macAddr' value=" . $mac . ">"; 
    echo "<input type='text' name='macName' value='" . $macdb->{$mac} . "'>"; 
    echo "<input type='submit' value='submit'>";
    echo "</form>";
    exit;
}

if (isset($_POST['tagList'])) {
    $tagList = $_POST['tagList'];
} elseif (isset($_GET['tag'])) {
    $tagList[] = $_GET['tag'];
}

if (isset($tagList)) {
    echo "<h3>TinyWebDB MAC List</h3>";
    echo "<table border=1>";

    echo "<tr>";
    echo "<th> MAC <br> Last update </th>";
    $max_clients = 0;
    foreach($tagList as $tagName) {
	$tagValue = file_get_contents($tagName . ".txt");
    	$obj = json_decode($tagValue);
	$clientArray[$tagName] = $obj->{'clientList'};
	if ($max_clients < $obj->{'clients'}) {
	    $max_clients = $obj->{'clients'};
	    $pattern = "/" . substr($tagName, -6, 6) . "$/";
    	    $clientList = $obj->{'clientList'};
	}
	$tim_stmp = $obj->{'localTime'} - 9*3600;
	echo "<td> " . $tagName . "<br>" . strftime("%D %T", (int)$tim_stmp) . "</td>";
    }
    echo "</tr>\n";

    // find max_clients WiFi senser mac & add to $clientList 
    foreach($tagList as $tagName) {
	$macs = preg_grep($pattern, array_keys((array)$clientArray[$tagName]));
	if (!is_null(key($macs))) {
	    $mac = $macs[key($macs)];
	    $clientList->{$mac} = "";
	}
    }

    $clients = (array)$clientList;
    ksort($clients);
    $colors = array("purple","green","orange","blue","yellow","pink","red","brown","gold","silver");
    foreach ($clients as $mac => $rssi ) {
	if ($rssi <= -80) continue;
        echo "<tr> ";
	$macs = preg_grep("/" . substr($mac, -6, 6) . "$/", $tagList);
	if (!is_null(key($macs))) { echo "<td bgcolor=red>"; }
	elseif(substr($mac, 0, 6) == "2c3ae8"){ echo "<td bgcolor=yellow>"; } 
	else { echo "<td>"; }
        echo "<a href=tags.php?mac=$mac>" . $mac . "</a>(";
	if (array_key_exists($mac, $macdb)) echo $macdb->{$mac};
	echo ")</td>";
    	foreach($tagList as $tagName) {
	    if (array_key_exists($mac, $clientArray[$tagName])) {
		$rssi = $clientArray[$tagName]->{$mac};
		$color = $colors[($rssi + 99) /10 ];
                echo "<td bgcolor=$color>" . $rssi . "</td>";
	    } else echo "<td></td>";
	}
        echo "</tr>\n";
    }
    echo "</table>";

    exit;
}
