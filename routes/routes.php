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

Router::enable();
