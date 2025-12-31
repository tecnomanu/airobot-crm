import SourceFormModal from "@/Components/Sources/SourceFormModal";
import SourceCombobox from "@/Components/Common/SourceCombobox";
import { Button } from "@/Components/ui/button";
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
import { Textarea } from "@/Components/ui/textarea";
import { router } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";

export default function AgentsTab({
    data,
    setData,
    errors,
    whatsappSources,
    webhookSources,
    clients = [],
}) {
    const [whatsappModalOpen, setWhatsappModalOpen] = useState(false);
    const [webhookModalOpen, setWebhookModalOpen] = useState(false);

    const updateCallAgent = (key, value) => {
        setData("call_agent", {
            ...data.call_agent,
            [key]: value,
        });
    };

    const updateCallAgentConfig = (key, value) => {
        setData("call_agent", {
            ...data.call_agent,
            config: {
                ...data.call_agent.config,
                [key]: value,
            },
        });
    };

    const updateWhatsappAgent = (key, value) => {
        setData("whatsapp_agent", {
            ...data.whatsapp_agent,
            [key]: value,
        });
    };

    const shouldShowCallAgent = () => {
        if (data.strategy_type === 'dynamic') return true;
        // Direct strategy: only show if action is 'call'
        return data.direct_action === 'call';
    };

    const shouldShowWhatsappAgent = () => {
        if (data.strategy_type === 'dynamic') return true;
        // Direct strategy: only show if action is 'whatsapp'
        return data.direct_action === 'whatsapp';
    };

    return (
        <div className="space-y-6">
            {/* Call Agent */}
            {shouldShowCallAgent() && (
                <Card>
                    <CardHeader>
                        <CardTitle>Agente de Llamadas (IA)</CardTitle>
                        <CardDescription>
                            Configura el agente de IA para llamadas telefónicas
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="call_agent_name">
                                    Nombre del Agente
                                </Label>
                                <Input
                                    id="call_agent_name"
                                    value={data.call_agent.name}
                                    onChange={(e) =>
                                        updateCallAgent("name", e.target.value)
                                    }
                                    placeholder="Ej: Agent Summer"
                                />
                                {errors["call_agent.name"] && (
                                    <p className="text-sm text-red-500">
                                        {errors["call_agent.name"]}
                                    </p>
                                )}
                            </div>
    
                            <div className="space-y-2">
                                <Label htmlFor="call_agent_provider">
                                    Proveedor
                                </Label>
                                <Select
                                    value={data.call_agent.provider}
                                    onValueChange={(value) =>
                                        updateCallAgent("provider", value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona proveedor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="retell">
                                            Retell AI
                                        </SelectItem>
                                        <SelectItem value="vapi">
                                            Vapi AI
                                        </SelectItem>
                                        <SelectItem value="otro">Otro</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors["call_agent.provider"] && (
                                    <p className="text-sm text-red-500">
                                        {errors["call_agent.provider"]}
                                    </p>
                                )}
                            </div>
                        </div>
    
                        <div className="space-y-2">
                            <Label>Script/Instrucciones</Label>
                            <Textarea
                                value={data.call_agent.config?.script || ""}
                                onChange={(e) =>
                                    updateCallAgentConfig("script", e.target.value)
                                }
                                placeholder="Script o instrucciones para el agente..."
                                rows={4}
                            />
                        </div>
    
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Idioma</Label>
                                <Input
                                    value={data.call_agent.config?.language || ""}
                                    onChange={(e) =>
                                        updateCallAgentConfig(
                                            "language",
                                            e.target.value
                                        )
                                    }
                                    placeholder="es"
                                />
                            </div>
    
                            <div className="space-y-2">
                                <Label>Voz</Label>
                                <Input
                                    value={data.call_agent.config?.voice || ""}
                                    onChange={(e) =>
                                        updateCallAgentConfig(
                                            "voice",
                                            e.target.value
                                        )
                                    }
                                    placeholder="female"
                                />
                            </div>
    
                            <div className="space-y-2">
                                <Label>Duración Máxima (seg)</Label>
                                <Input
                                    type="number"
                                    value={
                                        data.call_agent.config?.max_duration || ""
                                    }
                                    onChange={(e) =>
                                        updateCallAgentConfig(
                                            "max_duration",
                                            parseInt(e.target.value) || 0
                                        )
                                    }
                                    placeholder="300"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* WhatsApp Agent */}
            {shouldShowWhatsappAgent() && (
                <Card>
                    <CardHeader>
                        <CardTitle>Agente de WhatsApp (IA)</CardTitle>
                        <CardDescription>
                            Configura el agente de IA para respuestas automáticas
                            por WhatsApp
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="whatsapp_agent_name">
                                Nombre del Agente
                            </Label>
                            <Input
                                id="whatsapp_agent_name"
                                value={data.whatsapp_agent.name}
                                onChange={(e) =>
                                    updateWhatsappAgent("name", e.target.value)
                                }
                                placeholder="Ej: WhatsApp Bot"
                            />
                            {errors["whatsapp_agent.name"] && (
                                <p className="text-sm text-red-500">
                                    {errors["whatsapp_agent.name"]}
                                </p>
                            )}
                        </div>
    
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Idioma</Label>
                                <Input
                                    value={
                                        data.whatsapp_agent.config?.language || ""
                                    }
                                    onChange={(e) =>
                                        updateWhatsappAgentConfig(
                                            "language",
                                            e.target.value
                                        )
                                    }
                                    placeholder="es"
                                />
                            </div>
    
                            <div className="space-y-2">
                                <Label>Tono</Label>
                                <Input
                                    value={data.whatsapp_agent.config?.tone || ""}
                                    onChange={(e) =>
                                        updateWhatsappAgentConfig(
                                            "tone",
                                            e.target.value
                                        )
                                    }
                                    placeholder="friendly"
                                />
                            </div>
                        </div>
    
                        <div className="space-y-2">
                            <Label>Reglas de Comportamiento</Label>
                            <Textarea
                                value={
                                    Array.isArray(data.whatsapp_agent.config?.rules)
                                        ? data.whatsapp_agent.config.rules.join(
                                              "\n"
                                          )
                                        : ""
                                }
                                onChange={(e) =>
                                    updateWhatsappAgentConfig(
                                        "rules",
                                        e.target.value
                                            .split("\n")
                                            .filter((r) => r.trim())
                                    )
                                }
                                placeholder="Una regla por línea&#10;Ej: Responder en menos de 5 minutos&#10;Ser amable y profesional"
                                rows={4}
                            />
                            <p className="text-xs text-muted-foreground">
                                Una regla por línea
                            </p>
                        </div>
    
                        <div className="space-y-2 border-t pt-4">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="whatsapp_source">
                                    Fuente de WhatsApp
                                </Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setWhatsappModalOpen(true)}
                                    className="h-7 text-xs"
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Crear Nueva
                                </Button>
                            </div>
                            <SourceCombobox
                                sources={whatsappSources}
                                value={
                                    data.whatsapp_agent.source_id?.toString() ||
                                    null
                                }
                                onValueChange={(value) =>
                                    updateWhatsappAgent(
                                        "source_id",
                                        value ? parseInt(value) : null
                                    )
                                }
                                placeholder="Seleccionar fuente de WhatsApp"
                                emptyMessage="No se encontraron fuentes de WhatsApp"
                            />
                            <p className="text-xs text-muted-foreground">
                                {whatsappSources.length === 0
                                    ? "No hay fuentes de WhatsApp configuradas. Usa el botón 'Crear Nueva' arriba para configurar tu primera fuente."
                                    : "Esta fuente define qué número de WhatsApp usará esta campaña."}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Modales de creación de fuentes */}
            <SourceFormModal
                open={whatsappModalOpen}
                onOpenChange={setWhatsappModalOpen}
                presetType="whatsapp"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["whatsapp_sources"] })}
            />

            <SourceFormModal
                open={webhookModalOpen}
                onOpenChange={setWebhookModalOpen}
                presetType="webhook"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["webhook_sources"] })}
            />
        </div>
    );
}
