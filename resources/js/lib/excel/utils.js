/**
 * Utilidades para el sistema Excel/Spreadsheet
 */

/**
 * Convierte un índice numérico a letra de columna (1 -> A, 27 -> AA)
 * @param {number} index - Índice de columna (base 1)
 * @returns {string} Letra(s) de columna
 */
export function indexToColumn(index) {
    let result = '';
    let num = index - 1;
    
    while (num >= 0) {
        result = String.fromCharCode(65 + (num % 26)) + result;
        num = Math.floor(num / 26) - 1;
    }
    
    return result || 'A';
}

/**
 * Convierte letra de columna a índice numérico (A -> 1, AA -> 27)
 * @param {string} column - Letra(s) de columna
 * @returns {number} Índice de columna (base 1)
 */
export function columnToIndex(column) {
    let result = 0;
    for (let i = 0; i < column.length; i++) {
        result = result * 26 + (column.charCodeAt(i) - 64);
    }
    return result;
}

/**
 * Convierte coordenada de celda a objeto {row, col}
 * @param {string} cellId - Coordenada de celda (ej: 'A1', 'B2')
 * @returns {{row: number, col: number}} Objeto con fila y columna (base 0)
 */
export function cellToCoords(cellId) {
    const match = cellId.match(/^([A-Z]+)(\d+)$/);
    if (!match) return { row: 0, col: 0 };
    
    const col = columnToIndex(match[1]) - 1;
    const row = parseInt(match[2], 10) - 1;
    
    return { row, col };
}

/**
 * Convierte objeto {row, col} a coordenada de celda
 * @param {{row: number, col: number}} coords - Coordenadas (base 0)
 * @returns {string} Coordenada de celda (ej: 'A1', 'B2')
 */
export function coordsToCell(coords) {
    const col = indexToColumn(coords.col + 1);
    const row = coords.row + 1;
    return `${col}${row}`;
}

/**
 * Obtiene el rango de celdas entre dos coordenadas
 * @param {string} startCell - Celda inicial (ej: 'A1')
 * @param {string} endCell - Celda final (ej: 'C3')
 * @returns {string[]} Array de coordenadas de celdas
 */
export function getCellRange(startCell, endCell) {
    const start = cellToCoords(startCell);
    const end = cellToCoords(endCell);
    
    const cells = [];
    const minRow = Math.min(start.row, end.row);
    const maxRow = Math.max(start.row, end.row);
    const minCol = Math.min(start.col, end.col);
    const maxCol = Math.max(start.col, end.col);
    
    for (let row = minRow; row <= maxRow; row++) {
        for (let col = minCol; col <= maxCol; col++) {
            cells.push(coordsToCell({ row, col }));
        }
    }
    
    return cells;
}

/**
 * Ordena un array de objetos por una columna específica
 * @param {Array} data - Array de datos
 * @param {string} column - Columna por la que ordenar
 * @param {'asc'|'desc'} direction - Dirección del ordenamiento
 * @returns {Array} Array ordenado
 */
export function sortByColumn(data, column, direction = 'asc') {
    const sorted = [...data];
    
    sorted.sort((a, b) => {
        const aVal = a[column];
        const bVal = b[column];
        
        // Manejar valores nulos/undefined
        if (aVal == null && bVal == null) return 0;
        if (aVal == null) return 1;
        if (bVal == null) return -1;
        
        // Intentar comparar como números
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Comparar como strings
        const aStr = String(aVal).toLowerCase();
        const bStr = String(bVal).toLowerCase();
        
        if (direction === 'asc') {
            return aStr.localeCompare(bStr);
        } else {
            return bStr.localeCompare(aStr);
        }
    });
    
    return sorted;
}

/**
 * Exporta datos del grid a formato CSV
 * @param {Object} cells - Objeto con celdas {cellId: {value, ...}}
 * @param {number} maxRow - Fila máxima
 * @param {number} maxCol - Columna máxima
 * @returns {string} Contenido CSV
 */
export function exportToCSV(cells, maxRow, maxCol) {
    const rows = [];
    
    for (let row = 1; row <= maxRow; row++) {
        const rowData = [];
        for (let col = 1; col <= maxCol; col++) {
            const cellId = coordsToCell({ row: row - 1, col: col - 1 });
            const cell = cells[cellId];
            const value = cell?.value || '';
            
            // Escapar comillas y envolver en comillas si contiene comas o saltos de línea
            let csvValue = String(value);
            if (csvValue.includes(',') || csvValue.includes('"') || csvValue.includes('\n')) {
                csvValue = `"${csvValue.replace(/"/g, '""')}"`;
            }
            
            rowData.push(csvValue);
        }
        rows.push(rowData.join(','));
    }
    
    return rows.join('\n');
}

/**
 * Parsea contenido CSV a datos del grid
 * @param {string} csvContent - Contenido CSV
 * @returns {{cells: Object, maxRow: number, maxCol: number}} Objeto con celdas y dimensiones
 */
export function parseCSV(csvContent) {
    const cells = {};
    const lines = csvContent.split('\n');
    let maxRow = 0;
    let maxCol = 0;
    
    lines.forEach((line, rowIndex) => {
        if (!line.trim()) return;
        
        const columns = [];
        let current = '';
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === '"') {
                if (inQuotes && line[i + 1] === '"') {
                    current += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                columns.push(current);
                current = '';
            } else {
                current += char;
            }
        }
        columns.push(current);
        
        columns.forEach((value, colIndex) => {
            const cellId = coordsToCell({ row: rowIndex, col: colIndex });
            cells[cellId] = {
                value: value.trim(),
                format: {
                    backgroundColor: '#ffffff',
                    textColor: '#000000',
                    fontSize: 12,
                    fontFamily: 'Arial',
                    bold: false,
                    italic: false,
                    underline: false,
                    align: 'left',
                    format: 'text'
                }
            };
            
            maxRow = Math.max(maxRow, rowIndex + 1);
            maxCol = Math.max(maxCol, colIndex + 1);
        });
    });
    
    return { cells, maxRow, maxCol };
}

/**
 * Valida si un valor es numérico
 * @param {*} value - Valor a validar
 * @returns {boolean}
 */
export function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

/**
 * Valida si un valor es una fecha válida
 * @param {*} value - Valor a validar
 * @returns {boolean}
 */
export function isDate(value) {
    const date = new Date(value);
    return date instanceof Date && !isNaN(date);
}

