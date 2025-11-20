<?php

namespace App\Helpers;

use App\Models\Campaign;
use App\Models\Lead;

class PhoneHelper
{
    /**
     * Normalizar número de teléfono a formato internacional
     * Elimina espacios, guiones y paréntesis
     * Asegura que comience con +
     *
     * @deprecated Use normalizeForLead() instead
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si no empieza con +, asumimos código de país por defecto (configurable)
        if (! str_starts_with($phone, '+')) {
            // Default Argentina (+549 para móviles)
            $defaultCountryCode = config('app.default_country_code', '549');
            $phone = '+' . $defaultCountryCode . $phone;
        }

        return $phone;
    }

    /**
     * Normalizar teléfono con contexto de lead/campaña
     * Prioridad: 1) Campaign country, 2) Lead country, 3) Default AR
     */
    public static function normalizeForLead(
        ?string $phone,
        ?Campaign $campaign = null,
        ?Lead $lead = null
    ): ?string {
        if (empty($phone)) {
            return null;
        }

        // Determinar país según prioridad
        $country = null;
        if ($campaign && $campaign->country) {
            $country = $campaign->country;
        } elseif ($lead && $lead->country) {
            $country = $lead->country;
        } else {
            $country = 'AR'; // Default Argentina
        }

        return self::normalizeWithCountry($phone, $country);
    }

    /**
     * Normalizar teléfono según código de país ISO2
     */
    public static function normalizeWithCountry(?string $phone, string $countryCode): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si ya tiene +, retornar (ya está normalizado)
        if (str_starts_with($phone, '+')) {
            return self::fixArgentinaMobileFormat($phone);
        }

        // Normalizar según país
        $countryCode = strtoupper($countryCode);

        switch ($countryCode) {
            case 'AR': // Argentina
                return self::normalizeArgentina($phone);

            case 'ES': // España
                return self::normalizeSpain($phone);

            case 'MX': // México
                return self::normalizeMexico($phone);

            case 'CL': // Chile
                return self::normalizeChile($phone);

            case 'CO': // Colombia
                return self::normalizeColombia($phone);

            case 'US': // Estados Unidos
                return self::normalizeUSA($phone);

            default:
                // Para países no implementados, usar lógica genérica
                return '+' . $phone;
        }
    }

    /**
     * Normalizar teléfono de Argentina
     * Formato: +54 9 [código área] [número]
     * Móviles: +5492944633444 (11 dígitos después del +54)
     */
    private static function normalizeArgentina(string $phone): string
    {
        // Quitar +54 si ya lo tiene
        $phone = preg_replace('/^(\+?54)/', '', $phone);

        // Si ya tiene el 9 (móvil), agregamos +54
        if (str_starts_with($phone, '9')) {
            return '+54' . $phone;
        }

        // Si tiene 10 dígitos (sin el 9), es un móvil
        // Ejemplo: 2944633444 → +5492944633444
        if (strlen($phone) === 10) {
            return '+549' . $phone;
        }

        // Si tiene 11 dígitos y no empieza con 9, agregarlo
        // Ejemplo: 11234567890 (Buenos Aires) → +5491123456789
        if (strlen($phone) === 11 && ! str_starts_with($phone, '9')) {
            return '+549' . $phone;
        }

        // Si tiene 8 o 9 dígitos, puede ser fijo
        // Agregamos +54 sin el 9
        if (strlen($phone) >= 8 && strlen($phone) <= 9) {
            return '+54' . $phone;
        }

        // Default: agregar +549 (asumir móvil)
        return '+549' . $phone;
    }

    /**
     * Corregir formato de móviles argentinos que ya tienen +54
     * +542944633444 → +5492944633444
     */
    private static function fixArgentinaMobileFormat(string $phone): string
    {
        // Si empieza con +54 pero NO tiene el 9 después y tiene 12-13 dígitos totales (indicando que es móvil sin el 9)
        // Ejemplo: +542944633444 (13 caracteres) → +5492944633444 (14 caracteres)
        if (preg_match('/^\+54(\d{10})$/', $phone, $matches)) {
            // Verificar que no empiece con 9
            if (! str_starts_with($matches[1], '9')) {
                return '+549' . $matches[1];
            }
        }

        return $phone;
    }

    /**
     * Normalizar teléfono de España
     */
    private static function normalizeSpain(string $phone): string
    {
        // Quitar +34 si ya lo tiene
        $phone = preg_replace('/^(\+?34)/', '', $phone);

        return '+34' . $phone;
    }

    /**
     * Normalizar teléfono de México
     */
    private static function normalizeMexico(string $phone): string
    {
        // Quitar +52 si ya lo tiene
        $phone = preg_replace('/^(\+?52)/', '', $phone);

        return '+52' . $phone;
    }

    /**
     * Normalizar teléfono de Chile
     */
    private static function normalizeChile(string $phone): string
    {
        // Quitar +56 si ya lo tiene
        $phone = preg_replace('/^(\+?56)/', '', $phone);

        return '+56' . $phone;
    }

    /**
     * Normalizar teléfono de Colombia
     */
    private static function normalizeColombia(string $phone): string
    {
        // Quitar +57 si ya lo tiene
        $phone = preg_replace('/^(\+?57)/', '', $phone);

        return '+57' . $phone;
    }

    /**
     * Normalizar teléfono de Estados Unidos
     */
    private static function normalizeUSA(string $phone): string
    {
        // Quitar +1 si ya lo tiene
        $phone = preg_replace('/^(\+?1)/', '', $phone);

        return '+1' . $phone;
    }

    /**
     * Validar formato de teléfono
     */
    public static function isValid(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $normalized = self::normalize($phone);

        // Validar que tenga al menos 10 dígitos después del +
        return preg_match('/^\+\d{10,15}$/', $normalized) === 1;
    }

    /**
     * Formatear para display
     */
    public static function format(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $normalized = self::normalize($phone);

        // Formato: +XX XXX XXX XXX
        return preg_replace('/(\+\d{2})(\d{3})(\d{3})(\d+)/', '$1 $2 $3 $4', $normalized);
    }
}
