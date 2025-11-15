<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalizar número de teléfono a formato internacional
     * Elimina espacios, guiones y paréntesis
     * Asegura que comience con +
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si no empieza con +, asumimos código de país por defecto (configurable)
        if (!str_starts_with($phone, '+')) {
            $defaultCountryCode = config('app.default_country_code', '34');
            $phone = '+' . $defaultCountryCode . $phone;
        }

        return $phone;
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

