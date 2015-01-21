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
                $sql = "SELECT id, user FROM forum WHERE name = :name AND short_name = :short_name LIMIT 1;";
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

            $sql = "SELECT * FROM forum WHERE short_name = :forum LIMIT 1;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":forum", $forum);
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
                        $user_row = ControllersHelper::getUserByForumShortName($forum);
                        $buf2 = array();
                        $buf2["about"] = $user_row["about"];
                        $buf2["email"] = $user_row["email"];
                        $followers = ControllersHelper::getFollowers($user_row["id"]);
                        $buf2["followers"] = $followers == null ? array() : explode(",", $followers);
                        $following = ControllersHelper::getFollowing($user_row["id"]);
                        $buf2["following"] = $following == null ? array() : explode(",", $following);
                        $buf2["id"] = $user_row["id"];
                        $buf2["isAnonymous"] = $user_row["isAnonymous"] == 0 ? false : true;
                        $buf2["name"] = $user_row["name"];
                        $subscriptions = ControllersHelper::getSubscriptions($user_row["id"]);
                        $buf2["subscriptions"] = $subscriptions == null ? array() : explode(",", $subscriptions);
                        $buf2["username"] = $user_row["username"];
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
            count($related) == 1 && (array_key_exists("user", $related) || array_key_exists("thread", $related) ||
                                     array_key_exists("forum", $related)) ||
            count($related) == 2 && (array_key_exists("user", $related) && array_key_exists("forum", $related) ||
                                     array_key_exists("user", $related) && array_key_exists("thread", $related) ||
                                     array_key_exists("thread", $related) && array_key_exists("forum", $related)) ||
            count($related) == 3 && array_key_exists("user", $related) && array_key_exists("thread", $related) &&
                                    array_key_exists("forum", $related))) {
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

            if (strcmp($order, 'asc') && strcmp($order, 'desc')) {
                echo json_encode($response);
                return;
            }

            $connection = Yii::app()->db;

            $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE forum = :forum ";

            if (array_key_exists('since', $_GET))
                $sql .= " AND date >= :since ";

            $sql .= " ORDER BY date ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " LIMIT ".strval(intval($limit));
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
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
                        if (array_key_exists('forum', $related)) {
                            $forum_row = ControllersHelper::getForumByShortName($forum);
                            $buf2 = array();
                            $buf2["id"] = $forum_row["id"];
                            $buf2["name"] = $forum_row["name"];
                            $buf2["short_name"] = $forum_row["short_name"];
                            $buf2["user"] = $forum_row["user"];
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
                        if (array_key_exists('thread', $related)) {
                            $thread_row = ControllersHelper::getThreadByPostId($row["id"]);
                            $buf2 = array();
                            $buf2["date"] = $thread_row["date"];
                            $buf2["dislikes"] = (int)$thread_row["dislikes"];
                            $buf2["forum"] = $thread_row["forum"];
                            $buf2["id"] = $thread_row["id"];
                            $buf2["isClosed"] = $thread_row["isClosed"] == 0 ? false : true;
                            $buf2["isDeleted"] = $thread_row["isDeleted"] == 0 ? false : true;
                            $buf2["likes"] = (int)$thread_row["likes"];
                            $buf2["message"] = $thread_row["message"];
                            $buf2["points"] = (int)$thread_row["points"];
                            $buf2["posts"] = (int)$thread_row["posts"];
                            $buf2["slug"] = $thread_row["slug"];
                            $buf2["title"] = $thread_row["title"];
                            $buf2["user"] = $thread_row["user"];
                            $buf["thread"] = $buf2;
                        } else
                            $buf["thread"] = $row["thread"];
                        if (array_key_exists('user', $related)) {
                            $user_row = ControllersHelper::getUserByPostId($row["id"]);
                            $buf2 = array();
                            $buf2["about"] = $user_row["about"];
                            $buf2["email"] = $user_row["email"];
                            $followers = ControllersHelper::getFollowers($user_row["id"]);
                            $buf2["followers"] = $followers == null ? array() : explode(",", $followers);
                            $following = ControllersHelper::getFollowing($user_row["id"]);
                            $buf2["following"] = $following == null ? array() : explode(",", $following);
                            $buf2["id"] = $user_row["id"];
                            $buf2["isAnonymous"] = $user_row["isAnonymous"] == 0 ? false : true;
                            $buf2["name"] = $user_row["name"];
                            $subscriptions = ControllersHelper::getSubscriptions($user_row["id"]);
                            $buf2["subscriptions"] = $subscriptions == null ? array() : explode(",", $subscriptions);
                            $buf2["username"] = $user_row["username"];
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

            if (strcmp($order, 'asc') && strcmp($order, 'desc')) {
                echo json_encode($response);
                return;
            }

            $connection = Yii::app()->db;

            $sql = "SELECT *, (likes - dislikes) AS points FROM thread WHERE forum = :forum ";
            if (array_key_exists('since', $_GET))
                $sql .= " AND thread.date >= :since ";

            $sql .= " ORDER BY thread.date ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " LIMIT ".strval(intval($limit));
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
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
                        if (array_key_exists('forum', $related)) {
                            $forum_row = ControllersHelper::getForumByShortName($forum);
                            $buf2 = array();
                            $buf2["id"] = $forum_row["id"];
                            $buf2["name"] = $forum_row["name"];
                            $buf2["short_name"] = $forum_row["short_name"];
                            $buf2["user"] = $forum_row["user"];
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
                        if (array_key_exists('user', $related)) {
                            $user_row = ControllersHelper::getUserByThreadId($row["id"]);
                            $buf2 = array();
                            $buf2["about"] = $user_row["about"];
                            $buf2["email"] = $user_row["email"];
                            $followers = ControllersHelper::getFollowers($user_row["id"]);
                            $buf2["followers"] = $followers == null ? array() : explode(",", $followers);
                            $following = ControllersHelper::getFollowing($user_row["id"]);
                            $buf2["following"] = $following == null ? array() : explode(",", $following);
                            $buf2["id"] = $user_row["id"];
                            $buf2["isAnonymous"] = $user_row["isAnonymous"] == 0 ? false : true;
                            $buf2["name"] = $user_row["name"];
                            $subscriptions = ControllersHelper::getSubscriptions($user_row["id"]);
                            $buf2["subscriptions"] = $subscriptions == null ? array() : explode(",", $subscriptions);
                            $buf2["username"] = $user_row["username"];
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

            if (strcmp($order, 'asc') && strcmp($order, 'desc')) {
                echo json_encode($response);
                return;
            }

            $connection = Yii::app()->db;

            $sql = "SELECT DISTINCT user.*
                    FROM user
                    JOIN post USE KEY (forum_user)
                    ON email = post.user
                    WHERE post.forum = :forum ";
            if (array_key_exists('since_id', $_GET))
                $sql .= " AND user.id >= :since_id ";
            $sql .= " ORDER BY name ".$order." ";
            if (array_key_exists('limit', $_GET))
                $sql .= " LIMIT ".strval(intval($limit));
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
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];
                        $followers = ControllersHelper::getFollowers($row["id"]);
                        $buf["followers"] = $followers == null ? array() : explode(",", $followers);
                        $following = ControllersHelper::getFollowing($row["id"]);
                        $buf["following"] = $following == null ? array() : explode(",", $following);
                        $buf["id"] = $row["id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["name"];
                        $subscriptions = ControllersHelper::getSubscriptions($row["id"]);
                        $buf["subscriptions"] = $subscriptions == null ? array() : explode(",", $subscriptions);
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
