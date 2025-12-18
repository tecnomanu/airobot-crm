import { Link } from "@inertiajs/react";
import { AlertTriangle, ExternalLink } from "lucide-react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

export default function WebhookTab({ data, setData, errors, isLegacy = false }) {
    return (
        <div className="space-y-4">
            {/* Legacy Warning */}
            {isLegacy && (
                <div className="flex items-start gap-3 rounded-lg border-2 border-amber-300 bg-amber-50 p-4">
                    <AlertTriangle className="h-6 w-6 text-amber-600 mt-0.5 shrink-0" />
                    <div>
                        <h3 className="font-semibold text-amber-900 text-lg">
                            Configuración antigua (Legacy)
                        </h3>
                        <p className="text-sm text-amber-800 mt-1">
                            Esta forma de configurar webhooks está obsoleta. Las campañas nuevas deben usar <span className="font-semibold">Fuentes (Sources)</span> para gestionar webhooks de forma reutilizable.
                        </p>
                        <div className="mt-3 space-y-1">
                            <p className="text-sm text-amber-900 font-medium">Para configurar webhooks correctamente:</p>
                            <ol className="list-decimal list-inside text-sm text-amber-800 space-y-0.5 ml-1">
                                <li>
                                    Crea una fuente de tipo Webhook desde el{" "}
                                    <Link 
                                        href={route("sources.index")} 
                                        className="inline-flex items-center gap-1 font-semibold underline hover:text-amber-950"
                                    >
                                        menú Fuentes
                                        <ExternalLink className="h-3 w-3" />
                                    </Link>
                                </li>
                                <li>Asigna la fuente en la pestaña <span className="font-semibold">Agentes</span> (Fuente de Webhook / CRM)</li>
                                <li>Configura las acciones en <span className="font-semibold">Opciones & Automatización</span></li>
                            </ol>
                        </div>
                        <p className="text-xs text-amber-700 mt-3 italic">
                            Los campos a continuación están en modo solo lectura para campañas existentes.
                        </p>
                    </div>
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle className={isLegacy ? "text-muted-foreground" : ""}>
                        Configuración de Webhook {isLegacy && "(Legacy)"}
                    </CardTitle>
                    <CardDescription>
                        {isLegacy 
                            ? "Configuración legacy - Solo lectura" 
                            : "Envía leads a un webhook externo (CRM, n8n, etc.)"}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="webhook_enabled"
                            checked={data.webhook_enabled}
                            onCheckedChange={(checked) =>
                                setData("webhook_enabled", checked)
                            }
                            disabled={isLegacy}
                        />
                        <Label htmlFor="webhook_enabled" className={isLegacy ? "text-muted-foreground" : ""}>
                            Activar webhook
                        </Label>
                    </div>

                    {data.webhook_enabled && (
                        <>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="webhook_url" className={isLegacy ? "text-muted-foreground" : ""}>
                                        URL del Webhook *
                                    </Label>
                                    <Input
                                        id="webhook_url"
                                        value={data.webhook_url}
                                        onChange={(e) =>
                                            setData("webhook_url", e.target.value)
                                        }
                                        placeholder="https://example.com/webhook"
                                        type="url"
                                        disabled={isLegacy}
                                        className={isLegacy ? "bg-muted" : ""}
                                    />
                                    {errors.webhook_url && (
                                        <p className="text-sm text-destructive">
                                            {errors.webhook_url}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="webhook_method" className={isLegacy ? "text-muted-foreground" : ""}>
                                        Método HTTP
                                    </Label>
                                    <Select
                                        value={data.webhook_method}
                                        onValueChange={(value) =>
                                            setData("webhook_method", value)
                                        }
                                        disabled={isLegacy}
                                    >
                                        <SelectTrigger className={isLegacy ? "bg-muted" : ""}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="POST">POST</SelectItem>
                                            <SelectItem value="PUT">PUT</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="webhook_payload_template" className={isLegacy ? "text-muted-foreground" : ""}>
                                    Plantilla de Payload (JSON)
                                </Label>
                                <Textarea
                                    id="webhook_payload_template"
                                    value={data.webhook_payload_template}
                                    onChange={(e) =>
                                        setData("webhook_payload_template", e.target.value)
                                    }
                                    placeholder='{"lead_id": "{{id}}", "phone": "{{phone}}", "name": "{{name}}"}'
                                    rows={8}
                                    className={`font-mono text-sm ${isLegacy ? "bg-muted" : ""}`}
                                    disabled={isLegacy}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Variables disponibles: {"{"}
                                    {"{"}id{"}}"},  {"{"}
                                    {"{"}phone{"}}"},  {"{"}
                                    {"{"}name{"}}"},  {"{"}
                                    {"{"}city{"}}"},  {"{"}
                                    {"{"}option_selected{"}}"}
                                </p>
                            </div>

                            <div className="rounded-lg bg-muted p-4">
                                <h4 className="mb-2 text-sm font-medium">
                                    Ejemplo de Payload
                                </h4>
                                <pre className="overflow-x-auto text-xs">
                                    {JSON.stringify(
                                        {
                                            lead_id: "{{id}}",
                                            phone: "{{phone}}",
                                            name: "{{name}}",
                                            city: "{{city}}",
                                            campaign: "{{campaign.name}}",
                                            option: "{{option_selected}}",
                                        },
                                        null,
                                        2
                                    )}
                                </pre>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

