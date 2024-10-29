<?php

class zemu {

    private function getSongInfoFromAPI($songID) {
        require __DIR__ . "/../../config/zemu.php";
        $apiUrl = "https://zendomusic.ru/API/get-info/song.php?id=" . $songID . "&pass=" . $apikey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return null;
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return $decodedResponse;
    }

    public function songReupload($songID) {
        require __DIR__ . "/../../incl/lib/connection.php";
        require_once __DIR__ . "/../../incl/lib/exploitPatch.php";

        $songInfo = $this->getSongInfoFromAPI($songID);

        if (is_array($songInfo) && isset($songInfo[0]['url'])) {

            $songUrl = $songInfo[0]['url'];
            $song = str_replace("www.dropbox.com", "dl.dropboxusercontent.com", $songUrl);

            if (parse_url($song, PHP_URL_HOST) !== 'zendomusic.ru') {
                return "-5";
            }

            if (filter_var($song, FILTER_VALIDATE_URL) == TRUE && substr($song, 0, 4) == "http") {
                $song = str_replace(["?dl=0", "?dl=1"], "", $song);
                $song = trim($song);

                $query = $db->prepare("SELECT count(*) FROM songs WHERE download = :download");
                $query->execute([':download' => $song]);
                $count = $query->fetchColumn();

                if ($count != 0) {
                    return "-3";
                }

                $name = $songInfo[0]['songname'];
                $author = $songInfo[0]['author'];
                $info = $this->getFileInfo($song);

                if ($info === null) {
                    return "-4";
                }

                $size = $info['size'];

                $size = round($size / 1024 / 1024, 2);
                $hash = "";

                $query = $db->prepare("INSERT INTO songs (name, authorID, authorName, size, download, hash)
                VALUES (:name, '9', :author, :size, :download, :hash)");
                $query->execute([':name' => $name, ':download' => $song, ':author' => $author, ':size' => $size, ':hash' => $hash]);

                return $db->lastInsertId();
            } else {
                return "-2";
            }
        } else {
            return "-1";
        }
    }

    private function getFileInfo($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return null;
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);

        $headers = explode("\r\n", $headers);
        $info = [];

        foreach ($headers as $header) {
            if (strpos($header, ':') !== false) {
                list($key, $value) = explode(':', $header, 2);
                $info[strtolower(trim($key))] = trim($value);
            }
        }

        $info['size'] = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $info['type'] = $info['content-type'] ?? '';

        curl_close($ch);
        return $info;
    }
}

?>