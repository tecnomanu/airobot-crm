import { useCSVParser } from "@/hooks/csv/useCSVParser";
import { FileSpreadsheet, Upload, X } from "lucide-react";
import { useCallback, useState } from "react";

export default function CSVUploadStep({ onNext }) {
    const { parseCSV, parsing, error } = useCSVParser();
    const [file, setFile] = useState(null);
    const [dragActive, setDragActive] = useState(false);

    const handleFile = async (selectedFile) => {
        if (!selectedFile) return;
        setFile(selectedFile);

        try {
            const result = await parseCSV(selectedFile);
            onNext(result);
        } catch (err) {
            console.error(err);
            // Error handling is managed by hook and shown below
        }
    };

    const handleDrag = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    }, []);

    const handleDrop = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFile(e.dataTransfer.files[0]);
        }
    }, []);

    const handleChange = (e) => {
        e.preventDefault();
        if (e.target.files && e.target.files[0]) {
            handleFile(e.target.files[0]);
        }
    };

    return (
        <div className="space-y-4">
            <div
                className={`border-2 border-dashed rounded-lg p-12 text-center transition-colors ${
                    dragActive
                        ? "border-blue-500 bg-blue-50"
                        : "border-gray-300 hover:border-gray-400"
                }`}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
            >
                {parsing ? (
                    <div className="flex flex-col items-center justify-center space-y-3">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p className="text-sm text-gray-500">
                            Procesando archivo...
                        </p>
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center space-y-3">
                        <div className="p-4 bg-gray-50 rounded-full">
                            <Upload className="h-8 w-8 text-gray-400" />
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-gray-700">
                                Arrastra tu archivo CSV aquí o{" "}
                                <label
                                    htmlFor="file-upload"
                                    className="text-blue-600 hover:underline cursor-pointer"
                                >
                                    selecciónalo
                                </label>
                            </p>
                            <p className="text-xs text-gray-500">
                                Soporta .csv, .txt (max 5MB)
                            </p>
                        </div>
                        <input
                            id="file-upload"
                            type="file"
                            className="hidden"
                            accept=".csv,.txt"
                            onChange={handleChange}
                        />
                    </div>
                )}
            </div>

            {error && (
                <div className="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-600 flex items-center gap-2">
                    <X className="h-4 w-4" />
                    {error}
                </div>
            )}

            {/* Template Download Help */}
            <div className="bg-blue-50/50 rounded-lg p-4 border border-blue-100 flex items-start gap-3">
                <FileSpreadsheet className="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                    <h4 className="text-sm font-medium text-blue-900">
                        Formato Recomendado
                    </h4>
                    <p className="text-xs text-blue-700 mt-1">
                        Asegúrate de que tu archivo tenga encabezados en la
                        primera fila. Ejemplo: nombre, telefono, email.
                    </p>
                </div>
            </div>
        </div>
    );
}
