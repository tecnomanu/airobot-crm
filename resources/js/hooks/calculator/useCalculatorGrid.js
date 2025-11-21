import { evaluateFormula, isFormula } from '@/lib/calculator/formulas';
import { cellToCoords, coordsToCell, indexToColumn, sortByColumn } from '@/lib/calculator/utils';
import { useCallback, useState } from 'react';

// Importar sortByColumn desde calculatorUtils

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
 * Hook principal para gestionar el estado y lógica del grid Calculator
 */
export function useCalculatorGrid(initialData = {}) {
    const [cells, setCells] = useState(initialData.data || {});
    const [columns, setColumns] = useState(initialData.columns || Array.from({ length: DEFAULT_COLUMNS }, (_, i) => indexToColumn(i + 1)));
    const [rows, setRows] = useState(initialData.rows || Array.from({ length: DEFAULT_ROWS }, (_, i) => i + 1));
    // Inicializar selectedCell desde lastCursorPosition (que viene en formato 1-based)
    const [selectedCell, setSelectedCell] = useState(() => {
        if (initialData.lastCursorPosition?.col && initialData.lastCursorPosition?.row) {
            const col = indexToColumn(initialData.lastCursorPosition.col); // col ya es 1-based
            const row = initialData.lastCursorPosition.row; // row ya es 1-based
            return `${col}${row}`;
        }
        return 'A1';
    });
    const [selectedRange, setSelectedRange] = useState(initialData.selectedRange || null);
    const [sortConfig, setSortConfig] = useState(initialData.sortConfig || null);
    const [columnWidths, setColumnWidths] = useState(initialData.columnWidths || {});
    const [rowHeights, setRowHeights] = useState(initialData.rowHeights || {});
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
        const insertIndex = afterIndex !== null ? afterIndex : rows.length;

        // Mover contenido de las celdas hacia abajo
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de filas desde insertIndex hacia abajo
            for (let i = rows.length - 1; i >= insertIndex; i--) {
                const oldRowNum = rows[i];
                const newRowNum = i + 2; // Nueva posición (1-indexed)

                columns.forEach(col => {
                    const oldCellId = `${col}${oldRowNum}`;
                    const newCellId = `${col}${newRowNum}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Agregar nueva fila al array (siempre secuencial)
        setRows(prev => {
            const newRows = Array.from({ length: prev.length + 1 }, (_, i) => i + 1);
            return newRows;
        });

        saveToHistory();
    }, [rows, columns, saveToHistory]);

    // Eliminar fila
    const deleteRow = useCallback((rowNum) => {
        const rowIndex = rows.indexOf(rowNum);
        if (rowIndex === -1) return;

        // Mover contenido de las celdas hacia arriba
        setCells(prev => {
            const newCells = { ...prev };

            // Eliminar la fila actual
            columns.forEach(col => {
                const cellId = `${col}${rowNum}`;
                delete newCells[cellId];
            });

            // Mover filas superiores hacia arriba
            for (let i = rowIndex + 1; i < rows.length; i++) {
                const oldRowNum = rows[i];
                const newRowNum = i; // Nueva posición (1-indexed)

                columns.forEach(col => {
                    const oldCellId = `${col}${oldRowNum}`;
                    const newCellId = `${col}${newRowNum}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Reducir array de filas (siempre secuencial)
        setRows(prev => {
            const newRows = Array.from({ length: prev.length - 1 }, (_, i) => i + 1);
            return newRows;
        });

        saveToHistory();
    }, [rows, columns, saveToHistory]);

    // Agregar columna
    const addColumn = useCallback((afterIndex = null) => {
        const insertIndex = afterIndex !== null ? afterIndex : columns.length;

        // Mover contenido de las celdas hacia la derecha
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de columnas desde insertIndex hacia la derecha
            for (let i = columns.length - 1; i >= insertIndex; i--) {
                const oldColLetter = columns[i];
                const newColLetter = indexToColumn(i + 2); // Nueva posición

                rows.forEach(row => {
                    const oldCellId = `${oldColLetter}${row}`;
                    const newCellId = `${newColLetter}${row}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Agregar nueva columna al array (siempre secuencial A, B, C...)
        setColumns(prev => {
            const newColumns = Array.from({ length: prev.length + 1 }, (_, i) => indexToColumn(i + 1));
            return newColumns;
        });

        saveToHistory();
    }, [columns, rows, saveToHistory]);

    // Eliminar columna
    const deleteColumn = useCallback((colLetter) => {
        const colIndex = columns.indexOf(colLetter);
        if (colIndex === -1) return;

        // Mover contenido de las celdas hacia la izquierda
        setCells(prev => {
            const newCells = { ...prev };

            // Eliminar la columna actual
            rows.forEach(row => {
                const cellId = `${colLetter}${row}`;
                delete newCells[cellId];
            });

            // Mover columnas siguientes hacia la izquierda
            for (let i = colIndex + 1; i < columns.length; i++) {
                const oldColLetter = columns[i];
                const newColLetter = indexToColumn(i); // Nueva posición

                rows.forEach(row => {
                    const oldCellId = `${oldColLetter}${row}`;
                    const newCellId = `${newColLetter}${row}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Reducir array de columnas (siempre secuencial A, B, C...)
        setColumns(prev => {
            const newColumns = Array.from({ length: prev.length - 1 }, (_, i) => indexToColumn(i + 1));
            return newColumns;
        });

        saveToHistory();
    }, [columns, rows, saveToHistory]);

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
        const colIndex = columns.indexOf(targetColumn);
        if (colIndex === -1) return;

        // Mover contenido de las celdas hacia la derecha
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de columnas desde colIndex hacia la derecha
            for (let i = columns.length - 1; i >= colIndex; i--) {
                const oldColLetter = columns[i];
                const newColLetter = indexToColumn(i + 2); // Nueva posición

                rows.forEach(row => {
                    const oldCellId = `${oldColLetter}${row}`;
                    const newCellId = `${newColLetter}${row}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Regenerar array de columnas secuencial (A, B, C...)
        setColumns(prev => {
            const newColumns = Array.from({ length: prev.length + 1 }, (_, i) => indexToColumn(i + 1));
            return newColumns;
        });

        saveToHistory();
    }, [columns, rows, saveToHistory]);

    // Insertar columna a la derecha
    const insertColumnRight = useCallback((targetColumn) => {
        const colIndex = columns.indexOf(targetColumn);
        if (colIndex === -1) return;

        // Mover contenido de las celdas hacia la derecha
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de columnas desde colIndex+1 hacia la derecha
            for (let i = columns.length - 1; i > colIndex; i--) {
                const oldColLetter = columns[i];
                const newColLetter = indexToColumn(i + 2); // Nueva posición

                rows.forEach(row => {
                    const oldCellId = `${oldColLetter}${row}`;
                    const newCellId = `${newColLetter}${row}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Regenerar array de columnas secuencial (A, B, C...)
        setColumns(prev => {
            const newColumns = Array.from({ length: prev.length + 1 }, (_, i) => indexToColumn(i + 1));
            return newColumns;
        });

        saveToHistory();
    }, [columns, rows, saveToHistory]);

    // Insertar fila arriba
    const insertRowAbove = useCallback((targetRow) => {
        const rowIndex = rows.indexOf(targetRow);
        if (rowIndex === -1) return;

        // Mover contenido de las celdas hacia abajo
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de filas desde rowIndex hacia abajo
            for (let i = rows.length - 1; i >= rowIndex; i--) {
                const oldRowNum = rows[i];
                const newRowNum = i + 2; // Nueva posición (1-indexed)

                columns.forEach(col => {
                    const oldCellId = `${col}${oldRowNum}`;
                    const newCellId = `${col}${newRowNum}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Regenerar array de filas secuencial (1, 2, 3...)
        setRows(prev => {
            const newRows = Array.from({ length: prev.length + 1 }, (_, i) => i + 1);
            return newRows;
        });

        saveToHistory();
    }, [rows, columns, saveToHistory]);

    // Insertar fila abajo
    const insertRowBelow = useCallback((targetRow) => {
        const rowIndex = rows.indexOf(targetRow);
        if (rowIndex === -1) return;

        // Mover contenido de las celdas hacia abajo
        setCells(prev => {
            const newCells = { ...prev };

            // Mover contenido de filas desde rowIndex+1 hacia abajo
            for (let i = rows.length - 1; i > rowIndex; i--) {
                const oldRowNum = rows[i];
                const newRowNum = i + 2; // Nueva posición (1-indexed)

                columns.forEach(col => {
                    const oldCellId = `${col}${oldRowNum}`;
                    const newCellId = `${col}${newRowNum}`;

                    if (newCells[oldCellId]) {
                        newCells[newCellId] = newCells[oldCellId];
                        delete newCells[oldCellId];
                    }
                });
            }

            return newCells;
        });

        // Regenerar array de filas secuencial (1, 2, 3...)
        setRows(prev => {
            const newRows = Array.from({ length: prev.length + 1 }, (_, i) => i + 1);
            return newRows;
        });

        saveToHistory();
    }, [rows, columns, saveToHistory]);

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

    // Obtener estado completo para guardar
    const getState = useCallback(() => {
        // Convertir selectedCell a coordenadas 1-based para guardar (como las ve el usuario)
        const coords = selectedCell ? cellToCoords(selectedCell) : { row: 0, col: 0 };

        return {
            data: cells,
            lastCursorPosition: { row: coords.row + 1, col: coords.col + 1 }, // Guardar como 1-based
            columnWidths,
            rowHeights,
            frozenRows: 0,
            frozenColumns: 0,
        };
    }, [cells, selectedCell, columnWidths, rowHeights]);

    // Cargar estado desde datos
    const loadState = useCallback((data) => {
        if (data.data) {
            setCells(data.data);
        }
        if (data.columnWidths) {
            setColumnWidths(data.columnWidths);
        }
        if (data.rowHeights) {
            setRowHeights(data.rowHeights);
        }
        if (data.lastCursorPosition) {
            // lastCursorPosition viene en formato 1-based desde la BD
            const col = indexToColumn(data.lastCursorPosition.col); // col ya es 1-based
            const row = data.lastCursorPosition.row; // row ya es 1-based
            const cellId = `${col}${row}`;
            setSelectedCell(cellId);
        }
    }, []);

    // Actualizar ancho de columna
    const updateColumnWidth = useCallback((column, width) => {
        setColumnWidths(prev => ({
            ...prev,
            [column]: width
        }));
    }, []);

    // Actualizar altura de fila
    const updateRowHeight = useCallback((row, height) => {
        setRowHeights(prev => ({
            ...prev,
            [row]: height
        }));
    }, []);

    return {
        // Estado
        cells,
        columns,
        rows,
        selectedCell,
        selectedRange,
        sortConfig,
        clipboard,
        columnWidths,
        rowHeights,

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
        updateColumnWidth,
        updateRowHeight,

        // Estado completo
        getState,
        loadState,

        // Utilidades
        canUndo: historyIndex > 0,
        canRedo: historyIndex < history.length - 1
    };
}

