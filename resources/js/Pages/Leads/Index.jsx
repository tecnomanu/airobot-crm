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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import {
    hasNotificationPermission,
    notifyLeadUpdated,
    notifyNewLead,
} from "@/lib/notifications";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus, RefreshCw, Search, X } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getLeadColumns } from "./columns";

export default function LeadsIndex({ leads, campaigns, filters }) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [retryDialog, setRetryDialog] = useState({
        open: false,
        id: null,
        name: "",
    });
    const [isRetryingBatch, setIsRetryingBatch] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        phone: "",
        name: "",
        city: "",
        campaign_id: "",
        option_selected: "",
        notes: "",
    });

    const handleFilterChange = (name, value) => {
        router.get(
            route("leads.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("leads.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("leads.index"), {}, { preserveState: true });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("leads.store"), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success("Lead creado exitosamente");
            },
            onError: () => {
                toast.error("Error al crear el lead");
            },
        });
    };

    const handleDelete = (lead) => {
        setDeleteDialog({
            open: true,
            id: lead.id,
            name: lead.name || lead.phone,
        });
    };

    const confirmDelete = () => {
        router.delete(route("leads.destroy", deleteDialog.id), {
            onSuccess: () => {
                toast.success("Lead eliminado exitosamente");
            },
            onError: () => {
                toast.error("Error al eliminar el lead");
            },
        });
        setDeleteDialog({ open: false, id: null, name: "" });
    };

    const handleRetry = (lead) => {
        setRetryDialog({
            open: true,
            id: lead.id,
            name: lead.name || lead.phone,
        });
    };

    const confirmRetry = () => {
        router.post(
            route("leads.retry-automation", retryDialog.id),
            {},
            {
                onSuccess: () => {
                    toast.success("Procesamiento reiniciado exitosamente");
                },
                onError: () => {
                    toast.error("Error al reiniciar procesamiento");
                },
            }
        );
        setRetryDialog({ open: false, id: null, name: "" });
    };

    const handleRetryBatch = () => {
        setIsRetryingBatch(true);
        router.post(
            route("leads.retry-automation-batch"),
            { ...filters },
            {
                onSuccess: () => {
                    toast.success("Procesamiento masivo completado");
                    setIsRetryingBatch(false);
                },
                onError: () => {
                    toast.error("Error en procesamiento masivo");
                    setIsRetryingBatch(false);
                },
            }
        );
    };

    /**
     * Escucha eventos de WebSocket para actualizaciones en tiempo real
     * Cuando llega un nuevo lead o se actualiza uno existente,
     * recarga la página solo si estamos en la primera página o si el lead es visible
     */
    useEffect(() => {
        const channel = window.Echo.channel("leads");

        channel.listen(".lead.updated", (event) => {
            const { lead, action } = event;

            console.log("Evento de lead recibido:", { action, lead });

            // Determinar si debemos recargar
            const shouldReload = () => {
                // Si es un lead nuevo, solo recargar si estamos en la primera página sin filtros
                if (action === "created") {
                    const isFirstPage = !filters.page || filters.page === 1;
                    const hasNoFilters =
                        !filters.search &&
                        !filters.status &&
                        !filters.campaign_id;
                    return isFirstPage && hasNoFilters;
                }

                // Si es una actualización, verificar si el lead está visible en la página actual
                if (action === "updated") {
                    const isVisible = leads.data.some((l) => l.id === lead.id);
                    return isVisible;
                }

                return false;
            };

            if (shouldReload()) {
                // Recargar preservando el estado actual de la página
                router.reload({
                    preserveState: true,
                    preserveScroll: true,
                    only: ["leads"],
                    onSuccess: () => {
                        // Toast para feedback inmediato
                        if (action === "created") {
                            toast.success(
                                `Nuevo lead: ${lead.name || lead.phone}`
                            );
                        } else {
                            toast.info(
                                `Lead actualizado: ${lead.name || lead.phone}`
                            );
                        }
                    },
                });
            }

            // Notificaciones nativas del navegador (si están habilitadas)
            if (hasNotificationPermission()) {
                if (action === "created") {
                    notifyNewLead(lead);
                } else if (action === "updated") {
                    notifyLeadUpdated(lead);
                }
            }
        });

        // Cleanup al desmontar
        return () => {
            channel.stopListening(".lead.updated");
        };
    }, [leads.data, filters]);

    return (
        <AppLayout
            header={{
                title: "Leads",
                subtitle: "Gestión de leads y contactos",
                actions: [
                    <Button
                        key="retry"
                        variant="outline"
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={handleRetryBatch}
                        disabled={isRetryingBatch}
                    >
                        <RefreshCw
                            className={`h-3.5 w-3.5 mr-1.5 ${
                                isRetryingBatch ? "animate-spin" : ""
                            }`}
                        />
                        Reintentar Fallidos
                    </Button>,
                    <Button
                        key="create"
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={() => setIsCreateModalOpen(true)}
                    >
                        <Plus className="h-3.5 w-3.5 mr-1.5" />
                        Nuevo Lead
                    </Button>,
                ],
            }}
        >
            <Head title="Leads" />

            <div className="space-y-6">
                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-4">
                            <form
                                onSubmit={handleSearch}
                                className="flex gap-2"
                            >
                                <Input
                                    placeholder="Buscar teléfono o nombre..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                />
                                <Button
                                    type="submit"
                                    size="icon"
                                    variant="outline"
                                >
                                    <Search className="h-4 w-4" />
                                </Button>
                            </form>

                            <Select
                                value={filters.campaign_id || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        "campaign_id",
                                        value === "all" ? "" : value
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todas las campañas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todas las campañas
                                    </SelectItem>
                                    {campaigns.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={c.id.toString()}
                                        >
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
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los estados
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        Pendiente
                                    </SelectItem>
                                    <SelectItem value="in_progress">
                                        En Progreso
                                    </SelectItem>
                                    <SelectItem value="contacted">
                                        Contactado
                                    </SelectItem>
                                    <SelectItem value="closed">
                                        Cerrado
                                    </SelectItem>
                                    <SelectItem value="invalid">
                                        Inválido
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                                className="w-full"
                            >
                                <X className="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <DataTable
                    columns={getLeadColumns(handleDelete, handleRetry)}
                    data={leads.data}
                    filterColumn="phone"
                />
            </div>

            {/* Create Lead Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Crear Nuevo Lead</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="phone">Teléfono *</Label>
                                <Input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData("phone", e.target.value)
                                    }
                                    placeholder="+34600111222"
                                />
                                {errors.phone && (
                                    <p className="text-sm text-red-500">
                                        {errors.phone}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData("name", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="city">Ciudad</Label>
                            <Input
                                id="city"
                                type="text"
                                value={data.city}
                                onChange={(e) =>
                                    setData("city", e.target.value)
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="campaign_id">Campaña *</Label>
                            <Select
                                value={data.campaign_id}
                                onValueChange={(value) =>
                                    setData("campaign_id", value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar campaña" />
                                </SelectTrigger>
                                <SelectContent>
                                    {campaigns.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={c.id.toString()}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.campaign_id && (
                                <p className="text-sm text-red-500">
                                    {errors.campaign_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="option_selected">
                                Opción Seleccionada
                            </Label>
                            <Select
                                value={data.option_selected}
                                onValueChange={(value) =>
                                    setData("option_selected", value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar opción" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Opción 1</SelectItem>
                                    <SelectItem value="2">Opción 2</SelectItem>
                                    <SelectItem value="i">
                                        Opción I (Información)
                                    </SelectItem>
                                    <SelectItem value="t">
                                        Opción T (Transferir)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">Notas</Label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData("notes", e.target.value)
                                }
                                rows={3}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                        </div>

                        <div className="flex justify-end gap-3 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Creando..." : "Crear Lead"}
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
                title="¿Eliminar lead?"
                description={`¿Estás seguro de eliminar el lead "${deleteDialog.name}"? Esta acción no se puede deshacer.`}
                confirmText="Eliminar"
                cancelText="Cancelar"
                variant="destructive"
            />

            {/* Confirm Retry Dialog */}
            <ConfirmDialog
                open={retryDialog.open}
                onOpenChange={(open) =>
                    setRetryDialog({ ...retryDialog, open })
                }
                onConfirm={confirmRetry}
                title="¿Reintentar procesamiento?"
                description={`¿Deseas reintentar el procesamiento automático del lead "${retryDialog.name}"?`}
                confirmText="Reintentar"
                cancelText="Cancelar"
            />
        </AppLayout>
    );
}
