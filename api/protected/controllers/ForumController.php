<?php

/**
 * @author leshiy1295
 * @copyright 2014
 */

class ForumController extends Controller
{
    public function actionCreate()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);
    
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 3) {
            if (array_key_exists('name', $request) && array_key_exists('short_name', $request) && 
                array_key_exists('user', $request)) {
                $name = $request['name'];
                $short_name = $request['short_name'];
                $user = $request['user'];
                
                $connection = Yii::app()->db;
                $sql = "SELECT id, user FROM forum WHERE name = :name AND short_name = :short_name;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":name", $name);
                $command->bindParam(":short_name", $short_name);
                $dataReader = $command->queryAll();
                if (count($dataReader) == 0) {
                    $sql = "INSERT INTO forum (name, short_name, user) VALUES (:name, :short_name, :user);";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":name", $name);
                    $command->bindParam(":short_name", $short_name);
                    $command->bindParam(":user", $user);
                    try {
                        $command->execute();
                        $response["code"] = 0;
                        $buf = array();
                        $buf["id"] = $connection->getLastInsertID();
                        $buf["name"] = $name;
                        $buf["short_name"] = $short_name;
                        $buf["user"] = $user;
                        $response["response"] = $buf;
                    }
                    catch (Exception $e) {
                        $response["code"] = 4;
                        $response["response"] = $e->getMessage();
                    }    
                } else {
                    $response["code"] = 0;
                    $buf = array();
                    $elem = $dataReader[0];                        
                    $buf["id"] = $elem["id"];
                    $buf["name"] = $name;
                    $buf["short_name"] = $short_name;
                    $buf["user"] = $elem["user"];
                    $response["response"] = $buf;                                           
                }
            }
        }    
        echo json_encode($response);
    }
    
    
    public function actionDetails()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        $arr = explode("&", $_SERVER["QUERY_STRING"]);
        $related = array();
        foreach ($arr as $elem) {
           $buf = explode("=", $elem);
           if ($buf[0] == "related") {
               if (array_key_exists($buf[1], $related)) {
                   echo json_encode($response);            
                   exit;
               } else
                   $related[$buf[1]] = $buf[1];     
           }
        }        

        if (!(count($related) == 0 || count($related) == 1 && array_key_exists("user", $related))) {
            echo json_encode($response);
            exit;
        }

        if (array_key_exists('forum', $_GET)) {
            $forum = $_GET['forum'];
            $connection = Yii::app()->db;
            $correct = true;
            if (array_key_exists('user', $related)) {
                $sql = "select forum.*, self.id as u_id, self.name as u_name, about, email, isAnonymous, username,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_to
                    join user as u2
                    on f.u_from = u2.id
                    where u1.id = self.id
                    group by f.u_to
                    ) as followers
                    ,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_from
                    join user as u2
                    on f.u_to = u2.id
                    where u1.id = self.id
                    group by f.u_from
                    ) as following
                    ,
                    (
                    select group_concat(t_id)
                    from user as u
                    join subscriptions as s
                    on u.id = s.u_id
                    where u.id = self.id
                    group by u.id
                    ) as subscriptions
                   from forum join user as self on user = self.email
                   where short_name = :short_name;";
                $correct = true;
            }
            else
                $sql = "select * from forum where short_name = :short_name;";
            if ($correct) {
                $command = $connection->createCommand($sql);
                $command->bindParam(":short_name", $forum); 
                try {
                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "Forum was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["id"] = $row["id"];
                        $buf["name"] = $row["name"];
                        $buf["short_name"] = $row["short_name"];
                        if (array_key_exists('user', $related)) {
                            $buf2 = array();
                            $buf2["about"] = $row["about"];
                            $buf2["email"] = $row["email"];
                            
                            $buf2["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                            $buf2["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);
                            
                            $buf2["id"] = $row["u_id"];
                            $buf2["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                            $buf2["name"] = $row["u_name"];
                            
                            $buf2["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);
                            
                            $buf2["username"] = $row["username"];
                            $buf["user"] = $buf2;
                        } else {
                            $buf["user"] = $row["user"];
                        }
                        $response["response"] = $buf;
                    }
                }
                catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);   
    }
    
    
    public function actionListPosts()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        $arr = explode("&", $_SERVER["QUERY_STRING"]);
        $related = array();
        foreach ($arr as $elem) {
            $buf = explode("=", $elem);
            if ($buf[0] == "related") {
                if (array_key_exists($buf[1], $related)) {
                    echo json_encode($response);
                    exit;
                } else
                    $related[$buf[1]] = $buf[1];
            }
        }

        if (!(count($related) == 0 ||
            count($related) == 1 && (array_key_exists("user", $related) || array_key_exists("thread", $related) || array_key_exists("forum", $related)) ||
            count($related) == 2 && (array_key_exists("user", $related) && array_key_exists("forum", $related) ||
                array_key_exists("user", $related) && array_key_exists("thread", $related) ||
                array_key_exists("thread", $related) && array_key_exists("forum", $related)) ||
            count($related) == 3 && array_key_exists("user", $related) && array_key_exists("thread", $related) && array_key_exists("forum", $related))) {
            echo json_encode($response);
            exit;
        }

        if (array_key_exists('forum', $_GET)) {
            $forum = $_GET['forum'];

            if (array_key_exists('since', $_GET))
                $since = $_GET['since'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;
            $sql_user_part = " self.id as u_id, self.name as u_name, about, email, isAnonymous, username,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_to
                    join user as u2
                    on f.u_from = u2.id
                    where u1.id = self.id
                    group by f.u_to
                    ) as followers
                    ,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_from
                    join user as u2
                    on f.u_to = u2.id
                    where u1.id = self.id
                    group by f.u_from
                    ) as following
                    ,
                    (
                    select group_concat(t_id)
                    from user as u
                    join subscriptions as s
                    on u.id = s.u_id
                    where u.id = self.id
                    group by u.id
                    ) as subscriptions ";
            $sql_forum_part = " forum.id as f_id, forum.name as f_name, short_name, forum.user as f_user ";
            $sql_thread_part = " thread.date as t_date, thread.dislikes as t_dislikes, thread.forum as t_forum,
                    thread.id as t_id, isClosed, thread.isDeleted as t_isDeleted, thread.likes as t_likes,
                    thread.message as t_message, (thread.likes - thread.dislikes) as t_points, posts, slug, title, thread.user as t_user ";

            $sql = "select post.*, (post.likes - post.dislikes) as points ";
            $flag1 = 0;
            $flag2 = 0;
            $flag3 = 0;
            if (array_key_exists('user', $related)) {
                $sql .= ", ".$sql_user_part;
                $flag1 = 1;
            }
            if (array_key_exists('forum', $related)) {
                $sql .= ", ".$sql_forum_part;
                $flag2 = 1;
            }
            if (array_key_exists('thread', $related)) {
                $sql .= ", ".$sql_thread_part;
                $flag3 = 1;
            }
            $sql .= " from post ";
            if (array_key_exists('user', $related)) {
                $sql .= " join user as self on post.user = self.email ";
            }
            if (array_key_exists('forum', $related)) {
                $sql .= " join forum on post.forum = forum.short_name ";
            }
            if (array_key_exists('thread', $related)) {
                $sql .= " join thread on post.thread = thread.id ";
            }
            $sql .= " where post.forum = :forum ";

            if (array_key_exists('since', $_GET))
                $sql .= " and post.date >= :since ";

            $sql .= " order by post.date ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":forum", $forum);
            if (array_key_exists('since', $_GET))
                $command->bindParam(":since", $since);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 0;
                    $response["response"] = array();
