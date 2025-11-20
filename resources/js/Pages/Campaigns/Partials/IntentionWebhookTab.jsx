import SourceCombobox from "@/Components/common/SourceCombobox";
import CreateSourceModal from "@/Components/Sources/CreateSourceModal";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { router } from "@inertiajs/react";
import { CheckCircle, Plus, Webhook, XCircle } from "lucide-react";
import { useState } from "react";

export default function IntentionWebhookTab({ data, setData, errors, webhookSources, clients = [] }) {
    const [createWebhookModalOpen, setCreateWebhookModalOpen] = useState(false);
    // Filtrar solo fuentes de tipo webhook
    const availableWebhooks = webhookSources.filter(source => source.type === 'webhook');

    return (
        <div className="space-y-6">
            {/* Header con descripción */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Webhook className="h-5 w-5" />
                        Webhooks de Intención
                    </CardTitle>
                    <CardDescription>
                        Configura webhooks que se disparan automáticamente cuando se detecta la intención del lead.
                        Puedes enviar a diferentes webhooks según el lead esté interesado o no interesado.
                    </CardDescription>
                </CardHeader>
            </Card>

            {/* Webhook para Leads Interesados */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                        Lead Interesado
                    </CardTitle>
                    <CardDescription>
                        Webhook que se envía cuando se detecta que un lead está interesado
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Switch para activar/desactivar */}
                    <div className="flex items-center justify-between space-x-2 p-4 border rounded-lg">
                        <div className="space-y-0.5">
                            <Label htmlFor="send_interested" className="text-base">
                                Enviar webhook cuando está interesado
                            </Label>
                            <div className="text-sm text-muted-foreground">
                                Activa esta opción para enviar automáticamente al webhook cuando se detecta intención positiva
                            </div>
                        </div>
                        <Switch
                            id="send_interested"
                            checked={data.send_intention_interested_webhook}
                            onCheckedChange={(checked) =>
                                setData('send_intention_interested_webhook', checked)
                            }
                        />
                    </div>

                    {/* Select de webhook solo si está activado */}
                    {data.send_intention_interested_webhook && (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="interested_webhook">
                                    Webhook de Destino *
                                </Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setCreateWebhookModalOpen(true)}
                                    className="h-7 text-xs"
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Crear Webhook
                                </Button>
                            </div>
                            <SourceCombobox
                                sources={availableWebhooks}
                                value={data.intention_interested_webhook_id?.toString() || null}
                                onValueChange={(value) =>
                                    setData('intention_interested_webhook_id', value ? parseInt(value) : null)
                                }
                                placeholder="Seleccionar webhook..."
                                emptyMessage="No hay webhooks disponibles"
                            />
                            {errors.intention_interested_webhook_id && (
                                <p className="text-sm text-red-500">
                                    {errors.intention_interested_webhook_id}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                {availableWebhooks.length === 0
                                    ? "No hay webhooks disponibles. Usa el botón 'Crear Webhook' para crear uno."
                                    : data.intention_interested_webhook_id
                                    ? "Los leads con intención de 'interesado' se enviarán automáticamente a este webhook."
                                    : ""}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Webhook para Leads No Interesados */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <XCircle className="h-5 w-5 text-red-600" />
                        Lead No Interesado
                    </CardTitle>
                    <CardDescription>
                        Webhook que se envía cuando se detecta que un lead no está interesado
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Switch para activar/desactivar */}
                    <div className="flex items-center justify-between space-x-2 p-4 border rounded-lg">
                        <div className="space-y-0.5">
                            <Label htmlFor="send_not_interested" className="text-base">
                                Enviar webhook cuando no está interesado
                            </Label>
                            <div className="text-sm text-muted-foreground">
                                Activa esta opción para enviar automáticamente al webhook cuando se detecta intención negativa
                            </div>
                        </div>
                        <Switch
                            id="send_not_interested"
                            checked={data.send_intention_not_interested_webhook}
                            onCheckedChange={(checked) =>
                                setData('send_intention_not_interested_webhook', checked)
                            }
                        />
                    </div>

                    {/* Select de webhook solo si está activado */}
                    {data.send_intention_not_interested_webhook && (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="not_interested_webhook">
                                    Webhook de Destino *
                                </Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setCreateWebhookModalOpen(true)}
                                    className="h-7 text-xs"
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Crear Webhook
                                </Button>
                            </div>
                            <SourceCombobox
                                sources={availableWebhooks}
                                value={data.intention_not_interested_webhook_id?.toString() || null}
                                onValueChange={(value) =>
                                    setData('intention_not_interested_webhook_id', value ? parseInt(value) : null)
                                }
                                placeholder="Seleccionar webhook..."
                                emptyMessage="No hay webhooks disponibles"
                            />
                            {errors.intention_not_interested_webhook_id && (
                                <p className="text-sm text-red-500">
                                    {errors.intention_not_interested_webhook_id}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                {availableWebhooks.length === 0
                                    ? "No hay webhooks disponibles. Usa el botón 'Crear Webhook' para crear uno."
                                    : data.intention_not_interested_webhook_id
                                    ? "Los leads con intención de 'no interesado' se enviarán automáticamente a este webhook."
                                    : ""}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>


            {/* Modal de creación de fuente webhook */}
            <CreateSourceModal
                open={createWebhookModalOpen}
                onOpenChange={setCreateWebhookModalOpen}
                sourceType="webhook"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["webhook_sources"] })}
            />
        </div>
    );
}

