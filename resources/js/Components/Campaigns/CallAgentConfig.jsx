import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

export default function CallAgentConfig({
    data,
    setData,
    errors,
    readOnly = false,
}) {
    const updateCallAgent = (key, value) => {
        setData("call_agent", {
            ...data.call_agent,
            [key]: value,
        });
    };

    const updateCallAgentConfig = (key, value) => {
        setData("call_agent", {
            ...data.call_agent,
            config: {
                ...data.call_agent.config,
                [key]: value,
            },
        });
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="call_agent_name">
                        Nombre del Agente
                    </Label>
                    <Input
                        id="call_agent_name"
                        value={data.call_agent.name}
                        onChange={(e) =>
                            updateCallAgent("name", e.target.value)
                        }
                        placeholder="Ej: Agent Summer"
                        disabled={readOnly}
                    />
                    {errors["call_agent.name"] && (
                        <p className="text-sm text-red-500">
                            {errors["call_agent.name"]}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="call_agent_provider">
                        Proveedor
                    </Label>
                    <Select
                        value={data.call_agent.provider}
                        onValueChange={(value) =>
                            updateCallAgent("provider", value)
                        }
                        disabled={readOnly}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona proveedor" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="retell">
                                Retell AI
                            </SelectItem>
                            <SelectItem value="vapi">
                                Vapi AI
                            </SelectItem>
                            <SelectItem value="otro">Otro</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors["call_agent.provider"] && (
                        <p className="text-sm text-red-500">
                            {errors["call_agent.provider"]}
                        </p>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label>Script/Instrucciones</Label>
                <Textarea
                    value={data.call_agent.config?.script || ""}
                    onChange={(e) =>
                        updateCallAgentConfig("script", e.target.value)
                    }
                    placeholder="Script o instrucciones para el agente..."
                    rows={4}
                    disabled={readOnly}
                />
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                    <Label>Idioma</Label>
                    <Input
                        value={data.call_agent.config?.language || ""}
                        onChange={(e) =>
                            updateCallAgentConfig(
                                "language",
                                e.target.value
                            )
                        }
                        placeholder="es"
                        disabled={readOnly}
                    />
                </div>

                <div className="space-y-2">
                    <Label>Voz</Label>
                    <Input
                        value={data.call_agent.config?.voice || ""}
                        onChange={(e) =>
                            updateCallAgentConfig(
                                "voice",
                                e.target.value
                            )
                        }
                        placeholder="female"
                        disabled={readOnly}
                    />
                </div>

                <div className="space-y-2">
                    <Label>Duración Máxima (seg)</Label>
                    <Input
                        type="number"
                        value={
                            data.call_agent.config?.max_duration || ""
                        }
                        onChange={(e) =>
                            updateCallAgentConfig(
                                "max_duration",
                                parseInt(e.target.value) || 0
                            )
                        }
                        placeholder="300"
                        disabled={readOnly}
                    />
                </div>
            </div>
        </div>
    );
}
