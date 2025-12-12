import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Badge } from "@/components/ui/badge";
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus, Search, X, Zap, GitBranch, Info } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { getCampaignColumns } from "./columns";

export default function CampaignsIndex({ campaigns, clients, filters }) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
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

    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        client_id: "",
        description: "",
        status: "active",
        strategy_type: "dynamic", // default to dynamic (IVR/multiple options)
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

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("campaigns.store"), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success(
                    "Campaña creada exitosamente. Ahora puedes configurar la acción desde la vista de detalle."
                );
            },
            onError: () => {
                toast.error("Error al crear la campaña");
            },
        });
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
                    <Button
                        size="sm"
                        className="h-8 text-xs px-3 bg-indigo-600 hover:bg-indigo-700"
                        onClick={() => setIsCreateModalOpen(true)}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nueva Campaña
                    </Button>
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

            {/* Create Campaign Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="text-lg">Crear Nueva Campaña</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Basic Info */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name" className="text-sm">Nombre *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData("name", e.target.value)}
                                    placeholder="Ej: Campaña Verano 2024"
                                    className="h-9"
                                />
                                {errors.name && (
                                    <p className="text-xs text-red-500">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="client_id" className="text-sm">Cliente *</Label>
                                <Select
                                    value={data.client_id}
                                    onValueChange={(value) => setData("client_id", value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Seleccionar cliente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {clients.map((c) => (
                                            <SelectItem key={c.id} value={c.id.toString()}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.client_id && (
                                    <p className="text-xs text-red-500">{errors.client_id}</p>
                                )}
                            </div>
                        </div>

                        {/* Strategy Type Selection */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium">Tipo de Campaña *</Label>
                            <div className="grid grid-cols-2 gap-3">
                                {/* Direct Strategy */}
                                <button
                                    type="button"
                                    onClick={() => setData("strategy_type", "direct")}
                                    className={`relative p-4 rounded-lg border-2 text-left transition-all ${
                                        data.strategy_type === "direct"
                                            ? "border-green-500 bg-green-50"
                                            : "border-gray-200 hover:border-gray-300 bg-white"
                                    }`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className={`p-2 rounded-lg ${
                                            data.strategy_type === "direct"
                                                ? "bg-green-100"
                                                : "bg-gray-100"
                                        }`}>
                                            <Zap className={`h-5 w-5 ${
                                                data.strategy_type === "direct"
                                                    ? "text-green-600"
                                                    : "text-gray-500"
                                            }`} />
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-medium text-sm text-gray-900">
                                                Directa
                                            </p>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                Una sola acción para todos los leads
                                            </p>
                                        </div>
                                    </div>
                                    {data.strategy_type === "direct" && (
                                        <Badge className="absolute top-2 right-2 bg-green-600 text-[10px]">
                                            Seleccionado
                                        </Badge>
                                    )}
                                </button>

                                {/* Dynamic Strategy (IVR/Multiple) */}
                                <button
                                    type="button"
                                    onClick={() => setData("strategy_type", "dynamic")}
                                    className={`relative p-4 rounded-lg border-2 text-left transition-all ${
                                        data.strategy_type === "dynamic"
                                            ? "border-blue-500 bg-blue-50"
                                            : "border-gray-200 hover:border-gray-300 bg-white"
                                    }`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className={`p-2 rounded-lg ${
                                            data.strategy_type === "dynamic"
                                                ? "bg-blue-100"
                                                : "bg-gray-100"
                                        }`}>
                                            <GitBranch className={`h-5 w-5 ${
                                                data.strategy_type === "dynamic"
                                                    ? "text-blue-600"
                                                    : "text-gray-500"
                                            }`} />
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-medium text-sm text-gray-900">
                                                Múltiple (IVR)
                                            </p>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                Acciones según opción 1, 2, 0, i...
                                            </p>
                                        </div>
                                    </div>
                                    {data.strategy_type === "dynamic" && (
                                        <Badge className="absolute top-2 right-2 bg-blue-600 text-[10px]">
                                            Seleccionado
                                        </Badge>
                                    )}
                                </button>
                            </div>

                            {/* Info box based on selection */}
                            <div className={`rounded-lg p-3 text-xs flex items-start gap-2 ${
                                data.strategy_type === "direct"
                                    ? "bg-green-50 text-green-800"
                                    : "bg-blue-50 text-blue-800"
                            }`}>
                                <Info className="h-4 w-4 flex-shrink-0 mt-0.5" />
                                <div>
                                    {data.strategy_type === "direct" ? (
                                        <>
                                            <p className="font-medium">Campaña Directa</p>
                                            <p className="mt-0.5 text-green-700">
                                                Ideal para CSV, listas manuales. Al activar un lead, 
                                                se ejecutará automáticamente la acción configurada 
                                                (llamada, WhatsApp, etc).
                                            </p>
                                        </>
                                    ) : (
                                        <>
                                            <p className="font-medium">Campaña Múltiple (IVR)</p>
                                            <p className="mt-0.5 text-blue-700">
                                                Ideal para IVR y formularios. Cada opción (1, 2, 0, i) 
                                                puede tener una acción diferente. El sistema espera 
                                                la opción seleccionada antes de ejecutar.
                                            </p>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Status */}
                        <div className="space-y-1.5">
                            <Label htmlFor="status" className="text-sm">Estado Inicial</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) => setData("status", value)}
                            >
                                <SelectTrigger className="h-9 w-[200px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Activa</SelectItem>
                                    <SelectItem value="paused">Pausada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label htmlFor="description" className="text-sm">Descripción</Label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData("description", e.target.value)}
                                rows={2}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring resize-none"
                                placeholder="Describe el objetivo de esta campaña..."
                            />
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
                                {processing ? "Creando..." : "Crear Campaña"}
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
