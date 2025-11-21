import CalculatorFormulaBar from "@/Components/Calculator/CalculatorFormulaBar";
import CalculatorGrid from "@/Components/Calculator/CalculatorGrid";
import CalculatorToolbar from "@/Components/Calculator/CalculatorToolbar";
import { useCalculatorAutoSave } from "@/hooks/calculator/useCalculatorAutoSave";
import { useCalculatorFormat } from "@/hooks/calculator/useCalculatorFormat";
import { useCalculatorGrid } from "@/hooks/calculator/useCalculatorGrid";
import { useCalculatorKeyboard } from "@/hooks/calculator/useCalculatorKeyboard";
import CalculatorLayout from "@/Layouts/CalculatorLayout";
import {
    cellToCoords,
    coordsToCell,
    exportToCSV,
    getCellRange,
    parseCSV,
} from "@/lib/calculator/utils";
import { Head, router, usePage } from "@inertiajs/react";
import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";

export default function CalculatorIndex() {
    const { auth, calculator } = usePage().props;
    const user = auth?.user;
    const [calculatorTitle, setCalculatorTitle] = useState(
        calculator?.name || "Hoja sin título"
    );
    const [calculatorId, setCalculatorId] = useState(calculator?.id || null);
    const {
        cells,
        columns,
        rows,
        selectedCell,
        selectedRange,
        sortConfig,
        updateCell,
        updateCellFormat,
        selectCell,
        selectRange,
        selectColumn,
        selectRow,
        addRow,
        deleteRow,
        addColumn,
        deleteColumn,
        insertColumnLeft,
        insertColumnRight,
        insertRowAbove,
        insertRowBelow,
        sortByCol,
        copyCells,
        pasteCells,
        clearCells,
        undo,
        redo,
        getCellRawValue,
        getCellDisplayValue,
        getCellFormat,
        canUndo,
        canRedo,
        getState,
        columnWidths,
        rowHeights,
    } = useCalculatorGrid(calculator || {});

    const { getCellStyles } = useCalculatorFormat();

    // Auto-save hook
    const { isSaving } = useCalculatorAutoSave(
        calculatorId,
        getState(),
        !!calculatorId
    );

    const [selectedFormat, setSelectedFormat] = useState({
        backgroundColor: "#ffffff",
        textColor: "#000000",
        fontSize: 12,
        fontFamily: "Arial",
        bold: false,
        italic: false,
        underline: false,
        align: "left",
        format: "text",
    });

    const [isEditingFormula, setIsEditingFormula] = useState(false);

    // Actualizar formato seleccionado cuando cambia la celda
    useEffect(() => {
        if (selectedCell) {
            const format = getCellFormat(selectedCell);
            setSelectedFormat(format);
        }
    }, [selectedCell, getCellFormat]);

    // Manejar nuevo documento
    const handleNew = useCallback(async () => {
        if (!confirm("¿Está seguro de crear un nuevo documento?")) {
            return;
        }

        try {
            const response = await fetch(route("api.admin.calculator.store"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    name: "Hoja sin título",
                }),
            });

            const data = await response.json();
            if (data.success && data.data?.id) {
                router.visit(route("calculator.show", data.data.id));
            }
        } catch (error) {
            console.error("Error al crear nuevo documento:", error);
            toast.error("Error al crear nuevo documento");
        }
    }, []);

    // Manejar abrir
    const handleOpen = useCallback(() => {
        router.visit(route("calculator.index"));
    }, []);

    // Manejar guardar manualmente
    const handleSave = useCallback(async () => {
        if (!calculatorId) {
            toast.error("No hay documento para guardar");
            return;
        }

        try {
            const state = getState();
            await fetch(
                route("api.admin.calculator.save-state", calculatorId),
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                    body: JSON.stringify(state),
                }
            );

            toast.success("Documento guardado correctamente");
        } catch (error) {
            console.error("Error al guardar documento:", error);
            toast.error("Error al guardar documento");
        }
    }, [calculatorId, getState]);

    // Manejar exportar CSV
    const handleExportCSV = useCallback(() => {
        try {
            const maxRow = Math.max(...rows);
            const maxCol = columns.length;
            const csvContent = exportToCSV(cells, maxRow, maxCol);

            const blob = new Blob([csvContent], {
                type: "text/csv;charset=utf-8;",
            });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);

            link.setAttribute("href", url);
            link.setAttribute(
                "download",
                `spreadsheet_${new Date().getTime()}.csv`
            );
            link.style.visibility = "hidden";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            toast.success("Archivo CSV exportado correctamente");
        } catch (error) {
            toast.error("Error al exportar CSV");
            console.error(error);
        }
    }, [cells, columns, rows]);

    // Manejar importar CSV
    const handleImportCSV = useCallback(
        async (file) => {
            try {
                const text = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = () =>
                        reject(new Error("Error al leer el archivo"));
                    reader.readAsText(file, "UTF-8");
                });

                const { cells: importedCells } = parseCSV(text);

                // Limpiar celdas existentes primero (opcional, puedes comentar esto si quieres agregar)
                // Actualizar celdas con datos importados
                Object.keys(importedCells).forEach((cellId) => {
                    const cell = importedCells[cellId];
                    updateCell(cellId, cell.value, cell.format);
                });

                toast.success("CSV importado correctamente");
            } catch (error) {
                toast.error(
                    "Error al importar CSV: " +
                        (error.message || "Error desconocido")
                );
                console.error(error);
            }
        },
        [updateCell]
    );

    // Manejar copiar
    const handleCopy = useCallback(() => {
        const cellIds = selectedRange
            ? getCellRange(selectedRange.start, selectedRange.end)
            : [selectedCell];
        copyCells(cellIds);
        toast.success("Celdas copiadas");
    }, [selectedCell, selectedRange, copyCells]);

    // Manejar pegar
    const handlePaste = useCallback(async () => {
        try {
            // Intentar leer del clipboard del navegador primero (para pegar desde fuentes externas)
            const clipboardText = await navigator.clipboard.readText();

            if (clipboardText && clipboardText.trim()) {
                // Parsear contenido del clipboard (formato TSV usado por Google Sheets, Excel, etc.)
                const lines = clipboardText.trim().split("\n");
                const targetCoords = selectedCell
                    ? cellToCoords(selectedCell)
                    : { row: 0, col: 0 };

                lines.forEach((line, rowOffset) => {
                    const cells = line.split("\t"); // TSV: Tab Separated Values
                    cells.forEach((value, colOffset) => {
                        const newCoords = {
                            row: targetCoords.row + rowOffset,
                            col: targetCoords.col + colOffset,
                        };
                        const newCellId = coordsToCell(newCoords);

                        // Actualizar la celda con el valor pegado
                        updateCell(newCellId, value.trim());
                    });
                });

                toast.success(`${lines.length} fila(s) pegada(s)`);
            } else {
                // Fallback: usar clipboard interno si no hay contenido del navegador
                pasteCells(selectedCell);
                toast.success("Celdas pegadas");
            }
        } catch (error) {
            // Si falla la lectura del clipboard del navegador, usar clipboard interno
            console.log("Usando clipboard interno:", error.message);
            pasteCells(selectedCell);
            toast.success("Celdas pegadas");
        }
    }, [selectedCell, pasteCells, updateCell]);

    // Manejar eliminar
    const handleDelete = useCallback(() => {
        const cellIds = selectedRange
            ? getCellRange(selectedRange.start, selectedRange.end)
            : [selectedCell];
        clearCells(cellIds);
        toast.success("Celdas eliminadas");
    }, [selectedCell, selectedRange, clearCells]);

    // Manejar cambio de formato
    const handleFormatChange = useCallback(
        (newFormat) => {
            setSelectedFormat(newFormat);
            const cellIds = selectedRange
                ? getCellRange(selectedRange.start, selectedRange.end)
                : [selectedCell];
            updateCellFormat(cellIds, newFormat);
        },
        [selectedCell, selectedRange, updateCellFormat]
    );

    // Manejar ordenar
    const handleSort = useCallback(() => {
        if (selectedCell) {
            const col = selectedCell.match(/^([A-Z]+)/)?.[1];
            if (col) {
                sortByCol(col, "asc");
            }
        }
    }, [selectedCell, sortByCol]);

    // Manejar filtrar (dummy por ahora)
    const handleFilter = useCallback(() => {
        toast.info("Función de filtrar aún no implementada");
    }, []);

    // Manejar volver al menú principal
    const handleBackToMenu = useCallback(() => {
        router.visit(route("calculator.index"));
    }, []);

    // Manejar seleccionar todas las celdas
    const handleSelectAll = useCallback(() => {
        if (columns.length > 0 && rows.length > 0) {
            const firstCell = `${columns[0]}${rows[0]}`;
            const lastCell = `${columns[columns.length - 1]}${
                rows[rows.length - 1]
            }`;
            selectRange(firstCell, lastCell);
            toast.success("Todas las celdas seleccionadas");
        }
    }, [columns, rows, selectRange]);

    // Manejar cortar (copiar + eliminar)
    const handleCut = useCallback(() => {
        const cellIds = selectedRange
            ? getCellRange(selectedRange.start, selectedRange.end)
            : [selectedCell];
        copyCells(cellIds);
        clearCells(cellIds);
        toast.success("Celdas cortadas");
    }, [selectedCell, selectedRange, copyCells, clearCells]);

    // Hook centralizado para atajos de teclado
    useCalculatorKeyboard({
        selectedCell,
        selectedRange,
        selectedFormat,
        isEditingCell: false, // TODO: Conectar con estado de edición si es necesario
        onFormatChange: handleFormatChange,
        onUndo: undo,
        onRedo: redo,
        onCopy: handleCopy,
        onPaste: handlePaste,
        onCut: handleCut,
        onDelete: handleDelete,
        onSelectAll: handleSelectAll,
        onSave: handleSave,
        canUndo,
        canRedo,
    });

    // Manejar cambio de título
    const handleTitleChange = useCallback(
        async (newTitle) => {
            if (!calculatorId) {
                setCalculatorTitle(newTitle);
                return;
            }

            try {
                await fetch(
                    route("api.admin.calculator.update-name", calculatorId),
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                )?.content || "",
                        },
                        body: JSON.stringify({ name: newTitle }),
                    }
                );

                setCalculatorTitle(newTitle);
                toast.success("Título actualizado");
            } catch (error) {
                console.error("Error al actualizar título:", error);
                toast.error("Error al actualizar título");
            }
        },
        [calculatorId]
    );

    // Obtener valor raw de celda seleccionada (puede incluir fórmula con =)
    const cellRawValue = getCellRawValue(selectedCell);

    return (
        <CalculatorLayout
            toolbar={
                <CalculatorToolbar
                    onNew={handleNew}
                    onOpen={handleOpen}
                    onSave={handleSave}
                    onExportCSV={handleExportCSV}
                    onImportCSV={handleImportCSV}
                    onUndo={undo}
                    onRedo={redo}
                    canUndo={canUndo}
                    canRedo={canRedo}
                    onCopy={handleCopy}
                    onPaste={handlePaste}
                    onDelete={handleDelete}
                    selectedFormat={selectedFormat}
                    onFormatChange={handleFormatChange}
                    onBackToMenu={handleBackToMenu}
                    user={user}
                    calculatorTitle={calculatorTitle}
                    onTitleChange={handleTitleChange}
                />
            }
        >
            <Head title={`${calculatorTitle} - Calculator - AIRobot`} />

            <div className="flex flex-col h-full">
                {/* Barra de fórmulas */}
                <CalculatorFormulaBar
                    selectedCell={selectedCell}
                    selectedRange={selectedRange}
                    cellValue={cellRawValue}
                    onConfirm={(value) => {
                        updateCell(selectedCell, value);
                        setIsEditingFormula(false);
                    }}
                    onFormulaMode={setIsEditingFormula}
                    isEditingFormula={isEditingFormula}
                />

                {/* Grid principal */}
                <div className="flex-1 overflow-hidden">
                    <CalculatorGrid
                        cells={cells}
                        columns={columns}
                        rows={rows}
                        selectedCell={selectedCell}
                        selectedRange={selectedRange}
                        sortConfig={sortConfig}
                        isEditingFormula={isEditingFormula}
                        onUpdateCell={updateCell}
                        onSelectCell={selectCell}
                        onSelectRange={selectRange}
                        onSelectColumn={selectColumn}
                        onSelectRow={selectRow}
                        onSortColumn={sortByCol}
                        onAddRow={addRow}
                        onDeleteRow={deleteRow}
                        onAddColumn={addColumn}
                        onDeleteColumn={deleteColumn}
                        onInsertColumnLeft={insertColumnLeft}
                        onInsertColumnRight={insertColumnRight}
                        onInsertRowAbove={insertRowAbove}
                        onInsertRowBelow={insertRowBelow}
                        onCopyCells={copyCells}
                        onPasteCells={pasteCells}
                        onClearCells={clearCells}
                    />
                </div>
            </div>
        </CalculatorLayout>
    );
}