//                    $response["code"] = 1;
//                    $response["response"] = "Posts were not found";
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
                        if ($flag2 == 1) {
                            $buf2 = array();
                            $buf2["id"] = $row["f_id"];
                            $buf2["name"] = $row["f_name"];
                            $buf2["short_name"] = $row["short_name"];
                            $buf2["user"] = $row["f_user"];
                            $buf["forum"] = $buf2;
                        } else
                            $buf["forum"] = $row["forum"];
                        $buf["id"] = $row["id"];
                        $buf["isApproved"] = $row["isApproved"] == 0 ? false : true;
                        $buf["isDeleted"] = $row["isDeleted"] == 0 ? false : true;
                        $buf["isEdited"] = $row["isEdited"] == 0 ? false : true;
                        $buf["isHighlighted"] = $row["isHighlighted"] == 0 ? false : true;
                        $buf["isSpam"] = $row["isSpam"] == 0 ? false : true;
                        $buf["likes"] = (int)$row["likes"];
                        $buf["message"] = $row["message"];
                        $buf["parent"] = $row["parent"];
                        $buf["points"] = (int)$row["points"];
                        if ($flag3 == 1) {
                            $buf2 = array();
                            $buf2["date"] = $row["t_date"];
                            $buf2["dislikes"] = (int)$row["t_dislikes"];
                            $buf2["forum"] = $row["t_forum"];
                            $buf2["id"] = $row["t_id"];
                            $buf2["isClosed"] = $row["isClosed"] == 0 ? false : true;
                            $buf2["isDeleted"] = $row["t_isDeleted"] == 0 ? false : true;
                            $buf2["likes"] = (int)$row["t_likes"];
                            $buf2["message"] = $row["t_message"];
                            $buf2["points"] = (int)$row["t_points"];
                            $buf2["posts"] = (int)$row["posts"];
                            $buf2["slug"] = $row["slug"];
                            $buf2["title"] = $row["title"];
                            $buf2["user"] = $row["t_user"];
                            $buf["thread"] = $buf2;
                        } else
                            $buf["thread"] = $row["thread"];
                        if ($flag1 == 1) {
                            $buf2 = array();
                            $buf2["about"] = $row["about"];
                            $buf2["email"] = $row["email"];

                            $buf2["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                            $buf2["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                            $buf2["id"] = $row["u_id"];
                            $buf2["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                            $buf2["name"] = $row["u_name"];

                            $buf2["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                            $buf2["username"] = $row["username"];
                            $buf["user"] = $buf2;
                        } else {
                            $buf["user"] = $row["user"];
                        }
                        array_push($response["response"], $buf);
                    }
                }
            }
            catch (Exception $e) {
                $response["code"] = 4;
                $response["response"] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }


    public function actionListThreads()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        $arr = explode("&", $_SERVER["QUERY_STRING"]);
        $related = array();
        foreach ($arr as $elem) {
            $buf = explode("=", $elem);
            if ($buf[0] == "related") {
                if (array_key_exists($buf[1], $related)) {
                    echo json_encode($response);
                    exit;
                } else
                    $related[$buf[1]] = $buf[1];
            }
        }

        if (!(count($related) == 0 ||
            count($related) == 1 && (array_key_exists("user", $related) || array_key_exists("forum", $related)) ||
            count($related) == 2 && array_key_exists("user", $related) && array_key_exists("forum", $related))) {
            echo json_encode($response);
            exit;
        }

        if (array_key_exists('forum', $_GET)) {
            $forum = $_GET['forum'];

            if (array_key_exists('since', $_GET))
                $since = $_GET['since'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;
            $sql_user_part = " self.id as u_id, self.name as u_name, about, email, isAnonymous, username,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_to
                    join user as u2
                    on f.u_from = u2.id
                    where u1.id = self.id
                    group by f.u_to
                    ) as followers
                    ,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_from
                    join user as u2
                    on f.u_to = u2.id
                    where u1.id = self.id
                    group by f.u_from
                    ) as following
                    ,
                    (
                    select group_concat(t_id)
                    from user as u
                    join subscriptions as s
                    on u.id = s.u_id
                    where u.id = self.id
                    group by u.id
                    ) as subscriptions ";
            $sql_forum_part = " forum.id as f_id, forum.name as f_name, short_name, forum.user as f_user ";
            $sql = "select thread.*, (thread.likes - thread.dislikes) as points ";
            $flag1 = 0;
            $flag2 = 0;
            if (array_key_exists('user', $related)) {
                $sql .= ", ".$sql_user_part;
                $flag1 = 1;
            }
            if (array_key_exists('forum', $related)) {
                $sql .= ", ".$sql_forum_part;
                $flag2 = 1;
            }
            $sql .= " from thread ";
            if (array_key_exists('user', $related)) {
                $sql .= " join user as self on thread.user = self.email ";
            }
            if (array_key_exists('forum', $related)) {
                $sql .= " join forum on thread.forum = forum.short_name ";
            }
            $sql .= " where thread.forum = :forum ";

            if (array_key_exists('since', $_GET))
                $sql .= " and thread.date >= :since ";

            $sql .= " order by thread.date ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":forum", $forum);
            if (array_key_exists('since', $_GET))
                $command->bindParam(":since", $since);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 0;
                    $response["response"] = array();
