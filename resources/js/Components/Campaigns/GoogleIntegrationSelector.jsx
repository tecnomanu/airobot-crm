import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { usePage } from "@inertiajs/react";
import { Building2, Lock } from "lucide-react";
import { SiGoogle, SiGooglesheets } from "react-icons/si";

export default function GoogleIntegrationSelector({
    data,
    setData,
    errors,
    spreadsheetIdField = "google_spreadsheet_id",
    sheetNameField = "google_sheet_name",
}) {
    const { google_integrations: googleData, auth } = usePage().props;

    // Fallback to auth.google_integration for backwards compatibility
    const integrations = googleData?.integrations || [];
    const canChange = googleData?.can_change ?? true;
    const userIsInternal = googleData?.user_is_internal ?? false;

    // If no integrations data from props, use the shared google_integration
    const fallbackIntegration = auth.google_integration;

    // Find selected integration
    const selectedIntegrationId = data.google_integration_id;
    const selectedIntegration = integrations.find(
        (i) => i.id === selectedIntegrationId
    );

    // Check if current selection is from another client (read-only for non-internal users)
    const isReadOnly =
        selectedIntegrationId &&
        !canChange &&
        !integrations.find((i) => i.id === selectedIntegrationId);

    // No integrations available at all
    if (integrations.length === 0 && !fallbackIntegration && !selectedIntegrationId) {
        return (
            <div className="rounded-md border border-dashed p-6 text-center">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <SiGoogle className="h-6 w-6 text-gray-500" />
                </div>
                <h3 className="mt-2 text-sm font-semibold text-gray-900">
                    No hay cuenta conectada
                </h3>
                <p className="mt-1 text-sm text-gray-500">
                    Conecta tu cuenta de Google en Configuración para habilitar
                    la exportación.
                </p>
                <div className="mt-6">
                    <Button asChild variant="outline">
                        <a href={route("settings.integrations")}>
                            Ir a Integraciones
                        </a>
                    </Button>
                </div>
            </div>
        );
    }

    // Helper to get the display integration (selected or fallback)
    const displayIntegration =
        selectedIntegration ||
        (fallbackIntegration
            ? {
                  id: fallbackIntegration.id,
                  email: fallbackIntegration.email,
                  is_internal: false,
                  label: fallbackIntegration.email,
              }
            : null);

    // Auto-select first available integration if none selected and user has access
    const handleSpreadsheetChange = (value) => {
        const updates = { [spreadsheetIdField]: value };

        // Auto-set integration ID when typing spreadsheet ID
        if (value && !data.google_integration_id) {
            const defaultIntegration =
                integrations[0] || (fallbackIntegration ? { id: fallbackIntegration.id } : null);
            if (defaultIntegration) {
                updates.google_integration_id = defaultIntegration.id;
            }
        }

        setData((d) => ({ ...d, ...updates }));
    };

    return (
        <div className="space-y-4">
            {/* Integration Selector (only if multiple or can change) */}
            {integrations.length > 1 && canChange && (
                <div className="space-y-2">
                    <Label>Cuenta de Google</Label>
                    <Select
                        value={selectedIntegrationId || ""}
                        onValueChange={(value) =>
                            setData("google_integration_id", value || null)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar cuenta..." />
                        </SelectTrigger>
                        <SelectContent>
                            {integrations.map((integration) => (
                                <SelectItem
                                    key={integration.id}
                                    value={integration.id}
                                >
                                    <div className="flex items-center gap-2">
                                        {integration.is_internal && (
                                            <Building2 className="h-3 w-3 text-purple-600" />
                                        )}
                                        <span>{integration.label}</span>
                                    </div>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {/* Selected Integration Display */}
            {displayIntegration && (
                <div
                    className={`flex items-center justify-between rounded-lg border p-4 ${
                        displayIntegration.is_internal
                            ? "bg-purple-50/50 border-purple-200"
                            : "bg-gray-50/50"
                    }`}
                >
                    <div className="flex items-center gap-3">
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-full bg-white border shadow-sm ${
                                displayIntegration.is_internal
                                    ? "border-purple-300"
                                    : ""
                            }`}
                        >
                            {displayIntegration.is_internal ? (
                                <Building2 className="h-5 w-5 text-purple-600" />
                            ) : (
                                <SiGoogle className="h-5 w-5 text-gray-700" />
                            )}
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-900">
                                {displayIntegration.email}
                            </p>
                            <p className="text-xs text-gray-500">
                                {displayIntegration.is_internal
                                    ? "Integración de AirRobot"
                                    : "Cuenta conectada"}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {isReadOnly && (
                            <Badge
                                variant="outline"
                                className="text-gray-500 gap-1"
                            >
                                <Lock className="h-3 w-3" />
                                Solo lectura
                            </Badge>
                        )}
                        <Badge
                            variant="success"
                            className={
                                displayIntegration.is_internal
                                    ? "bg-purple-100 text-purple-800"
                                    : "bg-green-100 text-green-800"
                            }
                        >
                            {displayIntegration.is_internal
                                ? "AirRobot"
                                : "Activo"}
                        </Badge>
                    </div>
                </div>
            )}

            {/* Read-only notice for non-internal users viewing internal integration */}
            {isReadOnly && (
                <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                    Esta campaña usa una integración de AirRobot. Para usar tu
                    propia cuenta, conecta Google en{" "}
                    <a
                        href={route("settings.integrations")}
                        className="underline font-medium"
                    >
                        Integraciones
                    </a>
                    .
                </div>
            )}

            {/* Spreadsheet Configuration (editable only if not read-only) */}
            <div className="space-y-2">
                <Label htmlFor={spreadsheetIdField}>
                    ID de la Hoja de Cálculo
                </Label>
                <div className="flex gap-2">
                    <Input
                        id={spreadsheetIdField}
                        value={data[spreadsheetIdField] || ""}
                        onChange={(e) => handleSpreadsheetChange(e.target.value)}
                        placeholder="Ej: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                        disabled={isReadOnly}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        asChild
                        disabled={isReadOnly}
                    >
                        <a
                            href="https://docs.google.com/spreadsheets/create"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Crear nueva hoja"
                        >
                            <SiGooglesheets className="h-4 w-4 text-green-600" />
                        </a>
                    </Button>
                </div>
                {errors[spreadsheetIdField] && (
                    <p className="text-sm text-destructive">
                        {errors[spreadsheetIdField]}
                    </p>
                )}
                <p className="text-xs text-muted-foreground">
                    Copia el ID de la URL de tu Google Sheet:
                    docs.google.com/spreadsheets/d/<strong>ESTE-CODIGO</strong>
                    /edit
                </p>
            </div>

            <div className="space-y-2">
                <Label htmlFor={sheetNameField}>
                    Nombre de la Pestaña (Opcional)
                </Label>
                <Input
                    id={sheetNameField}
                    value={data[sheetNameField] || ""}
                    onChange={(e) => setData(sheetNameField, e.target.value)}
                    placeholder="Ej: Hoja 1 (Dejar vacío para usar la primera)"
                    disabled={isReadOnly}
                />
            </div>
        </div>
    );
}
