import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from "@/components/ui/context-menu";
import { cn } from "@/lib/utils";
import {
    ArrowDown,
    ArrowUp,
    ChevronLeft,
    ChevronRight,
    Clipboard,
    Copy,
    Scissors,
    Trash2,
} from "lucide-react";
import { memo, useState } from "react";

const CalculatorColumnHeader = memo(
    ({
        column,
        isSelected = false,
        sortConfig = null,
        width = 100,
        isLastColumn = false,
        onSelectColumn,
        onDragStartColumn,
        onDragOverColumn,
        onSort,
        onResize,
        onInsertLeft,
        onInsertRight,
        onDelete,
        onHide,
        onCut,
        onCopy,
        onPaste,
    }) => {
        const [isHoveringResize, setIsHoveringResize] = useState(false);
        const isSorted = sortConfig?.column === column;
        const sortDirection = isSorted ? sortConfig.direction : null;

        const handleMouseDown = (e) => {
            e.stopPropagation();
            if (onSelectColumn) {
                // Seleccionar toda la columna
                onSelectColumn(column);
            }
            if (onDragStartColumn) {
                onDragStartColumn(column);
            }
        };

        const handleMouseEnter = (e) => {
            // Solo si se está arrastrando (botón presionado)
            if (e.buttons === 1 && onDragOverColumn) {
                onDragOverColumn(column);
            }
        };

        return (
            <ContextMenu>
                <ContextMenuTrigger asChild>
                    <th
                        className={cn(
                            "border border-gray-400 p-0 text-center font-semibold sticky top-0 z-30 select-none cursor-pointer",
                            isSelected
                                ? "bg-blue-500 text-white"
                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                        )}
                        style={{
                            width: `${width}px`,
                            height: "25px",
                            minWidth: `${width}px`,
                            maxWidth: `${width}px`,
                        }}
                        onMouseDown={handleMouseDown}
                        onMouseEnter={handleMouseEnter}
                    >
                        <div className="flex items-center justify-center h-full relative">
                            {/* Handle de redimensionado mejorado con área ampliada */}
                            {!isLastColumn && (
                                <div
                                    className="absolute top-0 h-full cursor-col-resize z-10 group"
                                    style={{
                                        right: "-3px", // Se extiende 3px fuera del borde
                                        width: "6px", // Área total de hover: 6px
                                    }}
                                    onMouseDown={onResize}
                                    onMouseEnter={() =>
                                        setIsHoveringResize(true)
                                    }
                                    onMouseLeave={() =>
                                        setIsHoveringResize(false)
                                    }
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {/* Línea indicadora visible al hover */}
                                    <div
                                        className={cn(
                                            "absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-[2px] transition-colors",
                                            isHoveringResize
                                                ? "bg-gray-400"
                                                : "bg-transparent"
                                        )}
                                    />
                                </div>
                            )}
                            <span className="text-xs">{column}</span>
                            {isSorted && (
                                <span className="ml-1">
                                    {sortDirection === "asc" ? (
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
                    <ContextMenuItem onClick={() => onCut?.(column)}>
                        <Scissors className="mr-2 h-4 w-4" />
                        Cortar
                    </ContextMenuItem>
                    <ContextMenuItem onClick={() => onCopy?.(column)}>
                        <Copy className="mr-2 h-4 w-4" />
                        Copiar
                    </ContextMenuItem>
                    <ContextMenuItem onClick={() => onPaste?.(column)}>
                        <Clipboard className="mr-2 h-4 w-4" />
                        Pegar
                    </ContextMenuItem>
                    <ContextMenuSeparator />
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
                    <ContextMenuItem onClick={() => onSort?.(column, "asc")}>
                        <ArrowUp className="mr-2 h-4 w-4" />
                        Ordenar hoja A a la Z
                    </ContextMenuItem>
                    <ContextMenuItem onClick={() => onSort?.(column, "desc")}>
                        <ArrowDown className="mr-2 h-4 w-4" />
                        Ordenar hoja Z a la A
                    </ContextMenuItem>
                </ContextMenuContent>
            </ContextMenu>
        );
    }
);

CalculatorColumnHeader.displayName = "CalculatorColumnHeader";

export default CalculatorColumnHeader;
