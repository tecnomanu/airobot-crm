import ExcelFormulaBar from "@/Components/Excel/ExcelFormulaBar";
import ExcelGrid from "@/Components/Excel/ExcelGrid";
import ExcelToolbar from "@/Components/Excel/ExcelToolbar";
import { useExcelFormat } from "@/hooks/useExcelFormat";
import { useExcelGrid } from "@/hooks/useExcelGrid";
import { useExcelKeyboard } from "@/hooks/useExcelKeyboard";
import ExcelLayout from "@/Layouts/ExcelLayout";
import { exportToCSV, getCellRange, parseCSV } from "@/lib/excel/utils";
import { Head, router, usePage } from "@inertiajs/react";
import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";

export default function ExcelIndex() {
    const { auth } = usePage().props;
    const user = auth?.user;
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
        addRow,
        deleteRow,
        addColumn,
        deleteColumn,
        sortByCol,
        copyCells,
        pasteCells,
        clearCells,
        undo,
        redo,
        getCellValue,
        getCellFormat,
        canUndo,
        canRedo,
        getCellFormula,
    } = useExcelGrid();

    const { getCellStyles } = useExcelFormat();

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

    // Actualizar formato seleccionado cuando cambia la celda
    useEffect(() => {
        if (selectedCell) {
            const format = getCellFormat(selectedCell);
            setSelectedFormat(format);
        }
    }, [selectedCell, getCellFormat]);

    // Manejar nuevo documento
    const handleNew = useCallback(() => {
        if (
            confirm(
                "¿Está seguro de crear un nuevo documento? Se perderán los cambios no guardados."
            )
        ) {
            window.location.reload();
        }
    }, []);

    // Manejar abrir (por ahora dummy)
    const handleOpen = useCallback(() => {
        toast.info("Función de abrir aún no implementada");
    }, []);

    // Manejar guardar (por ahora dummy)
    const handleSave = useCallback(() => {
        toast.info("Función de guardar aún no implementada");
    }, []);

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
    const handlePaste = useCallback(() => {
        pasteCells(selectedCell);
        toast.success("Celdas pegadas");
    }, [selectedCell, pasteCells]);

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
        router.visit(route("dashboard"));
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
    useExcelKeyboard({
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

    // Obtener valor y fórmula de celda seleccionada
    const cellValue = getCellValue(selectedCell);
    const cellFormula = getCellFormula(selectedCell);

    return (
        <ExcelLayout
            toolbar={
                <ExcelToolbar
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
                />
            }
        >
            <Head title="Excel - AIRobot" />

            <div className="flex flex-col h-full">
                {/* Barra de fórmulas */}
                <ExcelFormulaBar
                    selectedCell={selectedCell}
                    cellValue={cellValue}
                    cellFormula={cellFormula}
                    onConfirm={(value) => {
                        updateCell(selectedCell, value);
                    }}
                />

                {/* Grid principal */}
                <div className="flex-1 overflow-hidden">
                    <ExcelGrid
                        cells={cells}
                        columns={columns}
                        rows={rows}
                        selectedCell={selectedCell}
                        selectedRange={selectedRange}
                        sortConfig={sortConfig}
                        onUpdateCell={updateCell}
                        onSelectCell={selectCell}
                        onSelectRange={selectRange}
                        onSortColumn={sortByCol}
                        onAddRow={addRow}
                        onDeleteRow={deleteRow}
                        onAddColumn={addColumn}
                        onDeleteColumn={deleteColumn}
                        onCopyCells={copyCells}
                        onPasteCells={pasteCells}
                        onClearCells={clearCells}
                    />
                </div>
            </div>
        </ExcelLayout>
    );
}
