<?php

/**
 * @author leshiy1295
 * @copyright 2014
 */

class PostController extends Controller
{
    public function actionFixPaths() {
        $connection = Yii::app()->db;

        $sql = "SELECT COUNT(*) AS cnt FROM post;";
        $command = $connection->createCommand($sql);
        try {
            $res = $command->queryAll();
            $cnt = strval($res[0]["cnt"]);

            for ($i = 1; $i <= $cnt; ++$i) {
                $sql = "SELECT parent, thread, id, path FROM post WHERE id = :id";
                $command = $connection->createCommand($sql);
                $command->bindParam(":id", $i);
                $res = $command->queryAll();
                $parent = $res[0]["parent"];
                echo "BEFORE: ";
                echo "thread:" . $res[0]["thread"] . " id:" . $res[0]["id"] . " path:" . $res[0]["path"] . " parent:" . ($parent == NULL ? "NULL" : $parent) . "<br>";
                if ($parent == NULL) {
                    $sql = "UPDATE post SET path = concat(thread, '.', id) WHERE id = :id;";
                    $command = $connection->createCommand($sql);
                } else {
                    $sql = "SELECT path FROM post WHERE id = :parent_id;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":parent_id", $parent);
                    $res = $command->queryAll();
                    $path = $res[0]["path"];
                    $sql = "UPDATE post SET path = concat(:path, '.', id) WHERE id = :id;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":path", $path);
                }
                $command->bindParam(":id", $i);
                $command->execute();
                $sql = "SELECT path FROM post WHERE id = :id;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":id", $i);
                $res = $command->queryAll();
                echo "AFTER: ";
                echo " path:" . $res[0]["path"] . "<br>";
            }
        }
        catch (Exception $e) {
            echo $e->getMessage()."<br>";
        }
    }


    public function actionCreate()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);
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

                $sql = "SELECT posts AS cnt FROM thread WHERE thread.id = :id LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":id", $thread);
                $res = $command->queryAll();
                $path = strval($res[0]["cnt"] + 1);

                if ($parent != NULL) {
                    $sql = "SELECT path FROM post WHERE post.id = :parent LIMIT 1;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":parent", $parent);
                    $res = $command->queryAll();
                    $path = $res[0]["path"].".".$path;
                } else
                    $path = strval($thread).".".$path;

                $sql = "INSERT INTO post (date, thread, message, user, forum, parent, isApproved, isHighlighted,
                                            isEdited, isSpam, isDeleted, path) 
                        VALUES (:date, :thread, :message, :user, :forum, :parent, :isApproved, :isHighlighted,
                                :isEdited, :isSpam, :isDeleted, :path);
                        UPDATE thread SET posts = posts + 1 WHERE thread.id = :thread LIMIT 1;";
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
        
        if (array_key_exists('post', $_GET)) {
            $post = $_GET['post'];
            
            $connection = Yii::app()->db;

            $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE id = :post LIMIT 1;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":post", $post);
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
                    if (array_key_exists('forum', $related)) {
                        $forum_row = ControllersHelper::getForumByPostId($post);
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
                        $thread_row = ControllersHelper::getThreadByPostId($post);
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
                        $user_row = ControllersHelper::getUserByPostId($post);
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

            if (strcmp($order, 'asc') && strcmp($order, 'desc')) {
                echo json_encode($response);
                return;
            }

            $connection = Yii::app()->db;
            $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE ";
            if (array_key_exists('forum', $_GET))
                $sql .= " forum = :forum ";
            else
                $sql .= " thread = :thread ";
            if (array_key_exists('since', $_GET))
                $sql .= " AND date >= :since ";
            $sql .= " ORDER BY date ".$order." ";
            if (array_key_exists('limit', $_GET))
                $sql .= " LIMIT ".strval(intval($limit));
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

                $thread = ControllersHelper::getPostThreadById($post);
                $connection = Yii::app()->db;

                $sql = "UPDATE post SET isDeleted = 1 WHERE id = :post LIMIT 1;
                        UPDATE thread SET posts = posts - 1 WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                $command->bindParam(":thread", $thread);
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
                $thread = ControllersHelper::getPostThreadById($post);
                $connection = Yii::app()->db;

                $sql = "UPDATE post SET isDeleted = 0 WHERE id = :post LIMIT 1;
                        UPDATE thread SET posts = posts + 1 WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                $command->bindParam(":thread", $thread);
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
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('post', $request) && array_key_exists('message', $request)) {
                $post = $request['post'];
                $message = $request['message'];

                $connection = Yii::app()->db;
                $sql = "UPDATE post SET message = :message WHERE id = :post LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":message", $message);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE id = :post LIMIT 1;";
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
                $sql = "UPDATE post SET " . ($vote == 1 ? " likes = likes + 1 " : "dislikes = dislikes + 1") .
                       " WHERE id = :post LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":post", $post);
                try {
                    $command->execute();
                    $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE id = :post LIMIT 1;";
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