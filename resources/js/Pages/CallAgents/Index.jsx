import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/components/ui/button";
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
import { AlertCircle, Plus, Search, X, Bot } from "lucide-react";
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
        // Local filtering handled by DataTable
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
                        className="h-8 text-xs px-3 bg-indigo-600 hover:bg-indigo-700"
                        onClick={() => setIsCreateModalOpen(true)}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Agente
                    </Button>
                ),
            }}
        >
            <Head title="Agentes de Llamadas" />

            <div className="space-y-4">
                {/* Error Message */}
                {error && (
                    <div
                        className={`rounded-xl border p-4 ${
                            errorType === "configuration"
                                ? "border-amber-200 bg-amber-50"
                                : "border-red-200 bg-red-50"
                        }`}
                    >
                        <div className="flex items-start gap-3">
                            <AlertCircle
                                className={`h-5 w-5 mt-0.5 flex-shrink-0 ${
                                    errorType === "configuration"
                                        ? "text-amber-600"
                                        : "text-red-600"
                                }`}
                            />
                            <div className="flex-1">
                                <p
                                    className={`font-medium text-sm ${
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
                                                Obtén tu API key desde el dashboard de
                                                Retell AI
                                            </li>
                                            <li>
                                                Reinicia el servidor de desarrollo
                                            </li>
                                        </ol>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Main Card Container */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                    {/* Header Section with padding */}
                    <div className="p-6 space-y-4">
                        {/* Filters Row */}
                        <div className="flex flex-wrap items-center gap-3">
                            <form
                                onSubmit={handleSearch}
                                className="flex-1 max-w-md relative"
                            >
                                <Input
                                    placeholder="Buscar agente..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pl-4 pr-10 py-2.5 bg-indigo-50 border-0 rounded-lg"
                                />
                                <Button
                                    type="submit"
                                    size="icon"
                                    variant="ghost"
                                    className="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8"
                                >
                                    <Search className="h-4 w-4 text-gray-400" />
                                </Button>
                            </form>

                            {searchTerm && (
                                <Button
                                    variant="outline"
                                    onClick={() => setSearchTerm("")}
                                    size="sm"
                                >
                                    <X className="mr-2 h-4 w-4" />
                                    Limpiar
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Table or Empty State */}
                    <div className="px-6 pb-6">
                        {agents.length > 0 ? (
                            <DataTable
                                columns={getCallAgentColumns(handleDelete)}
                                data={agents}
                                filterColumn="agent_name"
                            />
                        ) : (
                            <div className="text-center py-12 border rounded-lg bg-gray-50">
                                <Bot className="h-12 w-12 mx-auto text-gray-300 mb-4" />
                                <p className="text-gray-500 font-medium">
                                    {error
                                        ? "No se pudieron cargar los agentes"
                                        : "No hay agentes configurados"}
                                </p>
                                <p className="text-sm text-gray-400 mt-1">
                                    {!error && "Crea tu primer agente para comenzar"}
                                </p>
                                {!error && (
                                    <Button
                                        onClick={() => setIsCreateModalOpen(true)}
                                        className="mt-4 bg-indigo-600 hover:bg-indigo-700"
                                        size="sm"
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear Primer Agente
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Create Agent Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-lg">Crear Nuevo Agente</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="agent_name" className="text-sm">
                                    Nombre del Agente *
                                </Label>
                                <Input
                                    id="agent_name"
                                    value={data.agent_name}
                                    onChange={(e) => setData("agent_name", e.target.value)}
                                    placeholder="Ej: Agente de Ventas"
                                    className="h-9"
                                />
                                {errors.agent_name && (
                                    <p className="text-xs text-red-500">
                                        {errors.agent_name}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="voice_id" className="text-sm">
                                    Voice ID *
                                </Label>
                                <Input
                                    id="voice_id"
                                    value={data.voice_id}
                                    onChange={(e) => setData("voice_id", e.target.value)}
                                    placeholder="Ej: 11labs-Adrian"
                                    className="h-9"
                                />
                                {errors.voice_id && (
                                    <p className="text-xs text-red-500">
                                        {errors.voice_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="language" className="text-sm">
                                    Idioma *
                                </Label>
                                <Input
                                    id="language"
                                    value={data.language}
                                    onChange={(e) => setData("language", e.target.value)}
                                    placeholder="es-ES"
                                    className="h-9"
                                />
                                {errors.language && (
                                    <p className="text-xs text-red-500">
                                        {errors.language}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="llm_model" className="text-sm">
                                    Modelo LLM
                                </Label>
                                <Input
                                    id="llm_model"
                                    value={data.llm_model}
                                    onChange={(e) => setData("llm_model", e.target.value)}
                                    placeholder="gpt-4.1"
                                    className="h-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="first_message" className="text-sm">
                                Primer Mensaje
                            </Label>
                            <textarea
                                id="first_message"
                                value={data.first_message}
                                onChange={(e) => setData("first_message", e.target.value)}
                                rows={2}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring resize-none"
                                placeholder="Mensaje inicial que dirá el agente..."
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="webhook_url" className="text-sm">
                                Webhook URL (opcional)
                            </Label>
                            <Input
                                id="webhook_url"
                                type="url"
                                value={data.webhook_url}
                                onChange={(e) => setData("webhook_url", e.target.value)}
                                placeholder="https://tu-webhook.com/endpoint"
                                className="h-9"
                            />
                        </div>

                        <div className="rounded-lg bg-blue-50 p-3 text-xs text-blue-800 flex items-start gap-2">
                            <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-medium">Información importante</p>
                                <p className="mt-0.5 text-blue-700">
                                    El agente se creará directamente en Retell AI.
                                    Asegúrate de tener configurada la variable RETELL_API_KEY.
                                </p>
                            </div>
                        </div>

                        <div className="flex justify-end gap-2 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                className="bg-indigo-600 hover:bg-indigo-700"
                            >
                                {processing ? "Creando..." : "Crear Agente"}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) => setDeleteDialog({ ...deleteDialog, open })}
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
