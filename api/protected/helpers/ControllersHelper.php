<?php

class ControllersHelper
{
    public static function getUserIdByEmail($email)
    {
        $connection = Yii::app()->db;
        $sql = "SELECT id FROM user WHERE email = :email;";
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
        $sql = "SELECT thread FROM post WHERE id = :id;";
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