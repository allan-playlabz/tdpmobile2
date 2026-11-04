<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\Table;

/**
 * Data-provider validation tests: `validData()` is a valid create payload, each case
 * applies changes to it (self::UNSET removes the key) and lists the expected failing
 * rules per field.
 *
 * Runs the table's default validator directly (what newEntity() runs before marshalling),
 * so rules that don't query the table need no database schema.
 */
trait ValidationTestTrait
{
    public const UNSET = '__unset__';

    /**
     * @return array<string, mixed>
     */
    abstract protected function validData(): array;

    /**
     * @return array<string, list<string>> field => failing rule names
     */
    protected function validationErrors(Table $table, array $data, bool $newRecord = true): array
    {
        $errors = $table->getValidator()->validate($data, $newRecord);

        return array_map(fn(array $rules): array => array_keys($rules), $errors);
    }

    protected function assertValidation(Table $table, array $changes, array $expected): void
    {
        $data = $this->validData();
        foreach ($changes as $field => $value) {
            if ($value === self::UNSET) {
                unset($data[$field]);
            } else {
                $data[$field] = $value;
            }
        }
        $errors = $this->validationErrors($table, $data);
        ksort($errors);
        ksort($expected);

        $this->assertSame($expected, $errors);
    }
}
