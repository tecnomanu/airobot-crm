import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, router } from "@inertiajs/react";
import { Plus, Search, X } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getCampaignColumns } from "./columns";

export default function CampaignsIndex({ campaigns, clients, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
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

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("campaigns.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
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
                                placeholder="Buscar campaña..."
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

                        <Select
                            value={filters.client_id || "all"}
                            onValueChange={(value) =>
                                handleFilterChange(
                                    "client_id",
                                    value === "all" ? "" : value
                                )
                            }
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Todos los clientes" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los clientes
                                </SelectItem>
                                {clients.map((c) => (
                                    <SelectItem key={c.id} value={c.id.toString()}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.status || "all"}
                            onValueChange={(value) =>
                                handleFilterChange(
                                    "status",
                                    value === "all" ? "" : value
                                )
                            }
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">Activa</SelectItem>
                                <SelectItem value="paused">Pausada</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            variant="outline"
                            onClick={handleClearFilters}
                            size="sm"
                        >
                            <X className="mr-2 h-4 w-4" />
                            Limpiar
                        </Button>
                    </div>
                </div>

                {/* Table with padding */}
                <div className="px-6 pb-6">
                    <DataTable
                        columns={getCampaignColumns(handleDelete, handleToggleStatus)}
                        data={campaigns.data}
                        filterColumn="name"
                    />
                </div>
            </div>



            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={(open) => setDeleteDialog({ ...deleteDialog, open })}
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
                onOpenChange={(open) => setToggleDialog({ ...toggleDialog, open })}
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
                confirmText={toggleDialog.newStatus === "active" ? "Activar" : "Pausar"}
                cancelText="Cancelar"
            />
        </AppLayout>
    );
}
