<?php

function removeURL($DELETE)
{
    $data = file("eporner.txt");

    $out = [];

    foreach ($data as $line) {
        if (trim($line) != $DELETE) {
            $out[] = $line;
        }
    }

    $fp = fopen("eporner.txt", "w+");
    flock($fp, LOCK_EX);
    foreach ($out as $line) {
        fwrite($fp, $line);
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    //going to be using append.txt file on my desktop
    // opening the file in append mode
    $appendVar = fopen("eporner.com.txt", "a");
    // writing new lines to the file
    $wit = fwrite($appendVar, $DELETE);
    // Closing the file
    if ($wit) {
    }
    fclose($appendVar);
}

function remotefilesize($url)
{
    // Use the get_headers() function to retrieve the headers of the file
    $headers = get_headers($url, 1);

    // Check if the "Content-Length" header is present in the response
    if (isset($headers["Content-Length"])) {
        // Store the value of the "Content-Length" header in the $filesize variable
        $filesize = $headers["Content-Length"];

        // Output the size of the file
        return $filesize; //"The file size is: " . $filesize . " bytes";
    } else {
        // If the "Content-Length" header is not present, output an error message
        return false; //"Unable to retrieve the size of the file.";
    }
}

function getRedirectUrl($url)
{
    stream_context_set_default([
        "http" => [
            "method" => "HEAD",
        ],
    ]);
    $headers = get_headers($url, 1);
    if ($headers !== false && isset($headers["Location"])) {
        return is_array($headers["Location"])
            ? array_pop($headers["Location"])
            : $headers["Location"];
    }
    return false;
}

function getfinalurl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return $lastUrl;
}

function cleantmp()
{
    $files = glob("tmp/*"); // get all file names
    foreach ($files as $file) {
        // iterate files
        if (is_file($file)) {
            unlink($file); // delete file
        }
    }
}

while (true) {
    cleantmp();

    $lines = file("eporner.txt");
    $i = 1;
    foreach ($lines as $line) {
        $line = trim($line);

        echo "$i). $line\n";
        $url = "$line?click=1";
        $u = getRedirectUrl($url);
        echo "MP4 URL: $u\n";
        $url_components = parse_url($u);
        $old = "fin/" . basename($line);
        // Use parse_str() function to parse the
        // string passed via URL
        parse_str($url_components["query"], $params);

        // Display
        $out = $params["dload"];

        $tmp = "tmp/" . $out;
        $com = "fin/" . $out;
        rename($old, $com);
        if (!empty($line) && !file_exists($com)) {
            $fs = remotefilesize($u);
            echo "Expected Filesize: " . $fs . " b";

            shell_exec(
                "wget -D --span-hosts -U Mozilla '$url' -O " .
                    escapeshellarg($tmp)
            );
            sleep(1);
            if ($fs != false && $fs == filesize($tmp)) {
                echo "Filesize matches\n";
            }
            rename($tmp, $com);
            echo round(filesize($com) / 1000 / 1024, 2) . "MB";
            removeURL($line);

            $i++;
            sleep(60 * 5);
        }
    }
    sleep(60);
}
