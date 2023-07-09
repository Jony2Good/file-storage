<?php

namespace app\Database\Model;

use app\Database\DbRequests;

class DirectoryDbRequest extends DbRequests
{
    /**
     * @param string $id
     * @param string $dirName
     * @param string $path
     * @return void
     */
    public static function createDir(string $id, string $dirName, string $path): void
    {
        $sql = "INSERT INTO `folders` (`id`, `name`, `directory`,`user_id`) VALUES (null, :name, :directory, :id)";
        $data = ['name' => $dirName, 'directory' => $path, 'id' => $id];
        self::write($sql, $data);
    }
    /**
     * @param string $dirName
     * @param string $userId
     * @return array|string
     */
    public static function getIdDir(string $dirName, string $userId): array|string
    {
        $sql = "SELECT `id`, `name` FROM `folders` WHERE `name` = :dirName AND `user_id` = :userId";
        $data = ['dirName' => $dirName, 'userId' => $userId];
        return self::read($sql, $data, self::FETCH);
    }

    /**
     * @param string $dirId
     * @param string $userId
     * @return array|null
     */
    public static function getDir(string $dirId, string $userId): ?array
    {
        $sql = "SELECT f.name as directory, fs.user_file_name, fs.name as file_name  FROM `folders` f LEFT JOIN files fs ON f.id = fs.directory_id WHERE f.id = :dirId AND f.user_id = :user_id";
        $data = ['dirId' => $dirId, 'user_id' => $userId];
        return self::read($sql, $data, self::FETCH_All);
    }

    /**
     * @param string $newName
     * @param string $contentPath
     * @param string $userId
     * @param string $dirId
     * @return void
     */
    public static function updateDir(string $newName, string $contentPath, string $userId, string $dirId): void
    {
        $sql = "UPDATE `folders` SET `name` = :newName, `directory` = :new_directory WHERE `id` = :dirId AND `user_id` = :userId";
        $data = ['newName' => $newName, 'new_directory' => $contentPath, 'userId' => $userId, 'dirId' => $dirId];
        self::write($sql, $data);
    }

    /**
     * @param string $dirId
     * @return void
     */
    public static function deleteDir(string $dirId): void
    {
        $sql = "DELETE FROM `folders` WHERE `id` = :dirId";
        $data = ['dirId' => $dirId];
        self::write($sql, $data);
    }
}
