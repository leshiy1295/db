<?php

/**
 * @author leshiy1295
 * @copyright 2014
 */

class PostController extends Controller
{
    public function actionCreate()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);
        
//        $fp = fopen("C:\\file.txt", "a+");
//        fwrite($fp, file_get_contents('php://input')."\n");
//        fclose($fp);
//        $request = json_decode($_POST["a"], true);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) >= 5 && count($request) <= 11) {
            if (array_key_exists('date', $request) && array_key_exists('thread', $request) &&
                array_key_exists('message', $request) && array_key_exists('user', $request) &&
                array_key_exists('forum', $request)) {
                $date = $request['date'];
                $thread = $request['thread'];
                $message = $request['message'];
                $user = $request['user'];
                $forum = $request['forum'];

                $parent = NULL;
                if (array_key_exists('parent', $request))
                    $parent = $request['parent'];
                $isApproved = 0;
                if (array_key_exists('isApproved', $request))
                    $isApproved = $request['isApproved'] == false ? 0 : 1;
                $isHighlighted = 0;
                if (array_key_exists('isHighlighted', $request))
                    $isHighlighted = $request['isHighlighted'] == false ? 0 : 1;
                $isEdited = 0;
                if (array_key_exists('isEdited', $request))
                    $isEdited = $request['isEdited'] == false ? 0 : 1;
                $isSpam = 0;
                if (array_key_exists('isSpam', $request))
                    $isSpam = $request['isSpam'] == false ? 0 : 1;
                $isDeleted = 0;
                if (array_key_exists('isDeleted', $request))
                    $isDeleted = $request['isDeleted'] == false ? 0 : 1;
                
                $connection = Yii::app()->db;
                                                
                if ($parent != NULL) {
                    $sql = "update post set childs_count = childs_count + 1 where post.id = :parent;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":parent", $parent);
                    $command->execute();
                    $sql = "select concat(path, '.', childs_count) as path from post where post.id = :parent;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":parent", $parent);
                    $res = $command->queryAll();
                    $path = $res[0]["path"];
                } else {
                    $sql = "select count(*) as cnt from post where parent is NULL";
                    $command = $connection->createCommand($sql);
                    $res = $command->queryAll();
                    $path = strval($res[0]["cnt"] + 1);
                }
                $sql = "INSERT INTO post (date, thread, message, user, forum, parent, isApproved, isHighlighted,
                                            isEdited, isSpam, isDeleted, path) 
                        VALUES (:date, :thread, :message, :user, :forum, :parent, :isApproved, :isHighlighted,
                                :isEdited, :isSpam, :isDeleted, :path); ";
