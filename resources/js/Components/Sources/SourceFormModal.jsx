import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { useForm } from "@inertiajs/react";
import { useEffect } from "react";
import { toast } from "sonner";
import SourceFormWebhook from "./SourceFormWebhook";
import SourceFormWhatsApp from "./SourceFormWhatsApp";

export default function SourceFormModal({
    open,
    onOpenChange,
    source = null,
    sources = [],
    clients = [],
    presetType = null,
}) {
    const isEditing = !!source;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        type: presetType || "",
        status: "active",
        client_id: "",
        config: {},
    });

    // Load source data when editing
    useEffect(() => {
        if (source) {
            setData({
                name: source.name || "",
                type: source.type || "",
                status: source.status || "active",
                client_id: source.client_id?.toString() || "",
                config: source.config || {},
            });
        }
    }, [source]);

    // Reset form when modal closes
    useEffect(() => {
        if (!open) {
            reset();
            if (presetType) {
                setData("type", presetType);
            }
        }
    }, [open]);

    // Initialize type if preset
    useEffect(() => {
        if (presetType && !source) {
            setData("type", presetType);
        }
    }, [presetType, source]);

    const handleSubmit = (e) => {
        e.preventDefault();

        // Validate for duplicates
        const isDuplicate = sources.some((s) => {
            if (isEditing && s.id === source.id) return false;

            if (data.type === "whatsapp" && s.type === "whatsapp") {
                const existingPhone = s.config?.phone_number;
                const newPhone = data.config?.phone_number;
                return existingPhone && newPhone && existingPhone === newPhone;
            }

            if (data.type === "webhook" && s.type === "webhook") {
                const existingUrl = s.config?.url;
                const newUrl = data.config?.url;
                return existingUrl && newUrl && existingUrl === newUrl;
            }

            return false;
        });

        if (isDuplicate) {
            toast.error(
                "Ya existe una fuente con este valor. No se pueden crear duplicados."
            );
            return;
        }

        if (isEditing) {
            put(route("sources.update", source.id), {
                onSuccess: () => {
                    onOpenChange(false);
                    toast.success("Fuente actualizada exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar la fuente");
                },
            });
        } else {
            post(route("sources.store"), {
                onSuccess: () => {
                    onOpenChange(false);
                    toast.success("Fuente creada exitosamente");
                },
                onError: () => {
                    toast.error("Error al crear la fuente");
                },
            });
        }
    };

    const getDialogTitle = () => {
        if (isEditing) return "Editar Fuente";
        if (presetType === "whatsapp") return "Crear Fuente de WhatsApp";
        if (presetType === "webhook") return "Crear Fuente de Webhook";
        return "Crear Nueva Fuente";
    };

    const getDialogDescription = () => {
        if (isEditing) return "Modifica los datos de esta fuente existente.";
        if (presetType === "whatsapp")
            return "Configura una nueva fuente de WhatsApp para enviar mensajes a tus leads.";
        if (presetType === "webhook")
            return "Configura una nueva fuente de webhook para integrar con sistemas externos.";
        return "Completa los datos para crear una nueva fuente reutilizable.";
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{getDialogTitle()}</DialogTitle>
                    <DialogDescription>
                        {getDialogDescription()}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Type selector (only if not preset) */}
                    {!presetType && (
                        <div className="space-y-2">
                            <Label htmlFor="type">
                                Tipo de Fuente{" "}
                                <span className="text-red-500">*</span>
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData("type", value)
                                }
                                disabled={isEditing}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="whatsapp">
                                        WhatsApp (Evolution API)
                                    </SelectItem>
                                    <SelectItem value="webhook">
                                        Webhook HTTP
                                    </SelectItem>
                                    <SelectItem value="meta_whatsapp">
                                        WhatsApp Business (Meta)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <p className="text-sm text-red-500">
                                    {errors.type}
                                </p>
                            )}
                        </div>
                    )}

                    {/* Type-specific forms */}
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

                    {/* Message if no type selected */}
                    {!data.type && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                            <p className="font-medium">
                                Selecciona un tipo de fuente
                            </p>
                            <p className="text-xs mt-1">
                                Primero selecciona el tipo de fuente que deseas
                                configurar.
                            </p>
                        </div>
                    )}

                    {/* Unimplemented types */}
                    {data.type &&
                        !["whatsapp", "webhook"].includes(data.type) && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                <p className="font-medium">
                                    Configuración en desarrollo
                                </p>
                                <p className="text-xs mt-1">
                                    La configuración para este tipo de fuente
                                    estará disponible próximamente.
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
                        <Button
                            type="submit"
                            disabled={processing || !data.type}
                        >
                            {processing
                                ? isEditing
                                    ? "Actualizando..."
                                    : "Creando..."
                                : isEditing
                                ? "Actualizar Fuente"
                                : "Crear Fuente"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
