import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from "@/Components/ui/context-menu";
import { cn } from "@/lib/utils";
import {
    ChevronDown,
    ChevronUp,
    Clipboard,
    Copy,
    Scissors,
    Trash2,
} from "lucide-react";
import { memo, useState } from "react";

const CalculatorRowHeader = memo(
    ({
        row,
        isSelected = false,
        height = 25,
        isLastRow = false,
        onSelectRow,
        onDragStartRow,
        onDragOverRow,
        onResize,
        onInsertAbove,
        onInsertBelow,
        onDelete,
        onCut,
        onCopy,
        onPaste,
    }) => {
        const [isHoveringResize, setIsHoveringResize] = useState(false);

        const handleMouseDown = (e) => {
            e.stopPropagation();
            if (onSelectRow) {
                // Seleccionar toda la fila
                onSelectRow(row);
            }
            if (onDragStartRow) {
                onDragStartRow(row);
            }
        };

        const handleMouseEnter = (e) => {
            // Solo si se está arrastrando (botón presionado)
            if (e.buttons === 1 && onDragOverRow) {
                onDragOverRow(row);
            }
        };

        return (
            <ContextMenu>
                <ContextMenuTrigger asChild>
                    <th
                        className={cn(
                            "border border-gray-400 p-0 text-center font-semibold sticky left-0 z-20 select-none cursor-pointer",
                            isSelected
                                ? "bg-blue-500 text-white"
                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                        )}
                        style={{
                            width: "50px",
                            height: `${height}px`,
                            minWidth: "50px",
                            minHeight: `${height}px`,
                            maxHeight: `${height}px`,
                        }}
                        onMouseDown={handleMouseDown}
                        onMouseEnter={handleMouseEnter}
                    >
                        <div className="flex items-center justify-center h-full relative">
                            {/* Handle de redimensionado mejorado con área ampliada */}
                            {!isLastRow && (
                                <div
                                    className="absolute left-0 w-full cursor-row-resize z-10 group"
                                    style={{
                                        bottom: "-3px", // Se extiende 3px fuera del borde
                                        height: "6px", // Área total de hover: 6px
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
                                            "absolute left-0 right-0 top-1/2 -translate-y-1/2 h-[2px] transition-colors",
                                            isHoveringResize
                                                ? "bg-gray-400"
                                                : "bg-transparent"
                                        )}
                                    />
                                </div>
                            )}
                            <span className="text-xs">{row}</span>
                        </div>
                    </th>
                </ContextMenuTrigger>
                <ContextMenuContent className="w-52">
                    <ContextMenuItem onClick={() => onCut?.(row)}>
                        <Scissors className="mr-2 h-4 w-4" />
                        Cortar
                    </ContextMenuItem>
                    <ContextMenuItem onClick={() => onCopy?.(row)}>
                        <Copy className="mr-2 h-4 w-4" />
                        Copiar
                    </ContextMenuItem>
                    <ContextMenuItem onClick={() => onPaste?.(row)}>
                        <Clipboard className="mr-2 h-4 w-4" />
                        Pegar
                    </ContextMenuItem>
                    <ContextMenuSeparator />
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
    }
);

CalculatorRowHeader.displayName = "CalculatorRowHeader";

export default CalculatorRowHeader;
