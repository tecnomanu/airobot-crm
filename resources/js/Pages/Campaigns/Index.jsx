import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, router } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getCampaignColumns } from "./columns";

export default function CampaignsIndex({ campaigns, clients, filters }) {
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [toggleDialog, setToggleDialog] = useState({
        open: false,
        campaign: null,
        newStatus: null,
    });

    const handleFilterChange = (name, value) => {
        router.get(
            route("campaigns.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        router.get(route("campaigns.index"), {}, { preserveState: true });
    };

    const handleDelete = (campaign) => {
        setDeleteDialog({
            open: true,
            id: campaign.id,
            name: campaign.name,
        });
    };

    const confirmDelete = () => {
        router.delete(route("campaigns.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Campaña eliminada exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar la campaña");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const handleToggleStatus = (campaign) => {
        const newStatus = campaign.status === "active" ? "paused" : "active";
        setToggleDialog({
            open: true,
            campaign: campaign,
            newStatus: newStatus,
        });
    };

    const confirmToggleStatus = () => {
        router.patch(
            route("campaigns.toggle-status", toggleDialog.campaign.id),
            {},
            {
                onSuccess: () => {
                    const action =
                        toggleDialog.newStatus === "active"
                            ? "activada"
                            : "pausada";
                    toast.success(`Campaña ${action} exitosamente`);
                },
                onError: () => {
                    toast.error("Error al cambiar el estado de la campaña");
                },
            }
        );
        setToggleDialog({ open: false, campaign: null, newStatus: null });
    };

    return (
        <AppLayout
            header={{
                title: "Campañas",
                subtitle: "Gestión de campañas de marketing",
                actions: (
                    <Link href={route("campaigns.create")}>
                        <Button
                            size="sm"
                            className="h-8 text-xs px-3 bg-indigo-600 hover:bg-indigo-700"
                        >
                            <Plus className="h-3.5 w-3.5 mr-1.5" />
                            Nueva Campaña
                        </Button>
                    </Link>
                ),
            }}
        >
            <Head title="Campañas" />

            {/* Main Card Container */}
            <div className="bg-white rounded-xl shadow-sm">
                {/* Header Section with padding */}
                <div className="p-6">
                    <DataTable
                        columns={getCampaignColumns(
                            handleDelete,
                            handleToggleStatus
                        )}
                        data={campaigns.data}
                        actions={
                            <DataTableFilters
                                filters={[
                                    {
                                        type: "search",
                                        name: "search", // Changed from key to name to match others
                                        placeholder: "Buscar campaña...",
                                    },
                                    {
                                        type: "select",
                                        name: "client_id",
                                        placeholder: "Todos los clientes",
                                        allLabel: "Todos los clientes",
                                        options: clients,
                                    },
                                    {
                                        type: "select",
                                        name: "status",
                                        placeholder: "Todos los estados",
                                        allLabel: "Todos",
                                        className: "w-[160px]",
                                        options: [
                                            {
                                                value: "active",
                                                label: "Activa",
                                            },
                                            {
                                                value: "paused",
                                                label: "Pausada",
                                            },
                                        ],
                                    },
                                ]}
                                values={filters}
                                onChange={handleFilterChange}
                                onClear={handleClearFilters}
                            />
                        }
                    />
                </div>
            </div>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) =>
                    setDeleteDialog({ ...deleteDialog, open })
                }
                onConfirm={confirmDelete}
                title="¿Eliminar campaña?"
                description={`¿Estás seguro de eliminar la campaña "${deleteDialog.name}"? Se eliminarán todos los leads asociados. Esta acción no se puede deshacer.`}
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
                        ? "¿Activar campaña?"
                        : "¿Pausar campaña?"
                }
                description={
                    toggleDialog.newStatus === "active"
                        ? `¿Estás seguro de activar la campaña "${toggleDialog.campaign?.name}"? Los leads comenzarán a procesarse automáticamente.`
                        : `¿Estás seguro de pausar la campaña "${toggleDialog.campaign?.name}"? No se procesarán nuevos leads hasta que la reactives.`
                }
                confirmText={
                    toggleDialog.newStatus === "active" ? "Activar" : "Pausar"
                }
                cancelText="Cancelar"
            />
        </AppLayout>
    );
}
