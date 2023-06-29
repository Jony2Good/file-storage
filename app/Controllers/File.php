<?php

namespace Controllers;

use app\Database\Database;
use app\Services\CheckTokens;
use app\Services\Roles;
use app\Services\ValidationData;

class File
{
    /**
     * @param array<string> $data
     * @return void
     */
    public function createDirectory(array $data): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        $directoryName = $data['directory'] ?? '';
        if (!ValidationData::filterNameData($directoryName)) {
            http_response_code(400);
            echo json_encode(array("message" => "Error with entry information"));
            die();
        }
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("message" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT `name` FROM `folders` WHERE `name` = :name AND `user_id` = '$id'";
            $statement = $db->prepare($sql);
            $statement->execute(['name' => $directoryName]);
            $response = $statement->fetchColumn();
            if ($response || $response > 1) {
                http_response_code(400);
                echo json_encode(array("message" => "Error! Folder '{$directoryName}' is already exist"));
                die();
            } else {
                $parentDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                $contentPath = $parentDir . $directoryName;

                $sql = "INSERT INTO `folders` (`id`, `name`, `directory`,`user_id`) VALUES (null, :name, :directory, '$id')";
                $statement = $db->prepare($sql);
                $statement->execute(['name' => $directoryName, 'directory' => $contentPath]);

                $res = $db->query("SELECT `id`, `user_id` FROM `folders` WHERE `user_id` = '$id' AND `directory` = '$contentPath'");
                $row = $res->fetch(\PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $row['user_id'],
                    "folder_id" => $row['id'],
                    "name" => $directoryName,
                    "status" => "created"
                ));
            }
        }
    }

    /**
     * @param string $folderId
     * @return void
     */
    public function getDirectory(string $folderId): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';

        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("message" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT `id`, `name` FROM `folders` WHERE `id` = :folderId";
            $statement = $db->prepare($sql);
            $statement->execute(['folderId' => $folderId]);
            $response = $statement->fetch(\PDO::FETCH_ASSOC);
            if (!$response) {
                http_response_code(400);
                echo json_encode(array("message" => "Error! Folder with id: '{$folderId}' does not exist"));
                die();
            } else {
                $resName = $response['name'];
                $sql = "SELECT fl.user_file_name as file_name FROM `folders` f INNER JOIN folders_files ffs ON f.id = ffs.folders_id AND f.name = '$resName' AND f.user_id = '$id' AND f.id = '$folderId' LEFT JOIN files fl ON ffs.files_id = fl.id AND fl.user_id = '$id'";
                $statement = $db->prepare($sql);
                $statement->execute();
                $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
                if (empty($response)) {
                    exit(json_encode(array("message" => "Wrong id directory")));
                }
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $id,
                    "directory_id" => $folderId,
                    "directory_name" => $resName,
                    "files" => $response
                ));
            }
        }
    }

    public function renameDirectory(): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("error" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $json = file_get_contents('php://input');
            if (!$json) {
                http_response_code(401);
                echo json_encode(array("error" => "Failed with entry information"));
                die();
            }
            $obj = json_decode($json, true);
            $folderId = $obj['id'] ?? '';
            $newName = $obj['name'] ?? '';
            $userId = $_SESSION['user_data']['userId'] ?? '';

            $parentDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            $contentPath = $parentDir . $newName;

            $sql = "UPDATE `folders` SET `name` = :newName, `directory` = :new_directory WHERE `id` = :folderId AND `user_id` = '$id'";
            $statement = $db->prepare($sql);
            $statement->execute(['newName' => $newName, 'new_directory' => $contentPath, 'folderId' => $folderId]);

            http_response_code(200);
            echo json_encode(
                array(
                    "user_id" => $userId,
                    "directory_id" => $folderId,
                    "directory_name" => $newName,
                    "path" => $contentPath,
                    "status" => "updated"
                )
            );
        }
    }

    /**
     * @param string $data
     * @return void
     */
    public function deleteDirectory(string $data): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("message" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT f.name as directory, f.id, fs.name FROM `folders` f LEFT JOIN files fs ON f.id = fs.directory_id WHERE f.user_id = '$id' AND f.id = :data";
            $statement = $db->prepare($sql);
            $statement->execute(['data' => $data]);
            $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($response)) {
                http_response_code(401);
                echo json_encode(
                    array(
                        "user_id" => $id,
                        "error" => "directory_id: {$data} not exist"
                    )
                );
                die();
            } else {
                $sql = "DELETE FROM `folders` WHERE `id` = :data";
                $statement = $db->prepare($sql);
                $statement->execute(['data' => $data]);
                foreach ($response as $item) {
                    $file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $item['name'];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                http_response_code(200);
                echo json_encode(
                    array(
                        "user_id" => $id,
                        "directory_id" => $data,
                        "directory_name" => $response['0']['directory'],
                        "status" => "deleted"
                    )
                );
            }
        }
    }

    /**
     * @param array<string> $dataPost
     * @param array<string> $dataFile
     * @return void
     */
    public function createFile(array $dataPost, array $dataFile): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        $fileDirectory = $dataPost['folder'] ?? '';

        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("error" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            if (!ValidationData::checkFolderExistence($db, $fileDirectory, $id)) {
                echo json_encode(array("error" => "Directory does not exists. Set file directory"));
                die();
            }
            $file = ValidationData::filterFiles($dataFile);
            if (!$file) {
                http_response_code(400);
                echo json_encode(array("error" => "File error"));
                die();
            } else {
                $fileTemp = $dataFile['file']['tmp_name'];
                $path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                $destPath = 'uploads/';
                $newFileName = $file['newFileName'];
                $userFileName = $file['oldFileName'];
                $destFilePath = $destPath . $newFileName;
                $dirId = self::getDirectoryId($db, $id, $fileDirectory);

                if (!is_dir($path)) {
                    mkdir($path);
                }
                if (!ValidationData::checkFileExistence($db, $userFileName, $id, $dirId)) {
                    echo json_encode(array("error" => "File exists. Rename file"));
                    die();
                };
                try {
                    move_uploaded_file($fileTemp, $destFilePath);
                } catch (\Exception $e) {
                    echo "File upload error: " . $e->getMessage();
                    die();
                }
                $sql = "INSERT INTO `files` (`id`, `name`,`user_id`, `directory_id`, `user_file_name`) VALUES (null, :name, :user_id, :directory_id, :user_file_name)";
                $statement = $db->prepare($sql);
                $statement->execute(['name' => $newFileName, 'user_id' => $id, 'directory_id' => $dirId, 'user_file_name' => $userFileName]);
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $id,
                    "directory_id" => $dirId,
                    "file_name" => $userFileName,
                    "status" => "file created"
                ));
            }
        }
    }

    /**
     * @param \PDO $db
     * @param string $id
     * @param string $directoryName
     * @return string
     */
    private static function getDirectoryId(\PDO $db, string $id, string $directoryName): string
    {
        $sql = "SELECT `id` FROM `folders` WHERE `name` = :directoryName AND `user_id` = :id";
        $statement = $db->prepare($sql);
        $statement->execute(['directoryName' => $directoryName, 'id' => $id]);
        $response = $statement->fetch(\PDO::FETCH_ASSOC);
        return $response['id'];
    }

    public function getFiles(): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("error" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT f.name as directory, fs.name FROM `folders` f LEFT JOIN files fs ON f.id = fs.directory_id WHERE f.user_id = '$id'";
            $statement = $db->query($sql);
            $response = $statement->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);
            http_response_code(200);
            echo json_encode(array(
                "user_id" => $id,
                "response" => $response
            ));
        }
    }

    /**
     * @param string $data
     * @return void
     */
    public function getCurrentFile(string $data): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("error" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT fls.id, fls.user_file_name, f.name FROM `files` fls LEFT JOIN folders f ON fls.directory_id = f.id WHERE fls.id = :fileId and fls.user_id = '$id'";
            $statement = $db->prepare($sql);
            $statement->execute(['fileId' => $data]);
            $response = $statement->fetch(\PDO::FETCH_ASSOC);
            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "File with id: '{$data}' does not exist"));
                die();
            } else {
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $id,
                    "file_id" => $response['id'],
                    "directory" => $response['name'],
                    "response" => $response['user_file_name'],
                ));
            }
        }
    }

    /**
     * @param string $data
     * @return void
     */
    public function deleteFile(string $data): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("error" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $sql = "SELECT `name`, `user_file_name` FROM `files` WHERE `user_id` = '$id' AND `id` = :data";
            $statement = $db->prepare($sql);
            $statement->execute(['data' => $data]);
            $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $userFileName = $response[0]['user_file_name'] ?? "";
            $hashedFileName = $response[0]['name'] ?? "";
            if (empty($response)) {
                http_response_code(401);
                echo json_encode(
                    array(
                        "user_id" => $id,
                        "error" => "file_id: {$data} not exist"
                    )
                );
                die();
            } else {
                $file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $hashedFileName;
                if (file_exists($file)) {
                    unlink($file);
                }
                $sql = "DELETE FROM `files` WHERE `id` = :data";
                $statement = $db->prepare($sql);
                $statement->execute(['data' => $data]);

                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $id,
                    "file_id" => $data,
                    "file_name" => $userFileName,
                    "status" => "deleted"
                ));
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function changeFile(): void
    {
        session_start();
        $db = Database::connect();
        $id = $_SESSION['user_data']['userId'] ?? '';
        $token = $_SESSION['user_data']['sid'] ?? '';
        if (!ValidationData::checkUser($db, $id)) {
            http_response_code(401);
            echo json_encode(array("message" => "User not exist"));
            die();
        }
        if (CheckTokens::verifyUserToken($db, $token)) {
            $json = file_get_contents('php://input');
            if (!$json) {
                http_response_code(401);
                echo json_encode(array("error" => "Failed with entry information"));
                die();
            }
            $obj = json_decode($json, true);
            if(!isset($obj)) {
                throw new \Exception('Bad JSON');
            }
            $newDirectoryName = $obj['directory'] ?? '';
            $fileName = $obj['file_name'] ?? '';
            $fileId = $obj['file_id'] ?? '';
            if (!empty($newDirectoryName)) {
                $sql = "SELECT `id`, `name` FROM `folders` WHERE `name` = :newDirectoryName AND `user_id` = '$id'";
                $statement = $db->prepare($sql);
                $statement->execute(['newDirectoryName' => $newDirectoryName]);
                $response = $statement->fetch(\PDO::FETCH_ASSOC);
                if ($response) {
                    $directoryId = $response['id'];
                    $directoryName = $response['name'];
                    $sql = "UPDATE `files` SET `directory_id` = '$directoryId' WHERE `user_file_name` = :fileName AND `user_id` = '$id'";
                    $statement = $db->prepare($sql);
                    $statement->execute(['fileName' => $fileName]);
                    http_response_code(200);
                    echo json_encode(array(
                        "user_id" => $id,
                        "directory_id" => $directoryId,
                        "directory_name" => $directoryName,
                        "file" => $fileName,
                        "status" => "updated"
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("error" => "Directory not exist"));
                }
            }
            if (!empty($fileId) && !empty($fileName)) {
                $sql = "SELECT `id`, `user_file_name` as name FROM `files` WHERE `id` = :fileId AND `user_id` = '$id'";
                $statement = $db->prepare($sql);
                $statement->execute(['fileId' => $fileId]);
                $response = $statement->fetch(\PDO::FETCH_ASSOC);
                if ($response) {
                    $oldFileName = $response['name'];
                    $checkId = $response['id'];
                    $sql = "UPDATE `files` SET `user_file_name` = :fileName WHERE `id` = ' $checkId' AND `user_id` = '$id'";
                    $statement = $db->prepare($sql);
                    $statement->execute(['fileName' => $fileName]);
                    http_response_code(200);
                    echo json_encode(array(
                        "user_id" => $id,
                        "file_id" => $checkId,
                        "file" => $oldFileName . " updated on " . $fileName,
                        "status" => "success"
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("error" => "File not exist or file error"));
                }
            }
        }
    }
}
