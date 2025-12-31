import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import SourceFormModal from "@/Components/Sources/SourceFormModal";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";
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

    const handleModalClose = (open) => {
        setIsModalOpen(open);
        if (!open) {
            setEditingSource(null);
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

            {/* Source Form Modal */}
            <SourceFormModal
                open={isModalOpen}
                onOpenChange={handleModalClose}
                source={editingSource}
                sources={sources.data || sources}
                clients={clients}
                presetType={presetType}
            />

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
