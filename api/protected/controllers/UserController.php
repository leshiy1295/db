<?php

class UserController extends Controller
{
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
        if (count($request) >= 4 && count($request) <= 5) {
            if (array_key_exists('username', $request) && array_key_exists('about', $request) && 
                array_key_exists('name', $request) && array_key_exists('email', $request)) {
                $username = $request['username'];
                $about = $request['about'];
                $name = $request['name'];
                $email = $request['email'];
                
                $connection = Yii::app()->db;
                $sql = "SELECT 1 FROM user WHERE email = :email limit 1;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":email", $email);
                $dataReader = $command->queryAll();
                if (count($dataReader) == 0) {
                    $isAnonymous = 0;
                    if (array_key_exists('isAnonymous', $request))
                        $isAnonymous = $request['isAnonymous'] == false ? 0 : 1;
                    $sql = "INSERT INTO user (username, about, name, email, isAnonymous) 
                            VALUES (:username, :about, :name, :email, :isAnonymous);";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":username", $username);
                    $command->bindParam(":about", $about);
                    $command->bindParam(":name", $name);
                    $command->bindParam(":email", $email);
                    $command->bindParam(":isAnonymous", $isAnonymous);
                    try {
                        $command->execute();
                        $response["code"] = 0;
                        $buf = array();
                        $buf["about"] = $about;
                        $buf["email"] = $email;
                        $buf["id"] = $connection->getLastInsertID();
                        $buf["isAnonymous"] = $isAnonymous == 0 ? false : true;
                        $buf["name"] = $name;
                        $buf["username"] = $username;
                        $response["response"] = $buf;
                    }
                    catch (Exception $e) {
                        $response["code"] = 4;
                        $response["response"] = $e->getMessage();
                    }    
                } else {
                    $response["code"] = 5;
                    $response["response"] = "User with that email already exists";                                           
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
        
        if (array_key_exists('user', $_GET)) {
            $user = $_GET['user'];
            $connection = Yii::app()->db;
            $sql = "select ".ControllersHelper::getSqlBlockForUser().
               " from user as self
               where email = :email limit 1;";
            $command = $connection->createCommand($sql);
            $command->bindParam(":email", $user); 
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
                    $response["code"] = 1;
                    $response["response"] = "User was not found";
                } else {
                    $response["code"] = 0;
                    $row = $result[0];
                    $buf = array();
                    $buf["about"] = $row["about"];
                    $buf["email"] = $row["email"];
                    
                    $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                    $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);
                    
                    $buf["id"] = $row["user_id"];
                    $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                    $buf["name"] = $row["user_name"];
                    
                    $buf["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);
                    
                    $buf["username"] = $row["username"];
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


    public function actionFollow()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

//        $fp = fopen("C:\\file.txt", "w");
//        fwrite($fp, file_get_contents('php://input'));
//        fclose($fp);
//        $request = json_decode($_POST["a"], true);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('follower', $request) && array_key_exists('followee', $request)) {
                $follower = $request['follower'];
                $followee = $request['followee'];

                $follower_id = ControllersHelper::getUserIdByEmail($follower);
                $followee_id = ControllersHelper::getUserIdByEmail($followee);

                $connection = Yii::app()->db;

                $sql = "INSERT INTO followers (u_from, u_to) VALUES (:follower_id, :followee_id);";
                $command = $connection->createCommand($sql);
                $command->bindParam(":follower_id", $follower_id);
                $command->bindParam(":followee_id", $followee_id);
                try {
                    $command->execute();
                    $sql = "select ".
                            ControllersHelper::getSqlBlockForUser().
                           " from user as self
                           where email = :email limit 1;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":email", $follower);

                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "User was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];

                        $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                        $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                        $buf["id"] = $row["user_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["user_name"];

                        $buf["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                        $buf["username"] = $row["username"];
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


    public function actionListFollowers()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('user', $_GET)) {
            $user = $_GET['user'];

            if (array_key_exists('since_id', $_GET))
                $since_id = $_GET['since_id'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $id = ControllersHelper::getUserIdByEmail($user);

            $connection = Yii::app()->db;

            $sql = "select ".
                    ControllersHelper::getSqlBlockForUser().
                    " from user as self join
                    (select u_from from followers
                     where u_to = :id ";
            if (array_key_exists('since_id', $_GET))
                $sql .= " and u_from >= :since_id ";
            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ") as t
                    on self.id = t.u_from
                    order by self.name ".$order.";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":id", $id);
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

                        $buf["id"] = $row["user_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["user_name"];

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


    public function actionListFollowing()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('user', $_GET)) {
            $user = $_GET['user'];

            if (array_key_exists('since_id', $_GET))
                $since_id = $_GET['since_id'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $id = ControllersHelper::getUserIdByEmail($user);

            $connection = Yii::app()->db;

            $sql = "select ".
                ControllersHelper::getSqlBlockForUser().
                " from user as self join
                    (select u_to from followers
                     where u_from = :id ";
            if (array_key_exists('since_id', $_GET))
                $sql .= " and u_to >= :since_id ";
            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ") as t
                    on self.id = t.u_to
                    order by self.name ".$order.";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":id", $id);
            if (array_key_exists('since_id', $_GET))
                $command->bindParam(":since_id", $since_id);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
//                    $response["code"] = 1;
//                    $response["response"] = "Users were not found";
                    $response["code"] = 0;
                    $response["response"] = array();
                } else {
                    $response["code"] = 0;
                    $response["response"] = array();
                    foreach ($result as $row) {
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];

                        $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                        $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                        $buf["id"] = $row["user_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["user_name"];

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


    public function actionListPosts()
    {
        $code = 3;
        $status = "Invalid query";
        $response = array('code' => $code, 'response' => $status);

        if (array_key_exists('user', $_GET)) {
            $user = $_GET['user'];

            if (array_key_exists('since', $_GET))
                $since = $_GET['since'];

            if (array_key_exists('limit', $_GET))
                $limit = $_GET['limit'];

            $order = 'desc';
            if (array_key_exists('order', $_GET))
                $order = $_GET['order'];

            $connection = Yii::app()->db;

            $sql = "select *, (likes - dislikes) as points from post where user = :user ";

            if (array_key_exists('since', $_GET))
                $sql .= " and date >= :since ";

            $sql .= " order by date ".$order." ";

            if (array_key_exists('limit', $_GET))
                $sql .= " limit ".strval(intval($limit));
            $sql .= ";";

            $command = $connection->createCommand($sql);
            $command->bindParam(":user", $user);
            if (array_key_exists('since', $_GET))
                $command->bindParam(":since", $since);
            try {
                $result = $command->queryAll();
                if (count($result) == 0) {
//                    $response["code"] = 1;
//                    $response["response"] = "Posts were not found";
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


    public function actionUnfollow()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

//        $fp = fopen("C:\\file.txt", "w");
//        fwrite($fp, file_get_contents('php://input'));
//        fclose($fp);
//        $request = json_decode($_POST["a"], true);

        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 2) {
            if (array_key_exists('follower', $request) && array_key_exists('followee', $request)) {
                $follower = $request['follower'];
                $followee = $request['followee'];

                $follower_id = ControllersHelper::getUserIdByEmail($follower);
                $followee_id = ControllersHelper::getUserIdByEmail($followee);

                $connection = Yii::app()->db;

                $sql = "delete from followers where u_from = :follower_id and u_to = :followee_id;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":follower_id", $follower_id);
                $command->bindParam(":followee_id", $followee_id);
                try {
                    $command->execute();
                    $sql = "select ".
                            ControllersHelper::getSqlBlockForUser().
                           " from user as self
                           where email = :email;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":email", $follower);

                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "User was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];

                        $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                        $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                        $buf["id"] = $row["user_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["user_name"];

                        $buf["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                        $buf["username"] = $row["username"];
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


    public function actionUpdateProfile()
    {
        $code = 2;
        $status = "Invalid JSON";
        $response = array('code' => $code, 'response' => $status);

//        $request = json_decode($_POST["a"], true);
        $request = json_decode(file_get_contents('php://input'), true);
        if (count($request) == 3) {
            if (array_key_exists('about', $request) && array_key_exists('user', $request) && array_key_exists('name', $request)) {
                $about = $request['about'];
                $user = $request['user'];
                $name = $request['name'];

                $connection = Yii::app()->db;
                $sql = "update user set about = :about, name = :name where email = :user;";
                $command = $connection->createCommand($sql);
                $command->bindParam(":about", $about);
                $command->bindParam(":user", $user);
                $command->bindParam(":name", $name);
                try {
                    $command->execute();
                    $sql = "select ".
                            ControllersHelper::getSqlBlockForUser().
                            " from user as self
                            where email = :email;";
                    $command = $connection->createCommand($sql);
                    $command->bindParam(":email", $user);

                    $result = $command->queryAll();
                    if (count($result) == 0) {
                        $response["code"] = 1;
                        $response["response"] = "User was not found";
                    } else {
                        $response["code"] = 0;
                        $row = $result[0];
                        $buf = array();
                        $buf["about"] = $row["about"];
                        $buf["email"] = $row["email"];

                        $buf["followers"] = $row["followers"] == null ? array() : explode(",", $row["followers"]);
                        $buf["following"] = $row["following"] == null ? array() : explode(",", $row["following"]);

                        $buf["id"] = $row["user_id"];
                        $buf["isAnonymous"] = $row["isAnonymous"] == 0 ? false : true;
                        $buf["name"] = $row["user_name"];

                        $buf["subscriptions"] = $row["subscriptions"] == null ? array() : explode(",", $row["subscriptions"]);

                        $buf["username"] = $row["username"];
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
}