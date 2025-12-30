import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { useState } from "react";
import CSVColumnMapper from "./CSVColumnMapper";
import CSVPreviewStep from "./CSVPreviewStep";
import CSVUploadStep from "./CSVUploadStep";

export default function CSVImporterModal({
    open,
    onClose,
    onComplete,
    fields = [],
    entityName = "registros",
    title = "Importar CSV",
    description,
}) {
    const [step, setStep] = useState(1);
    const [csvData, setCsvData] = useState(null); // { headers, rows }
    const [mapping, setMapping] = useState({});
    const [isImporting, setIsImporting] = useState(false);

    const handleClose = () => {
        if (isImporting) return;
        setStep(1);
        setCsvData(null);
        setMapping({});
        onClose();
    };

    const handleUploadComplete = (data) => {
        setCsvData(data);
        setStep(2);
    };

    const handleMappingComplete = (newMapping) => {
        setMapping(newMapping);
        setStep(3);
    };

    const handleImport = async (validatedData, finalMapping) => {
        setIsImporting(true);
        try {
            await onComplete(validatedData, finalMapping);
            handleClose();
        } catch (error) {
            console.error("Error importing CSV:", error);
            // Error handling should be done by parent or toast here
        } finally {
            setIsImporting(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {description ||
                            `Sigue los pasos para importar ${entityName} desde un archivo CSV.`}
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4">
                    {/* Steps Indicator */}
                    <div className="flex items-center justify-center mb-8">
                        <div
                            className={`h-8 w-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${
                                step >= 1
                                    ? "bg-blue-600 text-white"
                                    : "bg-gray-200 text-gray-500"
                            }`}
                        >
                            1
                        </div>
                        <div
                            className={`w-12 h-1 ${
                                step >= 2 ? "bg-blue-600" : "bg-gray-200"
                            } transition-colors mx-2`}
                        />
                        <div
                            className={`h-8 w-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${
                                step >= 2
                                    ? "bg-blue-600 text-white"
                                    : "bg-gray-200 text-gray-500"
                            }`}
                        >
                            2
                        </div>
                        <div
                            className={`w-12 h-1 ${
                                step >= 3 ? "bg-blue-600" : "bg-gray-200"
                            } transition-colors mx-2`}
                        />
                        <div
                            className={`h-8 w-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${
                                step >= 3
                                    ? "bg-blue-600 text-white"
                                    : "bg-gray-200 text-gray-500"
                            }`}
                        >
                            3
                        </div>
                    </div>

                    {/* Step Content */}
                    <div className="mt-4">
                        {step === 1 && (
                            <CSVUploadStep onNext={handleUploadComplete} />
                        )}

                        {step === 2 && csvData && (
                            <CSVColumnMapper
                                headers={csvData.headers}
                                fields={fields}
                                onNext={handleMappingComplete}
                                onBack={() => setStep(1)}
                                initialMapping={mapping}
                            />
                        )}

                        {step === 3 && csvData && (
                            <CSVPreviewStep
                                rows={csvData.rows}
                                mapping={mapping}
                                fields={fields}
                                onImport={handleImport}
                                onBack={() => setStep(2)}
                                isImporting={isImporting}
                            />
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
