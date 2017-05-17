<?php
/**
 * User: Pavle Lee <523260513@qq.com>
 * Date: 2017/5/17
 * Time: 13:59
 */

namespace pavle\yii\redis;

use Predis\Client;
use yii\db\Exception;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use yii\redis\LuaScriptBuilder;

class Connection extends \yii\redis\Connection
{
    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var mixed Connection parameters for one or more servers.
     */
    public $parameters;

    /**
     * @var mixed Options to configure some behaviours of the client.
     */
    public $options;

    /**
     * @var Client redis connection
     */
    private $_socket = false;

    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     * @return bool whether the DB connection is established
     */
    public function getIsActive()
    {
        return $this->_socket !== false;
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->_socket !== false) {
            return;
        }

        $trace1 = VarDumper::dumpAsString($this->parameters);
        $trace2 = VarDumper::dumpAsString($this->options);
        $trace = <<<EOL
Opening redis DB connection
-- Parameters : 
{$trace1}
-- Options :
{$trace2}
EOL;

        \Yii::trace($trace, __METHOD__);

        $this->_socket = new Client($this->parameters, $this->options);
        if (!$this->_socket) {
            \Yii::error("Failed to open redis DB connection", __CLASS__);
            $message = YII_DEBUG ? $trace : 'Failed to open redis DB connection.';
            throw new Exception($message);
        }

        $this->initConnection();
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->_socket !== false) {
            $this->_socket->disconnect();
            $this->_socket = false;
        }
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Returns the name of the DB driver for the current [[dsn]].
     * @return string name of the DB driver
     */
    public function getDriverName()
    {
        return 'redis';
    }

    /**
     * @return LuaScriptBuilder
     */
    public function getLuaScriptBuilder()
    {
        return new LuaScriptBuilder();
    }

    /**
     * Allows issuing all supported commands via magic methods.
     *
     * ```php
     * $redis->hmset('test_collection', 'key1', 'val1', 'key2', 'val2')
     * ```
     *
     * @param string $name name of the missing method to execute
     * @param array $params method call arguments
     * @return mixed
     */
    public function __call($name, $params)
    {
        $redisCommand = strtoupper(Inflector::camel2words($name, false));
        if (in_array($redisCommand, $this->redisCommands)) {
            return $this->executeCommand($redisCommand, $params);
        } else {
            return parent::__call($name, $params);
        }
    }

    /**
     * Executes a redis command.
     * For a list of available commands and their parameters see http://redis.io/commands.
     *
     * The params array should contain the params separated by white space, e.g. to execute
     * `SET mykey somevalue NX` call the following:
     *
     * ```php
     * $redis->executeCommand('SET', ['mykey', 'somevalue', 'NX']);
     * ```
     *
     * @param string $name the name of the command
     * @param array $params list of parameters for the command
     * @return array|bool|null|string Dependent on the executed command this method
     * will return different data types:
     *
     * - `true` for commands that return "status reply" with the message `'OK'` or `'PONG'`.
     * - `string` for commands that return "status reply" that does not have the message `OK` (since version 2.0.1).
     * - `string` for commands that return "integer reply"
     *   as the value is in the range of a signed 64 bit integer.
     * - `string` or `null` for commands that return "bulk reply".
     * - `array` for commands that return "Multi-bulk replies".
     *
     * See [redis protocol description](http://redis.io/topics/protocol)
     * for details on the mentioned reply types.
     * @throws Exception for commands that return [error reply](http://redis.io/topics/protocol#error-reply).
     */
    public function executeCommand($name, $params = [])
    {
        $this->open();

        \Yii::trace("Executing Redis Command: {$name}", __METHOD__);

        return $this->_socket->executeCommand(
            $this->_socket->createCommand($name, $params)
        );
    }
}