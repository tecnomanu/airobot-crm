import SourceFormWhatsApp from "@/Components/Sources/SourceFormWhatsApp";
import SourceCombobox from "@/Components/common/SourceCombobox";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { Textarea } from "@/Components/ui/textarea";
import { router, useForm } from "@inertiajs/react";
import { Info, MessageCircle, Phone, Plus, SkipForward, Zap } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import CallAgentConfig from "./CallAgentConfig";

const actionTypes = [
    { value: "skip", label: "Sales Ready", description: "Pasar directamente a ventas", icon: SkipForward },
    { value: "whatsapp", label: "WhatsApp", description: "Enviar mensaje de validación", icon: MessageCircle },
    { value: "call_ai", label: "Llamada IA", description: "Iniciar llamada con agente IA", icon: Phone },
];

export default function DirectActionTab({
    data,
    setData,
    campaign,
    templates,
    whatsappSources,
    webhookSources,
    clients,
    errors,
}) {
    const [createWhatsappDialog, setCreateWhatsappDialog] = useState(false);

    // Get current configuration from campaign.configuration
    const config = campaign.configuration || {};
    const currentAction = data.direct_action || config.trigger_action || "skip";
    const currentSourceId = data.direct_source_id || config.source_id || null;
    const currentMessage = data.direct_message || config.message || "";

    const updateDirectConfig = (field, value) => {
        setData(field, value);
    };

    return (
        <div className="space-y-4">
            {/* Info Banner */}
            <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
                <Zap className="h-5 w-5 text-green-600 mt-0.5" />
                <div>
                    <h4 className="font-medium text-green-900">Campaña Directa</h4>
                    <p className="text-sm text-green-700 mt-1">
                        Todos los leads que entren a esta campaña ejecutarán la misma acción automáticamente.
                        Ideal para listas CSV, importaciones masivas o leads que ya fueron pre-calificados.
                    </p>
                </div>
            </div>

            {/* Action Selection */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Acción Principal</CardTitle>
                    <CardDescription>
                        Selecciona qué acción se ejecutará automáticamente para cada lead
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Action Type Selection */}
                    <div className="grid grid-cols-3 gap-3">
                        {actionTypes.map((action) => {
                            const Icon = action.icon;
                            const isSelected = currentAction === action.value;
                            return (
                                <button
                                    key={action.value}
                                    type="button"
                                    onClick={() => updateDirectConfig("direct_action", action.value)}
                                    className={`relative p-4 rounded-lg border-2 text-left transition-all ${
                                        isSelected
                                            ? "border-green-500 bg-green-50"
                                            : "border-gray-200 hover:border-gray-300 bg-white"
                                    }`}
                                >
                                    <div className="flex flex-col gap-2">
                                        <div className={`p-2 rounded-lg w-fit ${
                                            isSelected ? "bg-green-100" : "bg-gray-100"
                                        }`}>
                                            <Icon className={`h-5 w-5 ${
                                                isSelected ? "text-green-600" : "text-gray-500"
                                            }`} />
                                        </div>
                                        <div>
                                            <p className="font-medium text-sm text-gray-900">
                                                {action.label}
                                            </p>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                {action.description}
                                            </p>
                                        </div>
                                    </div>
                                    {isSelected && (
                                        <Badge className="absolute top-2 right-2 bg-green-600 text-[10px]">
                                            Activo
                                        </Badge>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* WhatsApp Configuration */}
            {currentAction === "whatsapp" && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <MessageCircle className="h-4 w-4 text-green-600" />
                            Configuración de WhatsApp
                        </CardTitle>
                        <CardDescription>
                            Configura el mensaje que se enviará a cada lead
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Source Selection */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Fuente WhatsApp *</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setCreateWhatsappDialog(true)}
                                    className="h-7 text-xs"
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Nueva Fuente
                                </Button>
                            </div>
                            <SourceCombobox
                                sources={whatsappSources || []}
                                value={currentSourceId?.toString() || null}
                                onValueChange={(value) =>
                                    updateDirectConfig("direct_source_id", value || null)
                                }
                                placeholder="Selecciona fuente WhatsApp"
                                emptyMessage="No hay fuentes WhatsApp disponibles"
                            />
                            {(!whatsappSources || whatsappSources.length === 0) && (
                                <p className="text-xs text-amber-600">
                                    ⚠️ No hay fuentes WhatsApp disponibles. Crea una usando el botón "Nueva Fuente".
                                </p>
                            )}
                        </div>

                        {/* Message */}
                        <div className="space-y-2">
                            <Label>Mensaje de WhatsApp *</Label>
                            <Textarea
                                value={currentMessage}
                                onChange={(e) => updateDirectConfig("direct_message", e.target.value)}
                                placeholder="Hola {{name}}! Gracias por tu interés. ¿Te gustaría recibir más información? Responde SÍ o NO."
                                rows={4}
                            />
                            <p className="text-xs text-muted-foreground">
                                Usa <code className="bg-gray-100 px-1 rounded">{"{{name}}"}</code> para incluir el nombre del lead.
                            </p>
                        </div>

                        {/* Template Selection (Optional) */}
                        <div className="space-y-2">
                            <Label>Plantilla (Opcional)</Label>
                            <Select
                                value={data.direct_template_id || "no_template"}
                                onValueChange={(value) =>
                                    updateDirectConfig("direct_template_id", value === "no_template" ? null : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Usar mensaje personalizado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no_template">Usar mensaje personalizado</SelectItem>
                                    {templates && templates.length > 0 && templates.map((template) => (
                                        <SelectItem key={template.id} value={template.id}>
                                            {template.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Selecciona una plantilla pre-definida o usa el mensaje personalizado arriba.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Call AI Configuration */}
            {currentAction === "call_ai" && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Phone className="h-4 w-4 text-purple-600" />
                            Configuración de Llamada IA
                        </CardTitle>
                        <CardDescription>
                            Configura el agente de IA que realizará las llamadas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CallAgentConfig 
                            data={data}
                            setData={setData}
                            errors={errors || {}}
                        />
                    </CardContent>
                </Card>
            )}

            {/* Skip/Sales Ready Info */}
            {currentAction === "skip" && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <SkipForward className="h-4 w-4 text-emerald-600" />
                            Sales Ready (Sin Acción Previa)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <Info className="h-5 w-5 text-emerald-600 mt-0.5" />
                                <div>
                                    <p className="text-sm text-emerald-900 font-medium">
                                        Los leads pasarán directamente a "Listos para Ventas"
                                    </p>
                                    <p className="text-xs text-emerald-700 mt-1">
                                        No se ejecutará ninguna acción automática. Los leads entrarán directamente 
                                        al pipeline de ventas como pre-calificados.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Dialog para crear fuente WhatsApp */}
            <CreateWhatsAppSourceDialog
                open={createWhatsappDialog}
                onOpenChange={setCreateWhatsappDialog}
                clients={clients}
            />
        </div>
    );
}

// Dialog para crear fuente WhatsApp (reutilizado de AutomationTab)
function CreateWhatsAppSourceDialog({ open, onOpenChange, clients }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        type: "whatsapp",
        client_id: "",
        status: "active",
        config: {
            phone_number: "",
            provider: "evolution_api",
            api_url: "",
            instance_name: "",
            api_key: "",
        },
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("sources.store"), {
            onSuccess: () => {
                toast.success("Fuente WhatsApp creada exitosamente");
                onOpenChange(false);
                reset();
                router.reload({ only: ["whatsapp_sources"] });
            },
            onError: () => {
                toast.error("Error al crear la fuente WhatsApp");
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Crear Fuente WhatsApp</DialogTitle>
                    <DialogDescription>
                        Configura una nueva fuente de WhatsApp para enviar mensajes automatizados.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <SourceFormWhatsApp
                        data={data}
                        setData={setData}
                        errors={errors}
                        clients={clients}
                    />
                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Creando..." : "Crear Fuente"}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

