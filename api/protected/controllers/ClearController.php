<?php

class ClearController extends Controller
{
	/**
     * Truncate all tables. 
     */
    public function actionIndex()
    {
        if (count($_POST) == 0) {
            $connection = Yii::app()->db;
            
            $sql = "SET FOREIGN_KEY_CHECKS=0;
                   TRUNCATE TABLE forum;
                   TRUNCATE TABLE post;
                   TRUNCATE TABLE user;
                   TRUNCATE TABLE followers;
                   TRUNCATE TABLE subscriptions;
                   TRUNCATE TABLE thread;
                   SET FOREIGN_KEY_CHECKS=1;";
            
            $command = $connection->createCommand($sql);
            $command->execute();
            
            $code = 0;    
            $status = "OK";
        } else {
            $code = 2;
            $status = "Invalid JSON";
        }
        
        $status = array('code' => $code, 'response' => $status);
        echo json_encode($status); 
    }
}