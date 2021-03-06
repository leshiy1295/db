<?php

/**
 * @author leshiy1295
 * @copyright 2014
 */

class ThreadController extends Controller 
{
    public function actionClose()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 1) {
            if (array_key_exists('thread', $request)) {
                $thread = $request['thread'];
                $connection = Yii::app()->db;

                $sql = "UPDATE thread SET isClosed = 1 WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
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


    public function actionCreate()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) >= 7 && count($request) <= 8) {
            if (array_key_exists('forum', $request) && array_key_exists('title', $request) && 
                array_key_exists('isClosed', $request) && array_key_exists('user', $request) &&
                array_key_exists('date', $request) && array_key_exists('message', $request) &&
                array_key_exists('slug', $request)) {
                $forum = $request['forum'];
                $title = $request['title'];
                $isClosed = $request['isClosed'] == false ? 0 : 1;
                $user = $request['user'];
                $date = $request['date'];
                $message = $request['message'];
                $slug = $request['slug']; 
                $isDeleted = 0;
                if (array_key_exists('isDeleted', $request))
                    $isDeleted = $request['isDeleted'] == false ? 0 : 1;
                
                $connection = Yii::app()->db;
                $sql = "INSERT INTO thread (forum, title, isClosed, user, date, message, slug, isDeleted) 
                        VALUES (:forum, :title, :isClosed, :user, :date, :message, :slug, :isDeleted);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":forum", $forum);
                $command->bindParam(":title", $title);
                $command->bindParam(":isClosed", $isClosed);
                $command->bindParam(":user", $user);
                $command->bindParam(":date", $date);
                $command->bindParam(":message", $message);
                $command->bindParam(":slug", $slug);
                $command->bindParam(":isDeleted", $isDeleted);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["date"] = $date;
                    $buf["forum"] = $forum;
                    $buf["id"] = $connection->getLastInsertID();
                    $buf['isClosed'] = $isClosed == 0 ? false : true;
                    $buf['isDeleted'] = $isDeleted == 0 ? false : true;
                    $buf["message"] = $message;
                    $buf["slug"] = $slug;
                    $buf["title"] = $title;
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
            count($related) == 1 && (array_key_exists("user", $related) || array_key_exists("forum", $related)) ||
            count($related) == 2 && array_key_exists("user", $related) && array_key_exists("forum", $related))) {
            echo json_encode($response);
            exit;
        }

        if (array_key_exists('thread', $_GET)) {
            $thread = $_GET['thread'];
            
            $connection = Yii::app()->db;

            $sql = "SELECT *, (likes - dislikes) AS points FROM thread WHERE id = :thread LIMIT 1;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":thread", $thread);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 1;
                    $response["response"] = "Thread was not found";
                } else {
                    $response["code"] = 0;
                    $row = $result[0];
                    $buf = array();
                    $buf["date"] = $row["date"];
                    $buf["dislikes"] = (int)$row["dislikes"];
                    if (array_key_exists('forum', $related)) {
                        $forum_row = ControllersHelper::getForumByThreadId($thread);
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
                        $user_row = ControllersHelper::getUserByThreadId($thread);
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

        if (array_key_exists('forum', $_GET) || array_key_exists('user', $_GET)) {
            if (array_key_exists('forum', $_GET))
                $forum = $_GET['forum'];

            if (array_key_exists('user', $_GET))
                $user = $_GET['user'];

            if (array_key_exists('since', $_GET))
                $since = $_GET['since'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;
            $sql = "SELECT *, (likes - dislikes) AS points FROM thread WHERE ";
            if (array_key_exists('forum', $_GET))
                $sql .= " forum = :forum ";
            else
                $sql .= " user = :user ";
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
                $command->bindParam(":user", $user);
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
                        $buf["isClosed"] = $row["isClosed"] == 0 ? false : true;
                        $buf["isDeleted"] = $row["isDeleted"] == 0 ? false : true;
                        $buf["likes"] = (int)$row["likes"];
                        $buf["message"] = $row["message"];
                        $buf["points"] = (int)$row["points"];
                        $buf["posts"] = (int)$row["posts"];
                        $buf["slug"] = $row["slug"];
                        $buf["title"] = $row["title"];
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


    public function actionListPosts()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('thread', $_GET)) {
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

            $sort = 'flat';
            if (array_key_exists('sort', $_GET))
                $sort = $_GET['sort'];

            if (strcmp($sort, 'flat') && strcmp($sort, 'tree') && strcmp($sort, 'parent_tree')) {
                echo json_encode($response);
                return;
            }

            if (strcmp($sort, "parent_tree") == 0) {
                $bounds = ControllersHelper::getBoundsForParentTreeSort($thread, $order, $limit);
            }

            $connection = Yii::app()->db;

            $sql = "SELECT *, (likes - dislikes) AS points FROM post WHERE thread = :thread ";

            if (array_key_exists('since', $_GET))
                $sql .= " AND date >= :since ";

            if (strcmp($sort, "flat") == 0) {
                $sql .= " ORDER BY date " . $order . " ";
                if (array_key_exists('limit', $_GET))
                    $sql .= " LIMIT " . strval(intval($limit));
            }
            elseif (strcmp($sort, "tree") == 0) {
                $sql .= " ORDER BY " . (strcmp($order, "desc") == 0 ?
                        (" SUBSTRING_INDEX(path, '.', 2) DESC, TRIM(LEADING SUBSTRING_INDEX(path, '.', 2) FROM path) ") :
                        " path ");
                if (array_key_exists('limit', $_GET))
                    $sql .= " LIMIT " . strval(intval($limit));
            } else {
                $sql .= " AND SUBSTRING_INDEX(path, '.', 2) BETWEEN :left AND :right
                        ORDER BY " . (strcmp($order, "desc") == 0 ? (" SUBSTRING_INDEX(path, '.', 2) DESC, TRIM(LEADING SUBSTRING_INDEX(path, '.', 2) FROM path) ") : " path ");
            }
            $sql .= ";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":thread", $thread);
            if (strcmp($sort, "parent_tree") == 0) {
                $command->bindParam(":left", $bounds[0]);
                $command->bindParam(":right", $bounds[1]);
            }
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
                    if  (strcmp($sort, "flat") == 0) {
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
                    } else {
                        $paths = array();
                        foreach ($result as $row) {
                            $path = explode('.', $row['path']);
                            $buf = &$response["response"];
                            $temp_paths = &$paths;
                            $flag1 = false; // skipping thread.id
                            $flag2 = false; // for root nodes
                            foreach ($path as $cur_path) {
                                if ($flag1) {
                                    if ($flag2) {
                                        $buf = &$buf["childs"];
                                        $temp_paths = &$temp_paths["childs"];
                                    } else
                                        $flag2 = true;
                                    $j = 0;
                                    $len = count($temp_paths);
                                    while ($j < $len && strcmp($temp_paths[$j]["cur_path"], $cur_path))
                                        ++$j;
                                    if ($j == $len) {
                                        array_push($buf, array());
                                        array_push($temp_paths, array());
                                    }
                                    $buf = &$buf[$j];
                                    $temp_paths = &$temp_paths[$j];
                                } else
                                    $flag1 = true;

                            }
                            $temp_paths["cur_path"] = $cur_path;
                            $buf["path"] = $row["path"];
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
                            $buf["childs"] = array();
                            $temp_paths["childs"] = array();
                        }
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


    public function actionOpen()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 1) {
            if (array_key_exists('thread', $request)) {
                $thread = $request['thread'];
                $connection = Yii::app()->db;

                $sql = "UPDATE thread SET isClosed = 0 WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
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


    public function actionRemove()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 1) {
            if (array_key_exists('thread', $request)) {
                $thread = $request['thread'];
                $connection = Yii::app()->db;

                $sql = "UPDATE thread SET isDeleted = 1, posts = 0 WHERE id = :thread LIMIT 1;
                        UPDATE post SET isDeleted = 1 WHERE thread = :thread;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
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
            if (array_key_exists('thread', $request)) {
                $thread = $request['thread'];

                $cnt = ControllersHelper::getPostCountByThreadId($thread);
                $connection = Yii::app()->db;

                $sql = "UPDATE thread SET isDeleted = 0, posts = :cnt WHERE id = :thread LIMIT 1;
                        UPDATE post SET isDeleted = 0 WHERE thread = :thread;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":cnt", $cnt);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
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


    public function actionSubscribe()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('user', $request) && array_key_exists('thread', $request)) {
                $user = $request['user'];
                $thread = $request['thread'];

                $user_id = ControllersHelper::getUserIdByEmail($user);
                $connection = Yii::app()->db;

                $sql = "INSERT INTO subscriptions (t_id, u_id) VALUES (:thread,:user_id);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                $command->bindParam(":user_id", $user_id);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
                    $buf["user"] = $user;
                    $response["response"] = $buf;
                } catch (Exception $e) {
                    $response["code"] = 4;
                    $response["response"] = $e->getMessage();
                    }
            }
        }
        echo json_encode($response);
    }


    public function actionUnsubscribe()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('user', $request) && array_key_exists('thread', $request)) {
                $user = $request['user'];
                $thread = $request['thread'];

                $user_id = ControllersHelper::getUserIdByEmail($user);
                $connection = Yii::app()->db;

                $sql = "DELETE FROM subscriptions WHERE t_id = :thread and u_id = :user_id LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                $command->bindParam(":user_id", $user_id);
                try {
                    $command->execute();
                    $response["code"] = 0;
                    $buf = array();
                    $buf["thread"] = $thread;
                    $buf["user"] = $user;
                    $response["response"] = $buf;
                } catch (Exception $e) {
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
        if (count($request) == 3) {
            if (array_key_exists('thread', $request) && array_key_exists('message', $request) &&
                array_key_exists('slug', $request)) {
                $thread = $request['thread'];
                $message = $request['message'];
                $slug = $request['slug'];

                $connection = Yii::app()->db;
                $sql = "UPDATE thread SET message = :message, slug = :slug WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":message", $message);
                $command->bindParam(":slug", $slug);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $sql = "SELECT *, (likes - dislikes) AS points FROM thread WHERE id = :thread LIMIT 1";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":thread", $thread);
                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "Thread was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["date"] = $row["date"];
                        $buf["dislikes"] = (int)$row["dislikes"];
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
            if (array_key_exists('thread', $request) && array_key_exists('vote', $request)) {
                $thread = $request['thread'];
                $vote = $request['vote'];
                if (abs($vote) != 1) {
                    $code = 3;
                    $status = "Invalid query";
                    $response = array('code' => $code, 'response' => $status);
                    echo json_encode($response);
                    exit;
                }
                $connection = Yii::app()->db;
                $sql = "UPDATE thread SET " . ($vote == 1 ? " likes = likes + 1 " : "dislikes = dislikes + 1") .
                                    " WHERE id = :thread LIMIT 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $sql = "SELECT *, (likes - dislikes) AS points FROM thread WHERE id = :post LIMIT 1";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":thread", $thread);
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
                        $buf["isClosed"] = $row["isClosed"] == 0 ? false : true;
                        $buf["isDeleted"] = $row["isDeleted"] == 0 ? false : true;
                        $buf["likes"] = (int)$row["likes"];
                        $buf["message"] = $row["message"];
                        $buf["points"] = (int)$row["points"];
                        $buf["posts"] = (int)$row["posts"];
                        $buf["slug"] = $row["slug"];
                        $buf["title"] = $row["title"];
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
