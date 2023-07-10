<?php

namespace app\Controllers;


use app\Database\Model\DirectoryDbRequest;
use app\Database\Model\FileDBRequest;
use app\Services\Tokens;
use app\Services\CreateSession;
use app\Services\Interface\SessionService;
use app\Services\ValidationData;

class File extends DirectoryDbRequest
{
    private string $token;
    private string $userId;

    private string $parentDir;
    private string $fileId;
    private string $fileName;

    public function __construct()
    {
    }

    /**
     * @return  SessionService
     */
    private static function startSessionUser(): SessionService
    {
        return new CreateSession();
    }

    private function setData(): void
    {
        $data = self::startSessionUser()->start();
        $this->token = $data['token'];
        $this->userId = $data['id'];
        $this->parentDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    }

    /**
     * @param array $obj
     * @return void
     * @throws \Exception
     */
    private function getJson(array $obj): void
    {
        if (!isset($obj)) {
            throw new \Exception('Bad JSON');
        }
        $this->fileId = $obj['file_id'] ?? '';
        $this->fileName = $obj['file_name'] ?? '';
    }

    /**
     * @param array<string> $data
     * @return void
     */
    public function createDirectory(array $data): void
    {
        $this->setData();
        $directoryName = $data['directory'] ?? '';
        if (!ValidationData::checkNameData($directoryName)) {
            http_response_code(400);
            echo json_encode(array("error" => "Wrong entry information"));
            die();
        }
        if (Tokens::verifyUserToken($this->token)) {
            if (ValidationData::checkFolderExistence($directoryName, $this->userId)) {
                http_response_code(400);
                echo json_encode(array("error" => "Directory '{$directoryName}' is already exist"));
                die();
            } else {
                $contentPath = $this->parentDir . $directoryName;
                DirectoryDbRequest::createDir($this->userId, $directoryName, $contentPath);
                http_response_code(200);
                echo json_encode(array(
                    "dir_name" => $directoryName,
                    "status" => "created"
                ));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $dirId
     * @return void
     */
    public function getDirectory(string $dirId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = DirectoryDbRequest::getDir($dirId, $this->userId);
            if (!$response) {
                http_response_code(400);
                echo json_encode(array("error" => "Directory with id: '{$dirId}' does not exist"));
                die();
            } else {
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $this->userId,
                    "directory_id" => $dirId,
                    "data" => $response
                ));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    public function renameDirectory(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $json = file_get_contents('php://input');
            if (!$json) {
                http_response_code(401);
                echo json_encode(array("error" => "Failed with entry information"));
                die();
            }
            $obj = json_decode($json, true);
            $dirId = $obj['directory_id'] ?? '';
            $newName = $obj['directory_name'] ?? '';
            $contentPath = $this->parentDir . $newName;
            if (empty(DirectoryDbRequest::getDir($dirId, $this->userId))) {
                http_response_code(400);
                echo json_encode(array("error" => "Directory with id: '{$dirId}' does not exist"));
                die();
            } else {
                if (!ValidationData::checkFolderExistence($newName, $this->userId)) {
                    DirectoryDbRequest::updateDir($newName, $contentPath, $this->userId, $dirId);
                    http_response_code(200);
                    echo json_encode(
                        array(
                            "user_id" => $this->userId,
                            "directory_id" => $dirId,
                            "directory_name" => $newName,
                            "path" => $contentPath,
                            "status" => "updated"
                        ));
                } else {
                    http_response_code(400);
                    echo json_encode(array("error" => "Directory '{$newName}' is already exist"));
                    die();
                }
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $dirId
     * @return void
     */
    public function deleteDirectory(string $dirId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = DirectoryDbRequest::getDir($dirId, $this->userId);
            if (!$response) {
                http_response_code(400);
                echo json_encode(array("error" => "File with id: '{$dirId}' does not exist"));
                die();
            } else {
                DirectoryDbRequest::deleteDir($dirId);
                foreach ($response as $item) {
                    if (isset($item['file_name'])) {
                        $file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $item['file_name'];
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
                http_response_code(200);
                echo json_encode(
                    array(
                        "user_id" => $this->userId,
                        "directory_id" => $dirId,
                        "status" => "deleted"
                    )
                );
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param array<string> $dataPost
     * @param array<string> $dataFile
     * @return void
     */
    public function createFile(array $dataPost, array $dataFile): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $dirName = $dataPost['folder'] ?? '';
            if (!ValidationData::checkFolderExistence($dirName, $this->userId)) {
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
                $newFileName = $file['newFileName'];
                $userFileName = $file['oldFileName'];
                $destFilePath = 'uploads/' . $newFileName;

                $dirId = DirectoryDbRequest::getIdDir($dirName, $this->userId);

                if (!is_dir($this->parentDir)) {
                    mkdir($this->parentDir);
                }
                if (!ValidationData::checkFileExistence($userFileName, $this->userId, $dirId['id'])) {
                    echo json_encode(array("error" => "File exists. Rename file"));
                    die();
                }
                try {
                    move_uploaded_file($fileTemp, $destFilePath);
                } catch (\Exception $e) {
                    echo "File upload error: " . $e->getMessage();
                    die();
                }
                FileDBRequest::createFile($newFileName, $this->userId, $dirId['id'], $userFileName);
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $this->userId,
                    "directory_id" => $dirId['id'],
                    "directory_name" => $dirName,
                    "file_name" => $userFileName,
                    "status" => "file created"
                ));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }


    public function getFiles(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::readFiles($this->userId);
            http_response_code(200);
            echo json_encode(array(
                "user_id" => $this->userId,
                "data" => $response
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $fileId
     * @return void
     */
    public function getCurrentFile(string $fileId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::getFile($this->userId, $fileId);
            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "File with id: '{$fileId}' does not exist"));
                die();
            } else {
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $this->userId,
                    "file_id" => $response['id'],
                    "directory" => $response['name'],
                    "response" => $response['user_file_name'],
                ));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $fileId
     * @return void
     */
    public function deleteFile(string $fileId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::getFile($this->userId, $fileId);
            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "file_id: {$fileId} not exist"));
                die();
            } else {
                $userFileName = $response['user_file_name'] ?? "";
                $hashedFileName = $response['file_name'] ?? "";
                $file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $hashedFileName;
                if (file_exists($file)) {
                    unlink($file);
                }
                FileDBRequest::deleteFile($fileId);
                http_response_code(200);
                echo json_encode(array(
                    "user_id" => $this->userId,
                    "file_id" => $fileId,
                    "file_name" => $userFileName,
                    "status" => "deleted"
                ));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @throws \Exception
     */
    public function renameFile(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $json = file_get_contents('php://input');
            if (!$json) {
                http_response_code(401);
                echo json_encode(array("error" => "Failed with entry information"));
                die();
            }
            $obj = json_decode($json, true);
            $this->getJson($obj);

            $response = FileDBRequest::getFile($this->userId, $this->fileId);
            $checkFile = ValidationData::checkFileExistence($response['user_file_name'], $this->userId, $response['dir_id']);

            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "File with id: '{$this->fileId}' does not exist"));
                die();
            } else {
                if ($checkFile) {
                    FileDBRequest::updateFile($this->fileName, $this->fileId, $this->userId);
                    http_response_code(200);
                    echo json_encode(array(
                        "user_id" => $this->userId,
                        "file_id" => $fileId,
                        "status" => "file updated"
                    ));
                } else {
                    http_response_code(400);
                    echo json_encode(array("error" => "File exists. Rename file"));
                    die();
                }
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $fileId
     * @return void
     */
    public function getSharingFiles(string $fileId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::readShareFiles($this->userId, $fileId);
            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "File not exist or file error"));
            } else {
                $newRes = [];
                foreach ($response as $file => $value) {
                    $newRes[] = [
                        'file' => $file,
                        'user_id' => $value
                    ];
                }
                http_response_code(200);
                echo json_encode($newRes);
            }
        }
    }

    /**
     * @param string $fileId
     * @param string $tempUserId
     * @return void
     */
    public function grantAccessFile(string $fileId, string $tempUserId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::getFile($this->userId, $fileId);
            if (!$response) {
                http_response_code(401);
                echo json_encode(array("error" => "File not exist or file error"));
                die();
            } else {
                $res = ValidationData::checkFileAccess($tempUserId, $fileId);
                if ($res > 0) {
                    http_response_code(401);
                    echo json_encode(array("error" => "User {$tempUserId} already has access to the file {$fileId}"));
                    die();
                } else {
                    FileDBRequest::createFileAccess($tempUserId, $fileId);
                    http_response_code(200);
                    echo json_encode(array(
                        "user" => $tempUserId,
                        "file" => $fileId,
                        "file_name" => $response['file_name'],
                        "status" => "file access granted"
                    ));
                }
            }
        }
    }

    /**
     * @param string $fileId
     * @param string $tempUserId
     * @return void
     * @throws \Exception
     */
    public function stopAccessingFile(string $fileId, string $tempUserId): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            if (!FileDBRequest::getFile($this->userId, $fileId)) {
                http_response_code(401);
                echo json_encode(array("error" => "File not exist or file error"));
                die();
            }
            if (!ValidationData::checkFileAccess($tempUserId, $fileId)) {
                http_response_code(401);
                echo json_encode(array("error" => "User {$tempUserId} has not access to the file {$fileId}"));
                die();
            } else {
                FileDBRequest::deleteAccessFile($tempUserId, $fileId);
                http_response_code(200);
                echo json_encode(array(
                    "user" => $tempUserId,
                    "file_id" => $fileId,
                    "status" => "file access terminated"
                ));
            }
        }
    }


}
