import GoogleIntegrationSelector from "@/Components/Campaigns/GoogleIntegrationSelector";
import SourceCombobox from "@/Components/Common/SourceCombobox";
import SourceFormModal from "@/Components/Sources/SourceFormModal";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/Components/ui/radio-group";
import { Switch } from "@/Components/ui/switch";
import { router } from "@inertiajs/react";
import { CheckCircle, CircleDot, Clock, Info, Sheet, Webhook, XCircle } from "lucide-react";
import { useState } from "react";

export default function IntentionWebhookTab({
    data,
    setData,
    errors,
    webhookSources,
    clients = [],
}) {
    const [createWebhookModalOpen, setCreateWebhookModalOpen] = useState(false);
    // Filtrar solo fuentes de tipo webhook
    const availableWebhooks = webhookSources.filter(
        (source) => source.type === "webhook" || source.type === "webhook_crm"
    );

    // Local state for UI selection - Interested
    const [interestedActionType, setInterestedActionType] = useState(() => {
        return data.google_spreadsheet_id ? "google_sheet" : "webhook";
    });

    // Local state for UI selection - Not Interested
    const [notInterestedActionType, setNotInterestedActionType] = useState(
        () => {
            return data.intention_not_interested_google_spreadsheet_id
                ? "google_sheet"
                : "webhook";
        }
    );

    const handleInterestedActionTypeChange = (value) => {
        setInterestedActionType(value);

        if (value === "webhook") {
            if (data.google_spreadsheet_id) {
                setData((d) => ({
                    ...d,
                    google_spreadsheet_id: null,
                    google_integration_id: null,
                }));
            }
        } else if (value === "google_sheet") {
            if (data.intention_interested_webhook_id) {
                setData((d) => ({
                    ...d,
                    intention_interested_webhook_id: null,
                }));
            }
        }
    };

    const handleNotInterestedActionTypeChange = (value) => {
        setNotInterestedActionType(value);

        if (value === "webhook") {
            if (data.intention_not_interested_google_spreadsheet_id) {
                setData((d) => ({
                    ...d,
                    intention_not_interested_google_spreadsheet_id: null,
                    // Do NOT clear google_integration_id if interested action is using it
                    // But for simplicity/robustness, the selector will re-set it if needed
                }));
            }
        } else if (value === "google_sheet") {
            if (data.intention_not_interested_webhook_id) {
                setData((d) => ({
                    ...d,
                    intention_not_interested_webhook_id: null,
                }));
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
                        Define qué sucede automáticamente cuando el bot detecta
                        una intención clara (interesado o no interesado).
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
                            <p className="text-xs text-muted-foreground">
                                Acción cuando la intención es positiva
                            </p>
                        </div>
                    </div>
                    <Switch
                        checked={data.send_intention_interested_webhook}
                        onCheckedChange={(checked) =>
                            setData(
                                "send_intention_interested_webhook",
                                checked
                            )
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
                                <div
                                    className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${
                                        interestedActionType === "webhook"
                                            ? "border-indigo-600 bg-indigo-50"
                                            : ""
                                    }`}
                                >
                                    <RadioGroupItem
                                        value="webhook"
                                        id="action-webhook"
                                    />
                                    <Label
                                        htmlFor="action-webhook"
                                        className="flex items-center gap-2 cursor-pointer w-full"
                                    >
                                        <Webhook className="h-4 w-4 text-gray-600" />
                                        <span>Enviar Webhook</span>
                                    </Label>
                                </div>
                                <div
                                    className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${
                                        interestedActionType === "google_sheet"
                                            ? "border-green-600 bg-green-50"
                                            : ""
                                    }`}
                                >
                                    <RadioGroupItem
                                        value="google_sheet"
                                        id="action-sheet"
                                    />
                                    <Label
                                        htmlFor="action-sheet"
                                        className="flex items-center gap-2 cursor-pointer w-full"
                                    >
                                        <Sheet className="h-4 w-4 text-green-600" />
                                        <span>Exportar a Google Sheet</span>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>

                        {interestedActionType === "webhook" ? (
                            <SourceCombobox
                                label="Webhook de Destino"
                                sources={availableWebhooks}
                                value={
                                    data.intention_interested_webhook_id?.toString() ||
                                    null
                                }
                                onValueChange={(value) =>
                                    setData(
                                        "intention_interested_webhook_id",
                                        value || null
                                    )
                                }
                                onCreateNew={() =>
                                    setCreateWebhookModalOpen(true)
                                }
                                placeholder="Seleccionar webhook..."
                                emptyMessage="No hay webhooks disponibles"
                                error={errors.intention_interested_webhook_id}
                                className="p-4 border rounded-lg bg-gray-50/30"
                            />
                        ) : (
                            <div className="p-4 border rounded-lg bg-gray-50/30">
                                <GoogleIntegrationSelector
                                    data={data}
                                    setData={setData}
                                    errors={errors}
                                    spreadsheetIdField="google_spreadsheet_id"
                                    sheetNameField="google_sheet_name"
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
                        <XCircle className="h-5 w-5 text-red-500" />
                        <div>
                            <h3 className="font-medium">Lead No Interesado</h3>
                            <p className="text-xs text-muted-foreground">
                                Acción cuando la intención es negativa (cerrar lead)
                            </p>
                        </div>
                    </div>
                    <Switch
                        checked={data.send_intention_not_interested_webhook}
                        onCheckedChange={(checked) =>
                            setData(
                                "send_intention_not_interested_webhook",
                                checked
                            )
                        }
                    />
                </div>

                {data.send_intention_not_interested_webhook && (
                    <CardContent className="pt-6">
                        <div className="space-y-6">
                            <div className="space-y-3">
                                <Label>Tipo de Acción</Label>
                                <RadioGroup
                                    value={notInterestedActionType}
                                    onValueChange={
                                        handleNotInterestedActionTypeChange
                                    }
                                    className="flex flex-col sm:flex-row gap-4"
                                >
                                    <div
                                        className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${
                                            notInterestedActionType ===
                                            "webhook"
                                                ? "border-indigo-600 bg-indigo-50"
                                                : ""
                                        }`}
                                    >
                                        <RadioGroupItem
                                            value="webhook"
                                            id="not-interested-action-webhook"
                                        />
                                        <Label
                                            htmlFor="not-interested-action-webhook"
                                            className="flex items-center gap-2 cursor-pointer w-full"
                                        >
                                            <Webhook className="h-4 w-4 text-gray-600" />
                                            <span>Enviar Webhook</span>
                                        </Label>
                                    </div>
                                    <div
                                        className={`flex items-center space-x-2 border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors flex-1 ${
                                            notInterestedActionType ===
                                            "google_sheet"
                                                ? "border-green-600 bg-green-50"
                                                : ""
                                        }`}
                                    >
                                        <RadioGroupItem
                                            value="google_sheet"
                                            id="not-interested-action-sheet"
                                        />
                                        <Label
                                            htmlFor="not-interested-action-sheet"
                                            className="flex items-center gap-2 cursor-pointer w-full"
                                        >
                                            <Sheet className="h-4 w-4 text-green-600" />
                                            <span>Exportar a Google Sheet</span>
                                        </Label>
                                    </div>
                                </RadioGroup>
                            </div>

                            {notInterestedActionType === "webhook" ? (
                                <SourceCombobox
                                    label="Webhook de Destino"
                                    sources={availableWebhooks}
                                    value={
                                        data.intention_not_interested_webhook_id?.toString() ||
                                        null
                                    }
                                    onValueChange={(value) =>
                                        setData(
                                            "intention_not_interested_webhook_id",
                                            value || null
                                        )
                                    }
                                    onCreateNew={() =>
                                        setCreateWebhookModalOpen(true)
                                    }
                                    placeholder="Seleccionar webhook..."
                                    emptyMessage="No hay webhooks disponibles"
                                    error={
                                        errors.intention_not_interested_webhook_id
                                    }
                                    className="p-4 border rounded-lg bg-gray-50/30"
                                />
                            ) : (
                                <div className="p-4 border rounded-lg bg-gray-50/30">
                                    <GoogleIntegrationSelector
                                        data={data}
                                        setData={setData}
                                        errors={errors}
                                        spreadsheetIdField="intention_not_interested_google_spreadsheet_id"
                                        sheetNameField="intention_not_interested_google_sheet_name"
                                    />
                                </div>
                            )}
                        </div>
                    </CardContent>
                )}
            </Card>

            {/* Sin Respuesta */}
            <Card>
                <div className="flex items-center justify-between border-b p-4 bg-gray-50/50">
                    <div className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-amber-500" />
                        <div>
                            <h3 className="font-medium">Sin Respuesta</h3>
                            <p className="text-xs text-muted-foreground">
                                Acción cuando el lead no responde después de X intentos
                            </p>
                        </div>
                    </div>
                    <Switch
                        checked={data.no_response_action_enabled || false}
                        onCheckedChange={(checked) =>
                            setData("no_response_action_enabled", checked)
                        }
                    />
                </div>

                {data.no_response_action_enabled && (
                    <CardContent className="pt-6 space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="no_response_max_attempts">
                                    Máximo de intentos
                                </Label>
                                <Input
                                    id="no_response_max_attempts"
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={data.no_response_max_attempts || 3}
                                    onChange={(e) =>
                                        setData(
                                            "no_response_max_attempts",
                                            parseInt(e.target.value) || 3
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Intentos antes de marcar como sin respuesta
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="no_response_timeout_hours">
                                    Timeout (horas)
                                </Label>
                                <Input
                                    id="no_response_timeout_hours"
                                    type="number"
                                    min="1"
                                    max="168"
                                    value={data.no_response_timeout_hours || 48}
                                    onChange={(e) =>
                                        setData(
                                            "no_response_timeout_hours",
                                            parseInt(e.target.value) || 48
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Horas de espera antes de cerrar automáticamente
                                </p>
                            </div>
                        </div>

                        <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                            <Info className="h-4 w-4 text-amber-600 mt-0.5 shrink-0" />
                            <p className="text-xs text-amber-700">
                                Cuando se cumplan las condiciones, el lead será cerrado automáticamente
                                con motivo "NO_RESPONSE" y será visible en el tab "Cerrados".
                            </p>
                        </div>
                    </CardContent>
                )}
            </Card>

            <SourceFormModal
                open={createWebhookModalOpen}
                onOpenChange={setCreateWebhookModalOpen}
                presetType="webhook"
                clients={clients}
                redirectTo={window.location.pathname}
                onSuccess={() => router.reload({ only: ["webhook_sources"] })}
            />
        </div>
    );
}
