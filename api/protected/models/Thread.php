<?php

/**
 * This is the model class for table "thread".
 *
 * The followings are the available columns in table 'thread':
 * @property integer $id
 * @property string $forum
 * @property string $title
 * @property integer $isClosed
 * @property string $user
 * @property string $date
 * @property string $message
 * @property string $slug
 * @property integer $isDeleted
 * @property integer $likes
 * @property integer $dislikes
 * @property integer $posts
 *
 * The followings are the available model relations:
 * @property Post[] $posts0
 * @property User[] $users
 * @property Forum $forum0
 * @property User $user0
 */
class Thread extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'thread';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('forum, title, isClosed, user, date, message, slug, isDeleted, likes, dislikes, posts', 'required'),
			array('isClosed, isDeleted, likes, dislikes, posts', 'numerical', 'integerOnly'=>true),
			array('forum, title, user, slug', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, forum, title, isClosed, user, date, message, slug, isDeleted, likes, dislikes, posts', 'safe', 'on'=>'search'),
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
			'posts0' => array(self::HAS_MANY, 'Post', 'thread'),
			'users' => array(self::MANY_MANY, 'User', 'subscriptions(t_id, u_id)'),
			'forum0' => array(self::BELONGS_TO, 'Forum', 'forum'),
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
			'forum' => 'Forum',
			'title' => 'Title',
			'isClosed' => 'Is Closed',
			'user' => 'User',
			'date' => 'Date',
			'message' => 'Message',
			'slug' => 'Slug',
			'isDeleted' => 'Is Deleted',
			'likes' => 'Likes',
			'dislikes' => 'Dislikes',
			'posts' => 'Posts',
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
		$criteria->compare('forum',$this->forum,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('isClosed',$this->isClosed);
		$criteria->compare('user',$this->user,true);
		$criteria->compare('date',$this->date,true);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('slug',$this->slug,true);
		$criteria->compare('isDeleted',$this->isDeleted);
		$criteria->compare('likes',$this->likes);
		$criteria->compare('dislikes',$this->dislikes);
		$criteria->compare('posts',$this->posts);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Thread the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
