import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { Edit } from "lucide-react";

export default function FunctionsTab({
    data,
    setData,
    onAddCustomFunction,
    onEditCustomFunction,
    onDeleteCustomFunction,
}) {
    return (
        <div className="space-y-2">
            <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                        <Label className="text-xs">End Call</Label>
                        <p className="text-xs text-muted-foreground">
                            Función por defecto para terminar llamadas
                        </p>
                    </div>
                    <Switch
                        checked={data.end_call_enabled}
                        onCheckedChange={(checked) => {
                            setData("end_call_enabled", checked);
                            // Actualizar el array de functions
                            const currentFunctions = data.functions || [];
                            if (checked) {
                                // Agregar end_call si no existe
                                const hasEndCall = currentFunctions.some(
                                    (f) =>
                                        f.type === "end_call" ||
                                        f.name === "end_call"
                                );
                                if (!hasEndCall) {
                                    setData("functions", [
                                        ...currentFunctions,
                                        { type: "end_call", name: "end_call" },
                                    ]);
                                }
                            } else {
                                // Remover end_call
                                setData(
                                    "functions",
                                    currentFunctions.filter(
                                        (f) =>
                                            f.type !== "end_call" &&
                                            f.name !== "end_call"
                                    )
                                );
                            }
                        }}
                    />
                </div>
            </div>

            <Separator />

            <div className="space-y-1.5">
                <Label className="text-xs">Custom Functions</Label>
                <div className="space-y-1.5">
                    {data.custom_functions?.map((func, idx) => (
                        <div
                            key={idx}
                            className="flex items-center gap-2 p-1.5 bg-muted rounded text-xs"
                        >
                            <span className="flex-1">
                                {func.name || "Custom Function"}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-5 w-5 p-0"
                                onClick={() => onEditCustomFunction(idx)}
                                title="Editar"
                            >
                                <Edit className="h-3 w-3" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-5 w-5 p-0"
                                onClick={() => onDeleteCustomFunction(idx)}
                                title="Eliminar"
                            >
                                ×
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-full h-7 text-xs"
                        onClick={onAddCustomFunction}
                    >
                        + Agregar Custom Function
                    </Button>
                </div>
            </div>

            <Separator />

            <div className="space-y-1.5">
                <Label className="text-xs text-muted-foreground">
                    Coming Soon
                </Label>
                <div className="space-y-1 text-xs text-muted-foreground">
                    <div>• Call Transfer</div>
                    <div>• Agent Transfer</div>
                    <div>• Calendar Booking</div>
                    <div>• Send SMS</div>
                </div>
            </div>
        </div>
    );
}

