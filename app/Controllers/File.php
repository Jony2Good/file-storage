<?php

namespace app\Controllers;

use app\Database\Model\DirectoryDbRequest;
use app\Database\Model\FileDBRequest;
use app\HTTP\Response\ServerResponse;
use app\Services\Tokens;
use app\Services\CreateSession;
use app\Services\Interface\SessionService;
use app\Services\ValidationData;

class File
{
    private string $token;
    private string $userId;

    private string $parentDir;
    private string $dirId;
    private string $dirName;
    private string $fileId;
    private string $fileName;

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
     * @return void
     * @throws \Exception
     */
    private function getJson(): void
    {
        $json = file_get_contents('php://input');
        if (!$json) {
            ServerResponse::createResponse(1, 400);
            die();
        }
        $obj = json_decode($json, true);
        if (!isset($obj)) {
            throw new \Exception('Bad JSON');
        }
        $this->fileId = $obj['file_id'] ?? '';
        $this->fileName = $obj['file_name'] ?? '';
        $this->dirId = $obj['directory_id'] ?? '';
        $this->dirName = $obj['directory_name'] ?? '';
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
            ServerResponse::createResponse(1, 400);
            die();
        }
        if (Tokens::verifyUserToken($this->token)) {
            if (ValidationData::checkFolderExistence($directoryName, $this->userId)) {
                ServerResponse::createResponse(2, 400);
                die();
            } else {
                $contentPath = $this->parentDir . $directoryName;
                DirectoryDbRequest::createDir($this->userId, $directoryName, $contentPath);
                ServerResponse::createResponseList([
                    "dir_name" => $directoryName,
                    "status" => "created"
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(4, 400);
                die();
            } else {
                ServerResponse::createResponseList([
                    "user_id" => $this->userId,
                    "directory_id" => $dirId,
                    "data" => $response
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }

    /**
     * @throws \Exception
     */
    public function renameDirectory(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $this->getJson();
            $contentPath = $this->parentDir . $this->dirName;
            if (empty(DirectoryDbRequest::getDir($this->dirId, $this->userId))) {
                ServerResponse::createResponse(4, 400);
                die();
            } else {
                if (!ValidationData::checkFolderExistence($this->dirName, $this->userId)) {
                    DirectoryDbRequest::updateDir($this->dirName, $contentPath, $this->userId, $this->dirId);
                    ServerResponse::createResponseList([
                        "user_id" => $this->userId,
                        "directory_id" => $this->dirId,
                        "directory_name" => $this->dirName,
                        "path" => $contentPath,
                        "status" => "updated"
                    ]);
                } else {
                    ServerResponse::createResponse(2, 400);
                    die();
                }
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
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
                ServerResponse::createResponseList([
                    "user_id" => $this->userId,
                    "directory_id" => $dirId,
                    "status" => "deleted"
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(4, 400);
                die();
            }
            $file = ValidationData::filterFiles($dataFile);
            if (!$file) {
                ServerResponse::createResponse(6, 400);
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
                    ServerResponse::createResponse(7, 400);
                    die();
                }
                try {
                    move_uploaded_file($fileTemp, $destFilePath);
                } catch (\Exception $e) {
                    echo "File upload error: " . $e->getMessage();
                    die();
                }
                FileDBRequest::createFile($newFileName, $this->userId, $dirId['id'], $userFileName);
                ServerResponse::createResponseList([
                    "user_id" => $this->userId,
                    "directory_id" => $dirId['id'],
                    "directory_name" => $dirName,
                    "file_name" => $userFileName,
                    "status" => "file created"
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }


    public function getFiles(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = FileDBRequest::readFiles($this->userId);
            ServerResponse::createResponseList([
                "user_id" => $this->userId,
                "data" => $response
            ]);
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
                die();
            } else {
                ServerResponse::createResponseList([
                    "user_id" => $this->userId,
                    "file_id" => $response['id'],
                    "directory" => $response['name'],
                    "response" => $response['user_file_name'],
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
                die();
            } else {
                $userFileName = $response['user_file_name'] ?? "";
                $hashedFileName = $response['file_name'] ?? "";
                $file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $hashedFileName;
                if (file_exists($file)) {
                    unlink($file);
                }
                FileDBRequest::deleteFile($fileId);
                ServerResponse::createResponseList([
                    "user_id" => $this->userId,
                    "file_id" => $fileId,
                    "file_name" => $userFileName,
                    "status" => "deleted"
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }

    /**
     * @throws \Exception
     */
    public function renameFile(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $this->getJson();
            $response = FileDBRequest::getFile($this->userId, $this->fileId);
            if (!$response) {
                ServerResponse::createResponse(5, 400);
                die();
            } else {
                $checkFile = ValidationData::checkFileExistence($this->fileName, $this->userId, $response['dir_id']);
                if ($checkFile) {
                    FileDBRequest::updateFile($this->fileName, $this->fileId, $this->userId);
                    ServerResponse::createResponseList([
                        "user_id" => $this->userId,
                        "file_id" => $this->fileId,
                        "status" => "file updated"
                    ]);
                } else {
                    ServerResponse::createResponse(7, 400);
                    die();
                }
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
                die();
            } else {
                $newRes = [];
                foreach ($response as $file => $value) {
                    $newRes[] = [
                        'file' => $file,
                        'user_id' => $value
                    ];
                }
                ServerResponse::createResponseList($newRes);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
                die();
            } else {
                $res = ValidationData::checkFileAccess($tempUserId, $fileId);
                if ($res > 0) {
                    ServerResponse::createResponse(8, 400);
                    die();
                } else {
                    FileDBRequest::createFileAccess($tempUserId, $fileId);
                    ServerResponse::createResponseList([
                        "user" => $tempUserId,
                        "file" => $fileId,
                        "file_name" => $response['file_name'],
                        "status" => "file access granted"
                    ]);
                }
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(5, 400);
                die();
            }
            if (!ValidationData::checkFileAccess($tempUserId, $fileId)) {
                ServerResponse::createResponse(9, 400);
                die();
            } else {
                FileDBRequest::deleteAccessFile($tempUserId, $fileId);
                ServerResponse::createResponseList([
                    "user" => $tempUserId,
                    "file_id" => $fileId,
                    "status" => "file access terminated"
                ]);
            }
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }
}
