import React, { memo } from 'react';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { ArrowUp, ArrowDown, ChevronLeft, ChevronRight, Trash2, Eye, EyeOff } from 'lucide-react';
import { cn } from '@/lib/utils';

const ExcelColumnHeader = memo(({ 
    column, 
    isSelected = false,
    sortConfig = null,
    width = 100,
    onSelectColumn,
    onSort,
    onResize,
    onInsertLeft,
    onInsertRight,
    onDelete,
    onHide
}) => {
    const isSorted = sortConfig?.column === column;
    const sortDirection = isSorted ? sortConfig.direction : null;
    
    const handleClick = (e) => {
        e.stopPropagation();
        if (onSelectColumn) {
            // Seleccionar toda la columna
            onSelectColumn(column);
        }
    };
    
    return (
        <ContextMenu>
            <ContextMenuTrigger asChild>
                <th
                    className={cn(
                        "border border-gray-400 bg-gray-100 text-gray-700",
                        "p-0 text-center font-semibold sticky top-0 z-30",
                        "select-none cursor-pointer hover:bg-gray-200",
                        isSelected && "bg-blue-200 ring-2 ring-blue-500"
                    )}
                    style={{ 
                        width: `${width}px`,
                        height: '25px',
                        minWidth: `${width}px`,
                        maxWidth: `${width}px`
                    }}
                    onClick={handleClick}
                >
                    <div className="flex items-center justify-center h-full relative">
                        {/* Handle de redimensionado */}
                        <div
                            className="absolute right-0 top-0 w-1 h-full cursor-col-resize hover:bg-blue-500 z-10"
                            onMouseDown={onResize}
                            onClick={(e) => e.stopPropagation()}
                        />
                        <span className="text-xs">{column}</span>
                        {isSorted && (
                            <span className="ml-1">
                                {sortDirection === 'asc' ? (
                                    <ArrowUp className="h-3 w-3" />
                                ) : (
                                    <ArrowDown className="h-3 w-3" />
                                )}
                            </span>
                        )}
                    </div>
                </th>
            </ContextMenuTrigger>
            <ContextMenuContent className="w-56">
                <ContextMenuItem onClick={() => onInsertLeft?.(column)}>
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    Insertar 1 columna a la izquierda
                </ContextMenuItem>
                <ContextMenuItem onClick={() => onInsertRight?.(column)}>
                    <ChevronRight className="mr-2 h-4 w-4" />
                    Insertar 1 columna a la derecha
                </ContextMenuItem>
                <ContextMenuSeparator />
                <ContextMenuItem 
                    onClick={() => onDelete?.(column)}
                    className="text-red-600 focus:text-red-600"
                >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Eliminar columna
                </ContextMenuItem>
                <ContextMenuSeparator />
                <ContextMenuItem onClick={() => onSort?.(column, 'asc')}>
                    <ArrowUp className="mr-2 h-4 w-4" />
                    Ordenar hoja A a la Z
                </ContextMenuItem>
                <ContextMenuItem onClick={() => onSort?.(column, 'desc')}>
                    <ArrowDown className="mr-2 h-4 w-4" />
                    Ordenar hoja Z a la A
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenu>
    );
});

ExcelColumnHeader.displayName = 'ExcelColumnHeader';

export default ExcelColumnHeader;