//                if ($isDeleted == 0)
                    $sql .= " UPDATE thread SET posts = posts + 1 WHERE thread.id = :thread; ";
                $command = $connection->createCommand($sql);
                $command->bindParam(":date", $date);
                $command->bindParam(":thread", $thread);
                $command->bindParam(":message", $message);
                $command->bindParam(":user", $user);
                $command->bindParam(":forum", $forum);
                $command->bindParam(":parent", $parent);
                $command->bindParam(":isApproved", $isApproved);
                $command->bindParam(":isHighlighted", $isHighlighted);
                $command->bindParam(":isEdited", $isEdited);
                $command->bindParam(":isSpam", $isSpam);
                $command->bindParam(":isDeleted", $isDeleted);                
                $command->bindParam(":path", $path);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["date"] = $date;
                    $buf["forum"] = $forum;
                    $buf["id"] = $connection->getLastInsertID();
                    $buf["isApproved"] = $isApproved == 0 ? false : true;
                    $buf["isDeleted"] = $isDeleted == 0 ? false : true;
                    $buf["isEdited"] = $isEdited == 0 ? false : true;
                    $buf["isHighlighted"] = $isHighlighted == 0 ? false : true;
                    $buf["isSpam"] = $isSpam == 0 ? false : true;
                    $buf["message"] = $message;
                    $buf["parent"] = $parent;
                    $buf["thread"] = $thread;
                    $buf["user"] = $user;
                    $response["response"] = $buf;
                }
                catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
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

        if (!(count($related) == 0 ||
            count($related) == 1 && (array_key_exists("user", $related) || array_key_exists("thread", $related) || array_key_exists("forum", $related)) ||
            count($related) == 2 && (array_key_exists("user", $related) && array_key_exists("forum", $related) ||
                                     array_key_exists("user", $related) && array_key_exists("thread", $related) ||
                                     array_key_exists("thread", $related) && array_key_exists("forum", $related)) ||
            count($related) == 3 && array_key_exists("user", $related) && array_key_exists("thread", $related) && array_key_exists("forum", $related))) {
            echo json_encode($response);
            exit;
        }
        
        if (array_key_exists('post', $_GET)) {
            $post = $_GET['post'];
            
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
            $sql .= " where post.id = :post_id;";
            
            $command = $connection->createCommand($sql);
            $command->bindParam(":post_id", $post);
            
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 1;
                    $response["response"] = "Post was not found";
                } else {
                    $response["code"] = 0;
                    $row = $result[0];            
                    
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
                    $response["response"] = $buf;

                }
            }
            catch (Exception $e) {
                $response["code"] = 4;
                $response["response"] = $e->getMessage();
            }
        }                
        echo json_encode($response);   
    }
    
    
    public function actionList()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('forum', $_GET) || array_key_exists('thread', $_GET)) {
            if (array_key_exists('forum', $_GET))
                $forum = $_GET['forum'];

            if (array_key_exists('thread', $_GET))
                $thread = $_GET['thread'];

            if (array_key_exists('since', $_GET))
                $since = $_GET['since'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;
            $sql = "select *, (likes - dislikes) as points from post where ";
            if (array_key_exists('forum', $_GET))
                $sql .= " forum = :forum ";
            else
                $sql .= " thread = :thread ";
            if (array_key_exists('since', $_GET))
                $sql .= " and date >= :since ";
            $sql .= " order by date ".$order." ";
            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval($limit);
            $sql .= ";";

            $command = $connection->createCommand($sql);
            if (array_key_exists('forum', $_GET))
                $command->bindParam(":forum", $forum);
            else
                $command->bindParam(":thread", $thread);
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
                        $buf["thread"] = $row["thread"];
                        $buf["user"] = $row["user"];
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


    public function actionRemove()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 1) {
            if (array_key_exists('post', $request)) {
                $post = $request['post'];
                $connection = Yii::app()->db;

                $sql = "UPDATE post SET isDeleted = 1 WHERE id = :post;
                        UPDATE thread SET posts = posts - 1 WHERE thread.id = (select thread from post where id = :post);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["post"] = $post;
                    $response["response"] = $buf;
                }
                catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }


    public function actionRestore()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 1) {
            if (array_key_exists('post', $request)) {
                $post = $request['post'];
                $connection = Yii::app()->db;

                $sql = "UPDATE post SET isDeleted = 0 WHERE id = :post;
                        UPDATE thread SET posts = posts + 1 WHERE thread.id = (select thread from post where id = :post);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["post"] = $post;
                    $response["response"] = $buf;
                }
                catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }


    public function actionUpdate()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

//        $request = json_decode($_POST["a"], true);
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('post', $request) && array_key_exists('message', $request)) {
                $post = $request['post'];
                $message = $request['message'];

                $connection = Yii::app()->db;
                $sql = "update post set message = :message where id = :post;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":message", $message);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $sql = "select *, (likes - dislikes) as points from post where id = :post";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":post", $post);
                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "Post was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
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
                        $buf["thread"] = $row["thread"];
                        $buf["user"] = $row["user"];
                        $response["response"] = $buf;
                    }
                } catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }


    public function actionVote()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

//        $request = json_decode($_POST["a"], true);
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('post', $request) && array_key_exists('vote', $request)) {
                $post = $request['post'];
                $vote = $request['vote'];
                if (abs($vote) != 1) {
                    $code = 3;
                    $status = "Invalid query";
                    $response = array('code' => $code, 'response' => $status);
                    echo json_encode($response);
                    exit;
                }
                $connection = Yii::app()->db;
                $sql = "update post set " . ($vote == 1 ? " likes = likes + 1 " : "dislikes = dislikes + 1") . " where id = :post;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $sql = "select *, (likes - dislikes) as points from post where id = :post";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":post", $post);
                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "Post was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
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
                        $buf["thread"] = $row["thread"];
                        $buf["user"] = $row["user"];
                        $response["response"] = $buf;
                    }
                } catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }
}
?>