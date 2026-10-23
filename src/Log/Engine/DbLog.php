<?php
/**
 * CakePHP DatabaseLog Plugin
 *
 * Licensed under The MIT License.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/dereuromark/CakePHP-DatabaseLog
 */

namespace App\Log\Engine;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Log\Engine\BaseLog;
use DatabaseLog\Log\Engine\DatabaseLog;

/**
 * DatabaseLog Engine
 */
class DbLog extends DatabaseLog {

	/**
	 * Construct the model class
	 *
	 * @param array $config
	 */
	public function __construct($config = []) {
		parent::__construct($config);
	}

	/**
	 * Write the log to database
	 * Overrides log function of parent
	 *
	 * @param string $level
	 * @param mixed $message
	 * @param array $context
	 * @return bool Success
	 */
	public function log($level, $message, array $context = []) {
		if ($this->getConfig('type')) {
			$level = $this->getConfig('type');
		} elseif ($this->getConfig('file')) {
			$level = $this->getConfig('file');
		}
		if (empty($context['scope'])) {
			$context['scope'] = Configure::read('Default.log_scope');
		}
        if (is_object($message) || is_array($message)) {
            $message = json_encode($message);
        }
		return parent::log($level, $message, $context);
	}

}
