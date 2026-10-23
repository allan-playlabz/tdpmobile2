<?php
/**
 * Database Session save handler. Allows saving session information into a model.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Http\Session;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Locator\LocatorAwareTrait;
use SessionHandlerInterface;

/**
 * DatabaseSession provides methods to be used with Session.
 */
class CustomDatabaseSession implements SessionHandlerInterface
{
    use LocatorAwareTrait;
    /**
     * Reference to the table handling the session data
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Number of seconds to mark the session as expired
     *
     * @var int
     */
    protected $_timeout;

    /**
     * Constructor. Looks at Session configuration information and
     * sets up the session model.
     *
     * @param array $config The configuration for this engine. It requires the 'model'
     * key to be present corresponding to the Table to use for managing the sessions.
     */
    public function __construct(array $config = [])
    {
        $tableLocator = isset($config['tableLocator']) ? $config['tableLocator'] : TableRegistry::getTableLocator();

        if (empty($config['model'])) {
            $config = $tableLocator->exists('AgentSessions') ? [] : ['table' => 'agent_sessions'];
            $this->_table = $tableLocator->get('AgentSessions', $config);
        } else {
            $this->_table = $tableLocator->get($config['model']);
        }

        $this->_timeout = ini_get('session.gc_maxlifetime');
    }

    /**
     * Set the timeout value for sessions.
     *
     * Primarily used in testing.
     *
     * @param int $timeout The timeout duration.
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;

        return $this;
    }

    /**
     * Method called on open of a database session.
     *
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool Success
     */
    public function open(string $savePath, string $name): bool
    {
        return true;
    }

    /**
     * Method called on close of a database session.
     *
     * @return bool Success
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Method used to read from a database session.
     *
     * @param string|int $id ID that uniquely identifies session in database.
     * @return string Session data or empty string if it does not exist.
     */
    public function read(string $id): string|false
    {
        $result = $this->_table
            ->find('all')
            ->select(['data'])
            ->where([$this->_table->getPrimaryKey() => $id])
            ->enableHydration(false)
            ->first();

        if (empty($result)) {
            return '';
        }

        if (is_string($result['data'])) {
            return $result['data'];
        }

        $session = stream_get_contents($result['data']);

        if ($session === false) {
            return '';
        }
        // debug($session);exit;
        return $session;
    }

    /**
     * Helper function called on write for database sessions.
     *
     * @param string|int $id ID that uniquely identifies session in database.
     * @param mixed $data The data to be saved.
     * @return bool True for successful write, false otherwise.
     */
    public function write(string $id, string $data): bool
    {
        if (!$id) {
            return false;
        }
        $expires = time() + $this->_timeout;
        $record = compact('data', 'expires');
        $record[$this->_table->getPrimaryKey()] = $id;
        $old_session = $_SESSION;
        session_decode($data);
        $decoded_data = $_SESSION;
        $_SESSION = $old_session;
        if (array_key_exists('Auth', $decoded_data)) {
            if (array_key_exists('Admin', $decoded_data['Auth'])) {
                $record['agent_user_id'] = $decoded_data['Auth']['Admin']['id'];
                // $record['user_type'] = 'admin';
            }
            else if (array_key_exists('User', $decoded_data['Auth'])){

                $record['agent_user_id'] = $decoded_data['Auth']['User']['id'];
                // $record['user_type'] = 'participant';
            }
            // 3.x matched nothing for a null agent_user_id; 5.x throws on null in where()
            $old_record = isset($record['agent_user_id']) ? $this->_table->find()->where(['id !='=>$record['id'],'agent_user_id'=>$record['agent_user_id']])->first() : null;
            if ($old_record) {
                $this->_table->delete($old_record);
            }
        }
        
        
        $record_entity = new Entity($record);
        $result = $this->_table->save($record_entity);

        return (bool)$result;
    }

    /**
     * Method called on the destruction of a database session.
     *
     * @param string|int $id ID that uniquely identifies session in database.
     * @return bool True for successful delete, false otherwise.
     */
    public function destroy(string $id): bool
    {
        $this->_table->delete(new Entity(
            [$this->_table->getPrimaryKey() => $id],
            ['markNew' => false]
        ));

        return true;
    }

    /**
     * Helper function called on gc for database sessions.
     *
     * @param int $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
     * @return bool True on success, false on failure.
     */
    public function gc(int $maxlifetime): int|false
    {
        return $this->_table->deleteAll(['expires <' => time() - $maxlifetime]);
    }
}
