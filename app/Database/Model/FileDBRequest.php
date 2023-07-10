<?php

namespace app\Database\Model;

use app\Database\DbRequests;

class FileDBRequest extends DbRequests
{

    /**
     * @param string $newFileName
     * @param string $userId
     * @param int $dirId
     * @param string $userFileName
     * @return void
     */
    public static function createFile(string $newFileName, string $userId, int $dirId, string $userFileName): void
    {
        $sql = "INSERT INTO `files` (`id`, `name`,`user_id`, `directory_id`, `user_file_name`) VALUES (null, :name, :user_id, :directory_id, :user_file_name)";
        $data = ['name' => $newFileName, 'user_id' => $userId, 'directory_id' => $dirId, 'user_file_name' => $userFileName];
        self::write($sql, $data);
    }

    /**
     * @param string $userId
     * @return array<string>
     */
    public static function readFiles(string $userId): array
    {
        $sql = "SELECT f.name as directory, fs.name FROM `folders` f LEFT JOIN files fs ON f.id = fs.directory_id WHERE f.user_id = :userId";
        $data = ['userId' => $userId];
        return self::read($sql, $data, self::FETCH_GROUP);
    }

    /**
     * @param string $userId
     * @param string $fileId
     * @return array|bool
     */
    public static function getFile(string $userId, string $fileId): array|bool
    {
        $sql = "SELECT fls.id, fls.user_file_name, fls.name as file_name, f.id as dir_id , f.name FROM `files` fls LEFT JOIN folders f ON fls.directory_id = f.id WHERE fls.id = :fileId and fls.user_id = :userId";
        $data = ['fileId' => $fileId, 'userId' => $userId];
        return self::read($sql, $data, self::FETCH);
    }

    /**
     * @param string $fileId
     * @return void
     */
    public static function deleteFile(string $fileId): void
    {
        $sql = "DELETE FROM `files` WHERE `id` = :fileId";
        $data = ['fileId' => $fileId];
        self::write($sql, $data);
    }

    /**
     * @param string $fileName
     * @param string $fileId
     * @param string $userId
     * @return void
     */
    public static function updateFile(string $fileName, string $fileId, string $userId): void
    {
        $sql = "UPDATE `files` SET `user_file_name` = :fileName WHERE `id` = :fileId AND `user_id` = :userId";
        $data = ['fileName' => $fileName, 'fileId' => $fileId, 'userId' => $userId];
        self::write($sql, $data);
    }

    /**
     * @param string $userId
     * @param string $fileId
     * @return void
     */
    public static function createFileAccess(string $userId, string $fileId): void
    {
        $sql = "INSERT INTO `users_files` (`id`, `user_id`, `file_id`) VALUES (null, :user_id, :fileId)";
        $data = ['user_id' => $userId, 'fileId' => $fileId];
        self::write($sql, $data);
    }

    /**
     * @param string $userId
     * @param String $fileId
     * @return array|bool
     */
    public static function readShareFiles(string $userId, string $fileId): array|bool
    {
        $sql = "SELECT f.user_file_name, uf.user_id  FROM `users_files` uf LEFT JOIN files f ON f.id = uf.file_id WHERE f.user_id = :userId AND uf.file_id = :fileId";
        $data = ['userId' => $userId, 'fileId' => $fileId];
        return self::read($sql, $data, self::FETCH_GROUP);
    }

    /**
     * @param string $userId
     * @param string $fileId
     * @return void
     */
    public static function deleteAccessFile(string $userId, string $fileId): void
    {
        $sql = "DELETE FROM `users_files` WHERE `file_id` = :fileId AND `user_id` = :userId";
        $data = ['fileId' => $fileId, 'userId' => $userId];
        self::write($sql, $data);
    }


}