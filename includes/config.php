<?php

define('SITE_NAME', 'Twitter');
// адрес сайта
define('HOST', 'http://' . $_SERVER['HTTP_HOST']);
// настройки для БД
define('DB_HOST', 'localhost');
// название базы данных 
define('DB_NAME', 'twitter');
// пользователь, подключающийся к БД
define('DB_USER', 'root');
// пароль для подключения к БД
define('DB_PASS', 'root');
// подключение массива сессии
session_start();
