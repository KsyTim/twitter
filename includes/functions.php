<?php
include_once('config.php');

function debug($var, $stop = false)
{
  echo '<pre>';
  print_r($var);
  echo '</pre>';
  if ($stop) die;
}

function redirect($where = '')
{
  header("Location: " . get_url($where));
  die;
}

function logged_in()
{
  return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

function get_url($page = '')
{
  return HOST . "/$page";
}

function get_page_title($title = '')
{
  if (!empty($title)) {
    return SITE_NAME . " - $title";
  } else {
    return SITE_NAME;
  }
}

function db()
{
  try {
    return new PDO(
      "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
      DB_USER,
      DB_PASS,
      [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]
    );
  } catch (PDOException $e) {
    die($e->getMessage());
  }
}

function db_query($sql, $exec = false)
{
  // проверка sql-запроса
  if (empty($sql)) return false;
  // если значение запроса передано
  if ($exec) return db()->exec($sql);
  // в случае если переданное значение не передано и по умолчанию false
  return db()->query($sql);
}

// получить посты пользователя по id
function get_posts($user_id = 0, $sort = false)
{
  $sorting = 'DESC';
  if ($sort) $sorting = 'ASC';
  if ($user_id > 0) return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id WHERE posts.user_id = $user_id ORDER BY posts.`date` $sorting;")->fetchAll();
  return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id ORDER BY posts.`date` $sorting;")->fetchAll();
}

function get_user_info($login)
{
  return db_query("SELECT * FROM `users` WHERE `login` = '$login';")->fetch();
}

function add_user($login, $pass)
{
  $login = trim($login);
  $name = ucfirst($login);
  $password = password_hash($pass, PASSWORD_DEFAULT);
  return db_query("INSERT INTO `users` (`id`, `login`, `pass`, `name`) VALUES (NULL, '$login', '$password', '$name');", true);
}

function register_user($auth_data)
{
  if (empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login']) || !isset($auth_data['pass']) || empty($auth_data['pass'])) return false;
  $user = get_user_info($auth_data['login']);
  // также можно сделать дополнительные проверки для инпутов логина и пароля пользователя на длину, включения и т.п.
  if (!empty($user)) {
    $_SESSION['error'] = 'Пользователь ' . $auth_data['login'] . ' уже существует';
    redirect('register.php');
  }

  if ($auth_data['pass'] !== $auth_data['pass2']) {
    $_SESSION['error'] = 'Пароли не совпадают';
    redirect('register.php');
  }

  if (add_user($auth_data['login'], $auth_data['pass'])) {
    redirect();
  }
}

function login($auth_data)
{
  if (empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login']) || !isset($auth_data['pass']) || empty($auth_data['pass'])) return false;
  $user = get_user_info($auth_data['login']);
  if (empty($user)) {
    $_SESSION['error'] = 'Логин или пароль был введен неправильно';
    redirect();
  }

  if (password_verify($auth_data['pass'], $user['pass'])) {
    // если пароль введен правильно (сверка hash с паролем в db)
    $_SESSION['user'] = $user;
    $_SESSION['error'] = '';
    redirect('user_posts.php?id=' . $user['id']);
  } else {
    $_SESSION['error'] = 'Пароль неверный';
    redirect();
  }
  // debug($auth_data, true);
}

function get_error_message()
{
  $error = '';
  if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
    $error = $_SESSION['error'];
    // очистить сессию от ошибок, но оставить ее на случай, если туда снова попадет ошибка P.S.данное условие выполняется, если ошибка в сессии существует и не пустая
    $_SESSION['error'] = '';
  }
  return $error;
}


function add_post($text, $image)
{
  $text = trim($text);
  if (mb_strlen($text) > 255) {
    $text = mb_substr($text, 0, 250) . ' ...';
  }
  $text = preg_replace('/\s{2,}/', ' ', $text);
  $array = explode(' ', $text);
  $text = implode(' ', array_slice($array, 0, 50));

  $user_id = $_SESSION['user']['id'];
  $sql = "INSERT INTO `posts` (`id`, `user_id`, `text`, `image`) VALUES (NULL, $user_id, '$text', '$image');";
  return db_query($sql, true);
}

function delete_post($id)
{
  // проверка что передано число и оно больше нуля
  if ((int)$id && $id > 0) {
    $user_id = $_SESSION['user']['id'];
    return db_query("DELETE FROM `posts` WHERE `id` = $id AND `user_id` = $user_id;", true);
  } else {
    $_SESSION['error'] = 'Такой статьи не существует, поэтому удаление не было произведено';
    redirect();
  }
}

function get_likes_count($post_id)
{
  if (empty($post_id)) return 0;
  return db_query("SELECT COUNT(*) FROM `likes` WHERE `post_id` = $post_id;")->fetchColumn();
}

function is_post_liked($post_id)
{
  $user_id = $_SESSION['user']['id'];
  if (empty($post_id)) return false;
  return db_query("SELECT * FROM `likes` WHERE `post_id` = $post_id AND `user_id` = $user_id;")->rowCount() > 0;
}

function add_like($post_id)
{
  $user_id = $_SESSION['user']['id'];
  if (empty($post_id)) return false;
  $sql = "INSERT INTO `likes` (`post_id`, `user_id`) VALUES ($post_id, $user_id);";
  return db_query($sql, true);
}


function delete_like($post_id)
{
  if (empty($post_id)) return false;
  $user_id = $_SESSION['user']['id'];
  return db_query("DELETE FROM `likes` WHERE `post_id` = $post_id AND `user_id` = $user_id;", true);
}
