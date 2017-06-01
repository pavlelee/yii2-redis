<?php
/**
 * User: Pavle Lee <523260513@qq.com>
 * Date: 2017/6/1
 * Time: 11:52
 */

namespace pavle\yii\redis;

use Yii;

class ActiveRecord extends \yii\redis\ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

}