import { cellToCoords, coordsToCell, getCellRange } from '@/lib/calculator/utils';
import { evaluateFormula, isFormula } from '@/lib/calculator/formulas';
import { useCallback, useEffect, useRef, useState } from 'react';
import CalculatorCell from './CalculatorCell';
import CalculatorColumnHeader from './CalculatorColumnHeader';
import CalculatorRowHeader from './CalculatorRowHeader';

export default function CalculatorGrid({
    cells = {},
    columns = [],
    rows = [],
    selectedCell = 'A1',
    selectedRange = null,
    sortConfig = null,
    isEditingFormula = false,
    onUpdateCell,
    onSelectCell,
    onSelectRange,
    onSelectColumn,
    onSelectRow,
    onSortColumn,
    onAddRow,
    onDeleteRow,
    onAddColumn,
    onDeleteColumn,
    onInsertColumnLeft,
    onInsertColumnRight,
    onInsertRowAbove,
    onInsertRowBelow,
    onCopyCells,
    onPasteCells,
    onClearCells
}) {
    const gridRef = useRef(null);
    const scrollContainerRef = useRef(null);
    const [dragStart, setDragStart] = useState(null);
    const [isDragging, setIsDragging] = useState(false);
    const [isSelecting, setIsSelecting] = useState(false);
    const [columnWidths, setColumnWidths] = useState({});
    const [rowHeights, setRowHeights] = useState({});
    const [resizing, setResizing] = useState(null);
    const [resizePreview, setResizePreview] = useState(null); // { type: 'column'|'row', position: number }
    const [selectionAnchor, setSelectionAnchor] = useState(null);
    const [dragColumnStart, setDragColumnStart] = useState(null);
    const [dragRowStart, setDragRowStart] = useState(null);
    
    // Manejar inicio de selección por arrastre
    const handleStartSelection = useCallback((cellId, isFillHandle = false) => {
        setDragStart(isFillHandle ? selectedCell : cellId);
        setIsSelecting(true);
        setIsDragging(isFillHandle);
        if (!isFillHandle && onSelectCell) {
            onSelectCell(cellId);
        }
    }, [onSelectCell, selectedCell]);
    
    // Manejar arrastre sobre celda
    const handleCellDragOver = useCallback((cellId) => {
        if (isSelecting && dragStart && onSelectRange) {
            onSelectRange(dragStart, cellId);
        }
    }, [isSelecting, dragStart, onSelectRange]);
    
    // Manejar pegado desde clipboard externo (Calculator, Google Sheets, etc.)
    const handlePaste = useCallback((e) => {
        if (!selectedCell || !onUpdateCell) return;
        
        // Obtener texto del clipboard
        const text = e.clipboardData?.getData('text/plain');
        if (!text) return;
        
        // Detectar si es contenido multi-celda (tiene tabulaciones o saltos de línea)
        const hasMultipleCells = text.includes('\t') || text.includes('\n');
        
        if (hasMultipleCells) {
            e.preventDefault();
            
            // Dividir en filas y columnas
            // Remover última línea vacía si existe (común en copias de Calculator/Google Sheets)
            const cleanText = text.endsWith('\n') ? text.slice(0, -1) : text;
            const pasteRows = cleanText.split('\n');
            const data = pasteRows.map(row => row.split('\t'));
            
            // Obtener coordenadas de la celda inicial
            const startCoords = cellToCoords(selectedCell);
            
            let cellsPasted = 0;
            
            // Pegar cada celda
            data.forEach((rowData, rowOffset) => {
                rowData.forEach((cellValue, colOffset) => {
                    const targetCoords = {
                        row: startCoords.row + rowOffset,
                        col: startCoords.col + colOffset
                    };
                    
                    // Verificar que no nos salimos del grid
                    if (targetCoords.row < rows.length && targetCoords.col < columns.length) {
                        const targetCellId = coordsToCell(targetCoords);
                        onUpdateCell(targetCellId, cellValue.trim());
                        cellsPasted++;
                    }
                });
            });
            
            console.log(`Pegadas ${cellsPasted} celdas desde clipboard externo`);
        }
        // Si no es multi-celda, dejar que se pegue normalmente en el input
    }, [selectedCell, rows, columns, onUpdateCell]);
    
    // Manejar fin de selección
    const handleEndSelection = useCallback(() => {
        // Si estaba haciendo fill handle drag, copiar el valor
        if (isDragging && selectedRange && selectedCell) {
            const sourceCellData = cells[selectedCell];
            if (sourceCellData && onUpdateCell) {
                const rangeCellIds = getCellRange(selectedRange.start, selectedRange.end);
                rangeCellIds.forEach(cellId => {
                    if (cellId !== selectedCell) {
                        onUpdateCell(cellId, sourceCellData.value, sourceCellData.format);
                    }
                });
            }
        }
        
        setIsSelecting(false);
        setIsDragging(false);
        setDragStart(null);
    }, [isDragging, selectedRange, selectedCell, cells, onUpdateCell]);
    
    // Manejar navegación con teclado a nivel de grid
    // Nota: Los atajos de teclado globales se manejan en useCalculatorKeyboard
    const handleKeyDown = useCallback((e) => {
        // Solo manejar eventos que no fueron manejados por las celdas individuales
        // Las celdas manejan su propia navegación, aquí solo backup
        if (e.defaultPrevented) return;
        
        // Atajos específicos del grid que no están en las celdas
        if (e.ctrlKey || e.metaKey) {
            // Los atajos Ctrl+C, Ctrl+V, etc. se manejan en useCalculatorKeyboard
            return;
        }
    }, []);
    
    useEffect(() => {
        const grid = gridRef.current;
        if (grid) {
            grid.addEventListener('keydown', handleKeyDown, { capture: false });
            grid.addEventListener('paste', handlePaste);
            return () => {
                grid.removeEventListener('keydown', handleKeyDown);
                grid.removeEventListener('paste', handlePaste);
            };
        }
    }, [handleKeyDown, handlePaste]);
    
    
    useEffect(() => {
        const handleMouseUp = () => {
            handleEndSelection();
        };
        
        document.addEventListener('mouseup', handleMouseUp);
        document.addEventListener('mouseleave', handleMouseUp);
        
        return () => {
            document.removeEventListener('mouseup', handleMouseUp);
            document.removeEventListener('mouseleave', handleMouseUp);
        };
    }, [handleEndSelection]);
    
    // Scroll inteligente a celda
    const scrollToCell = useCallback((cellId) => {
        if (!scrollContainerRef.current) return;
        
        const coords = cellToCoords(cellId);
        const cellElement = scrollContainerRef.current.querySelector(`[data-cell-id="${cellId}"]`);
        
        if (cellElement) {
            const container = scrollContainerRef.current;
            const cellRect = cellElement.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            
            const PADDING = 50; // Padding para anticipar el scroll
            
            // Scroll vertical
            if (cellRect.bottom > containerRect.bottom - PADDING) {
                container.scrollTop += (cellRect.bottom - containerRect.bottom + PADDING);
            } else if (cellRect.top < containerRect.top + PADDING) {
                container.scrollTop -= (containerRect.top - cellRect.top + PADDING);
            }
            
            // Scroll horizontal
            if (cellRect.right > containerRect.right - PADDING) {
                container.scrollLeft += (cellRect.right - containerRect.right + PADDING);
            } else if (cellRect.left < containerRect.left + 50 + PADDING) { // 50px = ancho del row header
                container.scrollLeft -= (containerRect.left + 50 - cellRect.left + PADDING);
            }
        }
    }, []);
    
    // Navegar a celda adyacente
    const handleNavigate = useCallback((direction, shiftKey) => {
        // Determinar desde qué celda navegar
        let currentCell;
        if (shiftKey && selectedRange) {
            // Si ya hay un rango, navegar desde el extremo (end)
            currentCell = selectedRange.end;
        } else {
            // Navegar desde la celda seleccionada
            currentCell = selectedCell;
        }
        
        const coords = cellToCoords(currentCell);
        let newRow = coords.row;
        let newCol = coords.col;
        
        switch (direction) {
            case 'ArrowUp':
                newRow = Math.max(0, newRow - 1);
                break;
            case 'ArrowDown':
                newRow = Math.min(rows.length - 1, newRow + 1);
                break;
            case 'ArrowLeft':
                newCol = Math.max(0, newCol - 1);
                break;
            case 'ArrowRight':
                newCol = Math.min(columns.length - 1, newCol + 1);
                break;
        }
        
        const newCellId = coordsToCell({ row: newRow, col: newCol });
        
        if (shiftKey) {
            // Mantener el punto de anclaje y expandir desde ahí
            const anchor = selectionAnchor || selectedCell;
            setSelectionAnchor(anchor);
            if (onSelectRange) {
                onSelectRange(anchor, newCellId);
            }
        } else {
            // Navegación normal sin Shift
            setSelectionAnchor(null);
            if (onSelectCell) {
                onSelectCell(newCellId);
            }
        }
        
        // Hacer scroll inteligente a la nueva celda
        setTimeout(() => scrollToCell(newCellId), 10);
    }, [selectedCell, selectedRange, selectionAnchor, columns, rows, onSelectCell, onSelectRange, scrollToCell]);
    
    // Obtener celdas en rango seleccionado
    const getRangeCells = useCallback(() => {
        if (!selectedRange) return [];
        return getCellRange(selectedRange.start, selectedRange.end);
    }, [selectedRange]);
    
    const rangeCells = getRangeCells();
    
    // Calcular bordes del rango para cada celda
    const getRangeBorders = useCallback((cellId) => {
        if (!selectedRange || rangeCells.length === 0) return {};
        if (!rangeCells.includes(cellId)) return {};
        
        const startCoords = cellToCoords(selectedRange.start);
        const endCoords = cellToCoords(selectedRange.end);
        const cellCoords = cellToCoords(cellId);
        
        const minRow = Math.min(startCoords.row, endCoords.row);
        const maxRow = Math.max(startCoords.row, endCoords.row);
        const minCol = Math.min(startCoords.col, endCoords.col);
        const maxCol = Math.max(startCoords.col, endCoords.col);
        
        return {
            top: cellCoords.row === minRow,
            bottom: cellCoords.row === maxRow,
            left: cellCoords.col === minCol,
            right: cellCoords.col === maxCol
        };
    }, [selectedRange, rangeCells]);
    
    // Verificar si una columna está completamente seleccionada
    const isColumnFullySelected = useCallback((col) => {
        if (!selectedRange) return false;
        
        const startCoords = cellToCoords(selectedRange.start);
        const endCoords = cellToCoords(selectedRange.end);
        
        const minCol = Math.min(startCoords.col, endCoords.col);
        const maxCol = Math.max(startCoords.col, endCoords.col);
        const minRow = Math.min(startCoords.row, endCoords.row);
        const maxRow = Math.max(startCoords.row, endCoords.row);
        
        const colIndex = columns.indexOf(col);
        
        // La columna está en el rango Y el rango abarca todas las filas
        return colIndex >= minCol && colIndex <= maxCol && 
               minRow === 0 && maxRow === rows.length - 1;
    }, [selectedRange, columns, rows]);
    
    // Verificar si una fila está completamente seleccionada
    const isRowFullySelected = useCallback((row) => {
        if (!selectedRange) return false;
        
        const startCoords = cellToCoords(selectedRange.start);
        const endCoords = cellToCoords(selectedRange.end);
        
        const minCol = Math.min(startCoords.col, endCoords.col);
        const maxCol = Math.max(startCoords.col, endCoords.col);
        const minRow = Math.min(startCoords.row, endCoords.row);
        const maxRow = Math.max(startCoords.row, endCoords.row);
        
        const rowIndex = rows.indexOf(row);
        
        // La fila está en el rango Y el rango abarca todas las columnas
        return rowIndex >= minRow && rowIndex <= maxRow && 
               minCol === 0 && maxCol === columns.length - 1;
    }, [selectedRange, columns, rows]);
    
    // Obtener ancho de columna
    const getColumnWidth = useCallback((col) => {
        return columnWidths[col] || 100;
    }, [columnWidths]);
    
    // Obtener alto de fila
    const getRowHeight = useCallback((row) => {
        return rowHeights[row] || 25;
    }, [rowHeights]);
    
    // Manejar inicio de drag de columna
    const handleDragStartColumn = useCallback((column) => {
        setDragColumnStart(column);
        setDragRowStart(null);
    }, []);
    
    // Manejar drag over de columna
    const handleDragOverColumn = useCallback((column) => {
        if (!dragColumnStart || !onSelectRange) return;
        
        const startIndex = columns.indexOf(dragColumnStart);
        const endIndex = columns.indexOf(column);
        
        if (startIndex === -1 || endIndex === -1) return;
        
        const firstCol = columns[Math.min(startIndex, endIndex)];
        const lastCol = columns[Math.max(startIndex, endIndex)];
        
        const firstRow = rows[0];
        const lastRow = rows[rows.length - 1];
        
        const startCell = `${firstCol}${firstRow}`;
        const endCell = `${lastCol}${lastRow}`;
        
        onSelectRange(startCell, endCell);
    }, [dragColumnStart, columns, rows, onSelectRange]);
    
    // Manejar inicio de drag de fila
    const handleDragStartRow = useCallback((row) => {
        setDragRowStart(row);
        setDragColumnStart(null);
    }, []);
    
    // Manejar drag over de fila
    const handleDragOverRow = useCallback((row) => {
        if (!dragRowStart || !onSelectRange) return;
        
        const startIndex = rows.indexOf(dragRowStart);
        const endIndex = rows.indexOf(row);
        
        if (startIndex === -1 || endIndex === -1) return;
        
        const firstRow = rows[Math.min(startIndex, endIndex)];
        const lastRow = rows[Math.max(startIndex, endIndex)];
        
        const firstCol = columns[0];
        const lastCol = columns[columns.length - 1];
        
        const startCell = `${firstCol}${firstRow}`;
        const endCell = `${lastCol}${lastRow}`;
        
        onSelectRange(startCell, endCell);
    }, [dragRowStart, rows, columns, onSelectRange]);
    
    // Limpiar drag al soltar mouse
    useEffect(() => {
        const handleMouseUp = () => {
            setDragColumnStart(null);
            setDragRowStart(null);
        };
        
        document.addEventListener('mouseup', handleMouseUp);
        return () => {
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }, []);
    
    // Manejar cortar columna
    const handleCutColumn = useCallback((column) => {
        if (!onSelectColumn || !onCopyCells) return;
        // Seleccionar la columna completa
        onSelectColumn(column);
        // Copiar el contenido
        const firstRow = rows[0];
        const lastRow = rows[rows.length - 1];
        const cellIds = getCellRange(`${column}${firstRow}`, `${column}${lastRow}`);
        onCopyCells(cellIds);
        // Limpiar las celdas
        if (onClearCells) {
            onClearCells(cellIds);
        }
    }, [rows, onSelectColumn, onCopyCells, onClearCells]);
    
    // Manejar copiar columna
    const handleCopyColumn = useCallback((column) => {
        if (!onSelectColumn || !onCopyCells) return;
        // Seleccionar la columna completa
        onSelectColumn(column);
        // Copiar el contenido
        const firstRow = rows[0];
        const lastRow = rows[rows.length - 1];
        const cellIds = getCellRange(`${column}${firstRow}`, `${column}${lastRow}`);
        onCopyCells(cellIds);
    }, [rows, onSelectColumn, onCopyCells]);
    
    // Manejar pegar en columna
    const handlePasteColumn = useCallback((column) => {
        if (!onPasteCells) return;
        // Pegar desde la primera celda de la columna
        const firstRow = rows[0];
        const targetCell = `${column}${firstRow}`;
        onPasteCells(targetCell);
    }, [rows, onPasteCells]);
    
    // Manejar cortar fila
    const handleCutRow = useCallback((row) => {
        if (!onSelectRow || !onCopyCells) return;
        // Seleccionar la fila completa
        onSelectRow(row);
        // Copiar el contenido
        const firstCol = columns[0];
        const lastCol = columns[columns.length - 1];
        const cellIds = getCellRange(`${firstCol}${row}`, `${lastCol}${row}`);
        onCopyCells(cellIds);
        // Limpiar las celdas
        if (onClearCells) {
            onClearCells(cellIds);
        }
    }, [columns, onSelectRow, onCopyCells, onClearCells]);
    
    // Manejar copiar fila
    const handleCopyRow = useCallback((row) => {
        if (!onSelectRow || !onCopyCells) return;
        // Seleccionar la fila completa
        onSelectRow(row);
        // Copiar el contenido
        const firstCol = columns[0];
        const lastCol = columns[columns.length - 1];
        const cellIds = getCellRange(`${firstCol}${row}`, `${lastCol}${row}`);
        onCopyCells(cellIds);
    }, [columns, onSelectRow, onCopyCells]);
    
    // Manejar pegar en fila
    const handlePasteRow = useCallback((row) => {
        if (!onPasteCells) return;
        // Pegar desde la primera celda de la fila
        const firstCol = columns[0];
        const targetCell = `${firstCol}${row}`;
        onPasteCells(targetCell);
    }, [columns, onPasteCells]);
    
    // Manejar inicio de redimensionado
    const handleResizeStart = useCallback((type, id, e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const currentSize = type === 'column' ? getColumnWidth(id) : getRowHeight(id);
        const startPos = type === 'column' ? e.clientX : e.clientY;
        
        // Calcular posición inicial de la línea guía
        const rect = scrollContainerRef.current?.getBoundingClientRect();
        if (!rect) return;
        
        const initialPosition = type === 'column' 
            ? e.clientX - rect.left
            : e.clientY - rect.top;
        
        setResizing({ 
            type, 
            id, 
            startPos,
            currentSize,
            initialPosition
        });
        
        setResizePreview({ 
            type, 
            position: initialPosition 
        });
    }, [getColumnWidth, getRowHeight]);
    
    // Manejar redimensionado con línea guía
    useEffect(() => {
        if (!resizing) return;
        
        const handleMouseMove = (e) => {
            const rect = scrollContainerRef.current?.getBoundingClientRect();
            if (!rect) return;
            
            if (resizing.type === 'column') {
                const diff = e.clientX - resizing.startPos;
                const newWidth = Math.max(50, resizing.currentSize + diff);
                const newPosition = resizing.initialPosition + diff;
                
                // Solo actualizar la línea de preview, no el tamaño real
                setResizePreview({ 
                    type: 'column', 
                    position: Math.max(50, newPosition)
                });
            } else {
                const diff = e.clientY - resizing.startPos;
                const newHeight = Math.max(20, resizing.currentSize + diff);
                const newPosition = resizing.initialPosition + diff;
                
                // Solo actualizar la línea de preview, no el tamaño real
                setResizePreview({ 
                    type: 'row', 
                    position: Math.max(20, newPosition)
                });
            }
        };
        
        const handleMouseUp = (e) => {
            // Aplicar el tamaño final al soltar
            if (resizing.type === 'column') {
                const diff = e.clientX - resizing.startPos;
                const newWidth = Math.max(50, resizing.currentSize + diff);
                setColumnWidths(prev => ({ ...prev, [resizing.id]: newWidth }));
            } else {
                const diff = e.clientY - resizing.startPos;
                const newHeight = Math.max(20, resizing.currentSize + diff);
                setRowHeights(prev => ({ ...prev, [resizing.id]: newHeight }));
            }
            
            // Limpiar estados
            setResizing(null);
            setResizePreview(null);
        };
        
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
        
        return () => {
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }, [resizing]);
    
    // Renderizar celda
    const renderCell = useCallback((row, col) => {
        const cellId = `${col}${row}`;
        const cell = cells[cellId];
        const isSelected = cellId === selectedCell;
        const isInRange = rangeCells.includes(cellId);
        const rangeBorders = getRangeBorders(cellId);
        
        // Obtener valor raw y valor para mostrar
        const rawValue = cell?.value || '';
        const displayValue = isFormula(rawValue) ? evaluateFormula(rawValue, cells) : rawValue;
        
        return (
            <CalculatorCell
                key={cellId}
                cellId={cellId}
                rawValue={rawValue}
                displayValue={displayValue}
                format={cell?.format || {}}
                isSelected={isSelected}
                isInRange={isInRange}
                rangeBorders={rangeBorders}
                width={getColumnWidth(col)}
                height={getRowHeight(row)}
                isEditingFormula={isEditingFormula}
                onUpdate={onUpdateCell}
                onSelect={(id, shift) => {
                    // Limpiar estados de drag de headers al seleccionar celda
                    setDragColumnStart(null);
                    setDragRowStart(null);
                    
                    // Si estamos editando fórmula, agregar referencia en lugar de seleccionar
                    if (isEditingFormula && window.__calculatorFormulaBar?.addCellReference) {
                        window.__calculatorFormulaBar.addCellReference(id);
                        return;
                    }
                    
                    if (shift && selectedCell) {
                        if (onSelectRange) {
                            onSelectRange(selectedCell, id);
                        }
                    } else {
                        if (onSelectCell) {
                            onSelectCell(id);
                        }
                    }
                }}
                onNavigate={handleNavigate}
                onStartDrag={(cellId, isFillHandle) => handleStartSelection(cellId, isFillHandle)}
                onDragOver={handleCellDragOver}
            />
        );
    }, [cells, selectedCell, rangeCells, isEditingFormula, onUpdateCell, onSelectCell, onSelectRange, handleNavigate, handleStartSelection, handleCellDragOver, getColumnWidth, getRowHeight, getRangeBorders]);
    
    return (
        <div className="flex flex-col h-full relative" ref={gridRef} tabIndex={-1}>
            {/* Contenedor con scroll */}
            <div 
                ref={scrollContainerRef}
                className="overflow-auto flex-1 relative" 
                style={{ height: '100%' }}
            >
                <table className="border-collapse select-none">
                    {/* Header de columnas - sticky */}
                    <thead>
                        <tr>
                            {/* Esquina superior izquierda */}
                            <th 
                                className="border border-gray-400 bg-gray-200 sticky top-0 left-0 z-40"
                                style={{ 
                                    width: '50px', 
                                    height: '25px',
                                    minWidth: '50px'
                                }}
                            ></th>
                            {/* Headers de columnas */}
                            {columns.map((col, colIndex) => (
                                <CalculatorColumnHeader
                                    key={col}
                                    column={col}
                                    isSelected={isColumnFullySelected(col)}
                                    sortConfig={sortConfig}
                                    width={getColumnWidth(col)}
                                    isLastColumn={colIndex === columns.length - 1}
                                    onSelectColumn={onSelectColumn}
                                    onDragStartColumn={handleDragStartColumn}
                                    onDragOverColumn={handleDragOverColumn}
                                    onSort={onSortColumn}
                                    onResize={(e) => handleResizeStart('column', col, e)}
                                    onInsertLeft={onInsertColumnLeft}
                                    onInsertRight={onInsertColumnRight}
                                    onDelete={onDeleteColumn}
                                    onCut={handleCutColumn}
                                    onCopy={handleCopyColumn}
                                    onPaste={handlePasteColumn}
                                />
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={row}>
                                {/* Header de fila - sticky */}
                                <CalculatorRowHeader
                                    row={row}
                                    isSelected={isRowFullySelected(row)}
                                    height={getRowHeight(row)}
                                    isLastRow={rowIndex === rows.length - 1}
                                    onSelectRow={onSelectRow}
                                    onDragStartRow={handleDragStartRow}
                                    onDragOverRow={handleDragOverRow}
                                    onResize={(e) => handleResizeStart('row', row, e)}
                                    onInsertAbove={onInsertRowAbove}
                                    onInsertBelow={onInsertRowBelow}
                                    onDelete={onDeleteRow}
                                    onCut={handleCutRow}
                                    onCopy={handleCopyRow}
                                    onPaste={handlePasteRow}
                                />
                                {/* Celdas */}
                                {columns.map(col => renderCell(row, col))}
                            </tr>
                        ))}
                    </tbody>
                </table>
                
                {/* Línea guía de redimensionado */}
                {resizePreview && (
                    <div
                        className="absolute pointer-events-none z-20"
                        style={{
                            ...(resizePreview.type === 'column' ? {
                                left: `${resizePreview.position}px`,
                                top: 0,
                                bottom: 0,
                                width: '2px',
                                backgroundColor: '#3b82f6', // blue-500
                                boxShadow: '0 0 4px rgba(59, 130, 246, 0.5)'
                            } : {
                                top: `${resizePreview.position}px`,
                                left: 0,
                                right: 0,
                                height: '2px',
                                backgroundColor: '#3b82f6', // blue-500
                                boxShadow: '0 0 4px rgba(59, 130, 246, 0.5)'
                            })
                        }}
                    />
                )}
            </div>
        </div>
    );
}

