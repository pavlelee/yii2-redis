<?php
/**
 * User: Pavle Lee <523260513@qq.com>
 * Date: 2017/6/1
 * Time: 11:46
 */

namespace pavle\yii\redis;


use yii\redis\ActiveRecord;

class ActiveQuery extends \yii\redis\ActiveQuery
{
    /**
     * Executes the query and returns all results as an array.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|ActiveRecord[] the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($db = null)
    {
        if ($this->emulateExecution) {
            return [];
        }

        // TODO add support for orderBy
        $rows = $this->executeScript($db, 'All');
        if (empty($rows)) {
            return [];
        }

        if (!empty($rows)) {
            $models = $this->createModels($rows);
            if (!empty($this->with)) {
                $this->findWith($this->with, $models);
            }
            if (!$this->asArray) {
                foreach ($models as $model) {
                    $model->afterFind();
                }
            }

            return $models;
        } else {
            return [];
        }
    }

    /**
     * Executes the query and returns a single row of result.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of [[asArray]],
     * the query result may be either an array or an ActiveRecord object. Null will be returned
     * if the query results in nothing.
     */
    public function one($db = null)
    {
        if ($this->emulateExecution) {
            return null;
        }

        // TODO add support for orderBy
        $row = $this->executeScript($db, 'One');
        if (empty($row)) {
            return null;
        }

        if ($this->asArray) {
            $model = $row;
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            $model = $class::instantiate($row);
            $class = get_class($model);
            $class::populateRecord($model, $row);
        }
        if (!empty($this->with)) {
            $models = [$model];
            $this->findWith($this->with, $models);
            $model = $models[0];
        }
        if (!$this->asArray) {
            $model->afterFind();
        }

        return $model;
    }
}