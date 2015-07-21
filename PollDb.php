<?php

namespace davidjeddy\yii2poll;

use yii;

class PollDb {

    public function isPollExist($pollName) {
        $db = Yii::$app->db;
        $command = $db->createCommand('SELECT * FROM poll_question WHERE poll_name=:pollName')->
        bindParam(':pollName',$pollName);

        $pollData = $command->queryOne();

        if ($pollData==null) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * [setVoicesData description]
     *
     * @deprecated 2.0.2 Use pollAnswerOptions()
     * @param [type] $pollName      [description]
     * @param [type] $answerOptions [description]
     */
    public function setVoicesData($pollName, $answerOptions) {
        $db = Yii::$app->db;
        $answersList = array();

        for($i=0; $i<count($answerOptions); $i++){
            $command = $db->createCommand()->insert('poll_response', [
                'answers'   => $answerOptions[$i],
                'poll_name' => $pollName,
                'value'     => 0,
            ])->execute(); 
        }
    }

    /**
     * poll_response TBO logic
     * ADDS new answers dynamically.
     * REMOVES answers that are not part of $pollObj->answerOptionsData
     * 
     * @param  [type] $pollObj [description]
     * @return [type]          [description]
     */
    public function pollAnswerOptions($pollObj)
    {
        $db = Yii::$app->db;

        foreach ($pollObj->answerOptions as $key => $value) {

            $answer = $db->createCommand('
                SELECT `answers`
                FROM `poll_response`
                WHERE `poll_name` = "'.$pollObj->pollName.'"
                    AND `answers` = "'.$value.'"
            ')->queryOne();

            if (!$answer) {
                $db->createCommand()->insert('poll_response', [
                    'answers'   => $value,
                    'poll_name' => $pollObj->pollName,
                    'value'     => 0,
                ])->execute(); 
            }
        }

        // remove answers that are no longer a part of the poll answer_options
        $db->createCommand('
            DELETE FROM `poll_response`
            WHERE `poll_name` = "'.$pollObj->pollName.'"
                AND `answers` NOT IN ( \''.implode($pollObj->answerOptions, "', '").'\' );
        ')->execute();

        return true;
    }

    public function getVoicesData($pollName) {
        $db = Yii::$app->db;
        $command = $db->createCommand('SELECT * FROM poll_response WHERE poll_name=:pollName')->
        bindParam(':pollName',$pollName);
        $voicesData = $command->queryAll();
        return $voicesData;
    }

    /**
     * [updateAnswers description]
     * @version  2.0.7
     * @since  na
     * @param  string $pollName     Poll name
     * @param  integer $voice       Integer of chosen key
     * @param  array $answerOptions Array fo possible options
     * @return object
     */
    public function updateAnswers($pollName, $voice, $answerOptions) {

        return Yii::$app->db->createCommand("
            UPDATE poll_response
            SET value = value +1  
            WHERE poll_name = '$pollName'
                AND answers = '$answerOptions[$voice]'")
        ->execute();

    }

    public function updateUsers($pollName) {
        $db = Yii::$app->db;
        $command = $db->createCommand('SELECT * FROM poll_question WHERE poll_name=:pollName')->
        bindParam(':pollName',$pollName);
        $userId;
        if(Yii::$app->user->getId()==null){
            $userId = 0;
        } else {
            $userId = Yii::$app->user->getId(); 
        }
        $pollData = $command->queryOne();
        $command = $db->createCommand()->insert('poll_user', [
            'poll_id' => $pollData['id'],
            'user_id' => $userId
            ])->execute(); 
    }

    public  function isVote($pollName) {
        $db = Yii::$app->db;
        $command = $db->createCommand('SELECT * FROM poll_question WHERE poll_name=:pollName')->
        bindParam(':pollName',$pollName);
        $pollData = $command->queryOne();
        $userId;
        if(Yii::$app->user->getId()== null){
            $userId = 0;
        } else {
            $userId = Yii::$app->user->getId(); 
        }
        $db = Yii::$app->db;
        $command = $db->createCommand("SELECT * FROM  poll_user  WHERE user_id='$userId' AND poll_id=:pollId")->
        bindParam(':pollId',$pollData['id']);
        $result = $command->queryOne();
        
        if($result == null){

            return false;
        } else {

            return true;
        }
    }

    /**
     * Create the DBO TBO and events using a sql dump file
     *
     * @todo  Differetn SQL files based on the DB type widget settings
     * @return [type] [description]
     */
    public function createTables() {   
        return Yii::$app->db->createCommand(
            file_get_contents("./sql/mysql.sql")
        )->execute(); 
    }

    public function isTableExists() {
        $db      = Yii::$app->db;
        $command = $db->createCommand("SHOW TABLES LIKE 'poll_question'");
        $res     = $command->queryAll();

        return $res;
    }
}
