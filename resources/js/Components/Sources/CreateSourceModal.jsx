import { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import { toast } from "sonner";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import SourceFormWhatsApp from "./SourceFormWhatsApp";
import SourceFormWebhook from "./SourceFormWebhook";

export default function CreateSourceModal({ 
    open, 
    onOpenChange, 
    sourceType = null, 
    clients = [],
    onSuccess = null,
    redirectTo = null
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        type: sourceType || "",
        status: "active",
        client_id: "",
        config: {},
        redirect_to: redirectTo || "",
    });

    // Resetear formulario cuando se abre/cierra modal
    useEffect(() => {
        if (!open) {
            reset();
            if (sourceType) {
                setData("type", sourceType);
            }
        }
    }, [open]);

    // Inicializar tipo y redirectTo
    useEffect(() => {
        if (sourceType) {
            setData("type", sourceType);
        }
        if (redirectTo) {
            setData("redirect_to", redirectTo);
        }
    }, [sourceType, redirectTo]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        post(route("sources.store"), {
            onSuccess: () => {
                toast.success("Fuente creada exitosamente");
                onOpenChange(false);
                if (onSuccess) {
                    onSuccess();
                }
            },
            onError: () => {
                toast.error("Error al crear la fuente");
            },
        });
    };

    const getDialogTitle = () => {
        if (sourceType === "whatsapp") return "Crear Fuente de WhatsApp";
        if (sourceType === "webhook") return "Crear Fuente de Webhook";
        return "Crear Nueva Fuente";
    };

    const getDialogDescription = () => {
        if (sourceType === "whatsapp") 
            return "Configura una nueva fuente de WhatsApp para enviar mensajes a tus leads.";
        if (sourceType === "webhook") 
            return "Configura una nueva fuente de webhook para integrar con sistemas externos.";
        return "Completa los datos para crear una nueva fuente reutilizable.";
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{getDialogTitle()}</DialogTitle>
                    <DialogDescription>{getDialogDescription()}</DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Selector de tipo (solo si no es específico) */}
                    {!sourceType && (
                        <div className="space-y-2">
                            <Label htmlFor="type">
                                Tipo de Fuente <span className="text-red-500">*</span>
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) => setData("type", value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="whatsapp">WhatsApp (Evolution API)</SelectItem>
                                    <SelectItem value="webhook">Webhook HTTP</SelectItem>
                                    <SelectItem value="meta_whatsapp">WhatsApp Business (Meta)</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <p className="text-sm text-red-500">{errors.type}</p>
                            )}
                        </div>
                    )}

                    {/* Formulario específico según tipo */}
                    {data.type === "whatsapp" && (
                        <SourceFormWhatsApp
                            data={data}
                            setData={setData}
                            errors={errors}
                            clients={clients}
                        />
                    )}

                    {data.type === "webhook" && (
                        <SourceFormWebhook
                            data={data}
                            setData={setData}
                            errors={errors}
                            clients={clients}
                        />
                    )}

                    {/* Mensaje si no hay tipo seleccionado */}
                    {!data.type && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                            <p className="font-medium">Selecciona un tipo de fuente</p>
                            <p className="text-xs mt-1">
                                Primero selecciona el tipo de fuente que deseas configurar.
                            </p>
                        </div>
                    )}

                    {/* Tipos no implementados */}
                    {data.type && !["whatsapp", "webhook"].includes(data.type) && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            <p className="font-medium">Configuración en desarrollo</p>
                            <p className="text-xs mt-1">
                                La configuración para este tipo de fuente estará disponible próximamente.
                            </p>
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing || !data.type}>
                            {processing ? "Creando..." : "Crear Fuente"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

