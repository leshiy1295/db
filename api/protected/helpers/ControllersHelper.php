<?php

class ControllersHelper
{
    public static function getSqlBlockForUser() {
        $sql_for_following = " (select group_concat(email)
                                from user
                                join followers
                                on id = u_to
                                where u_from = user_id) as following ";
        $sql_for_followers = " (select group_concat(email)
                                from user
                                join followers
                                on id = u_from
                                where u_to = user_id) as followers ";
        $sql_for_subscriptions = " (select group_concat(t_id)
                                    from subscriptions
                                    where u_id = user_id) as subscriptions ";
        return " self.id as user_id, self.name as user_name, about, email, isAnonymous, username,".
            $sql_for_followers.",".
            $sql_for_following.",".
            $sql_for_subscriptions." ";
    }

    public static function getUserIdByEmail($email)
    {
        $connection = Yii::app()->db;
        $sql = "select id
                from user
                where email = :email;";
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

    public static function getFollowers($id)
    {
        $connection = Yii::app()->db;
        $sql = "select group_concat(email) as followers
                    from user
                    join followers
                    on id = u_from
                    where u_to = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["followers"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return array();
    }

    public static function getFollowing($id)
    {
        $connection = Yii::app()->db;
        $sql = "select group_concat(email) as following
                    from user
                    join followers
                    on id = u_to
                    where u_from = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["followers"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return array();
    }

    public static function getSubscriptions($id)
    {
        $connection = Yii::app()->db;
        $sql = "select group_concat(t_id) as subscriptions
                    from subscriptions
                    where u_id = :id;";
        $command = $connection->createCommand($sql);
        $command->bindParam(":id", $id);
        try {
            $result = $command->queryAll();
            return $result[0]["followers"];
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        return array();
    }
}