<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomepageController@show');

// NewsLab

Route::get('/home', 'HomepageController@show');
Route::get('/news', 'NewsOpinionsFeedController@showNews');
Route::get('/opinions', 'NewsOpinionsFeedController@showOpinions');
Route::get('/feed', 'NewsOpinionsFeedController@showFeed');

// Static pages
Route::view('/about', 'pages.about');
Route::view('/faq', 'pages.faq');

// 3.4 Content Searching, Filtering and Presentation
Route::get('/search', 'SearchController@show');
Route::get('/tags/{tag_id}', 'TagController@show')->where(['tag_id' => '[0-9]+']);

// 3.5 Post, Comment and Versions
Route::get('/posts/{id}', 'PostController@show')->where(['id' => '[0-9]+']);

Route::get('/posts/create', 'PostController@showEditor');
Route::get('/posts/{post_id}/edit', 'PostController@showEditor')->where(['id' => '[0-9]+']);

Route::get('/admins/{id}', 'AdminController@show')->where(['id' => '[0-9]+']);

// Users
Route::get('/users/{id}', 'UserController@show')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/saved_posts', 'UserController@savedPosts')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/manage_subs/', 'UserController@manageSubs')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/stats', 'UserController@showStats')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/edit', 'UserController@showEditor')->where(['id' => '[0-9]+']);
Route::get('/posts/{id}/versions', 'PostController@versions')->where(['id' => '[0-9]+']);
Route::get('/comments/{id}/versions', 'CommentController@versions')->where(['id' => '[0-9]+']);
Route::get('/replies/{id}/versions', 'ReplyController@versions')->where(['id' => '[0-9]+']);

// Authentication

Route::post('login', 'Auth\LoginController@login')->name('login');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
Route::post('register', 'Auth\RegisterController@register')->name('register');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
Route::post('password/email', 'Auth\ForgotPasswordController@getEmail')->name('password.email');
