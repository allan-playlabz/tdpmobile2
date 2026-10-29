<?php
declare(strict_types=1);

namespace App\Command;

use App\Lib\TwoFactor;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * `bin/cake two_factor reset <email>`: removes an agent user's authenticator app
 * and backup codes, for someone who lost both and has nobody to reset them in the
 * portal (masters). They set it up again at their next login, after an emailed code.
 */
class TwoFactorResetCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'two_factor reset';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Remove an agent user\'s authenticator app and backup codes.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addArgument('email', ['help' => 'The agent user\'s email address', 'required' => true]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments
     * @param \Cake\Console\ConsoleIo $io Console io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $email = (string)$args->getArgument('email');
        $user = $this->fetchTable('AgentUsers')->find()->where(['email' => $email])->first();
        if ($user === null) {
            $io->error(sprintf('No agent user with the email %s.', $email));

            return static::CODE_ERROR;
        }
        TwoFactor::reset($user->id);
        // Not LogsTable::logCustom(): it reads request headers, which don't exist on the CLI.
        $Logs = $this->fetchTable('Logs');
        $log = $Logs->newEmptyEntity();
        $log->set([
            'user_table' => 'agent_users',
            'type' => 'two_factor_reset',
            'table_name' => 'agent_users',
            'record_id' => $user->id,
            'values_before' => '[]',
            'values_after' => json_encode(['two_factor' => 'reset', 'by' => 'console']),
            'created' => date('Y-m-d H:i:s'),
            'ip_address' => '',
        ], ['guard' => false]);
        $Logs->save($log, ['checkRules' => false]);
        $io->success(sprintf('Authenticator removed for %s. They will set it up again at their next login.', $email));

        return static::CODE_SUCCESS;
    }
}
