import { useState } from "react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { AlertCircle, Plus, X } from "lucide-react";

export default function SourceFormWebhook({ data, setData, errors, clients }) {
    const [newHeaderKey, setNewHeaderKey] = useState("");
    const [newHeaderValue, setNewHeaderValue] = useState("");

    const headers = data.config?.headers || {};

    const addHeader = () => {
        if (newHeaderKey && newHeaderValue) {
            setData("config", {
                ...data.config,
                headers: {
                    ...headers,
                    [newHeaderKey]: newHeaderValue,
                },
            });
            setNewHeaderKey("");
            setNewHeaderValue("");
        }
    };

    const removeHeader = (key) => {
        const newHeaders = { ...headers };
        delete newHeaders[key];
        setData("config", {
            ...data.config,
            headers: newHeaders,
        });
    };

    return (
        <div className="space-y-4">
            {/* Nombre */}
            <div className="space-y-2">
                <Label htmlFor="name">
                    Nombre de la Fuente <span className="text-red-500">*</span>
                </Label>
                <Input
                    id="name"
                    placeholder="ej: Webhook CRM Principal"
                    value={data.name}
                    onChange={(e) => setData("name", e.target.value)}
                />
                {errors.name && (
                    <p className="text-sm text-red-500">{errors.name}</p>
                )}
            </div>

            {/* Cliente */}
            <div className="space-y-2">
                <Label htmlFor="client_id">Cliente (Opcional)</Label>
                <Select
                    value={data.client_id?.toString() || "none"}
                    onValueChange={(value) =>
                        setData("client_id", value === "none" ? "" : value)
                    }
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar cliente" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">Ninguno</SelectItem>
                        {clients.map((c) => (
                            <SelectItem key={c.id} value={c.id.toString()}>
                                {c.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* Estado */}
            <div className="flex items-center justify-between space-y-2">
                <div className="space-y-0.5">
                    <Label htmlFor="status">Estado Activo</Label>
                    <p className="text-xs text-muted-foreground">
                        La fuente estará disponible para usar en campañas
                    </p>
                </div>
                <Switch
                    id="status"
                    checked={data.status === "active"}
                    onCheckedChange={(checked) =>
                        setData("status", checked ? "active" : "inactive")
                    }
                />
            </div>

            <div className="border-t pt-4">
                <h4 className="mb-3 font-semibold">Configuración del Webhook</h4>

                <div className="space-y-4">
                    {/* URL */}
                    <div className="space-y-2">
                        <Label htmlFor="url">
                            URL del Endpoint <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="url"
                            placeholder="https://api.micrm.com/leads"
                            value={data.config?.url || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    url: e.target.value,
                                })
                            }
                        />
                        {errors["config.url"] && (
                            <p className="text-sm text-red-500">
                                {errors["config.url"]}
                            </p>
                        )}
                    </div>

                    {/* Método HTTP */}
                    <div className="space-y-2">
                        <Label htmlFor="method">
                            Método HTTP <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={data.config?.method || "POST"}
                            onValueChange={(value) =>
                                setData("config", {
                                    ...data.config,
                                    method: value,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar método" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="GET">GET</SelectItem>
                                <SelectItem value="POST">POST</SelectItem>
                                <SelectItem value="PUT">PUT</SelectItem>
                                <SelectItem value="PATCH">PATCH</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Headers */}
                    <div className="space-y-2">
                        <Label>Headers HTTP (Opcional)</Label>
                        
                        {/* Lista de headers existentes */}
                        {Object.entries(headers).length > 0 && (
                            <div className="space-y-2 mb-3">
                                {Object.entries(headers).map(([key, value]) => (
                                    <div
                                        key={key}
                                        className="flex items-center gap-2 rounded-md border bg-muted/50 p-2"
                                    >
                                        <code className="flex-1 text-xs">
                                            {key}: {value}
                                        </code>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeHeader(key)}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Agregar nuevo header */}
                        <div className="flex gap-2">
                            <Input
                                placeholder="Header (ej: Authorization)"
                                value={newHeaderKey}
                                onChange={(e) => setNewHeaderKey(e.target.value)}
                            />
                            <Input
                                placeholder="Valor"
                                value={newHeaderValue}
                                onChange={(e) => setNewHeaderValue(e.target.value)}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={addHeader}
                                disabled={!newHeaderKey || !newHeaderValue}
                            >
                                <Plus className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Payload Template */}
                    <div className="space-y-2">
                        <Label htmlFor="payload_template">
                            Plantilla de Payload (JSON)
                        </Label>
                        <Textarea
                            id="payload_template"
                            placeholder={`{
  "lead_name": "{{name}}",
  "phone": "{{phone}}",
  "campaign": "{{campaign}}"
}`}
                            rows={8}
                            className="font-mono text-xs"
                            value={data.config?.payload_template || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    payload_template: e.target.value,
                                })
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Usa variables como {`{{name}}, {{phone}}, {{campaign}}`} que se
                            reemplazarán con datos reales.
                        </p>
                    </div>
                </div>

                <div className="mt-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                    <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                    <div>
                        <p className="font-medium">Tip de configuración</p>
                        <p className="text-xs mt-0.5">
                            Esta fuente enviará los datos del lead al webhook configurado
                            cuando se ejecute la acción correspondiente en una campaña.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

