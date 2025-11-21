import { useCalculatorCell } from "@/hooks/calculator/useCalculatorCell";
import { formatCellValue } from "@/lib/calculator/formatters";
import { cn } from "@/lib/utils";
import { memo, useEffect, useRef } from "react";

const CalculatorCell = memo(
    ({
        cellId,
        rawValue = "",
        displayValue = "",
        format = {},
        isSelected = false,
        isInRange = false,
        rangeBorders = {},
        width = 100,
        height = 25,
        isEditingFormula = false,
        onUpdate,
        onSelect,
        onNavigate,
        onStartDrag,
        onDragOver,
    }) => {
        const cellRef = useRef(null);
        const {
            isEditing,
            editValue,
            setEditValue,
            inputRef,
            startEditing,
            confirmEdit,
            cancelEdit,
            handleKeyDown,
        } = useCalculatorCell(cellId, rawValue, displayValue, onUpdate, onNavigate);

        // Enfocar celda cuando es seleccionada
        useEffect(() => {
            if (isSelected && !isEditing && cellRef.current) {
                cellRef.current.focus();
            }
        }, [isSelected, isEditing]);

        const cellStyles = {
            // Solo aplicar backgroundColor si no está en un rango seleccionado
            ...((!isInRange || isSelected) && {
                backgroundColor: format.backgroundColor || "#ffffff",
            }),
            color: format.textColor || "#000000",
            fontSize: `${format.fontSize || 12}px`,
            fontFamily: format.fontFamily || "Arial",
            fontWeight: format.bold ? "bold" : "normal",
            fontStyle: format.italic ? "italic" : "normal",
            textDecoration: format.underline ? "underline" : "none",
            textAlign: format.align || "left",
        };

        const cellDisplayValue = isEditing
            ? editValue
            : formatCellValue(displayValue, format.format || "text");

        const handleClick = (e) => {
            e.stopPropagation();
            if (onSelect) {
                onSelect(cellId, e.shiftKey);
            }
        };

        const handleDoubleClick = (e) => {
            e.stopPropagation();
            if (!isEditing) {
                startEditing();
            }
        };

        const handleMouseDown = (e) => {
            if (e.button === 0 && onStartDrag && !isEditing) {
                onStartDrag(cellId, false); // false indica selección normal (no fill handle)
            }
        };

        const handleMouseEnter = (e) => {
            if (e.buttons & 1 && onDragOver && !isEditing) {
                e.preventDefault();
                onDragOver(cellId);
            }
        };

        // Construir estilos de borde para el rango
        const getRangeBorderStyles = () => {
            if (!isInRange || Object.keys(rangeBorders).length === 0) return {};

            const borderStyle = "2px solid #2563eb"; // blue-600
            const styles = {};

            if (rangeBorders.top) styles.borderTop = borderStyle;
            if (rangeBorders.bottom) styles.borderBottom = borderStyle;
            if (rangeBorders.left) styles.borderLeft = borderStyle;
            if (rangeBorders.right) styles.borderRight = borderStyle;

            return styles;
        };

        return (
            <td
                ref={cellRef}
                data-cell-id={cellId}
                className={cn(
                    "border border-gray-300 relative select-none p-0",
                    "focus:outline-none",
                    isEditingFormula ? "cursor-crosshair hover:ring-2 hover:ring-green-500 hover:z-[2]" : "cursor-cell",
                    isSelected && "border-2 border-blue-600 z-10 bg-white",
                    isInRange && !isSelected && "bg-blue-100 relative z-[1]"
                )}
                style={{
                    ...cellStyles,
                    ...getRangeBorderStyles(),
                    width: `${width}px`,
                    height: `${height}px`,
                    minWidth: `${width}px`,
                    maxWidth: `${width}px`,
                    minHeight: `${height}px`,
                    maxHeight: `${height}px`,
                }}
                onClick={handleClick}
                onDoubleClick={handleDoubleClick}
                onMouseDown={handleMouseDown}
                onMouseEnter={handleMouseEnter}
                onKeyDown={handleKeyDown}
                tabIndex={isSelected ? 0 : -1}
                title={isEditingFormula ? `Click para agregar ${cellId} a la fórmula` : undefined}
            >
                {isEditing ? (
                    <input
                        ref={inputRef}
                        type="text"
                        value={editValue}
                        onChange={(e) => setEditValue(e.target.value)}
                        onBlur={confirmEdit}
                        className="w-full h-full px-1 border-0 outline-none bg-transparent"
                        style={cellStyles}
                        autoFocus
                    />
                ) : (
                    <div className="px-1 py-0.5 h-full overflow-hidden text-ellipsis whitespace-nowrap">
                        {cellDisplayValue}
                    </div>
                )}
                {/* Handle de arrastre en la esquina inferior derecha */}
                {isSelected && !isEditing && (
                    <div
                        className="absolute bottom-0 right-0 w-[6px] h-[6px] cursor-crosshair bg-blue-600 z-20"
                        style={{
                            transform: "translate(50%, 50%)",
                            borderRadius: "1px",
                        }}
                        onMouseDown={(e) => {
                            e.stopPropagation();
                            e.preventDefault();
                            if (onStartDrag) {
                                onStartDrag(cellId, true); // true indica que es el fill handle
                            }
                        }}
                        title="Arrastrar para copiar"
                    />
                )}
            </td>
        );
    }
);

CalculatorCell.displayName = "CalculatorCell";

export default CalculatorCell;
