<?php

use app\Router\Router;
use app\Controllers\Auth;
use app\Controllers\User;
use app\Controllers\File;

Router::post('/auth/signup', Auth::class, 'signUp', true);
Router::post('/auth/login', Auth::class, 'login', true);
Router::get('/auth/logout', Auth::class, 'logout');
Router::get('/auth/reset', Auth::class, 'resetPassword');
Router::post('/auth/change-password', Auth::class, 'changePassword');

Router::get('/user', User::class, 'showUsers');
Router::get('/users/{id}', User::class, 'showOneUser', true);
Router::put('/user', User::class, 'changeUserData');
Router::delete('/user/{id}', User::class, 'deleteOneUser');

Router::get('/admin/user', User::class, 'showUserList');
Router::get('/admin/user/{id}', User::class, 'getUser', true);
Router::delete('/admin/user/{id}', User::class, 'deleteUser');
Router::put('/admin/user', User::class, 'updateUser');

Router::post('/directory', File::class, 'createDirectory', true);
Router::get('/directory/{id}', File::class, 'getDirectory', true);
Router::put('/directory', File::class, 'renameDirectory');
Router::delete('/directory/{id}', File::class, 'deleteDirectory');

Router::post('/file', File::class, 'createFile', true, true);
Router::get('/files', File::class, 'getFiles');
Router::get('/file/{id}', File::class, 'getCurrentFile', true);
Router::delete('/file/{id}', File::class, 'deleteFile');
Router::put('/file', File::class, 'renameFile');

Router::get('/user/search/{$id}', User::class, 'searchUser', true);

Router::get('/files/share/{id}', File::class, 'getSharingFiles', true);
Router::put('/files/share/{id}/{user_id}', File::class, 'grantAccessFile', true);
Router::delete('/files/share/{id}/{user_id}', File::class, 'stopAccessingFile');

Router::enable();
