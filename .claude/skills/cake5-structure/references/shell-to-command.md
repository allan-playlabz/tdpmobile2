# Shell → Command recipe

## Before (CakePHP 3.x)
```php
namespace App\Shell;

use Cake\Console\Shell;

class DeleteTempShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('TempFiles');
    }

    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addArgument('days', ['help' => 'Age in days', 'required' => false]);
        return $parser;
    }

    public function main()
    {
        $days = $this->args[0] ?? 7;
        $this->out('Deleting files older than ' . $days . ' days');
        $this->_cleanup((int)$days);
    }

    private function _cleanup(int $days) { /* ... $this->TempFiles->... */ }
}
```

## After (CakePHP 5.x)
```php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class DeleteTempCommand extends Command
{
    protected ConsoleIo $io;

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Delete temp files older than N days.');
        $parser->addArgument('days', ['help' => 'Age in days', 'required' => false]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->io = $io;
        $days = (int)($args->getArgument('days') ?? 7);
        $io->out('Deleting files older than ' . $days . ' days');
        $this->_cleanup($days);

        return static::CODE_SUCCESS;
    }

    private function _cleanup(int $days): void
    {
        $TempFiles = $this->fetchTable('TempFiles');
        // ...
    }
}
```

## Mapping
| Shell (3.x) | Command (5.x) |
|---|---|
| `extends Shell` | `extends Cake\Command\Command` |
| `main()` | `execute(Arguments $args, ConsoleIo $io): ?int` — return `static::CODE_SUCCESS` / `static::CODE_ERROR` |
| `getOptionParser()` | `buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser` |
| `$this->args[0]` | `$args->getArgument('name')` (declare it in the parser) or `$args->getArgumentAt(0)` |
| `$this->params['x']` / `$this->param('x')` | `$args->getOption('x')` |
| `$this->out() / err() / info() / success() / hr()` | `$io->out() / err() / info() / success() / hr()` — keep `$this->io = $io` if private helpers print |
| `$this->abort('msg')` | `$io->error('msg'); return static::CODE_ERROR;` or `$this->abort()` |
| `$this->in('Continue?', ['y','n'])` | `$io->askChoice('Continue?', ['y', 'n'])` |
| `$this->loadModel('X')` / `$this->X` | `$this->fetchTable('X')` (Command has `LocatorAwareTrait` already — don't add it again) |
| Sub-command methods (`bin/cake orders complete <ref>`) | Separate Command classes, or one command with an argument/option. **Don't** rely on `bin/cake x method arg` — it now parses `method` as the first argument. |
| Tasks (`src/Shell/Task`) | Plain service classes, or `$this->executeCommand(OtherCommand::class, [...])` |
| `$this->dispatchShell('x')` | `$this->executeCommand(XCommand::class, $args, $io)` |

## Naming & description
`bin/cake` derives the name from the class: `SendDigestCommand` → `send_digest`. Override with `public static function defaultName(): string { return 'old_name'; }` to keep a cron line unchanged.

The one-line description in the `bin/cake --help` list comes from a static method, not the option parser:
```php
public static function getDescription(): string
{
    return 'Email each user the articles published in the last N days.';
}
```
(`$parser->setDescription()` only shows in `bin/cake <name> --help`.)

## Checks
- `bin/cake <name> --help` for each converted command.
- Run each once in a safe mode/dry run if available.
- Update cron/supervisor/systemd entries; list the old → new command lines in `docs/UPGRADE_PROGRESS.md`.
- Shell-outs from web code (`exec('bin/cake x y z')`) — re-check argument order against the new parser (an old `bin/cake orders complete <ref>` sub-command call becomes `complete` + an extra argument, which a single-argument command rejects).
