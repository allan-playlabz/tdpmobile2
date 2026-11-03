<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\AppView;
use App\View\Helper\QuestionGroupHelper;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\FormHelper;
use Exception;

/**
 * Booking questions (question groups) rendered on the checkout page.
 */
class QuestionGroupHelperTest extends TestCase
{
    private QuestionGroupHelper $helper;
    private FormHelper $form;

    protected function setUp(): void
    {
        parent::setUp();
        $view = new AppView(new ServerRequest());
        $this->helper = new QuestionGroupHelper($view);
        $this->form = new FormHelper($view);
        // Title de-duplication is kept in static properties across calls.
        QuestionGroupHelper::$group_title = '';
        QuestionGroupHelper::$attraction_ticket_name = '';
    }

    private function field(array $values): array
    {
        return $values + ['name' => 'f', 'question' => 'Question?', 'is_required' => false, 'type' => 'text', 'min' => null, 'max' => null, 'data' => null];
    }

    private function render(array $fields, string $title = 'Guest details', string $suffix = 'g1', bool $lastRow = false): string
    {
        ob_start();
        $this->helper->render(['title' => $title, 'fields' => $fields], $this->form, $suffix, 'Zoo', 'Adult', '01 Jan 2027', 1, null, $lastRow);

        return (string)ob_get_clean();
    }

    public function testTitleBlockAndTextFieldWithLengthLimits(): void
    {
        $html = $this->render([$this->field(['name' => 'full_name', 'question' => 'Full name', 'is_required' => true, 'min' => 2, 'max' => 50])]);

        $this->assertStringContainsString('<h5>Zoo<br>Adult<br>01 Jan 2027</h5>', $html);
        $this->assertStringContainsString('<p>Guest details</p>', $html);
        $this->assertStringContainsString('name="question_group[full_name][g1]"', $html);
        $this->assertStringContainsString('[1] Full name', $html);
        $this->assertStringContainsString('minlength="2"', $html);
        $this->assertStringContainsString('maxlength="50"', $html);
        $this->assertStringContainsString('required="required"', $html);
        $this->assertStringContainsString('<hr class="hr-50">', $html);
    }

    public function testTitleIsPrintedOnceForTheSameGroupAndTicket(): void
    {
        $this->render([$this->field([])]);
        $second = $this->render([$this->field([])], 'Guest details', 'g2', true);

        $this->assertStringNotContainsString('form_title', $second);
        $this->assertStringNotContainsString('<hr', $second, 'no separator after the last row');

        $other = $this->render([$this->field([])], 'Pickup');
        $this->assertStringContainsString('<p>Pickup</p>', $other);
    }

    public function testNumberUsesMinMax(): void
    {
        $html = $this->render([$this->field(['name' => 'age', 'type' => 'number', 'min' => 3, 'max' => 99])]);

        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="3"', $html);
        $this->assertStringContainsString('max="99"', $html);
    }

    public function testSelectAndCountryFieldsUseOptions(): void
    {
        $html = $this->render([
            $this->field(['name' => 'size', 'type' => 'select', 'data' => 'S | M|L']),
            $this->field(['name' => 'country', 'type' => 'country', 'data' => ['Singapore', 'Malaysia']]),
        ]);

        $this->assertStringContainsString('<option value="M">M</option>', $html);
        $this->assertStringContainsString('name="question_group[country][g1]"', $html);
        $this->assertStringContainsString('<option value="Malaysia">Malaysia</option>', $html);
    }

    public function testCheckboxAndRadio(): void
    {
        $html = $this->render([
            $this->field(['name' => 'extras', 'type' => 'checkbox', 'data' => 'Meal|Locker', 'is_required' => true]),
            $this->field(['name' => 'seat', 'type' => 'radio', 'data' => 'Window|Aisle']),
        ]);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="Locker"', $html);
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('value="Aisle"', $html);
    }

    public function testDateAndRange(): void
    {
        $range = htmlentities(json_encode(['from_select' => 1, 'to_select' => 10, 'from_label' => 'Low', 'to_label' => 'High']));
        $html = $this->render([
            $this->field(['name' => 'arrival', 'type' => 'date']),
            $this->field(['name' => 'level', 'type' => 'range', 'data' => [$range]]),
        ]);

        $this->assertStringContainsString('date-picker', $html);
        $this->assertStringContainsString('type="range"', $html);
        $this->assertStringContainsString('(Low to High)', $html);
        $this->assertStringContainsString('max="10"', $html);
    }

    public function testFieldWithoutQuestionThrows(): void
    {
        $this->expectException(Exception::class);
        ob_start();
        try {
            $this->helper->render(['title' => 't', 'fields' => [$this->field(['question' => ''])]], $this->form, '', 'Zoo', 'Adult', '', 1);
        } finally {
            ob_end_clean();
        }
    }
}
