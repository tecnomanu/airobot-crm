import { useExcelCell } from "@/hooks/useExcelCell";
import { formatCellValue } from "@/lib/excel/formatters";
import { cn } from "@/lib/utils";
import { memo, useEffect, useRef } from "react";

const ExcelCell = memo(
    ({
        cellId,
        value = "",
        formula = null,
        format = {},
        isSelected = false,
        isInRange = false,
        rangeBorders = {},
        width = 100,
        height = 25,
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
        } = useExcelCell(cellId, value, formula, onUpdate, onNavigate);

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

        const displayValue = isEditing
            ? editValue
            : formatCellValue(value, format.format || "text");

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
                    "border border-gray-300 relative cursor-cell select-none p-0",
                    "focus:outline-none",
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
                        {displayValue}
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

ExcelCell.displayName = "ExcelCell";

export default ExcelCell;
