import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { usePage } from "@inertiajs/react";
import { SiGoogle, SiGooglesheets } from "react-icons/si";

export default function GoogleIntegrationSelector({ data, setData, errors }) {
    const { auth } = usePage().props;
    const integration = auth.user.google_integration;

    if (!integration) {
        return (
            <div className="rounded-md border border-dashed p-6 text-center">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <SiGoogle className="h-6 w-6 text-gray-500" />
                </div>
                <h3 className="mt-2 text-sm font-semibold text-gray-900">No hay cuenta conectada</h3>
                <p className="mt-1 text-sm text-gray-500">Conecta tu cuenta de Google en Configuración para habilitar la exportación.</p>
                <div className="mt-6">
                    <Button asChild variant="outline">
                        <a href={route('settings.integrations')}>Ir a Integraciones</a>
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between rounded-lg border p-4 bg-gray-50/50">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white border shadow-sm">
                        <SiGoogle className="h-5 w-5 text-gray-700" />
                    </div>
                    <div>
                        <p className="text-sm font-medium text-gray-900">{integration.email}</p>
                        <p className="text-xs text-gray-500">Cuenta conectada</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="success" className="bg-green-100 text-green-800">Activo</Badge>
                </div>
            </div>

            {/* Hidden field to store integration ID */}
            {/* We auto-select the integration if it exists */}
            {/* useEffect to set google_integration_id if not set? Maybe handled in Form default */}
            
            <div className="space-y-2">
                 <Label htmlFor="google_spreadsheet_id">ID de la Hoja de Cálculo</Label>
                 <div className="flex gap-2">
                    <Input 
                        id="google_spreadsheet_id"
                        value={data.google_spreadsheet_id || ''}
                        onChange={e => {
                            setData('google_spreadsheet_id', e.target.value);
                            // Auto-set integration ID if user types spreadsheet ID
                            if (e.target.value && !data.google_integration_id) {
                                setData(d => ({...d, google_integration_id: integration.id, google_spreadsheet_id: e.target.value}));
                            }
                        }}
                        placeholder="Ej: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                    />
                    <Button type="button" variant="outline" size="icon" asChild>
                        <a href="https://docs.google.com/spreadsheets/create" target="_blank" rel="noopener noreferrer" title="Crear nueva hoja">
                             <SiGooglesheets className="h-4 w-4 text-green-600" />
                        </a>
                    </Button>
                 </div>
                 {errors.google_spreadsheet_id && (
                     <p className="text-sm text-destructive">{errors.google_spreadsheet_id}</p>
                 )}
                 <p className="text-xs text-muted-foreground">
                    Copia el ID de la URL de tu Google Sheet: docs.google.com/spreadsheets/d/<strong>ESTE-CODIGO</strong>/edit
                 </p>
            </div>
            
             <div className="space-y-2">
                 <Label htmlFor="google_sheet_name">Nombre de la Pestaña (Opcional)</Label>
                 <Input 
                        id="google_sheet_name"
                        value={data.google_sheet_name || ''}
                        onChange={e => setData('google_sheet_name', e.target.value)}
                        placeholder="Ej: Hoja 1 (Dejar vacío para usar la primera)"
                    />
             </div>
        </div>
    );
}
