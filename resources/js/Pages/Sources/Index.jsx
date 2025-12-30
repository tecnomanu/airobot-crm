import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import SourceFormWebhook from "@/Components/Sources/SourceFormWebhook";
import SourceFormWhatsApp from "@/Components/Sources/SourceFormWhatsApp";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getSourceColumns } from "./columns";

export default function SourcesIndex({
    sources,
    clients,
    filters,
    presetType = null,
}) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingSource, setEditingSource] = useState(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [toggleDialog, setToggleDialog] = useState({
        open: false,
        source: null,
        newStatus: null,
    });

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        type: presetType || "",
        status: "active",
        client_id: "",
        config: {},
    });

    // Resetear formulario cuando se abre/cierra modal
    useEffect(() => {
        if (!isModalOpen) {
            setEditingSource(null);
            reset();
            if (presetType) {
                setData("type", presetType);
            }
        }
    }, [isModalOpen]);

    // Cargar datos al editar
    useEffect(() => {
        if (editingSource) {
            setData({
                name: editingSource.name || "",
                type: editingSource.type || "",
                status: editingSource.status || "active",
                client_id: editingSource.client_id?.toString() || "",
                config: editingSource.config || {},
            });
        }
    }, [editingSource]);

    // Inicializar tipo si es preset
    useEffect(() => {
        if (presetType && !editingSource) {
            setData("type", presetType);
        }
    }, [presetType, editingSource]);

    const handleFilterChange = (name, value) => {
        router.get(
            route("sources.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("sources.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("sources.index"), {}, { preserveState: true });
    };

    const handleCreate = () => {
        setEditingSource(null);
        setIsModalOpen(true);
    };

    const handleEdit = (source) => {
        setEditingSource(source);
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Validate for duplicates
        const isDuplicate = sources.data?.some((source) => {
            if (editingSource && source.id === editingSource.id) return false; // Skip self when editing

            if (data.type === "whatsapp" && source.type === "whatsapp") {
                const existingPhone = source.config?.phone_number;
                const newPhone = data.config?.phone_number;
                return existingPhone && newPhone && existingPhone === newPhone;
            }

            if (data.type === "webhook" && source.type === "webhook") {
                const existingUrl = source.config?.url;
                const newUrl = data.config?.url;
                return existingUrl && newUrl && existingUrl === newUrl;
            }

            return false;
        });

        if (isDuplicate) {
            toast.error(
                "Ya existe una fuente con este valor. No se pueden crear duplicados."
            );
            return;
        }

        if (editingSource) {
            put(route("sources.update", editingSource.id), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    toast.success("Fuente actualizada exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar la fuente");
                },
            });
        } else {
            post(route("sources.store"), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    toast.success("Fuente creada exitosamente");
                },
                onError: () => {
                    toast.error("Error al crear la fuente");
                },
            });
        }
    };

    const handleDelete = (source) => {
        setDeleteDialog({
            open: true,
            id: source.id,
            name: source.name,
        });
    };

    const confirmDelete = () => {
        router.delete(route("sources.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Fuente eliminada exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar la fuente");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    // ... (existing form hook) ...

    // ... (existing useEffects) ...

    // ... (existing filter handlers) ...

    // ... (existing modal handlers) ...

    // ... (existing delete handlers) ...

    const handleToggleStatus = (source) => {
        const newStatus = source.status === "active" ? "inactive" : "active";
        setToggleDialog({
            open: true,
            source: source,
            newStatus: newStatus,
        });
    };

    const confirmToggleStatus = () => {
        router.put(
            route("sources.toggle-status", toggleDialog.source.id),
            { status: toggleDialog.newStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const action =
                        toggleDialog.newStatus === "active"
                            ? "activada"
                            : "desactivada";
                    toast.success(`Fuente ${action} exitosamente`);
                },
                onError: () => {
                    toast.error("Error al cambiar el estado de la fuente");
                },
            }
        );
        setToggleDialog({ open: false, source: null, newStatus: null });
    };

    const getDialogTitle = () => {
        if (editingSource) return "Editar Fuente";
        if (presetType === "whatsapp") return "Crear Fuente de WhatsApp";
        if (presetType === "webhook") return "Crear Fuente de Webhook";
        return "Crear Nueva Fuente";
    };

    const getDialogDescription = () => {
        if (editingSource)
            return "Modifica los datos de esta fuente existente.";
        if (presetType === "whatsapp")
            return "Configura una nueva fuente de WhatsApp para enviar mensajes a tus leads.";
        if (presetType === "webhook")
            return "Configura una nueva fuente de webhook para integrar con sistemas externos.";
        return "Completa los datos para crear una nueva fuente reutilizable.";
    };

    return (
        <AppLayout
            header={{
                title: "Fuentes",
                subtitle: "Gestión de fuentes de captura de leads",
                actions: (
                    <Button
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={handleCreate}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nueva Fuente
                    </Button>
                ),
            }}
        >
            <Head title="Fuentes" />

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div className="p-6">
                    {/* Table with Actions (Filters) */}
                    <DataTable
                        columns={getSourceColumns(
                            handleEdit,
                            handleDelete,
                            handleToggleStatus
                        )}
                        data={sources.data || sources}
                        actions={
                            <DataTableFilters
                                filters={[
                                    {
                                        name: "search",
                                        type: "search",
                                        placeholder: "Buscar fuente...",
                                    },
                                    {
                                        name: "type",
                                        type: "select",
                                        placeholder: "Todos los tipos",
                                        options: [
                                            {
                                                value: "whatsapp",
                                                label: "WhatsApp",
                                            },
                                            {
                                                value: "webhook",
                                                label: "Webhook",
                                            },
                                            {
                                                value: "meta_whatsapp",
                                                label: "WhatsApp Business",
                                            },
                                            {
                                                value: "facebook_lead_ads",
                                                label: "Facebook Lead Ads",
                                            },
                                            {
                                                value: "google_ads",
                                                label: "Google Ads",
                                            },
                                        ],
                                    },
                                    {
                                        name: "status",
                                        type: "select",
                                        placeholder: "Todos los estados",
                                        options: [
                                            {
                                                value: "active",
                                                label: "Activo",
                                            },
                                            {
                                                value: "inactive",
                                                label: "Inactivo",
                                            },
                                            { value: "error", label: "Error" },
                                            {
                                                value: "pending_setup",
                                                label: "Pendiente configuración",
                                            },
                                        ],
                                    },
                                ]}
                                values={{
                                    search: searchTerm,
                                    type: filters.type,
                                    status: filters.status,
                                }}
                                onChange={(name, value) => {
                                    if (name === "search") {
                                        setSearchTerm(value);
                                    } else {
                                        handleFilterChange(name, value);
                                    }
                                }}
                                onSearch={handleSearch}
                                onClear={handleClearFilters}
                            />
                        }
                    />
                </div>
            </div>

            {/* Create/Edit Source Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{getDialogTitle()}</DialogTitle>
                        <DialogDescription>
                            {getDialogDescription()}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Selector de tipo (solo si no es preset) */}
                        {!presetType && (
                            <div className="space-y-2">
                                <Label htmlFor="type">
                                    Tipo de Fuente{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData("type", value)
                                    }
                                    disabled={!!editingSource}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="whatsapp">
                                            WhatsApp (Evolution API)
                                        </SelectItem>
                                        <SelectItem value="webhook">
                                            Webhook HTTP
                                        </SelectItem>
                                        <SelectItem value="meta_whatsapp">
                                            WhatsApp Business (Meta)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && (
                                    <p className="text-sm text-red-500">
                                        {errors.type}
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Formulario específico según tipo */}
                        {data.type === "whatsapp" && (
                            <SourceFormWhatsApp
                                data={data}
                                setData={setData}
                                errors={errors}
                                clients={clients}
                            />
                        )}

                        {data.type === "webhook" && (
                            <SourceFormWebhook
                                data={data}
                                setData={setData}
                                errors={errors}
                                clients={clients}
                            />
                        )}

                        {/* Mensaje si no hay tipo seleccionado */}
                        {!data.type && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                                <p className="font-medium">
                                    Selecciona un tipo de fuente
                                </p>
                                <p className="text-xs mt-1">
                                    Primero selecciona el tipo de fuente que
                                    deseas configurar.
                                </p>
                            </div>
                        )}

                        {/* Tipos no implementados */}
                        {data.type &&
                            !["whatsapp", "webhook"].includes(data.type) && (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    <p className="font-medium">
                                        Configuración en desarrollo
                                    </p>
                                    <p className="text-xs mt-1">
                                        La configuración para este tipo de
                                        fuente estará disponible próximamente.
                                    </p>
                                </div>
                            )}

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing || !data.type}
                            >
                                {processing
                                    ? editingSource
                                        ? "Actualizando..."
                                        : "Creando..."
                                    : editingSource
                                    ? "Actualizar Fuente"
                                    : "Crear Fuente"}
                            </Button>
                        </DialogFooter>
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
                title="¿Eliminar fuente?"
                description={`¿Estás seguro de eliminar la fuente "${deleteDialog.name}"? Las campañas asociadas perderán esta fuente. Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />

            {/* Confirm Toggle Status Dialog */}
            <ConfirmDialog
                open={toggleDialog.open}
                onOpenChange={(open) =>
                    setToggleDialog({ ...toggleDialog, open })
                }
                onConfirm={confirmToggleStatus}
                title={
                    toggleDialog.newStatus === "active"
                        ? "¿Activar fuente?"
                        : "¿Pausar fuente?"
                }
                description={
                    toggleDialog.newStatus === "active"
                        ? `¿Estás seguro de activar la fuente "${toggleDialog.source?.name}"? Estará disponible para su uso en campañas.`
                        : `¿Estás seguro de pausar la fuente "${toggleDialog.source?.name}"? No se podrá utilizar en nuevas campañas hasta que se reactive.`
                }
                confirmText={
                    toggleDialog.newStatus === "active" ? "Activar" : "Pausar"
                }
                cancelText="Cancelar"
            />
        </AppLayout>
    );
}
