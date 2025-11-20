import React, { memo } from 'react';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { ChevronUp, ChevronDown, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';

const ExcelRowHeader = memo(({ 
    row, 
    isSelected = false,
    height = 25,
    onSelectRow,
    onResize,
    onInsertAbove,
    onInsertBelow,
    onDelete
}) => {
    const handleClick = (e) => {
        e.stopPropagation();
        if (onSelectRow) {
            // Seleccionar toda la fila
            onSelectRow(row);
        }
    };
    
    return (
        <ContextMenu>
            <ContextMenuTrigger asChild>
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
                    </div>
                </th>
            </ContextMenuTrigger>
            <ContextMenuContent className="w-52">
                <ContextMenuItem onClick={() => onInsertAbove?.(row)}>
                    <ChevronUp className="mr-2 h-4 w-4" />
                    Insertar 1 fila por arriba
                </ContextMenuItem>
                <ContextMenuItem onClick={() => onInsertBelow?.(row)}>
                    <ChevronDown className="mr-2 h-4 w-4" />
                    Insertar 1 fila por abajo
                </ContextMenuItem>
                <ContextMenuSeparator />
                <ContextMenuItem 
                    onClick={() => onDelete?.(row)}
                    className="text-red-600 focus:text-red-600"
                >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Eliminar fila
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenu>
    );
});

ExcelRowHeader.displayName = 'ExcelRowHeader';

export default ExcelRowHeader;

