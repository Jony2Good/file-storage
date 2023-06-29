<?php
use app\Router\Router;
use app\Controllers\Auth;
use app\Controllers\User;
use app\Controllers\File;

require_once "../app/Router/Router.php";

Router::post('/auth/signup', Auth::class, 'signUp', true);
Router::post('/auth/login', Auth::class, 'login', true);
Router::get('/auth/logout', Auth::class, 'logout');
Router::get('/auth/reset', Auth::class, 'resetPassword');
Router::post('/auth/change-password', Auth::class, 'changePassword');

Router::get('/admin/user', User::class, 'showUserList');
Router::get('/admin/user/{id}', User::class, 'getUser', true);
Router::delete('/admin/user/{id}', User::class, 'deleteUser');
Router::put('/admin/user', User::class, 'update');

Router::post('/directory', File::class, 'createDirectory', true);
Router::get('/directory/{id}', File::class, 'getDirectory', true);
Router::put('/directory', File::class, 'renameDirectory');
Router::delete('/directory/{id}', File::class, 'deleteDirectory');

Router::post('/file', File::class, 'createFile', true, true);
Router::get('/file', File::class, 'getFiles');
Router::get('/file/{id}', File::class, 'getCurrentFile', true);
Router::delete('/file/{id}', File::class, 'deleteFile');
Router::put('/file', File::class, 'changeFile');

Router::enable();
