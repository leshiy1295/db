-- phpMyAdmin SQL Dump
-- version 4.2.9
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1
-- Время создания: Ноя 26 2014 г., 20:57
-- Версия сервера: 5.6.20-log
-- Версия PHP: 5.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `technopark_db`
--

DELIMITER $$
--
-- Процедуры
--
$$

$$

$$

$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `development`
--

CREATE TABLE IF NOT EXISTS `development` (
  `a` int(11) NOT NULL DEFAULT '0',
  `b` int(11) DEFAULT NULL,
  `c` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `followers`
--

CREATE TABLE IF NOT EXISTS `followers` (
  `u_from` int(11) NOT NULL,
  `u_to` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `forum`
--

CREATE TABLE IF NOT EXISTS `forum` (
`id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

-- --------------------------------------------------------

--
-- Структура таблицы `post`
--

CREATE TABLE IF NOT EXISTS `post` (
`id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `thread` int(11) NOT NULL,
  `message` text NOT NULL,
  `user` varchar(255) NOT NULL,
  `forum` varchar(255) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `isApproved` tinyint(1) NOT NULL DEFAULT '0',
  `isHighlighted` tinyint(1) NOT NULL DEFAULT '0',
  `isEdited` tinyint(1) NOT NULL DEFAULT '0',
  `isSpam` tinyint(1) NOT NULL DEFAULT '0',
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `likes` int(11) NOT NULL DEFAULT '0',
  `dislikes` int(11) NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL,
  `childs_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

-- --------------------------------------------------------

--
-- Структура таблицы `production`
--

CREATE TABLE IF NOT EXISTS `production` (
`a` int(11) NOT NULL,
  `b` int(11) NOT NULL,
  `c` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `subscriptions`
--

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `t_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `thread`
--

CREATE TABLE IF NOT EXISTS `thread` (
`id` int(11) NOT NULL,
  `forum` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `isClosed` tinyint(1) NOT NULL DEFAULT '0',
  `user` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `slug` varchar(255) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `likes` int(11) NOT NULL DEFAULT '0',
  `dislikes` int(11) NOT NULL DEFAULT '0',
  `posts` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE IF NOT EXISTS `user` (
`id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `about` text,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `isAnonymous` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `followers`
--
ALTER TABLE `followers`
 ADD PRIMARY KEY (`u_from`,`u_to`), ADD KEY `u_to` (`u_to`);

--
-- Индексы таблицы `forum`
--
ALTER TABLE `forum`
 ADD PRIMARY KEY (`id`), ADD KEY `short_name` (`short_name`,`name`,`user`), ADD KEY `user` (`user`), ADD KEY `name` (`name`);

--
-- Индексы таблицы `post`
--
ALTER TABLE `post`
 ADD PRIMARY KEY (`id`), ADD KEY `parent` (`parent`), ADD KEY `forum` (`forum`,`date`,`likes`,`dislikes`), ADD KEY `thread` (`thread`,`date`,`likes`,`dislikes`), ADD KEY `user` (`user`,`date`,`likes`,`dislikes`);

--
-- Индексы таблицы `production`
--
ALTER TABLE `production`
 ADD PRIMARY KEY (`a`), ADD KEY `b` (`b`);

--
-- Индексы таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
 ADD PRIMARY KEY (`t_id`,`u_id`), ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `thread`
--
ALTER TABLE `thread`
 ADD PRIMARY KEY (`id`), ADD KEY `forum` (`forum`), ADD KEY `likes-dislikes` (`likes`,`dislikes`), ADD KEY `user` (`user`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
 ADD PRIMARY KEY (`id`), ADD KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `forum`
--
ALTER TABLE `forum`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `post`
--
ALTER TABLE `post`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `production`
--
ALTER TABLE `production`
MODIFY `a` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `thread`
--
ALTER TABLE `thread`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `followers`
--
ALTER TABLE `followers`
ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`u_from`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`u_to`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `forum`
--
ALTER TABLE `forum`
ADD CONSTRAINT `forum_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `post`
--
ALTER TABLE `post`
ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`forum`) REFERENCES `forum` (`short_name`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `post_ibfk_2` FOREIGN KEY (`thread`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `post_ibfk_3` FOREIGN KEY (`user`) REFERENCES `user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`t_id`) REFERENCES `thread` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`u_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `thread`
--
ALTER TABLE `thread`
ADD CONSTRAINT `thread_ibfk_1` FOREIGN KEY (`forum`) REFERENCES `forum` (`short_name`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `thread_ibfk_2` FOREIGN KEY (`user`) REFERENCES `user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
