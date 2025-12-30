
import { useCallback } from 'react';

export function useCSVValidation(fields = []) {

    const validateValue = (value, type) => {
        if (!value) return true; // Empty check handled separately

        switch (type) {
            case 'email':
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            case 'phone':
                // Basic check: starts with +, contains only numbers, spaces, dashes
                return /^\+?[\d\s-]+$/.test(value) && value.replace(/\D/g, '').length >= 8;
            case 'number':
                return !isNaN(parseFloat(value)) && isFinite(value);
            case 'date':
                const d = new Date(value);
                return d instanceof Date && !isNaN(d);
            default:
                return true;
        }
    };

    const validateRow = useCallback((row, mapping) => {
        const errors = [];
        const validatedData = {};

        fields.forEach(field => {
            // Find corresponding CSV value using mapping
            // Mapping: { [csvHeader]: "fieldKey" } -> We need to find header that maps to this field key
            const mappedHeader = Object.keys(mapping).find(header => mapping[header] === field.key);
            const rawValue = mappedHeader ? row[mappedHeader] : undefined;

            // Required check
            if (field.required && !rawValue) {
                errors.push({ field: field.key, message: `El campo ${field.label} es obligatorio` });
            }

            // Type validation
            if (rawValue && field.type && !validateValue(rawValue, field.type)) {
                errors.push({ field: field.key, message: `El formato de ${field.label} no es válido` });
            }

            // Validation passed (or empty optional), add to result
            validatedData[field.key] = rawValue || null;

            // If field has 'option' or 'select' type, we might want to validate against options
            // But for now we'll allow mapping text to select
        });

        return { isValid: errors.length === 0, errors, validatedData };
    }, [fields]);

    const validateAll = useCallback((rows, mapping) => {
        const results = rows.map((row, index) => {
            const { isValid, errors, validatedData } = validateRow(row, mapping);
            // Add _originalRow for reference
            return {
                originalRow: row,
                isValid,
                errors,
                data: validatedData,
                rowIndex: index
            };
        });

        const validRows = results.filter(r => r.isValid);
        const invalidRows = results.filter(r => !r.isValid);

        return { results, validRows, invalidRows };
    }, [validateRow]);

    return { validateRow, validateAll };
}
