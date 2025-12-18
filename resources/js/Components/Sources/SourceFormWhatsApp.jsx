import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { AlertCircle } from "lucide-react";

export default function SourceFormWhatsApp({ data, setData, errors, clients }) {
    return (
        <div className="space-y-4">
            {/* Nombre */}
            <div className="space-y-2">
                <Label htmlFor="name">
                    Nombre de la Fuente <span className="text-red-500">*</span>
                </Label>
                <Input
                    id="name"
                    placeholder="ej: WhatsApp Principal"
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
                <h4 className="mb-3 font-semibold">
                    Configuración de WhatsApp
                </h4>

                <div className="space-y-4">
                    {/* Número de WhatsApp */}
                    <div className="space-y-2">
                        <Label htmlFor="phone_number">
                            Número de WhatsApp (E.164){" "}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="phone_number"
                            placeholder="+5492215648523"
                            value={data.config?.phone_number || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    phone_number: e.target.value,
                                })
                            }
                        />
                        {errors["config.phone_number"] && (
                            <p className="text-sm text-red-500">
                                {errors["config.phone_number"]}
                            </p>
                        )}
                    </div>

                    {/* Proveedor */}
                    <div className="space-y-2">
                        <Label htmlFor="provider">
                            Proveedor <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={data.config?.provider || "evolution_api"}
                            onValueChange={(value) =>
                                setData("config", {
                                    ...data.config,
                                    provider: value,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar proveedor" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="evolution_api">
                                    Evolution API
                                </SelectItem>
                                <SelectItem value="whatsapp_business_api">
                                    WhatsApp Business API
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* API URL */}
                    <div className="space-y-2">
                        <Label htmlFor="api_url">
                            Dominio / Base URL{" "}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="api_url"
                            placeholder="https://api.evolution.com"
                            value={data.config?.api_url || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    api_url: e.target.value,
                                })
                            }
                        />
                        {errors["config.api_url"] && (
                            <p className="text-sm text-red-500">
                                {errors["config.api_url"]}
                            </p>
                        )}
                    </div>

                    {/* Instance Name */}
                    <div className="space-y-2">
                        <Label htmlFor="instance_name">
                            Instance ID / Nombre de Instancia{" "}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="instance_name"
                            placeholder="my-instance"
                            value={data.config?.instance_name || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    instance_name: e.target.value,
                                })
                            }
                        />
                        {errors["config.instance_name"] && (
                            <p className="text-sm text-red-500">
                                {errors["config.instance_name"]}
                            </p>
                        )}
                    </div>

                    {/* API Key */}
                    <div className="space-y-2">
                        <Label htmlFor="api_key">
                            API Token / Key{" "}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="api_key"
                            type="password"
                            placeholder="••••••••••••••••"
                            value={data.config?.api_key || ""}
                            onChange={(e) =>
                                setData("config", {
                                    ...data.config,
                                    api_key: e.target.value,
                                })
                            }
                        />
                        {errors["config.api_key"] && (
                            <p className="text-sm text-red-500">
                                {errors["config.api_key"]}
                            </p>
                        )}
                    </div>
                </div>

                <div className="mt-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                    <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                    <div>
                        <p className="font-medium">Tip de configuración</p>
                        <p className="text-xs mt-0.5">
                            Estos datos se obtienen de tu panel de Evolution API
                            o tu proveedor de WhatsApp Business.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
