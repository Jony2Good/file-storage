<?php

namespace app\Services;

class ValidationData
{
    /**
     * @param string $data
     * @return bool
     */
    public static function filterNameData(string $data): bool
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        if (!filter_var($data, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^[a-zA-Z\s]+$/")))) {
            return false;
        }
        return true;
    }

    /**
     * @param string $data
     * @return bool
     */
    public static function filterEmailData(string $data): bool
    {
        $data = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param \PDO $db
     * @param string $id
     * @return bool
     */
    public static function checkUser(\PDO $db, string $id): bool
    {
        $sql = "SELECT `id` FROM `users` WHERE `id` = :id";
        $statement = $db->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetchColumn();
        if (!$row) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param \PDO $db
     * @param string $email
     * @return bool
     */
    public static function checkEmailExistence(\PDO $db, string $email): bool
    {
        $sql = "SELECT * FROM `users` WHERE `email` = :email";
        $statement = $db->prepare($sql);
        $statement->execute(['email' => $email]);
        $response = $statement->fetchColumn();
        if ($response > 1) {
            return false;
        }
        return true;
    }

    /**
     * @param \PDO $db
     * @param string $userFile
     * @param string $id
     * @param string $dirId
     * @return bool
     */
    public static function checkFileExistence(\PDO $db, string $userFile, string $id, string $dirId): bool
    {
        $sql = "SELECT `user_file_name` FROM `files` WHERE `user_file_name` = :user_file AND `user_id` = '$id' AND `directory_id` = :directory_id";
        $statement = $db->prepare($sql);
        $statement->execute(['user_file' => $userFile, 'directory_id' => $dirId]);
        $row = $statement->fetchColumn();
        if ($row > 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param \PDO $db
     * @param string $folder
     * @param string $id
     * @return bool
     */
    public static function checkFolderExistence(\PDO $db, string $folder, string $id): bool
    {
        $sql = "SELECT `name`, `id` FROM `folders` WHERE `name` = :folder AND `user_id` = :id";
        $statement = $db->prepare($sql);
        $statement->execute(['folder' => $folder, 'id' => $id]);
        $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($response)) {
            return false;
        }
        return true;
    }

    /**
     * @param array<string> $file
     * @return false|array<string, false>
     */
    public static function filterFiles(array $file): false|array
    {
        if ($file['file']['error'] > 1) {
            return false;
        }
        $fileName = $file['file']['name'];
        $fileSize = $file['file']['size'];

        $fileExtension = self::getPathInfo($fileName);

        $baseName = preg_replace("/^[a-zA-Zа-яёА-ЯЁ]+$/u", "_", $fileExtension['filename']);
        $newFileName = md5(time() . $baseName) . "." . $fileExtension['extension'];

        $notAlowedExtension = ['phtml', 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps', 'cgi', 'pl', 'asp',
            'aspx', 'shtml', 'shtm', 'htaccess', 'htpasswd', 'ini', 'log', 'sh', 'js', 'html',
            'htm', 'css', 'sql', 'spl', 'scgi', 'fcgi'];

        if (in_array($fileExtension, $notAlowedExtension) && $fileSize < 2147483647) {
            return false;
        }
        return [
            'newFileName' => $newFileName,
            'oldFileName' => $fileName,
            'type' => $fileExtension['extension']
        ];
    }

    /**
     * @param string $filepath
     * @return array<string>
     */
    private static function getPathInfo(string $filepath): array
    {
        $arr = [];
        preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $filepath, $m);
        if ($m[1]) {
            $arr['dirname'] = $m[1];
        }
        if ($m[2]) {
            $arr['basename'] = $m[2];
        }
        if ($m[5]) {
            $arr['extension'] = $m[5];
        }
        if ($m[3]) {
            $arr['filename'] = $m[3];
        }
        return $arr;
    }

}
