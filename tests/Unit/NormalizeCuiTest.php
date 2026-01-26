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
    }

    #[Test]
    public function it_throws_for_invalid_cui(): void
    {
        $this->expectException(InvalidCuiException::class);

        NormalizeCui::normalize('invalid');
    }
}
