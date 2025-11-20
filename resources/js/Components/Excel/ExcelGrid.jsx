import { cellToCoords, coordsToCell, getCellRange } from '@/lib/excel/utils';
import { useCallback, useEffect, useRef, useState } from 'react';
import ExcelCell from './ExcelCell';
import ExcelColumnHeader from './ExcelColumnHeader';
import ExcelRowHeader from './ExcelRowHeader';

export default function ExcelGrid({
    cells = {},
    columns = [],
    rows = [],
    selectedCell = 'A1',
    selectedRange = null,
    sortConfig = null,
    onUpdateCell,
    onSelectCell,
    onSelectRange,
    onSortColumn,
    onAddRow,
    onDeleteRow,
    onAddColumn,
    onDeleteColumn,
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
    const [selectionAnchor, setSelectionAnchor] = useState(null);
    
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
    // Nota: Los atajos de teclado globales se manejan en useExcelKeyboard
    const handleKeyDown = useCallback((e) => {
        // Solo manejar eventos que no fueron manejados por las celdas individuales
        // Las celdas manejan su propia navegación, aquí solo backup
        if (e.defaultPrevented) return;
        
        // Atajos específicos del grid que no están en las celdas
        if (e.ctrlKey || e.metaKey) {
            // Los atajos Ctrl+C, Ctrl+V, etc. se manejan en useExcelKeyboard
            return;
        }
    }, []);
    
    useEffect(() => {
        const grid = gridRef.current;
        if (grid) {
            grid.addEventListener('keydown', handleKeyDown, { capture: false });
            return () => {
                grid.removeEventListener('keydown', handleKeyDown);
            };
        }
    }, [handleKeyDown]);
    
    
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
    
    // Obtener ancho de columna
    const getColumnWidth = useCallback((col) => {
        return columnWidths[col] || 100;
    }, [columnWidths]);
    
    // Obtener alto de fila
    const getRowHeight = useCallback((row) => {
        return rowHeights[row] || 25;
    }, [rowHeights]);
    
    // Manejar inicio de redimensionado
    const handleResizeStart = useCallback((type, id, e) => {
        e.preventDefault();
        e.stopPropagation();
        setResizing({ type, id, startPos: type === 'column' ? e.clientX : e.clientY });
    }, []);
    
    // Manejar redimensionado
    useEffect(() => {
        if (!resizing) return;
        
        const handleMouseMove = (e) => {
            if (resizing.type === 'column') {
                const diff = e.clientX - resizing.startPos;
                const currentWidth = getColumnWidth(resizing.id);
                const newWidth = Math.max(50, currentWidth + diff);
                setColumnWidths(prev => ({ ...prev, [resizing.id]: newWidth }));
                setResizing(prev => ({ ...prev, startPos: e.clientX }));
            } else {
                const diff = e.clientY - resizing.startPos;
                const currentHeight = getRowHeight(resizing.id);
                const newHeight = Math.max(20, currentHeight + diff);
                setRowHeights(prev => ({ ...prev, [resizing.id]: newHeight }));
                setResizing(prev => ({ ...prev, startPos: e.clientY }));
            }
        };
        
        const handleMouseUp = () => {
            setResizing(null);
        };
        
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
        
        return () => {
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }, [resizing, getColumnWidth, getRowHeight]);
    
    // Renderizar celda
    const renderCell = useCallback((row, col) => {
        const cellId = `${col}${row}`;
        const cell = cells[cellId];
        const isSelected = cellId === selectedCell;
        const isInRange = rangeCells.includes(cellId);
        const rangeBorders = getRangeBorders(cellId);
        
        return (
            <ExcelCell
                key={cellId}
                cellId={cellId}
                value={cell?.value || ''}
                formula={cell?.formula || null}
                format={cell?.format || {}}
                isSelected={isSelected}
                isInRange={isInRange}
                rangeBorders={rangeBorders}
                width={getColumnWidth(col)}
                height={getRowHeight(row)}
                onUpdate={onUpdateCell}
                onSelect={(id, shift) => {
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
    }, [cells, selectedCell, rangeCells, onUpdateCell, onSelectCell, onSelectRange, handleNavigate, handleStartSelection, handleCellDragOver, getColumnWidth, getRowHeight, getRangeBorders]);
    
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
                                <ExcelColumnHeader
                                    key={col}
                                    column={col}
                                    isSelected={false}
                                    sortConfig={sortConfig}
                                    width={getColumnWidth(col)}
                                    onSort={onSortColumn}
                                    onResize={(e) => handleResizeStart('column', col, e)}
                                    onInsertBefore={(col) => {
                                        const colIndex = columns.indexOf(col);
                                        if (onAddColumn) {
                                            onAddColumn(colIndex);
                                        }
                                    }}
                                    onInsertAfter={(col) => {
                                        const colIndex = columns.indexOf(col);
                                        if (onAddColumn && colIndex < columns.length - 1) {
                                            onAddColumn(colIndex + 1);
                                        } else if (onAddColumn) {
                                            onAddColumn(columns.length);
                                        }
                                    }}
                                    onDelete={onDeleteColumn}
                                />
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={row}>
                                {/* Header de fila - sticky */}
                                <ExcelRowHeader
                                    row={row}
                                    isSelected={false}
                                    height={getRowHeight(row)}
                                    onResize={(e) => handleResizeStart('row', row, e)}
                                    onInsertBefore={() => {
                                        const rowIndex = rows.indexOf(row);
                                        if (onAddRow) {
                                            onAddRow(rowIndex);
                                        }
                                    }}
                                    onInsertAfter={() => {
                                        const rowIndex = rows.indexOf(row);
                                        if (onAddRow && rowIndex < rows.length - 1) {
                                            onAddRow(rowIndex + 1);
                                        } else if (onAddRow) {
                                            onAddRow(rows.length);
                                        }
                                    }}
                                    onDelete={onDeleteRow}
                                />
                                {/* Celdas */}
                                {columns.map(col => renderCell(row, col))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

