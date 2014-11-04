<?php

/**
 * This is the model class for table "post".
 *
 * The followings are the available columns in table 'post':
 * @property integer $id
 * @property string $date
 * @property integer $thread
 * @property string $message
 * @property string $user
 * @property string $forum
 * @property integer $parent
 * @property integer $isApproved
 * @property integer $isHighlighted
 * @property integer $isEdited
 * @property integer $isSpam
 * @property integer $isDeleted
 * @property integer $likes
 * @property integer $dislikes
 * @property string $path
 *
 * The followings are the available model relations:
 * @property Forum $forum0
 * @property Thread $thread0
 * @property User $user0
 */
class Post extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'post';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('date, thread, message, user, forum, isApproved, isHighlighted, isEdited, isSpam, isDeleted, likes, dislikes, path', 'required'),
			array('thread, parent, isApproved, isHighlighted, isEdited, isSpam, isDeleted, likes, dislikes', 'numerical', 'integerOnly'=>true),
			array('user, forum, path', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, date, thread, message, user, forum, parent, isApproved, isHighlighted, isEdited, isSpam, isDeleted, likes, dislikes, path', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'forum0' => array(self::BELONGS_TO, 'Forum', 'forum'),
			'thread0' => array(self::BELONGS_TO, 'Thread', 'thread'),
			'user0' => array(self::BELONGS_TO, 'User', 'user'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'date' => 'Date',
			'thread' => 'Thread',
			'message' => 'Message',
			'user' => 'User',
			'forum' => 'Forum',
			'parent' => 'Parent',
			'isApproved' => 'Is Approved',
			'isHighlighted' => 'Is Highlighted',
			'isEdited' => 'Is Edited',
			'isSpam' => 'Is Spam',
			'isDeleted' => 'Is Deleted',
			'likes' => 'Likes',
			'dislikes' => 'Dislikes',
			'path' => 'Path',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('date',$this->date,true);
		$criteria->compare('thread',$this->thread);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('user',$this->user,true);
		$criteria->compare('forum',$this->forum,true);
		$criteria->compare('parent',$this->parent);
		$criteria->compare('isApproved',$this->isApproved);
		$criteria->compare('isHighlighted',$this->isHighlighted);
		$criteria->compare('isEdited',$this->isEdited);
		$criteria->compare('isSpam',$this->isSpam);
		$criteria->compare('isDeleted',$this->isDeleted);
		$criteria->compare('likes',$this->likes);
		$criteria->compare('dislikes',$this->dislikes);
		$criteria->compare('path',$this->path,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Post the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
