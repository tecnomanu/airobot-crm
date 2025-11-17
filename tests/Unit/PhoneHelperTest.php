<?php

namespace Tests\Unit;

use App\Helpers\PhoneHelper;
use Tests\TestCase;

class PhoneHelperTest extends TestCase
{
    /** @test */
    public function normaliza_telefono_argentino_sin_codigo()
    {
        $result = PhoneHelper::normalizeWithCountry('2944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_argentino_con_54()
    {
        $result = PhoneHelper::normalizeWithCountry('542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_argentino_con_mas_54()
    {
        $result = PhoneHelper::normalizeWithCountry('+542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_argentino_con_9_incluido()
    {
        $result = PhoneHelper::normalizeWithCountry('92944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_argentino_con_espacios_y_guiones()
    {
        $result = PhoneHelper::normalizeWithCountry('294-463-3444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_argentino_buenos_aires()
    {
        $result = PhoneHelper::normalizeWithCountry('1123456789', 'AR');
        $this->assertEquals('+5491123456789', $result);
    }

    /** @test */
    public function normaliza_telefono_espanol()
    {
        $result = PhoneHelper::normalizeWithCountry('612345678', 'ES');
        $this->assertEquals('+34612345678', $result);
    }

    /** @test */
    public function normaliza_telefono_espanol_con_codigo()
    {
        $result = PhoneHelper::normalizeWithCountry('34612345678', 'ES');
        $this->assertEquals('+34612345678', $result);
    }

    /** @test */
    public function normaliza_telefono_mexicano()
    {
        $result = PhoneHelper::normalizeWithCountry('5512345678', 'MX');
        $this->assertEquals('+525512345678', $result);
    }

    /** @test */
    public function normaliza_telefono_mexicano_con_codigo()
    {
        $result = PhoneHelper::normalizeWithCountry('525512345678', 'MX');
        $this->assertEquals('+525512345678', $result);
    }

    /** @test */
    public function normaliza_telefono_chileno()
    {
        $result = PhoneHelper::normalizeWithCountry('912345678', 'CL');
        $this->assertEquals('+56912345678', $result);
    }

    /** @test */
    public function normaliza_telefono_colombiano()
    {
        $result = PhoneHelper::normalizeWithCountry('3001234567', 'CO');
        $this->assertEquals('+573001234567', $result);
    }

    /** @test */
    public function normaliza_telefono_estadounidense()
    {
        $result = PhoneHelper::normalizeWithCountry('2025551234', 'US');
        $this->assertEquals('+12025551234', $result);
    }

    /** @test */
    public function normaliza_telefono_con_parentesis_y_espacios()
    {
        $result = PhoneHelper::normalizeWithCountry('(294) 463-3444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function normaliza_telefono_con_plus_al_inicio()
    {
        $result = PhoneHelper::normalizeWithCountry('+542944633444', 'AR');
        $this->assertEquals('+5492944633444', $result);
    }

    /** @test */
    public function retorna_null_para_telefono_vacio()
    {
        $result = PhoneHelper::normalizeWithCountry('', 'AR');
        $this->assertNull($result);
    }

    /** @test */
    public function retorna_null_para_telefono_null()
    {
        $result = PhoneHelper::normalizeWithCountry(null, 'AR');
        $this->assertNull($result);
    }

    /** @test */
    public function normaliza_telefono_con_codigo_pais_por_defecto()
    {
        // Sin especificar país, debe usar AR por defecto en normalize()
        $result = PhoneHelper::normalize('2944633444');
        // El método normalize usa config o default, esperamos que normalice
        $this->assertStringStartsWith('+', $result);
    }

    /** @test */
    public function valida_telefono_correcto()
    {
        $this->assertTrue(PhoneHelper::isValid('+5492944633444'));
        $this->assertTrue(PhoneHelper::isValid('2944633444'));
    }

    /** @test */
    public function invalida_telefono_vacio()
    {
        $this->assertFalse(PhoneHelper::isValid(''));
        $this->assertFalse(PhoneHelper::isValid(null));
    }

    /** @test */
    public function formatea_telefono_para_display()
    {
        $result = PhoneHelper::format('+5492944633444');
        // Esperamos formato: +54 294 463 3444
        $this->assertStringContainsString(' ', $result);
        $this->assertStringStartsWith('+54', $result);
    }
}
