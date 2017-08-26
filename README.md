# yii2-redis
Using [Predis](https://github.com/nrk/predis) and yii2 combination
This extension is fully compatible with the yii2-redis extension, and you can use predis only if you replace the configuration

# Usage

1. Change your redis component configuration, There is no need to change any of the previous code, That's all right.
```
'redis' => [
    'class' => pavle\yii\redis\Connection::class,
    'parameters' => ['tcp://xx.xx.x.xx:30001', 'tcp://xx.xx.x.xx:30002', 'tcp://xx.xx.x.xx:30003'],
    //'parameters' => 'tcp://192.168.2.240:6379',
    'options' => ['cluster' => 'redis'],
],
```

2.More usage
[yii2-redis Document](https://github.com/yiisoft/yii2-redis/blob/master/docs/guide/README.md)

3.Using the predis client
```
Yii::$app->redis->getClient();
```
