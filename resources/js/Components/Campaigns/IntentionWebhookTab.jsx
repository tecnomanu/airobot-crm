import GoogleIntegrationSelector from "@/Components/Campaigns/GoogleIntegrationSelector";
import SourceCombobox from "@/Components/common/SourceCombobox";
import CreateSourceModal from "@/Components/Sources/CreateSourceModal";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/Components/ui/radio-group";
import { Switch } from "@/components/ui/switch";
import { router } from "@inertiajs/react";
import { CheckCircle, CircleDot, Plus, Sheet, Webhook, XCircle } from "lucide-react";
import { useState } from "react";

export default function IntentionWebhookTab({ data, setData, errors, webhookSources, clients = [] }) {
    const [createWebhookModalOpen, setCreateWebhookModalOpen] = useState(false);
    // Filtrar solo fuentes de tipo webhook
    const availableWebhooks = webhookSources.filter(source => source.type === 'webhook');

    // Local state for UI selection
    const [interestedActionType, setInterestedActionType] = useState(() => {
        return data.google_spreadsheet_id ? 'google_sheet' : 'webhook';
    });

    const handleInterestedActionTypeChange = (value) => {
        setInterestedActionType(value);
        
        if (value === 'webhook') {
            // When switching to webhook, we clear the google sheet fields
            // to ensure we don't submit mixed data
            if (data.google_spreadsheet_id) {
               setData(d => ({...d, google_spreadsheet_id: null, google_integration_id: null}));
            }
        } else if (value === 'google_sheet') {
             // When switching to sheet, we clear the webhook ID
             if (data.intention_interested_webhook_id) {
                setData(d => ({...d, intention_interested_webhook_id: null})); 
             }
        }
    };

    return (
        <div className="space-y-6">
            {/* Header con descripción */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CircleDot className="h-5 w-5" />
                        Acciones Post-Intención
                    </CardTitle>
                    <CardDescription>
                        Define qué sucede automáticamente cuando el bot detecta una intención clara (interesado o no interesado).
                    </CardDescription>
                </CardHeader>
            </Card>

            {/* Lead Interesado */}
            <Card>
                <div className="flex items-center justify-between border-b p-4 bg-gray-50/50">
                    <div className="flex items-center gap-2">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                        <div>
                            <h3 className="font-medium">Lead Interesado</h3>
                            <p className="text-xs text-muted-foreground">Acción cuando la intención es positiva</p>
                        </div>
                    </div>
                    <Switch
                        checked={data.send_intention_interested_webhook}
                        onCheckedChange={(checked) =>
                            setData('send_intention_interested_webhook', checked)
                        }
                    />
                </div>
                
                {data.send_intention_interested_webhook && (
                    <CardContent className="pt-6 space-y-6">
                        {/* Selector de Tipo de Acción */}
                        <div className="space-y-3">
                            <Label>Tipo de Acción</Label>
                            <RadioGroup 
                                value={interestedActionType} 
                                onValueChange={handleInterestedActionTypeChange}
                                className="flex flex-col sm:flex-row gap-4"
                            >
                                <div className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${interestedActionType === 'webhook' ? 'border-indigo-600 bg-indigo-50' : ''}`}>
                                    <RadioGroupItem value="webhook" id="action-webhook" />
                                    <Label htmlFor="action-webhook" className="flex items-center gap-2 cursor-pointer w-full">
                                        <Webhook className="h-4 w-4 text-gray-600" />
                                        <span>Enviar Webhook</span>
                                    </Label>
                                </div>
                                <div className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${interestedActionType === 'google_sheet' ? 'border-green-600 bg-green-50' : ''}`}>
                                    <RadioGroupItem value="google_sheet" id="action-sheet" />
                                    <Label htmlFor="action-sheet" className="flex items-center gap-2 cursor-pointer w-full">
                                        <Sheet className="h-4 w-4 text-green-600" />
                                        <span>Exportar a Google Sheet</span>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>

                        {/* Configuración según selección */}
                        {interestedActionType === 'webhook' ? (
                            <div className="space-y-2 p-4 border rounded-lg bg-gray-50/30">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="interested_webhook">Webhook de Destino</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setCreateWebhookModalOpen(true)}
                                        className="h-7 text-xs text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50"
                                    >
                                        <Plus className="mr-1 h-3 w-3" />
                                        Crear Nuevo
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
                                    <p className="text-sm text-red-500">{errors.intention_interested_webhook_id}</p>
                                )}
                            </div>
                        ) : (
                            <div className="p-4 border rounded-lg bg-gray-50/30">
                                <GoogleIntegrationSelector 
                                    data={data}
                                    setData={setData}
                                    errors={errors}
                                />
                            </div>
                        )}
                    </CardContent>
                )}
            </Card>

            {/* Lead No Interesado */}
            <Card>
                <div className="flex items-center justify-between border-b p-4 bg-gray-50/50">
                    <div className="flex items-center gap-2">
                        <XCircle className="h-5 w-5 text-red-600" />
                        <div>
                            <h3 className="font-medium">Lead No Interesado</h3>
                            <p className="text-xs text-muted-foreground">Acción cuando la intención es negativa</p>
                        </div>
                    </div>
                    <Switch
                        checked={data.send_intention_not_interested_webhook}
                        onCheckedChange={(checked) =>
                            setData('send_intention_not_interested_webhook', checked)
                        }
                    />
                </div>
                
                {data.send_intention_not_interested_webhook && (
                    <CardContent className="pt-6">
                         {/* Por ahora solo soportamos Webhooks para No Interesado, 
                             pero mantenemos la estructura visual consistente si agregamos más opciones despues */}
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label>Tipo de Acción</Label>
                                <div className="flex items-center space-x-2 border rounded-lg p-3 border-indigo-600 bg-indigo-50">
                                    <div className="h-4 w-4 rounded-full border-4 border-indigo-600"></div>
                                    <span className="text-sm font-medium">Enviar Webhook</span>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Actualmente solo soportamos Webhooks para leads no interesados.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="not_interested_webhook">Webhook de Destino</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setCreateWebhookModalOpen(true)}
                                        className="h-7 text-xs text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50"
                                    >
                                        <Plus className="mr-1 h-3 w-3" />
                                        Crear Nuevo
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
                                    <p className="text-sm text-red-500">{errors.intention_not_interested_webhook_id}</p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                )}
            </Card>

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
