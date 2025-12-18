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
    isCreating = false, // New prop
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

                {/* Switches: Estado y Auto Proceso (Minimalistas) */}
                <div className="flex flex-col sm:flex-row gap-6 p-1">
                    <div className="flex items-center gap-3">
                        <Switch
                            id="status"
                            checked={data.status === "active"}
                            onCheckedChange={(checked) => setData("status", checked ? "active" : "paused")}
                            disabled={!isCreating && false} // Permitir cambio de estado siempre
                        />
                        <div className="space-y-0.5">
                            <Label htmlFor="status" className="font-medium cursor-pointer">
                                {data.status === "active" ? "Campaña Activa" : "Campaña Pausada"}
                            </Label>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
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
                        <div className="space-y-0.5">
                            <Label htmlFor="auto_process_enabled" className="font-medium cursor-pointer">
                                Procesamiento Automático
                            </Label>
                        </div>
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

                {/* Strategy Section */}
                <div className="space-y-3 pt-2">
                    <Label className="text-base font-semibold">Estrategia de Contacto</Label>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Direct Strategy */}
                        <button
                            type="button"
                            onClick={() => setData("strategy_type", "direct")}
                            className={`relative p-4 rounded-xl border text-left transition-all hover:shadow-md cursor-pointer ${
                                data.strategy_type === "direct"
                                    ? "border-green-500 bg-green-50/50 ring-1 ring-green-500"
                                    : "border-gray-200 bg-white hover:border-green-300"
                            }`}
                        >
                            <div className="flex items-start gap-4">
                                <div className={`p-3 rounded-full shrink-0 transition-colors ${
                                    data.strategy_type === "direct"
                                        ? "bg-green-100 text-green-600"
                                        : "bg-gray-100 text-gray-500"
                                }`}>
                                    <Zap className="h-6 w-6" />
                                </div>
                                <div className="flex-1 space-y-1">
                                    <p className={`font-semibold text-base ${
                                        data.strategy_type === "direct" ? "text-green-900" : "text-gray-900"
                                    }`}>
                                        Campaña Directa
                                    </p>
                                    <p className="text-sm text-gray-500 leading-relaxed">
                                        Una acción única (WhatsApp o Llamada) por lead.
                                    </p>
                                </div>
                            </div>
                            {data.strategy_type === "direct" && (
                                <div className="absolute top-4 right-4">
                                    <Badge className="bg-green-600 hover:bg-green-700">Seleccionado</Badge>
                                </div>
                            )}
                        </button>

                        {/* Dynamic Strategy (IVR/Multiple) */}
                        <button
                            type="button"
                            onClick={() => setData("strategy_type", "dynamic")}
                            className={`relative p-4 rounded-xl border text-left transition-all hover:shadow-md cursor-pointer ${
                                data.strategy_type === "dynamic"
                                    ? "border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500"
                                    : "border-gray-200 bg-white hover:border-indigo-300"
                            }`}
                        >
                            <div className="flex items-start gap-4">
                                <div className={`p-3 rounded-full shrink-0 transition-colors ${
                                    data.strategy_type === "dynamic"
                                        ? "bg-indigo-100 text-indigo-600"
                                        : "bg-gray-100 text-gray-500"
                                }`}>
                                    <GitBranch className="h-6 w-6" />
                                </div>
                                <div className="flex-1 space-y-1">
                                    <p className={`font-semibold text-base ${
                                        data.strategy_type === "dynamic" ? "text-indigo-900" : "text-gray-900"
                                    }`}>
                                        Flujo Múltiple (IVR)
                                    </p>
                                    <p className="text-sm text-gray-500 leading-relaxed">
                                        Árbol de decisiones con opciones (1, 2, audio IA).
                                    </p>
                                </div>
                            </div>
                            {data.strategy_type === "dynamic" && (
                                <div className="absolute top-4 right-4">
                                    <Badge className="bg-indigo-600 hover:bg-indigo-700">Seleccionado</Badge>
                                </div>
                            )}
                        </button>
                    </div>
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
