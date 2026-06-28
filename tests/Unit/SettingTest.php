<?php

namespace Tests\Unit;

use App\Models\Setting;
use PHPUnit\Framework\TestCase;

class SettingTest extends TestCase
{
    public function test_get_value_with_cast(): void
    {
        $s = new Setting(['key' => 'test_bool', 'value' => true]);
        $this->assertTrue($s->value);
    }

    public function test_get_value_with_numeric_cast(): void
    {
        $s = new Setting(['key' => 'test_int', 'value' => 42]);
        $this->assertEquals(42, $s->value);
    }

    public function test_get_value_with_string_cast(): void
    {
        $s = new Setting(['key' => 'test_str', 'value' => 'hello']);
        $this->assertEquals('hello', $s->value);
    }

    public function test_get_value_with_array_cast(): void
    {
        $s = new Setting(['key' => 'test_arr', 'value' => ['a' => 1]]);
        $this->assertEquals(['a' => 1], $s->value);
    }

    public function test_fillable_attributes(): void
    {
        $s = new Setting(['key' => 'k', 'value' => true]);
        $this->assertTrue($s->value);
    }
}
