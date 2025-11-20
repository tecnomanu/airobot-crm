import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { AlertCircle, Plus, Search, X } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getCallAgentColumns } from "./columns";

export default function CallAgentsIndex({
    agents = [],
    error,
    errorType,
    totalVersions,
    uniqueAgents,
}) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });

    const { data, setData, post, processing, errors, reset } = useForm({
        agent_name: "",
        voice_id: "",
        language: "es-ES",
        first_message: "",
        webhook_url: "",
        llm_model: "gpt-4.1",
        voice_speed: 1.0,
        voice_temperature: 0.7,
        llm_temperature: 0.7,
    });

    const handleSearch = (e) => {
        e.preventDefault();
        // Implementar búsqueda local o filtrado
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("call-agents.store"), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success("Agente creado exitosamente");
            },
            onError: () => {
                toast.error("Error al crear el agente");
            },
        });
    };

    const handleDelete = (agent) => {
        setDeleteDialog({
            open: true,
            id: agent.agent_id || agent.id,
            name: agent.agent_name || "este agente",
        });
    };

    const confirmDelete = () => {
        router.delete(route("call-agents.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Agente eliminado exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar el agente");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const subtitle = `Gestión de agentes de IA para llamadas telefónicas (Retell AI)${
        totalVersions && uniqueAgents && totalVersions > uniqueAgents
            ? ` (${uniqueAgents} agentes únicos de ${totalVersions} versiones)`
            : ""
    }`;

    return (
        <AppLayout
            header={{
                title: "Agentes de Llamadas",
                subtitle,
                actions: (
                    <Button
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={() => setIsCreateModalOpen(true)}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Agente
                    </Button>
                ),
            }}
        >
            <Head title="Agentes de Llamadas" />

            <div className="space-y-6">
                {/* Error Message */}
                {error && (
                    <Card
                        className={
                            errorType === "configuration"
                                ? "border-amber-200 bg-amber-50"
                                : "border-red-200 bg-red-50"
                        }
                    >
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-2">
                                <AlertCircle
                                    className={`h-5 w-5 mt-0.5 ${
                                        errorType === "configuration"
                                            ? "text-amber-600"
                                            : "text-red-600"
                                    }`}
                                />
                                <div className="flex-1">
                                    <p
                                        className={`font-medium ${
                                            errorType === "configuration"
                                                ? "text-amber-800"
                                                : "text-red-800"
                                        }`}
                                    >
                                        {errorType === "configuration"
                                            ? "Configuración requerida"
                                            : "Error al cargar agentes"}
                                    </p>
                                    <p
                                        className={`text-sm mt-1 ${
                                            errorType === "configuration"
                                                ? "text-amber-600"
                                                : "text-red-600"
                                        }`}
                                    >
                                        {error}
                                    </p>
                                    {errorType === "configuration" && (
                                        <div className="mt-3 space-y-2">
                                            <p className="text-xs font-medium text-amber-800">
                                                Pasos para configurar:
                                            </p>
                                            <ol className="text-xs text-amber-700 list-decimal list-inside space-y-1">
                                                <li>
                                                    Abre el archivo{" "}
                                                    <code className="bg-amber-100 px-1 rounded">
                                                        .env
                                                    </code>{" "}
                                                    en la raíz del proyecto
                                                </li>
                                                <li>
                                                    Agrega la línea:{" "}
                                                    <code className="bg-amber-100 px-1 rounded">
                                                        RETELL_API_KEY=tu_api_key_aqui
                                                    </code>
                                                </li>
                                                <li>
                                                    Obtén tu API key desde el
                                                    dashboard de Retell AI
                                                </li>
                                                <li>
                                                    Reinicia el servidor de
                                                    desarrollo
                                                </li>
                                            </ol>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <Input
                                placeholder="Buscar agente..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="flex-1"
                            />
                            <Button type="submit" size="icon" variant="outline">
                                <Search className="h-4 w-4" />
                            </Button>
                            {searchTerm && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setSearchTerm("")}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                {agents.length > 0 ? (
                    <DataTable
                        columns={getCallAgentColumns(handleDelete)}
                        data={agents}
                        filterColumn="agent_name"
                    />
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">
                                    {error
                                        ? "No se pudieron cargar los agentes"
                                        : "No hay agentes configurados"}
                                </p>
                                {!error && (
                                    <Button
                                        onClick={() =>
                                            setIsCreateModalOpen(true)
                                        }
                                        className="mt-4"
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear Primer Agente
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Create Agent Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Crear Nuevo Agente</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="agent_name">
                                        Nombre del Agente *
                                    </Label>
                                    <Input
                                        id="agent_name"
                                        value={data.agent_name}
                                        onChange={(e) =>
                                            setData(
                                                "agent_name",
                                                e.target.value
                                            )
                                        }
                                        placeholder="Ej: Agente de Ventas"
                                    />
                                    {errors.agent_name && (
                                        <p className="text-sm text-red-500">
                                            {errors.agent_name}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="voice_id">Voice ID *</Label>
                                    <Input
                                        id="voice_id"
                                        value={data.voice_id}
                                        onChange={(e) =>
                                            setData("voice_id", e.target.value)
                                        }
                                        placeholder="Ej: 11labs-Adrian"
                                    />
                                    {errors.voice_id && (
                                        <p className="text-sm text-red-500">
                                            {errors.voice_id}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="language">Idioma *</Label>
                                    <Input
                                        id="language"
                                        value={data.language}
                                        onChange={(e) =>
                                            setData("language", e.target.value)
                                        }
                                        placeholder="es-ES"
                                    />
                                    {errors.language && (
                                        <p className="text-sm text-red-500">
                                            {errors.language}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="llm_model">
                                        Modelo LLM
                                    </Label>
                                    <Input
                                        id="llm_model"
                                        value={data.llm_model}
                                        onChange={(e) =>
                                            setData("llm_model", e.target.value)
                                        }
                                        placeholder="gpt-4.1"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="first_message">
                                    Primer Mensaje
                                </Label>
                                <textarea
                                    id="first_message"
                                    value={data.first_message}
                                    onChange={(e) =>
                                        setData("first_message", e.target.value)
                                    }
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    placeholder="Mensaje inicial que dirá el agente..."
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="webhook_url">
                                    Webhook URL (opcional)
                                </Label>
                                <Input
                                    id="webhook_url"
                                    type="url"
                                    value={data.webhook_url}
                                    onChange={(e) =>
                                        setData("webhook_url", e.target.value)
                                    }
                                    placeholder="https://tu-webhook.com/endpoint"
                                />
                            </div>

                            <div className="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                                <p className="font-medium">
                                    ℹ️ Información importante
                                </p>
                                <p className="mt-1 text-xs">
                                    El agente se creará directamente en Retell
                                    AI. Asegúrate de tener configurada la
                                    variable RETELL_API_KEY.
                                </p>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Creando..." : "Crear Agente"}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) =>
                    setDeleteDialog({ ...deleteDialog, open })
                }
                onConfirm={confirmDelete}
                title="¿Eliminar agente?"
                description={`¿Estás seguro de eliminar "${deleteDialog.name}"? Esta acción no se puede deshacer y el agente se eliminará de Retell AI.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />
        </AppLayout>
    );
}
