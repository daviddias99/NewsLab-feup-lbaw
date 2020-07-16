<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', 'Auth\LoginController@getUser');

// API
Route::get('/users/{id}', 'UserController@getUser')->where(['id' => '[0-9]+']);
Route::post('/users/{id}', 'UserController@update')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/posts', 'UserController@posts')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/likes', 'UserController@likes')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/comments', 'UserController@comments')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/badges', 'UserController@getBadges')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/stats', 'UserController@getStats')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/tags', 'UserController@getPostTags')->where(['id' => '[0-9]+']);
Route::get('/users/{id}/saved_posts', 'UserController@getSavedPosts')->where(['id' => '[0-9]+']);
Route::get('/users/banned', 'UserController@getBanned');
Route::delete('/users/{id}/saved_posts', 'UserController@deleteSavedPost')->where(['id' => '[0-9]+']);
Route::delete('/users/{id}', 'UserController@delete')->where(['id' => '[0-9]+']);
Route::post('/users/{id}/saved_posts', 'UserController@addSavedPost')->where(['id' => '[0-9]+']);
Route::post('/users/{id}/ban', 'UserController@banUser')->where(['id' => '[0-9]+']);
Route::post('/users/{id}/unban', 'UserController@unbanUser')->where(['id' => '[0-9]+']);

// Admin center
Route::get('/admins', 'AdminController@list');
Route::get('/admins/{id}', 'AdminController@info')->where(['id' => '[0-9]+']);
Route::get('/admins/candidates', 'AdminController@candidates');
Route::post('/admins', 'AdminController@create');
Route::delete('/admins/{id}', 'AdminController@delete')->where(['id' => '[0-9]+']);

// Reports
Route::get('/reports', 'ReportController@list')->where(['id' => '[0-9]+']);
Route::put('/reports/{report_id}', 'ReportController@close')->where(['report_id' => '[0-9]+']);

Route::get('/posts/{post_id}/comments_replies', 'PostController@getComments')->where(['post_id' => '[0-9]+']);
Route::get('/posts/{post_id}/related_posts', 'PostController@getRelatedPosts')->where(['post_id' => '[0-9]+']);
Route::get('/posts/{post_id}/related_tags', 'PostController@getRelatedTags')->where(['post_id' => '[0-9]+']);
Route::get('/posts/{id}', 'PostController@getPost')->where(['id' => '[0-9]+']);
Route::delete('/posts/{post_id}', 'PostController@delete')->where(['post_id' => '[0-9]+']);
Route::post('/posts', 'PostController@create');
Route::post('/posts/{post_id}/comment', 'CommentController@create')->where(['post_id' => '[0-9]+']);
Route::post('/posts/{post_id}', 'PostController@update')->where(['id' => '[0-9]+']);
Route::post('/posts/{id}/visibility', 'PostController@visibility')->where(['id' => '[0-9]+']);

Route::post('/comments/{comment_id}/reply', 'ReplyController@create')->where(['post_id' => '[0-9]+', 'comment_id' => '[0-9]+']);
Route::put('/comments/{comment_id}', 'CommentController@update')->where(['comment_id' => '[0-9]+']);
Route::delete('/comments/{comment_id}', 'CommentController@delete')->where(['comment_id' => '[0-9]+']);

Route::put('/replies/{reply_id}', 'ReplyController@update')->where(['reply_id' => '[0-9]+']);
Route::delete('/replies/{reply_id}', 'ReplyController@delete')->where(['reply_id' => '[0-9]+']);

Route::post('/rate/{content_id}', 'ContentController@rate')->where(['content_id' => '[0-9]+']);
Route::put('/rate/{content_id}', 'ContentController@rate')->where(['content_id' => '[0-9]+']);
Route::delete('/rate/{content_id}', 'ContentController@unrate')->where(['content_id' => '[0-9]+']);

Route::post('/contents/{content_id}/report', 'ContentController@report')->where(['content_id' => '[0-9]+']);
Route::post('/tags/{tag_id}/report', 'TagController@report')->where(['tag_id' => '[0-9]+']);
Route::post('/users/{user_id}/report', 'UserController@report')->where(['user_id' => '[0-9]+']);

Route::get('/posts/{id}/versions', 'PostController@getVersions')->where(['id' => '[0-9]+']);
Route::get('/comments/{id}/versions', 'CommentController@getVersions')->where(['id' => '[0-9]+']);
Route::get('/replies/{id}/versions', 'ReplyController@getVersions')->where(['id' => '[0-9]+']);

Route::get('/users/{id}/manage_subs/users', 'UserController@getManageSubsUsers')->where(['id' => '[0-9]+']);
Route::post('/users/{id}/manage_subs/users', 'UserController@addUserSub')->where(['id' => '[0-9]+']);
Route::delete('/users/{id}/manage_subs/users', 'UserController@deleteUserSub')->where(['id' => '[0-9]+']);

Route::get('/users/{id}/manage_subs/tags', 'UserController@getManageSubsTags')->where(['id' => '[0-9]+']);
Route::post('/users/{id}/manage_subs/tags', 'UserController@addTagSub')->where(['id' => '[0-9]+']);
Route::delete('/users/{id}/manage_subs/tags', 'UserController@deleteTagSub')->where(['id' => '[0-9]+']);


Route::get('/search/posts', 'PostController@search');
Route::get('/search/users', 'UserController@search');
Route::get('/search/tags', 'TagController@search');


Route::get('/feed/users', 'UserController@getSubbedAuthorPosts');
Route::get('/feed/tags', 'UserController@getSubbedTagPosts');

// Misc
Route::get('/weather', 'HomepageController@weather');