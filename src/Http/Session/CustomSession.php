<?php
namespace App\Http\Session;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Session\DatabaseSession;
use App\Http\Session\CustomDatabaseSession;

class CustomSession extends CustomDatabaseSession
{
    public $cacheKey;

    public function __construct()
    {
        // $this->cacheKey = Configure::read('Session.handler.cache');
        parent::__construct();
    }

    // Read data from the session.
    public function read($id)
    {
        // $result = Cache::read($id, $this->cacheKey);
        // if ($result) {
        //     return $result;
        // }
        $data = parent::read($id);
        return $data;
    }

    // Write data into the session.
    public function write($id, $data)
    {
        // Cache::write($id, $data, $this->cacheKey);
        return parent::write($id, $data);
    }

    // Destroy a session.
    public function destroy($id)
    {
        // Cache::delete($id, $this->cacheKey);
        return parent::destroy($id);
    }

    // Removes expired sessions.
    public function gc($expires = null)
    {
        return /*Cache::gc($this->cacheKey) &&*/ parent::gc($expires);
    }
}
