import { Button } from "@/Components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { useColumnMapping } from "@/hooks/csv/useColumnMapping";
import { AlertCircle, ArrowRight } from "lucide-react";
import { useEffect } from "react";

export default function CSVColumnMapper({
    headers,
    fields,
    onNext,
    onBack,
    initialMapping = {},
}) {
    const { mapping, updateMapping, getMappedField, getMissingRequiredFields } =
        useColumnMapping(headers, fields);

    // Initial load if provided
    useEffect(() => {
        if (Object.keys(initialMapping).length > 0) {
            Object.entries(initialMapping).forEach(([header, field]) => {
                updateMapping(header, field);
            });
        }
    }, []);

    const missingRequired = getMissingRequiredFields();
    const isValid = missingRequired.length === 0;

    const handleNext = () => {
        if (isValid) {
            onNext(mapping);
        }
    };

    return (
        <div className="space-y-6">
            <div className="bg-white border rounded-lg overflow-hidden">
                <div className="grid grid-cols-12 gap-4 p-4 bg-gray-50 border-b text-sm font-medium text-gray-500">
                    <div className="col-span-4">Columna en tu Archivo</div>
                    <div className="col-span-1 text-center"></div>
                    <div className="col-span-7">Campo en el Sistema</div>
                </div>

                <div className="max-h-[400px] overflow-y-auto">
                    {headers.map((header) => {
                        const selectedFieldKey = getMappedField(header);
                        const isMapped = !!selectedFieldKey;

                        return (
                            <div
                                key={header}
                                className="grid grid-cols-12 gap-4 p-4 border-b last:border-0 items-center hover:bg-gray-50/50"
                            >
                                <div
                                    className="col-span-4 truncate font-medium text-sm"
                                    title={header}
                                >
                                    {header}
                                </div>
                                <div className="col-span-1 flex justify-center text-gray-400">
                                    <ArrowRight className="h-4 w-4" />
                                </div>
                                <div className="col-span-7">
                                    <Select
                                        value={selectedFieldKey || "ignore"}
                                        onValueChange={(value) =>
                                            updateMapping(
                                                header,
                                                value === "ignore"
                                                    ? null
                                                    : value
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            className={`h-9 ${
                                                isMapped
                                                    ? "border-green-500 bg-green-50/10 text-green-700"
                                                    : ""
                                            }`}
                                        >
                                            <SelectValue placeholder="Ignorar columna" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                value="ignore"
                                                className="text-gray-500 italic"
                                            >
                                                -- Ignorar columna --
                                            </SelectItem>
                                            {fields.map((field) => {
                                                // Check if field is already mapped to another header (optional logic, skipping for flexibility)
                                                return (
                                                    <SelectItem
                                                        key={field.key}
                                                        value={field.key}
                                                    >
                                                        {field.label}{" "}
                                                        {field.required && "*"}
                                                    </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Validation Errors */}
            {missingRequired.length > 0 && (
                <div className="p-4 bg-amber-50 rounded-lg border border-amber-200 flex items-start gap-3">
                    <AlertCircle className="h-5 w-5 text-amber-600 mt-0.5" />
                    <div>
                        <h4 className="text-sm font-medium text-amber-900">
                            Campos requeridos faltantes
                        </h4>
                        <ul className="mt-1 space-y-1">
                            {missingRequired.map((field) => (
                                <li
                                    key={field.key}
                                    className="text-xs text-amber-700"
                                >
                                    • {field.label}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            <div className="flex justify-between pt-4 border-t">
                <Button variant="outline" onClick={onBack}>
                    Atrás
                </Button>
                <div className="space-x-2">
                    <span className="text-xs text-muted-foreground mr-2">
                        {Object.keys(mapping).length} columnas mapeadas
                    </span>
                    <Button onClick={handleNext} disabled={!isValid}>
                        Continuar
                    </Button>
                </div>
            </div>
        </div>
    );
}
