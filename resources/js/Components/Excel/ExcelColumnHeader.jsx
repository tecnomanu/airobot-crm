import React, { memo } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ArrowUpDown, ArrowUp, ArrowDown, Plus, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';

const ExcelColumnHeader = memo(({ 
    column, 
    isSelected = false,
    sortConfig = null,
    width = 100,
    onSelect,
    onSort,
    onResize,
    onInsertBefore,
    onInsertAfter,
    onDelete
}) => {
    const isSorted = sortConfig?.column === column;
    const sortDirection = isSorted ? sortConfig.direction : null;
    
    const handleClick = (e) => {
        e.stopPropagation();
        if (onSelect) {
            onSelect(column);
        }
    };
    
    const handleDoubleClick = (e) => {
        e.stopPropagation();
        if (onSort) {
            const newDirection = isSorted && sortDirection === 'asc' ? 'desc' : 'asc';
            onSort(column, newDirection);
        }
    };
    
    return (
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
            onDoubleClick={handleDoubleClick}
        >
            <div className="flex items-center justify-center h-full relative">
                {/* Handle de redimensionado */}
                <div
                    className="absolute right-0 top-0 w-1 h-full cursor-col-resize hover:bg-blue-500 z-10"
                    onMouseDown={onResize}
                    onClick={(e) => e.stopPropagation()}
                    onDoubleClick={(e) => e.stopPropagation()}
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
                
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            className="absolute right-0 top-0 h-full w-4 opacity-0 hover:opacity-100 flex items-center justify-center"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <ArrowUpDown className="h-3 w-3" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => onSort?.(column, 'asc')}>
                            <ArrowUp className="mr-2 h-4 w-4" />
                            Ordenar Ascendente
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onSort?.(column, 'desc')}>
                            <ArrowDown className="mr-2 h-4 w-4" />
                            Ordenar Descendente
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => onInsertBefore?.(column)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Insertar Columna Antes
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onInsertAfter?.(column)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Insertar Columna Después
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem 
                            onClick={() => onDelete?.(column)}
                            className="text-red-600"
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Eliminar Columna
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </th>
    );
});

ExcelColumnHeader.displayName = 'ExcelColumnHeader';

export default ExcelColumnHeader;

