import SourceFormWebhook from "@/Components/Sources/SourceFormWebhook";
import SourceFormWhatsApp from "@/Components/Sources/SourceFormWhatsApp";
import SourceCombobox from "@/Components/common/SourceCombobox";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { router, useForm } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

const optionConfig = {
    1: { title: "Opción 1", description: "Primera opción seleccionada" },
    2: { title: "Opción 2", description: "Segunda opción seleccionada" },
    i: { title: "Opción I", description: "Información solicitada" },
    t: { title: "Opción T", description: "Transferir a agente" },
};

const actionTypes = [
    { value: "skip", label: "Sin acción" },
    { value: "whatsapp", label: "Enviar WhatsApp" },
    { value: "call_ai", label: "Agendar llamada IA" },
    { value: "webhook_crm", label: "Usar Webhook" },
    { value: "manual_review", label: "Revisión Manual" },
];

export default function AutomationTab({
    data,
    setData,
    campaign,
    templates,
    whatsappSources,
    webhookSources,
    clients,
}) {
    const [createWhatsappDialog, setCreateWhatsappDialog] = useState(false);
    const [createWebhookDialog, setCreateWebhookDialog] = useState(false);

    // Helper para actualizar una opción específica
    const updateOption = (optionKey, field, value) => {
        const updatedOptions = data.options.map((opt) => {
            if (opt.option_key === optionKey) {
                return { ...opt, [field]: value };
            }
            return opt;
        });
        setData("options", updatedOptions);
    };

    // Helper para obtener una opción específica
    const getOption = (optionKey) => {
        return (
            data.options.find((opt) => opt.option_key === optionKey) || {
                option_key: optionKey,
                action: "skip",
                source_id: null,
                template_id: null,
                message: "",
                delay: 5,
                enabled: true,
            }
        );
    };

    return (
        <div className="space-y-4">
            {/* Options Configuration */}
            <div className="grid gap-4 md:grid-cols-2">
                {Object.entries(optionConfig).map(([key, config]) => (
                    <OptionCard
                        key={key}
                        optionKey={key}
                        option={getOption(key)}
                        title={config.title}
                        description={config.description}
                        updateOption={updateOption}
                        templates={templates}
                        whatsappSources={whatsappSources}
                        webhookSources={webhookSources}
                        callAgentName={data.call_agent?.name}
                        onCreateWhatsappSource={() =>
                            setCreateWhatsappDialog(true)
                        }
                        onCreateWebhookSource={() =>
                            setCreateWebhookDialog(true)
                        }
                    />
                ))}
            </div>

            {/* Dialog para crear fuente WhatsApp */}
            <CreateWhatsAppSourceDialog
                open={createWhatsappDialog}
                onOpenChange={setCreateWhatsappDialog}
                clients={clients}
            />

            {/* Dialog para crear fuente Webhook */}
            <CreateWebhookSourceDialog
                open={createWebhookDialog}
                onOpenChange={setCreateWebhookDialog}
                clients={clients}
            />
        </div>
    );
}

