import SourceCombobox from "@/Components/Common/SourceCombobox";
import CreateSourceModal from "@/Components/Sources/CreateSourceModal";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { router } from "@inertiajs/react";
import { useState } from "react";

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
            if (String(opt.option_key) === String(optionKey)) {
                return { ...opt, [field]: value };
            }
            return opt;
        });
        setData("options", updatedOptions);
    };

    // Helper para obtener una opción específica
    const getOption = (optionKey) => {
        return (
            data.options.find(
                (opt) => String(opt.option_key) === String(optionKey)
            ) || {
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
            <CreateSourceModal
                open={createWhatsappDialog}
                onOpenChange={setCreateWhatsappDialog}
                sourceType="whatsapp"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["whatsappSources"] })}
            />

            {/* Dialog para crear fuente Webhook */}
            <CreateSourceModal
                open={createWebhookDialog}
                onOpenChange={setCreateWebhookDialog}
                sourceType="webhook"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["webhookSources"] })}
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
                        <SourceCombobox
                            label="Fuente WhatsApp"
                            sources={whatsappSources || []}
                            value={currentSourceId?.toString() || null}
                            onValueChange={(value) =>
                                updateOption(
                                    optionKey,
                                    "source_id",
                                    value || null
                                )
                            }
                            onCreateNew={onCreateWhatsappSource}
                            placeholder="Selecciona fuente WhatsApp"
                            emptyMessage="No hay fuentes WhatsApp disponibles"
                            helperText={
                                !whatsappSources || whatsappSources.length === 0
                                    ? "No hay fuentes WhatsApp disponibles. Usa el botón 'Nueva Fuente' para crear una."
                                    : !currentSourceId
                                    ? "Selecciona una fuente WhatsApp para enviar mensajes."
                                    : ""
                            }
                        />

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
                    <SourceCombobox
                        label="Fuente Webhook"
                        sources={webhookSources || []}
                        value={currentSourceId?.toString() || null}
                        onValueChange={(value) =>
                            updateOption(optionKey, "source_id", value || null)
                        }
                        onCreateNew={onCreateWebhookSource}
                        placeholder="Selecciona webhook"
                        emptyMessage="No hay fuentes webhook disponibles"
                        helperText={
                            !webhookSources || webhookSources.length === 0
                                ? "No hay fuentes webhook disponibles. Usa el botón 'Nueva Fuente' para crear una."
                                : !currentSourceId
                                ? "Selecciona una fuente webhook para enviar datos."
                                : ""
                        }
                    />
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
