<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Valsis\RoCompanyLookup\Exceptions\InvalidCuiException;
use Valsis\RoCompanyLookup\Support\NormalizeCui;
use Valsis\RoCompanyLookup\Tests\TestCase;

#[CoversClass(NormalizeCui::class)]
class NormalizeCuiTest extends TestCase
{
    #[Test]
    public function it_normalizes_cui_strings(): void
    {
        $this->assertSame(123, NormalizeCui::normalize('RO123'));
        $this->assertSame(123, NormalizeCui::normalize('  ro 123  '));
        $this->assertSame(123, NormalizeCui::normalize('123'));
        $this->assertSame(83723123, NormalizeCui::normalize('ro 83723123'));
    }

    #[Test]
    public function it_throws_for_invalid_cui(): void
    {
        try {
            NormalizeCui::normalize('invalid');
            $this->fail('Expected InvalidCuiException was not thrown.');
        } catch (InvalidCuiException $exception) {
            $this->assertSame(InvalidCuiException::ERROR_INVALID, $exception->error());
        }
    }

    #[Test]
    public function it_throws_for_too_short_cui(): void
    {
        try {
            NormalizeCui::normalize('RO1');
            $this->fail('Expected InvalidCuiException was not thrown.');
        } catch (InvalidCuiException $exception) {
            $this->assertSame(InvalidCuiException::ERROR_TOO_SHORT, $exception->error());
        }
    }

    #[Test]
    public function it_throws_for_too_long_cui(): void
    {
        try {
            NormalizeCui::normalize('12345678901');
            $this->fail('Expected InvalidCuiException was not thrown.');
        } catch (InvalidCuiException $exception) {
            $this->assertSame(InvalidCuiException::ERROR_TOO_LONG, $exception->error());
        }
    }
}
