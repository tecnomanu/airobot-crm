import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Check, Copy, GitBranch, Zap } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

export default function BasicInfoTab({
    data,
    setData,
    errors,
    clients,
    campaign,
}) {
    const [copiedSlug, setCopiedSlug] = useState(false);

    const handleCopySlug = () => {
        if (campaign?.slug) {
            navigator.clipboard.writeText(campaign.slug);
            setCopiedSlug(true);
            toast.success("Slug copiado al portapapeles");
            setTimeout(() => setCopiedSlug(false), 2000);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Información General</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre de la Campaña *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            placeholder="Ej: Campaña Verano 2024"
                        />
                        {errors.name && (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="client_id">Cliente *</Label>
                        <Select
                            value={data.client_id}
                            onValueChange={(value) =>
                                setData("client_id", value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un cliente" />
                            </SelectTrigger>
                            <SelectContent>
                                {clients.map((client) => (
                                    <SelectItem
                                        key={client.id}
                                        value={client.id}
                                    >
                                        {client.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.client_id && (
                            <p className="text-sm text-destructive">
                                {errors.client_id}
                            </p>
                        )}
                    </div>
                </div>

                {/* Slug (editable) */}
                <div className="space-y-2">
                    <Label htmlFor="slug">ID de Campaña (Slug)</Label>
                    <div className="flex gap-2">
                        <Input
                            id="slug"
                            value={data.slug || campaign?.slug || ""}
                            onChange={(e) => {
                                // Solo permitir caracteres alfanuméricos, guiones y guiones bajos
                                const value = e.target.value
                                    .toLowerCase()
                                    .replace(/[^a-z0-9-_]/g, "");
                                setData("slug", value);
                            }}
                            placeholder="campana-verano-2024"
                            className="font-mono"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={handleCopySlug}
                        >
                            {copiedSlug ? (
                                <Check className="h-4 w-4 text-green-600" />
                            ) : (
                                <Copy className="h-4 w-4" />
                            )}
                        </Button>
                    </div>
                    {errors.slug && (
                        <p className="text-sm text-destructive">
                            {errors.slug}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Usa este ID para enviar leads desde webhooks. Solo
                        letras minúsculas, números, guiones y guiones bajos.
                        Ejemplo:{" "}
                        <code className="rounded bg-muted px-1 py-0.5 font-mono text-xs">
                            {`{ "campaign": "${
                                data.slug || campaign?.slug || "campana-slug"
                            }" }`}
                        </code>
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="status">Estado</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData("status", value)}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Activa</SelectItem>
                                <SelectItem value="paused">Pausada</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Tipo de Campaña</Label>
                        <div className={`flex items-center gap-3 p-3 rounded-lg border ${
                            campaign.strategy_type === "direct"
                                ? "bg-green-50 border-green-200"
                                : "bg-blue-50 border-blue-200"
                        }`}>
                            {campaign.strategy_type === "direct" ? (
                                <>
                                    <Zap className="h-5 w-5 text-green-600" />
                                    <div>
                                        <p className="font-medium text-sm text-green-900">Directa</p>
                                        <p className="text-xs text-green-700">Una acción para todos los leads</p>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <GitBranch className="h-5 w-5 text-blue-600" />
                                    <div>
                                        <p className="font-medium text-sm text-blue-900">Múltiple (IVR)</p>
                                        <p className="text-xs text-blue-700">Acciones según opción seleccionada</p>
                                    </div>
                                </>
                            )}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            El tipo de campaña no se puede cambiar después de crearla.
                        </p>
                    </div>
                </div>

                {/* Auto Process Enabled */}
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                        <Label
                            htmlFor="auto_process_enabled"
                            className="text-base"
                        >
                            Procesamiento Automático
                        </Label>
                        <p className="text-sm text-muted-foreground">
                            {campaign.strategy_type === "direct"
                                ? "Ejecutar automáticamente la acción configurada cuando lleguen nuevos leads"
                                : "Ejecutar automáticamente las acciones configuradas cuando lleguen leads con opciones 1, 2, i o t"
                            }
                        </p>
                    </div>
                    <Switch
                        id="auto_process_enabled"
                        checked={
                            data.auto_process_enabled !== undefined
                                ? data.auto_process_enabled
                                : true
                        }
                        onCheckedChange={(checked) =>
                            setData("auto_process_enabled", checked)
                        }
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Descripción</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData("description", e.target.value)}
                        placeholder="Describe el objetivo de esta campaña..."
                        rows={4}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
