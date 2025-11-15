import CreateSourceModal from "@/Components/Sources/CreateSourceModal";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { router } from "@inertiajs/react";
import { AlertCircle, Plus } from "lucide-react";
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

    const updateWhatsappAgentConfig = (key, value) => {
        setData("whatsapp_agent", {
            ...data.whatsapp_agent,
            config: {
                ...data.whatsapp_agent.config,
                [key]: value,
            },
        });
    };

    return (
        <div className="space-y-6">
            {/* Call Agent */}
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

            {/* WhatsApp Agent */}
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
                        {whatsappSources.length > 0 ? (
                            <>
                                <Select
                                    value={
                                        data.whatsapp_agent.source_id?.toString() ||
                                        "none"
                                    }
                                    onValueChange={(value) =>
                                        updateWhatsappAgent(
                                            "source_id",
                                            value === "none"
                                                ? null
                                                : parseInt(value)
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar fuente de WhatsApp" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Sin fuente
                                        </SelectItem>
                                        {whatsappSources.map((source) => (
                                            <SelectItem
                                                key={source.id}
                                                value={source.id.toString()}
                                            >
                                                {source.name} -{" "}
                                                {source.config?.phone_number ||
                                                    source.config
                                                        ?.instance_name ||
                                                    "N/A"}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    Esta fuente define qué número de WhatsApp
                                    usará esta campaña.
                                </p>
                            </>
                        ) : (
                            <div className="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                                <div>
                                    <p className="font-medium">
                                        No hay fuentes de WhatsApp configuradas
                                    </p>
                                    <p className="text-xs mt-0.5">
                                        Usa el botón "Crear Nueva" arriba para
                                        configurar tu primera fuente de
                                        WhatsApp.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Modales de creación de fuentes */}
            <CreateSourceModal
                open={whatsappModalOpen}
                onOpenChange={setWhatsappModalOpen}
                sourceType="whatsapp"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["whatsapp_sources"] })}
            />

            <CreateSourceModal
                open={webhookModalOpen}
                onOpenChange={setWebhookModalOpen}
                sourceType="webhook"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["webhook_sources"] })}
            />
        </div>
    );
}
