<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property integer $id
 * @property string $username
 * @property string $about
 * @property string $email
 * @property integer $isAnonymous
 *
 * The followings are the available model relations:
 * @property Followers[] $followers
 * @property Followers[] $followers1
 * @property Forum[] $forums
 * @property Post[] $posts
 * @property Thread[] $threads
 * @property Thread[] $threads1
 */
class User extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, about, email, isAnonymous', 'required'),
			array('isAnonymous', 'numerical', 'integerOnly'=>true),
			array('username, email', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, username, about, email, isAnonymous', 'safe', 'on'=>'search'),
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
			'followers' => array(self::HAS_MANY, 'Followers', 'u_from'),
			'followers1' => array(self::HAS_MANY, 'Followers', 'u_to'),
			'forums' => array(self::HAS_MANY, 'Forum', 'user'),
			'posts' => array(self::HAS_MANY, 'Post', 'user'),
			'threads' => array(self::MANY_MANY, 'Thread', 'subscriptions(u_id, t_id)'),
			'threads1' => array(self::HAS_MANY, 'Thread', 'user'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => 'Username',
			'about' => 'About',
			'email' => 'Email',
			'isAnonymous' => 'Is Anonymous',
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
		$criteria->compare('username',$this->username,true);
		$criteria->compare('about',$this->about,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('isAnonymous',$this->isAnonymous);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
