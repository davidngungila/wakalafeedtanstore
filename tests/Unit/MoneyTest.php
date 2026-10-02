<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_reads_small_numbers(): void
    {
        $this->assertSame('moja', Money::inWords(1));
        $this->assertSame('tisa', Money::inWords(9));
        $this->assertSame('kumi', Money::inWords(10));
        $this->assertSame('kumi na sita', Money::inWords(16));
        $this->assertSame('ishirini', Money::inWords(20));
        $this->assertSame('ishirini na tano', Money::inWords(25));
        $this->assertSame('hamsini', Money::inWords(50));
        $this->assertSame('nini na tisa', Money::inWords(99));
    }

    public function test_it_reads_hundreds_and_thousands(): void
    {
        $this->assertSame('mia moja', Money::inWords(100));
        $this->assertSame('mia mbili na tano', Money::inWords(205));
        $this->assertSame('elfu moja', Money::inWords(1000));
        $this->assertSame('elfu moja na mia moja', Money::inWords(1100));
        $this->assertSame('elfu mbili na mia tatu na arobaini na tano', Money::inWords(2345));
        // Hundreds of thousands read count-first: "mia moja elfu".
        $this->assertSame('mia moja elfu', Money::inWords(100_000));
    }

    public function test_it_reads_amounts_of_thousands_of_shillings(): void
    {
        // The running float total that appears on the real receipts.
        $this->assertSame(
            'milioni mbili na mia nane na hamsini na tatu elfu na mia sita na arobaini na tatu',
            Money::inWords(2_853_643)
        );
        $this->assertSame('milioni moja', Money::inWords(1_000_000));
        $this->assertSame('milioni moja na mia moja elfu', Money::inWords(1_100_000));
        $this->assertSame('bilioni moja na milioni mbili', Money::inWords(1_002_000_000));
    }

    public function test_it_handles_zero_and_rounds_to_the_nearest_shilling(): void
    {
        $this->assertSame('sifuri', Money::inWords(0));
        // The schema has no minor-unit column, so cents are not rendered.
        $this->assertSame('elfu moja', Money::inWords(1000.4));
        $this->assertSame('elfu moja na mia tano na moja', Money::inWords(1500.6));
    }

    public function test_it_accepts_decimal_strings(): void
    {
        $this->assertSame('elfu moja', Money::inWords('1000.00'));
        $this->assertSame('ishirini na tano', Money::inWords('25'));
    }
}
