<?php

namespace Tests\Unit;

use App\Services\Assistant\SpokenDigits;
use PHPUnit\Framework\TestCase;

class SpokenDigitsTest extends TestCase
{
    public function test_parses_literal_digits(): void
    {
        $this->assertSame('7205', SpokenDigits::parseFourDigits('7205'));
        $this->assertSame('7205', SpokenDigits::parseFourDigits('7 2 0 5'));
    }

    public function test_parses_spanish_number_words(): void
    {
        $this->assertSame('7205', SpokenDigits::parseFourDigits('siete dos cero cinco'));
        $this->assertSame('1234', SpokenDigits::parseFourDigits('uno dos tres cuatro'));
    }

    public function test_parses_mixed_words_and_digits(): void
    {
        $this->assertSame('7205', SpokenDigits::parseFourDigits('siete 2 cero cinco'));
    }

    public function test_rejects_wrong_digit_count(): void
    {
        $this->assertNull(SpokenDigits::parseFourDigits('setenta y dos'));
        $this->assertNull(SpokenDigits::parseFourDigits('123'));
        $this->assertNull(SpokenDigits::parseFourDigits('123456'));
        $this->assertNull(SpokenDigits::parseFourDigits(''));
        $this->assertNull(SpokenDigits::parseFourDigits('hola mundo'));
    }

    public function test_space_out_formats_for_speech(): void
    {
        $this->assertSame('7 2 0 5', SpokenDigits::spaceOut('7205'));
    }
}
