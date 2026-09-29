<?php

namespace Tests\Unit;

use App\Services\Assistant\SpokenDigits;
use App\Services\Assistant\VoiceText;
use PHPUnit\Framework\TestCase;

class VoiceTextTest extends TestCase
{
    public function test_lowercases_and_strips_accents_and_special_characters(): void
    {
        $this->assertSame('rosa maria mamani', VoiceText::normalize('  ROSA  María, Mamaní. '));
        $this->assertSame('pena nunez', VoiceText::normalize('Peña Núñez'));
        $this->assertSame('hola que tal', VoiceText::normalize('¡Hola! ¿Qué tal?'));
        $this->assertSame('zona 3 san blas', VoiceText::normalize('Zona #3 - San Blas'));
        $this->assertSame('', VoiceText::normalize(null));
    }

    public function test_letters_only(): void
    {
        $this->assertTrue(VoiceText::isLettersOnly('rosa maria'));
        $this->assertFalse(VoiceText::isLettersOnly('rosa 2'));
        $this->assertFalse(VoiceText::isLettersOnly(''));
    }

    public function test_phone_parsing(): void
    {
        $this->assertSame('987654321', SpokenDigits::parsePhone('987 654 321'));
        $this->assertSame('987654321', SpokenDigits::parsePhone('mi número es nueve ocho siete seis cinco cuatro tres dos uno'));
        $this->assertSame('984111222', SpokenDigits::parsePhone('984-111-222'));
        $this->assertNull(SpokenDigits::parsePhone('no tengo'));
        $this->assertNull(SpokenDigits::parsePhone('123'));
    }
}
