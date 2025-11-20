/**
 * Formateadores de celdas para diferentes tipos de datos
 */

/**
 * Formatea un número con separadores de miles y decimales
 * @param {number|string} value - Valor a formatear
 * @param {number} decimals - Número de decimales
 * @returns {string} Valor formateado
 */
export function formatNumber(value, decimals = 2) {
    const num = parseFloat(value);
    if (isNaN(num)) return String(value);
    
    return num.toLocaleString('es-ES', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Formatea un valor como moneda
 * @param {number|string} value - Valor a formatear
 * @param {string} currency - Código de moneda (por defecto 'EUR')
 * @returns {string} Valor formateado como moneda
 */
export function formatCurrency(value, currency = 'EUR') {
    const num = parseFloat(value);
    if (isNaN(num)) return String(value);
    
    return num.toLocaleString('es-ES', {
        style: 'currency',
        currency: currency
    });
}

/**
 * Formatea un valor como porcentaje
 * @param {number|string} value - Valor a formatear (0-1 o 0-100)
 * @param {number} decimals - Número de decimales
 * @returns {string} Valor formateado como porcentaje
 */
export function formatPercentage(value, decimals = 2) {
    const num = parseFloat(value);
    if (isNaN(num)) return String(value);
    
    // Si el valor es mayor que 1, asumimos que está en formato 0-100
    const percentage = num > 1 ? num : num * 100;
    
    return `${percentage.toFixed(decimals)}%`;
}

/**
 * Formatea un valor como fecha
 * @param {string|Date} value - Valor a formatear
 * @param {string} format - Formato de fecha ('short', 'long', 'datetime')
 * @returns {string} Valor formateado como fecha
 */
export function formatDate(value, format = 'short') {
    const date = new Date(value);
    if (isNaN(date.getTime())) return String(value);
    
    const options = {
        short: { year: 'numeric', month: '2-digit', day: '2-digit' },
        long: { year: 'numeric', month: 'long', day: 'numeric' },
        datetime: { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }
    };
    
    return date.toLocaleDateString('es-ES', options[format] || options.short);
}

/**
 * Formatea un valor según su tipo
 * @param {*} value - Valor a formatear
 * @param {string} formatType - Tipo de formato ('text'|'number'|'currency'|'percentage'|'date')
 * @param {Object} options - Opciones adicionales de formato
 * @returns {string} Valor formateado
 */
export function formatCellValue(value, formatType = 'text', options = {}) {
    if (value == null || value === '') return '';
    
    switch (formatType) {
        case 'number':
            return formatNumber(value, options.decimals);
        case 'currency':
            return formatCurrency(value, options.currency);
        case 'percentage':
            return formatPercentage(value, options.decimals);
        case 'date':
            return formatDate(value, options.dateFormat);
        case 'text':
        default:
            return String(value);
    }
}

/**
 * Parsea un valor formateado de vuelta a su valor original
 * @param {string} formattedValue - Valor formateado
 * @param {string} formatType - Tipo de formato
 * @returns {*} Valor original
 */
export function parseFormattedValue(formattedValue, formatType = 'text') {
    if (!formattedValue) return '';
    
    switch (formatType) {
        case 'number':
        case 'currency':
        case 'percentage':
            // Remover separadores y símbolos
            const cleaned = formattedValue
                .replace(/[^\d.,-]/g, '')
                .replace(',', '.');
            return parseFloat(cleaned) || 0;
        case 'date':
            return new Date(formattedValue).toISOString();
        case 'text':
        default:
            return formattedValue;
    }
}

