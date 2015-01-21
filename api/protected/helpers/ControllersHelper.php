<?php

class ControllersHelper
{
    public static function getUserIdByEmail($email)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT id FROM user WHERE email = :email LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":email", $email);
        try {
            $result = $command->queryAll();
            return $result[0]["id"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return -1;
    }

    public static function getBoundsForParentTreeSort($thread, $order, $limit)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT SUBSTRING_INDEX(MIN(t.path), '.', 2) AS left_bound
                FROM
                (SELECT path
                FROM post
                WHERE thread = :thread AND parent IS NULL
                ORDER BY path ".$order." ";
        if (array_key_exists('limit', $_GET))
            $sql .= " LIMIT " . strval(intval($limit))." ";
        $sql .= ") AS t;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":thread", $thread);
        try {
            $result = $command->queryAll();
            $left = $result[0]["left_bound"];
            $sql = "SELECT MAX(t.path) AS right_bound
                    FROM
                    (SELECT path
                    FROM post
                    WHERE thread = :thread AND parent IS NULL
                    ORDER BY path ".$order." ";
            if (array_key_exists('limit', $_GET))
                $sql .= " LIMIT " . strval(intval($limit))." ";
            $sql .= ") AS t;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":thread", $thread);
            $result = $command->queryAll();
            $right = $result[0]["right_bound"];
            $result = array();
            $result[0] = $left;
            $result[1] = $right;
            return $result;
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getForumByShortName($short_name)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT * FROM forum WHERE short_name = :short_name LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":short_name", $short_name);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getThreadById($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT * FROM thread WHERE id = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getPostCountByThreadId($thread) {
        $connection = Yii::app()->db;
        $sql = "SELECT COUNT(*) AS cnt FROM post WHERE thread = :thread;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":thread", $thread);
        try {
            $result = $command->queryAll();
            return $result[0]["cnt"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return -1;
    }

    public static function getPostThreadById($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT thread FROM post WHERE id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["thread"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return -1;
    }

    public static function getForumByPostId($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT forum.*
                FROM forum
                JOIN post
                ON post.forum = forum.short_name
                WHERE post.id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getUserByPostId($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT user.*
                FROM user
                JOIN post
                ON post.user = user.email
                WHERE post.id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getThreadByPostId($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT thread.*, (thread.likes - thread.dislikes) AS points
                FROM thread
                JOIN post
                ON post.thread = thread.id
                WHERE post.id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getForumByThreadId($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT forum.*
                FROM forum
                JOIN thread
                ON thread.forum = forum.short_name
                WHERE thread.id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getUserByThreadId($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT user.*
                FROM user
                JOIN thread
                ON thread.user = user.email
                WHERE thread.id = :id LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getUserByForumShortName($short_name)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT user.*
                FROM user
                JOIN forum
                ON forum.user = user.email
                WHERE forum.short_name = :short_name LIMIT 1;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":short_name", $short_name);
        try {
            $result = $command->queryAll();
            return $result[0];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getFollowers($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT group_concat(email) AS followers
                FROM user
                JOIN followers
                ON id = u_from
                WHERE u_to = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["followers"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getFollowing($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT group_concat(email) AS following
                FROM user
                JOIN followers
                ON id = u_to
                WHERE u_from = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["following"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }

    public static function getSubscriptions($id)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT group_concat(t_id) AS subscriptions
                FROM subscriptions
                WHERE u_id = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["subscriptions"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return null;
    }
}