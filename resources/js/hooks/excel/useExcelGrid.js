import { evaluateFormula, isFormula } from '@/lib/excel/formulas';
import { cellToCoords, coordsToCell, indexToColumn, sortByColumn } from '@/lib/excel/utils';
import { useCallback, useState } from 'react';

// Importar sortByColumn desde excelUtils

const DEFAULT_COLUMNS = 26; // A-Z
const DEFAULT_ROWS = 100;
const DEFAULT_CELL_WIDTH = 100;
const DEFAULT_CELL_HEIGHT = 25;

const defaultFormat = {
    backgroundColor: '#ffffff',
    textColor: '#000000',
    fontSize: 12,
    fontFamily: 'Arial',
    bold: false,
    italic: false,
    underline: false,
    align: 'left',
    format: 'text'
};

/**
 * Hook principal para gestionar el estado y lógica del grid Excel
 */
export function useExcelGrid(initialData = {}) {
    const [cells, setCells] = useState(initialData.cells || {});
    const [columns, setColumns] = useState(initialData.columns || Array.from({ length: DEFAULT_COLUMNS }, (_, i) => indexToColumn(i + 1)));
    const [rows, setRows] = useState(initialData.rows || Array.from({ length: DEFAULT_ROWS }, (_, i) => i + 1));
    const [selectedCell, setSelectedCell] = useState(initialData.selectedCell || 'A1');
    const [selectedRange, setSelectedRange] = useState(initialData.selectedRange || null);
    const [sortConfig, setSortConfig] = useState(initialData.sortConfig || null);
    const [history, setHistory] = useState([]);
    const [historyIndex, setHistoryIndex] = useState(-1);
    const [clipboard, setClipboard] = useState(null);

    const maxHistorySize = 50;

    // Guardar estado en historial
    const saveToHistory = useCallback(() => {
        const currentState = {
            cells: { ...cells },
            selectedCell,
            selectedRange,
            sortConfig
        };

        setHistory(prev => {
            const newHistory = prev.slice(0, historyIndex + 1);
            newHistory.push(currentState);

            // Limitar tamaño del historial
            if (newHistory.length > maxHistorySize) {
                newHistory.shift();
            } else {
                setHistoryIndex(newHistory.length - 1);
            }

            return newHistory;
        });
    }, [cells, selectedCell, selectedRange, sortConfig, historyIndex]);

    // Undo
    const undo = useCallback(() => {
        if (historyIndex > 0) {
            const prevState = history[historyIndex - 1];
            setCells(prevState.cells);
            setSelectedCell(prevState.selectedCell);
            setSelectedRange(prevState.selectedRange);
            setSortConfig(prevState.sortConfig);
            setHistoryIndex(historyIndex - 1);
        }
    }, [history, historyIndex]);

    // Redo
    const redo = useCallback(() => {
        if (historyIndex < history.length - 1) {
            const nextState = history[historyIndex + 1];
            setCells(nextState.cells);
            setSelectedCell(nextState.selectedCell);
            setSelectedRange(nextState.selectedRange);
            setSortConfig(nextState.sortConfig);
            setHistoryIndex(historyIndex + 1);
        }
    }, [history, historyIndex]);

    // Actualizar valor de celda
    const updateCell = useCallback((cellId, value, format = null) => {
        setCells(prev => {
            const newCells = { ...prev };

            if (value === '' || value == null) {
                // Si el valor está vacío y no hay formato personalizado, eliminar la celda
                if (!format && (!newCells[cellId] || !newCells[cellId].format ||
                    JSON.stringify(newCells[cellId].format) === JSON.stringify(defaultFormat))) {
                    delete newCells[cellId];
                } else {
                    newCells[cellId] = {
                        ...newCells[cellId],
                        value: '',
                        format: format || newCells[cellId]?.format || defaultFormat
                    };
                }
            } else {
                // Guardar siempre el valor raw (lo que el usuario escribió)
                newCells[cellId] = {
                    value,
                    format: format || newCells[cellId]?.format || defaultFormat
                };
            }

            return newCells;
        });

        saveToHistory();
    }, [saveToHistory]);

    // Actualizar formato de celda(s)
    const updateCellFormat = useCallback((cellIds, formatUpdates) => {
        setCells(prev => {
            const newCells = { ...prev };
            const ids = Array.isArray(cellIds) ? cellIds : [cellIds];

            ids.forEach(cellId => {
                const currentCell = newCells[cellId] || { value: '', format: { ...defaultFormat } };
                newCells[cellId] = {
                    ...currentCell,
                    format: {
                        ...currentCell.format,
                        ...formatUpdates
                    }
                };
            });

            return newCells;
        });

        saveToHistory();
    }, [saveToHistory]);

    // Agregar fila
    const addRow = useCallback((afterIndex = null) => {
        setRows(prev => {
            const newRows = [...prev];
            const insertIndex = afterIndex !== null ? afterIndex : newRows.length;
            const newRowNum = insertIndex + 1;
            newRows.splice(insertIndex, 0, newRowNum);
            return newRows;
        });
    }, []);

    // Eliminar fila
    const deleteRow = useCallback((rowNum) => {
        setRows(prev => prev.filter(r => r !== rowNum));

        // Eliminar celdas de la fila
        setCells(prev => {
            const newCells = { ...prev };
            columns.forEach(col => {
                const cellId = `${col}${rowNum}`;
                delete newCells[cellId];
            });
            return newCells;
        });

        saveToHistory();
    }, [columns, saveToHistory]);

    // Agregar columna
    const addColumn = useCallback((afterIndex = null) => {
        setColumns(prev => {
            const newCols = [...prev];
            const insertIndex = afterIndex !== null ? afterIndex : newCols.length;
            // Calcular nueva letra de columna basada en el índice
            const newColLetter = indexToColumn(insertIndex + 1);
            newCols.splice(insertIndex, 0, newColLetter);
            return newCols;
        });
    }, []);

    // Eliminar columna
    const deleteColumn = useCallback((colLetter) => {
        setColumns(prev => prev.filter(c => c !== colLetter));

        // Eliminar celdas de la columna
        setCells(prev => {
            const newCells = { ...prev };
            rows.forEach(row => {
                const cellId = `${colLetter}${row}`;
                delete newCells[cellId];
            });
            return newCells;
        });

        saveToHistory();
    }, [rows, saveToHistory]);

    // Ordenar por columna
    const sortByCol = useCallback((colLetter, direction = 'asc') => {
        setSortConfig({ column: colLetter, direction });

        // Convertir celdas a array de objetos para ordenar
        const dataArray = rows.map(row => {
            const obj = { __row__: row };
            columns.forEach(col => {
                const cellId = `${col}${row}`;
                obj[col] = cells[cellId]?.value || '';
            });
            return obj;
        });

        const sorted = sortByColumn(dataArray, colLetter, direction);

        // Reordenar celdas según el ordenamiento
        setCells(prev => {
            const newCells = { ...prev };
            // Primero, crear un mapeo de filas antiguas a nuevas
            const rowMapping = {};
            sorted.forEach((item, index) => {
                const oldRow = item.__row__;
                const newRow = rows[index];
                rowMapping[oldRow] = newRow;
            });

            // Crear nuevas celdas con las filas reordenadas
            const reorderedCells = {};
            Object.keys(prev).forEach(cellId => {
                const match = cellId.match(/^([A-Z]+)(\d+)$/);
                if (match) {
                    const col = match[1];
                    const oldRow = parseInt(match[2], 10);
                    const newRow = rowMapping[oldRow] || oldRow;
                    const newCellId = `${col}${newRow}`;
                    reorderedCells[newCellId] = { ...prev[cellId] };
                } else {
                    reorderedCells[cellId] = { ...prev[cellId] };
                }
            });

            return reorderedCells;
        });

        saveToHistory();
    }, [cells, columns, rows, saveToHistory]);

    // Copiar celdas
    const copyCells = useCallback((cellIds) => {
        const data = {};
        cellIds.forEach(cellId => {
            if (cells[cellId]) {
                data[cellId] = { ...cells[cellId] };
            }
        });
        setClipboard({ data, cellIds });
    }, [cells]);

    // Pegar celdas
    const pasteCells = useCallback((targetCellId) => {
        if (!clipboard) return;

        const targetCoords = cellToCoords(targetCellId);
        const sourceCellIds = clipboard.cellIds;

        if (sourceCellIds.length === 0) return;

        // Calcular offset del rango original
        const firstSource = cellToCoords(sourceCellIds[0]);
        const offsets = sourceCellIds.map(id => {
            const coords = cellToCoords(id);
            return {
                rowOffset: coords.row - firstSource.row,
                colOffset: coords.col - firstSource.col,
                cellId: id
            };
        });

        // Pegar en nuevas posiciones
        setCells(prev => {
            const newCells = { ...prev };
            offsets.forEach(({ rowOffset, colOffset, cellId }) => {
                const newCoords = {
                    row: targetCoords.row + rowOffset,
                    col: targetCoords.col + colOffset
                };
                const newCellId = coordsToCell(newCoords);

                if (clipboard.data[cellId]) {
                    newCells[newCellId] = { ...clipboard.data[cellId] };
                }
            });
            return newCells;
        });

        saveToHistory();
    }, [clipboard, saveToHistory]);

    // Limpiar celdas seleccionadas
    const clearCells = useCallback((cellIds) => {
        setCells(prev => {
            const newCells = { ...prev };
            cellIds.forEach(cellId => {
                if (newCells[cellId]) {
                    delete newCells[cellId];
                }
            });
            return newCells;
        });

        saveToHistory();
    }, [saveToHistory]);

    // Seleccionar celda
    const selectCell = useCallback((cellId) => {
        setSelectedCell(cellId);
        setSelectedRange(null);
    }, []);

    // Seleccionar rango
    const selectRange = useCallback((startCell, endCell) => {
        setSelectedCell(startCell);
        setSelectedRange({ start: startCell, end: endCell });
    }, []);
    
    // Seleccionar toda una columna
    const selectColumn = useCallback((column) => {
        if (rows.length === 0) return;
        const firstCell = `${column}${rows[0]}`;
        const lastCell = `${column}${rows[rows.length - 1]}`;
        selectRange(firstCell, lastCell);
    }, [rows, selectRange]);
    
    // Seleccionar toda una fila
    const selectRow = useCallback((row) => {
        if (columns.length === 0) return;
        const firstCell = `${columns[0]}${row}`;
        const lastCell = `${columns[columns.length - 1]}${row}`;
        selectRange(firstCell, lastCell);
    }, [columns, selectRange]);
    
    // Insertar columna a la izquierda
    const insertColumnLeft = useCallback((targetColumn) => {
        setColumns(prev => {
            const newCols = [...prev];
            const index = newCols.indexOf(targetColumn);
            if (index === -1) return prev;
            
            // Generar nueva letra de columna
            const newColLetter = indexToColumn(index + 1);
            newCols.splice(index, 0, newColLetter);
            return newCols;
        });
        saveToHistory();
    }, [saveToHistory]);
    
    // Insertar columna a la derecha
    const insertColumnRight = useCallback((targetColumn) => {
        setColumns(prev => {
            const newCols = [...prev];
            const index = newCols.indexOf(targetColumn);
            if (index === -1) return prev;
            
            // Generar nueva letra de columna
            const newColLetter = indexToColumn(index + 2);
            newCols.splice(index + 1, 0, newColLetter);
            return newCols;
        });
        saveToHistory();
    }, [saveToHistory]);
    
    // Insertar fila arriba
    const insertRowAbove = useCallback((targetRow) => {
        setRows(prev => {
            const newRows = [...prev];
            const index = newRows.indexOf(targetRow);
            if (index === -1) return prev;
            
            newRows.splice(index, 0, targetRow);
            // Re-numerar filas posteriores
            for (let i = index + 1; i < newRows.length; i++) {
                newRows[i] = newRows[i] + 1;
            }
            return newRows;
        });
        saveToHistory();
    }, [saveToHistory]);
    
    // Insertar fila abajo
    const insertRowBelow = useCallback((targetRow) => {
        setRows(prev => {
            const newRows = [...prev];
            const index = newRows.indexOf(targetRow);
            if (index === -1) return prev;
            
            newRows.splice(index + 1, 0, targetRow + 1);
            // Re-numerar filas posteriores
            for (let i = index + 2; i < newRows.length; i++) {
                newRows[i] = newRows[i] + 1;
            }
            return newRows;
        });
        saveToHistory();
    }, [saveToHistory]);

    // Obtener valor raw de celda (lo que el usuario escribió)
    const getCellRawValue = useCallback((cellId) => {
        return cells[cellId]?.value || '';
    }, [cells]);
    
    // Obtener valor para mostrar (aplica transformer si es fórmula)
    const getCellDisplayValue = useCallback((cellId) => {
        const rawValue = cells[cellId]?.value || '';
        
        // Si es una fórmula, evaluarla
        if (isFormula(rawValue)) {
            return evaluateFormula(rawValue, cells);
        }
        
        // Si no, devolver el valor tal cual
        return rawValue;
    }, [cells]);

    // Obtener formato de celda
    const getCellFormat = useCallback((cellId) => {
        return cells[cellId]?.format || defaultFormat;
    }, [cells]);

    return {
        // Estado
        cells,
        columns,
        rows,
        selectedCell,
        selectedRange,
        sortConfig,
        clipboard,

        // Acciones
        updateCell,
        updateCellFormat,
        selectCell,
        selectRange,
        selectColumn,
        selectRow,
        addRow,
        deleteRow,
        addColumn,
        deleteColumn,
        insertColumnLeft,
        insertColumnRight,
        insertRowAbove,
        insertRowBelow,
        sortByCol,
        copyCells,
        pasteCells,
        clearCells,
        undo,
        redo,
        getCellRawValue,
        getCellDisplayValue,
        getCellFormat,

        // Utilidades
        canUndo: historyIndex > 0,
        canRedo: historyIndex < history.length - 1
    };
}

