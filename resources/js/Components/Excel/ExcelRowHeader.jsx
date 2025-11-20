import React, { memo } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Plus, Trash2, MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';

const ExcelRowHeader = memo(({ 
    row, 
    isSelected = false,
    height = 25,
    onSelect,
    onResize,
    onInsertBefore,
    onInsertAfter,
    onDelete
}) => {
    const handleClick = (e) => {
        e.stopPropagation();
        if (onSelect) {
            onSelect(row);
        }
    };
    
    return (
        <th
            className={cn(
                "border border-gray-400 bg-gray-100 text-gray-700",
                "p-0 text-center font-semibold sticky left-0 z-20",
                "select-none cursor-pointer hover:bg-gray-200",
                isSelected && "bg-blue-200 ring-2 ring-blue-500"
            )}
            style={{ 
                width: '50px',
                height: `${height}px`,
                minWidth: '50px',
                minHeight: `${height}px`,
                maxHeight: `${height}px`
            }}
            onClick={handleClick}
        >
            <div className="flex items-center justify-center h-full relative">
                {/* Handle de redimensionado */}
                <div
                    className="absolute bottom-0 left-0 w-full h-1 cursor-row-resize hover:bg-blue-500 z-10"
                    onMouseDown={onResize}
                    onClick={(e) => e.stopPropagation()}
                />
                <span className="text-xs">{row}</span>
                
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            className="absolute right-0 top-0 h-full w-full opacity-0 hover:opacity-100 flex items-center justify-center"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <MoreHorizontal className="h-3 w-3" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => onInsertBefore?.(row)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Insertar Fila Antes
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onInsertAfter?.(row)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Insertar Fila Después
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem 
                            onClick={() => onDelete?.(row)}
                            className="text-red-600"
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Eliminar Fila
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </th>
    );
});

ExcelRowHeader.displayName = 'ExcelRowHeader';

export default ExcelRowHeader;