function OptionCard({
    optionKey,
    option,
    title,
    description,
    updateOption,
    templates,
    whatsappSources,
    webhookSources,
    callAgentName,
    onCreateWhatsappSource,
    onCreateWebhookSource,
}) {
    const currentAction = option.action || "skip";
    const currentSourceId = option.source_id;
    const currentTemplateId = option.template_id;
    const currentDelay = option.delay || 5;

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-baseline gap-3">
                    <CardTitle className="text-base">{title}</CardTitle>
                    <CardDescription className="text-xs">
                        {description}
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent className="space-y-4 pt-3">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Acción</Label>
                        <Select
                            value={currentAction}
                            onValueChange={(value) =>
                                updateOption(optionKey, "action", value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona una acción" />
                            </SelectTrigger>
                            <SelectContent>
                                {actionTypes.map((action) => (
                                    <SelectItem
                                        key={action.value}
                                        value={action.value}
                                    >
                                        {action.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {currentAction && currentAction !== "skip" && (
                        <div className="space-y-2">
                            <Label>Delay (segundos)</Label>
                            <Input
                                type="number"
                                value={currentDelay}
                                onChange={(e) =>
                                    updateOption(
                                        optionKey,
                                        "delay",
                                        parseInt(e.target.value) || 5
                                    )
                                }
                                placeholder="5"
                            />
                            <p className="text-xs text-muted-foreground">
                                Tiempo de espera antes de ejecutar la acción
                            </p>
                        </div>
                    )}
                </div>

                {/* WhatsApp Action */}
                {currentAction === "whatsapp" && (
                    <>
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Fuente WhatsApp</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={onCreateWhatsappSource}
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
                                    updateOption(
                                        optionKey,
                                        "source_id",
                                        value ? parseInt(value) : null
                                    )
                                }
                                placeholder="Selecciona fuente WhatsApp"
                                emptyMessage="No hay fuentes WhatsApp disponibles"
                            />
                            <p className="text-xs text-muted-foreground">
                                {!whatsappSources ||
                                whatsappSources.length === 0
                                    ? "No hay fuentes WhatsApp disponibles. Usa el botón 'Nueva Fuente' para crear una."
                                    : !currentSourceId
                                    ? "Selecciona una fuente WhatsApp para enviar mensajes."
                                    : ""}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label>Plantilla de WhatsApp</Label>
                            <Select
                                value={currentTemplateId || ""}
                                onValueChange={(value) =>
                                    updateOption(
                                        optionKey,
                                        "template_id",
                                        value || null
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona plantilla" />
                                </SelectTrigger>
                                <SelectContent>
                                    {templates && templates.length > 0 ? (
                                        templates.map((template) => (
                                            <SelectItem
                                                key={template.id}
                                                value={template.id}
                                            >
                                                {template.name}
                                            </SelectItem>
                                        ))
                                    ) : (
                                        <SelectItem value="none" disabled>
                                            No hay plantillas disponibles
                                        </SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                            {(!templates || templates.length === 0) && (
                                <p className="text-xs text-amber-600">
                                    ⚠️ No hay plantillas. Crea una en la pestaña{" "}
                                    <span className="font-semibold">
                                        Plantillas
                                    </span>
                                </p>
                            )}
                        </div>
                    </>
                )}

                {/* Webhook/CRM Action */}
                {currentAction === "webhook_crm" && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label>Fuente Webhook</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={onCreateWebhookSource}
                                className="h-7 text-xs"
                            >
                                <Plus className="mr-1 h-3 w-3" />
                                Nueva Fuente
                            </Button>
                        </div>
                        <SourceCombobox
                            sources={webhookSources || []}
                            value={currentSourceId?.toString() || null}
                            onValueChange={(value) =>
                                updateOption(
                                    optionKey,
                                    "source_id",
                                    value ? parseInt(value) : null
                                )
                            }
                            placeholder="Selecciona webhook"
                            emptyMessage="No hay fuentes webhook disponibles"
                        />
                        <p className="text-xs text-muted-foreground">
                            {!webhookSources || webhookSources.length === 0
                                ? "No hay fuentes webhook disponibles. Usa el botón 'Nueva Fuente' para crear una."
                                : !currentSourceId
                                ? "Selecciona una fuente webhook para enviar datos."
                                : ""}
                        </p>
                    </div>
                )}

                {currentAction === "call_ai" && (
                    <div className="space-y-2">
                        <Label>Agente de Llamada</Label>
                        <Input
                            value={callAgentName || "Sin agente configurado"}
                            disabled
                            className="bg-gray-50"
                        />
                        <p className="text-xs text-muted-foreground">
                            Configura el agente en la pestaña "Agentes"
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// Dialog para crear fuente WhatsApp
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
                router.reload({ only: ["whatsappSources"] });
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
                        Configura una nueva fuente de WhatsApp para enviar
                        mensajes automatizados.
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

// Dialog para crear fuente Webhook
function CreateWebhookSourceDialog({ open, onOpenChange, clients }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        type: "webhook_crm",
        client_id: "",
        status: "active",
        config: {
            url: "",
            method: "POST",
            headers: {},
            payload_template: "",
        },
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("sources.store"), {
            onSuccess: () => {
                toast.success("Fuente Webhook creada exitosamente");
                onOpenChange(false);
                reset();
                router.reload({ only: ["webhookSources"] });
            },
            onError: () => {
                toast.error("Error al crear la fuente Webhook");
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Crear Fuente Webhook</DialogTitle>
                    <DialogDescription>
                        Configura una nueva fuente webhook para enviar datos a
                        sistemas externos (CRM, etc.).
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <SourceFormWebhook
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
