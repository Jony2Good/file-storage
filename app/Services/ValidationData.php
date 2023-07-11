<?php

namespace app\Services;

use app\Database\DbRequests;

class ValidationData extends DbRequests
{
    /**
     * @param string $data
     * @return bool
     */
    public static function checkNameData(string $data): bool
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
    public static function checkEmailData(string $data): bool
    {
        $data = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $id
     * @return bool
     */
    public static function checkUser(string $id): bool
    {
        $sql = "SELECT `id` FROM `users` WHERE `id` = :id";
        $data = ['id' => $id];
        $row = DbRequests::read($sql, $data, self::FETCH);
        if (!$row) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function checkEmailExistence(string $email): bool
    {
        $sql = "SELECT * FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        $response = DbRequests::read($sql, $data, self::FETCH_COLUMN);
        if ($response > 1) {
            return false;
        }
        return true;
    }

    /**
     * @param string $email
     * @param string $token
     * @param string $tempPass
     * @return void
     */
    public static function checkTemporaryPassword(string $email, string $token, string $tempPass): void
    {
        $sql = "SELECT `id` FROM `reset_pas` WHERE `email` = :email";
        $data = ['email' => $email];
        $response = self::read($sql, $data, self::FETCH_COLUMN);
        if ($response > 1) {
            $stm = "UPDATE `reset_pas` SET `cookies_token` = '$token', `temporary_pass` = '$tempPass' WHERE `email` = '$email'";
        } else {
            $stm = "INSERT INTO `reset_pas` (`id`, `email`, `cookies_token`,`temporary_pass`) VALUES (null, '$email', '$token', '$tempPass')";
        }
        self::query($stm);
    }

    public static function checkRoles(string $userId): bool
    {
        $sql = "SELECT u.id, r.group_name FROM `users` u INNER JOIN users_roles ur ON u.id = ur.user_id INNER JOIN roles r ON ur.roles_id = r.id WHERE u.id = :user_id AND r.group_name = 'admin'";
        $data = ['user_id' => $userId];
        $statement = self::read($sql, $data, self::FETCH);
        if (!$statement) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $userFile
     * @param string $userId
     * @param int $dirId
     * @return array|bool
     */
    public static function checkFileExistence(string $userFile, string $userId, int $dirId): array|bool
    {
        $sql = "SELECT `user_file_name` FROM `files` WHERE `user_file_name` = :user_file AND `user_id` = :user_id AND `directory_id` = :directory_id";
        $data = ['user_file' => $userFile, 'user_id' => $userId, 'directory_id' => $dirId];
        $row = self::read($sql, $data, self::FETCH_COLUMN);
        if ($row > 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $dirName
     * @param string $userId
     * @return bool
     */
    public static function checkFolderExistence(string $dirName, string $userId): bool
    {
        $sql = "SELECT `name`, `id` FROM `folders` WHERE `name` = :dirName AND `user_id` = :userId";
        $data = ['dirName' => $dirName, 'userId' => $userId];
        $response = self::read($sql, $data, self::FETCH_All);

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

    /**
     * @param string $fileUser
     * @param string $fileId
     * @return string|bool
     */
    public static function checkFileAccess(string $fileUser, string $fileId): string|bool
    {
        $sql = "SELECT `id` FROM `users_files` WHERE `user_id` = :fileUser AND `file_id` = :fileId";
        $data = ['fileUser' => $fileUser, 'fileId' => $fileId];
        return self::read($sql, $data, self::FETCH_COLUMN);
    }

}