//                    $response["code"] = 1;
//                    $response["response"] = "Threads were not found";
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
                        if ($flag2 == 1) {
                            $buf2 = array();
                            $buf2["id"] = $row["f_id"];
                            $buf2["name"] = $row["f_name"];
                            $buf2["short_name"] = $row["short_name"];
                            $buf2["user"] = $row["f_user"];
                            $buf["forum"] = $buf2;
                        } else
                            $buf["forum"] = $row["forum"];
                        $buf["id"] = $row["id"];
                        $buf["isClosed"] = $row["isClosed"] == 0 ? false : true;
                        $buf["isDeleted"] = $row["isDeleted"] == 0 ? false : true;
                        $buf["likes"] = (int)$row["likes"];
                        $buf["message"] = $row["message"];
                        $buf["points"] = (int)$row["points"];
                        $buf["posts"] = (int)$row["posts"];
                        $buf["slug"] = $row["slug"];
                        $buf["title"] = $row["title"];
                        if ($flag1 == 1) {
                            $buf2 = array();
                            $buf2["about"] = $row["about"];
                            $buf2["email"] = $row["email"];

                            $buf2["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                            $buf2["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                            $buf2["id"] = $row["u_id"];
                            $buf2["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                            $buf2["name"] = $row["u_name"];

                            $buf2["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                            $buf2["username"] = $row["username"];
                            $buf["user"] = $buf2;
                        } else {
                            $buf["user"] = $row["user"];
                        }
                        array_push($response["response"], $buf);
                    }
                }
            }
            catch (Exception $e) {
                $response["code"] = 4;
                $response["response"] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }


    public function actionListUsers()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('forum', $_GET)) {
            $forum = $_GET['forum'];

            if (array_key_exists('since_id', $_GET))
                $since_id = $_GET['since_id'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;

            $sql = "select id as u_id, name as u_name, about, email, isAnonymous, username,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_to
                    join user as u2
                    on f.u_from = u2.id
                    where u1.id = self.id
                    group by f.u_to
                    ) as followers
                    ,
                    (
                    select group_concat(u2.email)
                    from user as u1
                    join followers as f
                    on u1.id = f.u_from
                    join user as u2
                    on f.u_to = u2.id
                    where u1.id = self.id
                    group by f.u_from
                    ) as following
                    ,
                    (
                    select group_concat(t_id)
                    from user as u
                    join subscriptions as s
                    on u.id = s.u_id
                    where u.id = self.id
                    group by u.id
                    ) as subscriptions
                    from user as self join
                    (select u.id as u_id from post
                    join user as u on u.email = post.user
                    where post.forum = :forum
                    group by u.id) as t
                    on self.id = t.u_id ";
            if (array_key_exists('since_id', $_GET))
                $sql .= " where self.id >= :since_id ";

            //$sql .= " order by -self.username ".(strcmp($order, "desc") == 0 ? "" : "desc")." ";
            $sql .= " order by self.name ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ";";
            $command = $connection->createCommand($sql);
            $command->bindParam(":forum", $forum);
            if (array_key_exists('since_id', $_GET))
                $command->bindParam(":since_id", $since_id);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 0;
                    $response["response"] = array();
//                    $response["code"] = 1;
//                    $response["response"] = "Users were not found";
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];

                        $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                        $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                        $buf["id"] = $row["u_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["u_name"];

                        $buf["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                        $buf["username"] = $row["username"];
                        array_push($response["response"], $buf);
                    }
                }
            }
            catch (Exception $e) {
                $response["code"] = 4;
                $response["response"] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }
}
?>
