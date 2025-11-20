/**
 * Sistema de evaluación de fórmulas para Excel
 * Soporta operaciones matemáticas, referencias a celdas y funciones básicas
 */

import { getCellRange } from './excelUtils';

/**
 * Evalúa una fórmula de Excel
 * @param {string} formula - Fórmula que empieza con =
 * @param {Object} cells - Objeto con todas las celdas { cellId: { value, ... } }
 * @returns {string|number} - Resultado de la fórmula o error
 */
export function evaluateFormula(formula, cells) {
    if (!formula || typeof formula !== 'string') {
        return '';
    }

    // Si no empieza con =, no es una fórmula
    if (!formula.startsWith('=')) {
        return formula;
    }

    try {
        // Remover el = inicial
        let expression = formula.substring(1).trim();

        // Evaluar funciones primero
        expression = evaluateFunctions(expression, cells);

        // Reemplazar referencias a celdas por sus valores
        expression = replaceCellReferences(expression, cells);

        // Evaluar la expresión matemática
        const result = evaluateExpression(expression);

        return result;
    } catch (error) {
        return `#ERROR: ${error.message}`;
    }
}

/**
 * Evalúa funciones de Excel como SUM, AVERAGE, etc.
 */
function evaluateFunctions(expression, cells) {
    // Funciones soportadas
    const functions = {
        SUM: (values) => values.reduce((sum, val) => sum + parseNumber(val), 0),
        AVERAGE: (values) => {
            const nums = values.map(parseNumber);
            return nums.reduce((sum, val) => sum + val, 0) / nums.length;
        },
        MIN: (values) => Math.min(...values.map(parseNumber)),
        MAX: (values) => Math.max(...values.map(parseNumber)),
        COUNT: (values) => values.filter(v => v !== '' && v != null).length,
        COUNTA: (values) => values.filter(v => v !== '' && v != null).length,
        COUNTBLANK: (values) => values.filter(v => v === '' || v == null).length,
    };

    // Regex para encontrar funciones: FUNCTION(args)
    const functionRegex = /([A-Z]+)\(([^)]+)\)/gi;

    return expression.replace(functionRegex, (match, funcName, args) => {
        const func = functions[funcName.toUpperCase()];

        if (!func) {
            throw new Error(`Función desconocida: ${funcName}`);
        }

        // Obtener valores de los argumentos
        const values = getFunctionArguments(args, cells);

        // Ejecutar función
        const result = func(values);

        return result;
    });
}

/**
 * Obtiene los valores de los argumentos de una función
 */
function getFunctionArguments(args, cells) {
    const values = [];

    // Separar argumentos por coma (pero no dentro de paréntesis)
    const argList = splitArguments(args);

    for (const arg of argList) {
        const trimmedArg = arg.trim();

        // Es un rango (ej: A1:B10)
        if (trimmedArg.includes(':')) {
            const [start, end] = trimmedArg.split(':');
            const range = getCellRange(start.trim(), end.trim());

            for (const cellId of range) {
                const cell = cells[cellId];
                if (cell && cell.value !== undefined) {
                    values.push(cell.value);
                } else {
                    values.push(0);
                }
            }
        }
        // Es una celda individual
        else if (/^[A-Z]+[0-9]+$/i.test(trimmedArg)) {
            const cell = cells[trimmedArg.toUpperCase()];
            if (cell && cell.value !== undefined) {
                values.push(cell.value);
            } else {
                values.push(0);
            }
        }
        // Es un número literal
        else if (!isNaN(trimmedArg)) {
            values.push(parseFloat(trimmedArg));
        }
    }

    return values;
}

/**
 * Divide argumentos de función respetando paréntesis anidados
 */
function splitArguments(args) {
    const result = [];
    let current = '';
    let depth = 0;

    for (let i = 0; i < args.length; i++) {
        const char = args[i];

        if (char === '(') {
            depth++;
            current += char;
        } else if (char === ')') {
            depth--;
            current += char;
        } else if (char === ',' && depth === 0) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }

    if (current.trim()) {
        result.push(current.trim());
    }

    return result;
}

/**
 * Reemplaza referencias a celdas (ej: A1, B2) por sus valores
 */
function replaceCellReferences(expression, cells) {
    // Regex para encontrar referencias a celdas: A1, B2, etc.
    const cellRegex = /\b([A-Z]+[0-9]+)\b/gi;

    return expression.replace(cellRegex, (match) => {
        const cellId = match.toUpperCase();
        const cell = cells[cellId];

        if (cell && cell.value !== undefined && cell.value !== '') {
            // Si el valor es otra fórmula, evaluarla recursivamente
            if (typeof cell.value === 'string' && cell.value.startsWith('=')) {
                return evaluateFormula(cell.value, cells);
            }
            return cell.value;
        }

        return 0; // Celda vacía = 0
    });
}

/**
 * Evalúa una expresión matemática simple
 */
function evaluateExpression(expression) {
    try {
        // Validar que solo contenga números, operadores y paréntesis
        const validChars = /^[\d+\-*/.() ]+$/;
        if (!validChars.test(expression)) {
            throw new Error('Expresión inválida');
        }

        // Evaluar la expresión (usando Function en lugar de eval por seguridad)
        const result = new Function(`return ${expression}`)();

        // Redondear a 2 decimales si es necesario
        if (typeof result === 'number') {
            return Math.round(result * 100) / 100;
        }

        return result;
    } catch (error) {
        throw new Error('Error al evaluar expresión');
    }
}

/**
 * Convierte un valor a número, manejando strings
 */
function parseNumber(value) {
    if (typeof value === 'number') {
        return value;
    }

    if (typeof value === 'string') {
        // Si es una fórmula, devolver 0
        if (value.startsWith('=')) {
            return 0;
        }

        const num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    }

    return 0;
}

/**
 * Verifica si una cadena es una fórmula válida
 */
export function isFormula(value) {
    return typeof value === 'string' && value.startsWith('=');
}

/**
 * Obtiene todas las celdas referenciadas en una fórmula
 */
export function getFormulaDependencies(formula) {
    if (!isFormula(formula)) {
        return [];
    }

    const dependencies = new Set();
    const cellRegex = /\b([A-Z]+[0-9]+)\b/gi;
    const rangeRegex = /([A-Z]+[0-9]+):([A-Z]+[0-9]+)/gi;

    // Encontrar referencias individuales
    let match;
    while ((match = cellRegex.exec(formula)) !== null) {
        dependencies.add(match[1].toUpperCase());
    }

    // Encontrar rangos
    while ((match = rangeRegex.exec(formula)) !== null) {
        const range = getCellRange(match[1], match[2]);
        range.forEach(cellId => dependencies.add(cellId));
    }

    return Array.from(dependencies);
}

