<?php

namespace Tests\Unit;

use App\Helpers\PhoneHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoneHelperTest extends TestCase
{
    #[Test]
    public function normalizes_argentina_phone_without_code()
    {
        $result = PhoneHelper::normalizeWithCountry('2944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_argentina_phone_with_54()
    {
        $result = PhoneHelper::normalizeWithCountry('542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_argentina_phone_with_plus_54()
    {
        $result = PhoneHelper::normalizeWithCountry('+542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_argentina_phone_with_9_included()
    {
        $result = PhoneHelper::normalizeWithCountry('92944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_argentina_phone_with_spaces_and_dashes()
    {
        $result = PhoneHelper::normalizeWithCountry('294-463-3444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_argentina_buenos_aires_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('1123456789', 'AR');
        $this->assertEquals('+5491123456789', $result);
    }

    #[Test]
    public function normalizes_spanish_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('612345678', 'ES');
        $this->assertEquals('+34612345678', $result);
    }

    #[Test]
    public function normalizes_spanish_phone_with_code()
    {
        $result = PhoneHelper::normalizeWithCountry('34612345678', 'ES');
        $this->assertEquals('+34612345678', $result);
    }

    #[Test]
    public function normalizes_mexican_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('5512345678', 'MX');
        $this->assertEquals('+525512345678', $result);
    }

    #[Test]
    public function normalizes_mexican_phone_with_code()
    {
        $result = PhoneHelper::normalizeWithCountry('525512345678', 'MX');
        $this->assertEquals('+525512345678', $result);
    }

    #[Test]
    public function normalizes_chilean_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('912345678', 'CL');
        $this->assertEquals('+56912345678', $result);
    }

    #[Test]
    public function normalizes_colombian_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('3001234567', 'CO');
        $this->assertEquals('+573001234567', $result);
    }

    #[Test]
    public function normalizes_us_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('2025551234', 'US');
        $this->assertEquals('+12025551234', $result);
    }

    #[Test]
    public function normalizes_phone_with_parentheses_and_spaces()
    {
        $result = PhoneHelper::normalizeWithCountry('(294) 463-3444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function normalizes_phone_with_leading_plus()
    {
        $result = PhoneHelper::normalizeWithCountry('+542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    #[Test]
    public function returns_null_for_empty_phone()
    {
        $result = PhoneHelper::normalizeWithCountry('', 'AR');
        $this->assertNull($result);
    }

    #[Test]
    public function returns_null_for_null_phone()
    {
        $result = PhoneHelper::normalizeWithCountry(null, 'AR');
        $this->assertNull($result);
    }

    #[Test]
    public function normalizes_phone_with_default_country_code()
    {
        // Sin especificar país, debe usar AR por defecto en normalize()
        $result = PhoneHelper::normalize('2944633444');
        // El método normalize usa config o default, esperamos que normalice
        $this->assertStringStartsWith('+', $result);
    }

    #[Test]
    public function validates_correct_phone()
    {
        $this->assertTrue(PhoneHelper::isValid('+5492944633444'));
        $this->assertTrue(PhoneHelper::isValid('2944633444'));
    }

    #[Test]
    public function invalidates_empty_phone()
    {
        $this->assertFalse(PhoneHelper::isValid(''));
        $this->assertFalse(PhoneHelper::isValid(null));
    }

    #[Test]
    public function formats_phone_for_display()
    {
        $result = PhoneHelper::format('+5492944633444');
        // Esperamos formato: +54 294 463 3444
        $this->assertStringContainsString(' ', $result);
        $this->assertStringStartsWith('+54', $result);
    }
}
