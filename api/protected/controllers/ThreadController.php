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

                $sql = "UPDATE thread SET isClosed = 1 WHERE id = :thread;";
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
    
//        $fp = fopen("C:\\file.txt", "w");
//        fwrite($fp, file_get_contents('php://input'));
//        fclose($fp);
//        $request = json_decode($_POST["a"], true);

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
            $correct = true;            
            $rel_type = 0;
            $sql_user_part = "user.id as u_id, user.name as u_name, about, email, isAnonymous, username,
                   (
                   select group_concat(u2.email)
                   from user as u1
                   join followers as f
                   on u1.id = f.u_to
                   join user as u2
                   on f.u_from = u2.id
                   where u1.id = u_id
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
                   where u1.id = u_id
                   group by f.u_from
                   ) as following
                   ,
                   (
                   select group_concat(t_id)
                   from user as u
                   join subscriptions as s
                   on u.id = s.u_id
                   where u.id = u_id
                   group by u.id
                   ) as subscriptions ";
            if (array_key_exists('user', $related) && array_key_exists('forum', $related)) {
                $sql = "select forum.id as f_id, forum.name as f_name, short_name, forum.user as f_user, 
                    thread.*, (likes - dislikes) as points, ".
                    $sql_user_part.
                    "from thread join user on thread.user = user.email
                    join forum on thread.forum = forum.short_name 
                    where thread.id = :thread_id;";
                $rel_type = 1;
            } else if (array_key_exists('user', $related)) {
                $sql = "select thread.*, (likes - dislikes) as points, ".
                    $sql_user_part.
                    "from thread join user on thread.user = user.email
                    where thread.id = :thread_id;";
                $rel_type = 2;
            } else if (array_key_exists('forum', $related)) {
                $sql = "select forum.id as f_id, forum.name as f_name, short_name, forum.user as f_user, 
                    thread.*, (likes - dislikes) as points
                    from thread join forum on thread.forum = forum.short_name 
                    where thread.id = :thread_id;";
                $rel_type = 3;
            } else
                $sql = "select *, (likes - dislikes) as points from thread where thread.id = :thread_id;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":thread_id", $thread); 
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
                    if ($rel_type == 1 || $rel_type == 3) {
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
                    if ($rel_type == 1 || $rel_type == 2) {
                        $buf2 = array();
                        $buf2["about"] = $row["about"];
                        $buf2["email"] = $row["email"];
                        
                        $buf2["followers"] = $row["followers"] == null ? array() : explode(", ", $row["followers"]);
                        $buf2["following"] = $row["following"] == null ? array() : explode(", ", $row["following"]);
                        
                        $buf2["id"] = $row["u_id"];
                        $buf2["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf2["name"] = $row["u_name"];
                        
                        $buf2["subscriptions"] = $row["subscriptions"] == null ? array() : explode(", ", $row["subscriptions"]);
                        
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
            $sql = "select *, (likes - dislikes) as points from thread where ";
            if (array_key_exists('forum', $_GET))
                $sql .= " forum = :forum ";
            else
                $sql .= " user = :user ";
            if (array_key_exists('since', $_GET))
                $sql .= " and date >= :since ";
            $sql .= " order by date ".$order." ";
            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
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
//                    $response["code"] = 1;
//                    $response["response"] = "Threads were not found";
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

            $sort = 'flat';
            if (array_key_exists('sort', $_GET))
                $sort = $_GET['sort'];

            $connection = Yii::app()->db;

            $sql = "select *, (likes - dislikes) as points from post where thread = :thread ";

            if (array_key_exists('since', $_GET))
                $sql .= " and date >= :since ";

            if (strcmp($sort, "flat") == 0) {
                $sql .= " order by date " . $order . " ";
                if (array_key_exists('limit', $_GET))
                    $sql .= " limit " . strval(intval($limit));
            }
            elseif (strcmp($sort, "tree") == 0) {
                $sql .= " order by " . (strcmp($order, "desc") == 0 ? (" substring_index(path, '.', 2) " . $order . ", trim(leading substring_index(path, '.', 2) from path) ") : " path ");
                if (array_key_exists('limit', $_GET))
                    $sql .= " limit " . strval(intval($limit));
            } else {
                $sql .= " and substring_index(path, '.', 2) between
                    (select substring_index(min(t.path), '.', 2) from
                        (select path from post where thread = :thread and parent is NULL order by path ".$order." ";
                if (array_key_exists('limit', $_GET))
                    $sql .= " limit " . strval(intval($limit))." ";
                $sql .= ") as t
                    )
                    and
                    (select max(t.path) from
                        (select path from post where thread = :thread and parent is NULL order by path ".$order." ";
                if (array_key_exists('limit', $_GET))
                    $sql .= " limit " . strval(intval($limit))." ";
                $sql .= ") as t
                    ) ";
                $sql .= " order by " . (strcmp($order, "desc") == 0 ? (" substring_index(path, '.', 2) " .$order. ", trim(leading substring_index(path, '.', 2) from path) ") : " path ");
            }

            $sql .= ";";

            $command = $connection->createCommand($sql);
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
                    } else if (!strcmp($sort, "tree") || !strcmp($sort, "parent_tree")) {
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
                    } else {
                        $code = 3;
                        $status = "Invalid query";
                        $response = array('code' => $code, 'response' => $status);
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

                $sql = "UPDATE thread SET isClosed = 0 WHERE id = :thread;";
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

                $sql = "UPDATE thread SET isDeleted = 1, posts = 0 WHERE id = :thread;
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
                $connection = Yii::app()->db;

                $sql = "UPDATE thread SET isDeleted = 0, posts = (select count(post.id) from post where post.thread = thread.id)
                          WHERE id = :thread;
                        UPDATE post SET isDeleted = 0 WHERE thread = :thread;";
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
                $connection = Yii::app()->db;

                $sql = "INSERT INTO subscriptions (t_id, u_id) values (:thread,(select id from user where email = :user));";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                $command->bindParam(":user", $user);
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
                $connection = Yii::app()->db;

                $sql = "delete from subscriptions where t_id = :thread and u_id = (select id from user where email = :user);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                $command->bindParam(":user", $user);
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

//        $request = json_decode($_POST["a"], true);
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 3) {
            if (array_key_exists('thread', $request) && array_key_exists('message', $request) && array_key_exists('slug', $request)) {
                $thread = $request['thread'];
                $message = $request['message'];
                $slug = $request['slug'];

                $connection = Yii::app()->db;
                $sql = "update thread set message = :message, slug = :slug where id = :thread;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":message", $message);
                $command->bindParam(":slug", $slug);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $sql = "select *, (likes - dislikes) as points from thread where id = :thread";
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

//        $request = json_decode($_POST["a"], true);
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
                $sql = "update thread set " . ($vote == 1 ? " likes = likes + 1 " : "dislikes = dislikes + 1") . " where id = :thread;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":thread", $thread);
                try {
                    $command->execute();
                    $sql = "select *, (likes - dislikes) as points from thread where id = :post";
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
