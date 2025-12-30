import { Button } from "@/Components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { useCSVValidation } from "@/hooks/csv/useCSVValidation";
import { AlertCircle, CheckCircle, Loader2 } from "lucide-react";
import { useMemo, useState } from "react";

export default function CSVPreviewStep({
    rows,
    mapping,
    fields,
    onImport,
    onBack,
    isImporting,
}) {
    const { validateAll } = useCSVValidation(fields);
    const [showErrorsOnly, setShowErrorsOnly] = useState(false);

    // Validate data immediately
    const { results, validRows, invalidRows } = useMemo(() => {
        return validateAll(rows, mapping);
    }, [rows, mapping, validateAll]);

    // Derived stats
    const totalRows = results.length;
    const errorCount = invalidRows.length;
    const successCount = validRows.length;
    const hasErrors = errorCount > 0;

    // Filter displayed rows
    const displayRows = useMemo(() => {
        return showErrorsOnly ? invalidRows : results.slice(0, 50); // Limit to 50 for performance
    }, [showErrorsOnly, invalidRows, results]);

    const handleImport = () => {
        // Enviar solo filas válidas o todas según lógica de negocio.
        // Aquí enviamos solo las visualmente válidas + mapeo por si el backend necesita procesar
        if (successCount > 0) {
            onImport(
                validRows.map((r) => r.data),
                mapping
            );
        }
    };

    return (
        <div className="space-y-6">
            {/* Stats Summary */}
            <div className="grid grid-cols-2 gap-4">
                <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                    <div className="p-2 bg-green-100 rounded-full">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-green-700">
                            {successCount}
                        </p>
                        <p className="text-xs text-green-800">
                            Filas listas para importar
                        </p>
                    </div>
                </div>

                <div
                    className={`${
                        hasErrors
                            ? "bg-amber-50 border-amber-200"
                            : "bg-gray-50 border-gray-200"
                    } border rounded-lg p-4 flex items-center gap-3`}
                >
                    <div
                        className={`p-2 ${
                            hasErrors ? "bg-amber-100" : "bg-gray-100"
                        } rounded-full`}
                    >
                        <AlertCircle
                            className={`h-5 w-5 ${
                                hasErrors ? "text-amber-600" : "text-gray-400"
                            }`}
                        />
                    </div>
                    <div>
                        <p
                            className={`text-2xl font-bold ${
                                hasErrors ? "text-amber-700" : "text-gray-700"
                            }`}
                        >
                            {errorCount}
                        </p>
                        <p
                            className={`text-xs ${
                                hasErrors ? "text-amber-800" : "text-gray-500"
                            }`}
                        >
                            Filas con errores
                        </p>
                    </div>
                </div>
            </div>

            {hasErrors && (
                <div className="flex items-center gap-2">
                    <label className="text-sm text-gray-700 flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            checked={showErrorsOnly}
                            onChange={(e) =>
                                setShowErrorsOnly(e.target.checked)
                            }
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        Mostrar solo filas con errores
                    </label>
                </div>
            )}

            {/* Preview Table */}
            <div className="border rounded-lg overflow-hidden max-h-[400px]">
                <div className="overflow-auto">
                    <Table>
                        <TableHeader className="sticky top-0 bg-gray-50">
                            <TableRow>
                                <TableHead className="w-[50px]">#</TableHead>
                                {fields.map((field) => (
                                    <TableHead
                                        key={field.key}
                                        className="whitespace-nowrap"
                                    >
                                        {field.label}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {displayRows.map((row, idx) => (
                                <TableRow
                                    key={idx}
                                    className={
                                        !row.isValid
                                            ? "bg-red-50 hover:bg-red-100/50"
                                            : ""
                                    }
                                >
                                    <TableCell className="text-xs text-gray-500">
                                        {row.originalRow._lineIndex || idx + 1}
                                    </TableCell>
                                    {fields.map((field) => {
                                        const error = row.errors.find(
                                            (e) => e.field === field.key
                                        );
                                        const value = row.data[field.key];

                                        return (
                                            <TableCell
                                                key={field.key}
                                                className="relative"
                                            >
                                                <span
                                                    className={
                                                        !value
                                                            ? "text-gray-400 italic"
                                                            : ""
                                                    }
                                                >
                                                    {value || "-"}
                                                </span>
                                                {error && (
                                                    <div className="absolute top-1 right-1">
                                                        <div
                                                            className="h-2 w-2 rounded-full bg-red-500"
                                                            title={
                                                                error.message
                                                            }
                                                        />
                                                    </div>
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {!showErrorsOnly && totalRows > 50 && (
                <p className="text-xs text-center text-gray-500">
                    Mostrando 50 de {totalRows} filas.
                </p>
            )}

            <div className="flex justify-between pt-4 border-t">
                <Button
                    variant="outline"
                    onClick={onBack}
                    disabled={isImporting}
                >
                    Atrás
                </Button>
                <Button
                    onClick={handleImport}
                    disabled={successCount === 0 || isImporting}
                    className="min-w-[120px]"
                >
                    {isImporting ? (
                        <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Importando...
                        </>
                    ) : (
                        `Importar ${successCount} filas`
                    )}
                </Button>
            </div>
        </div>
    );
}
