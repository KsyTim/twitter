<?php
include_once('includes/functions.php');
$error = get_error_message();
if (!logged_in()) redirect();
// если передан через get, то берем из запроса
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $id = $_GET['id'];
  // если пользователь авторизован, то его id из массива сессии
} else if (logged_in()) {
  $id = $_SESSION['user']['id'];
  // в противном случае равен 0
} else {
  $id = 0;
}
$posts = get_posts($id);
$title = 'Твиты';
if (!empty($posts)) $title = 'Твиты пользователя @' . $posts[0]['login'];
include_once('includes/header.php');
if (logged_in()) {
  include_once('includes/tweet_form.php');
}
include_once('includes/posts.php');
include_once('includes/footer.php');
