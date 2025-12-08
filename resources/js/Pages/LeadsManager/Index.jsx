import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { Badge } from "@/components/ui/badge";
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
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import {
    hasNotificationPermission,
    notifyLeadUpdated,
    notifyNewLead,
    notifyLeadDeleted,
} from "@/lib/notifications";
import { Head, router, useForm } from "@inertiajs/react";
import { Plus, RefreshCw, Search, X, MessageSquare, Phone } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getLeadsManagerColumns } from "./columns";

export default function LeadsManagerIndex({
    leads,
    campaigns,
    clients,
    filters,
    activeTab,
    tabCounts,
}) {
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
        country: "",
        campaign_id: "",
        client_id: "",
        option_selected: "",
        notes: "",
    });

    // Tab configuration
    const tabs = [
        {
            value: "inbox",
            label: "Inbox / Raw",
            count: tabCounts.inbox,
            description: "Nuevos leads sin procesar",
        },
        {
            value: "active",
            label: "Active Pipeline",
            count: tabCounts.active,
            description: "Leads en proceso de automatización",
        },
        {
            value: "sales_ready",
            label: "Sales Ready",
            count: tabCounts.sales_ready,
            description: "Alta intención, requiere acción",
        },
    ];

    const handleTabChange = (newTab) => {
        router.get(
            route("leads-manager.index"),
            { ...filters, tab: newTab },
            { preserveState: true }
        );
    };

    const handleFilterChange = (name, value) => {
        router.get(
            route("leads-manager.index"),
            { ...filters, tab: activeTab, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("leads-manager.index"),
            { ...filters, tab: activeTab, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(
            route("leads-manager.index"),
            { tab: activeTab },
            { preserveState: true }
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("leads-manager.store"), {
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
        router.delete(route("leads-manager.destroy", deleteDialog.id), {
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
            route("leads-manager.retry-automation", retryDialog.id),
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
            route("leads-manager.retry-automation-batch"),
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

    // Quick Actions
    const handleCall = (lead) => {
        router.post(route("leads-manager.call-action", lead.id));
    };

    const handleWhatsApp = (lead) => {
        router.post(route("leads-manager.whatsapp-action", lead.id));
    };

    /**
     * Real-time updates via WebSocket
     */
    useEffect(() => {
        const channel = window.Echo.channel("leads");

        channel.listen(".lead.updated", (event) => {
            const { lead, action } = event;

            console.log("Evento de lead recibido:", { action, lead });

            // Determine if should reload
            const shouldReload = () => {
                if (action === "created") {
                    const isFirstPage = !filters.page || filters.page === 1;
                    const hasNoFilters =
                        !filters.search &&
                        !filters.status &&
                        !filters.campaign_id &&
                        !filters.client_id;
                    return isFirstPage && hasNoFilters && activeTab === "inbox";
                }

                if (action === "updated") {
                    const isVisible = leads.data.some((l) => l.id === lead.id);
                    return isVisible;
                }

                if (action === "deleted") {
                    const isVisible = leads.data.some((l) => l.id === lead.id);
                    return isVisible;
                }

                return false;
            };

            if (shouldReload()) {
                router.reload({
                    preserveState: true,
                    preserveScroll: true,
                    only: ["leads", "tabCounts"],
                    onSuccess: () => {
                        if (action === "created") {
                            toast.success(
                                `Nuevo lead: ${lead.name || lead.phone}`
                            );
                        } else if (action === "updated") {
                            toast.info(
                                `Lead actualizado: ${lead.name || lead.phone}`
                            );
                        } else if (action === "deleted") {
                            toast.error(
                                `Lead eliminado: ${lead.name || lead.phone}`
                            );
                        }
                    },
                });
            }

            // Browser notifications
            if (hasNotificationPermission()) {
                if (action === "created") {
                    notifyNewLead(lead);
                } else if (action === "updated") {
                    notifyLeadUpdated(lead);
                } else if (action === "deleted") {
                    notifyLeadDeleted(lead);
                }
            }
        });

        return () => {
            channel.stopListening(".lead.updated");
        };
    }, [leads.data, filters, activeTab]);

    return (
        <AppLayout
            header={{
                title: "Leads Manager",
                subtitle: "Gestión unificada de leads con flujo de trabajo",
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
            <Head title="Leads Manager" />

            <div className="space-y-6">
                {/* Tabs System */}
                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList className="grid w-full grid-cols-3">
                        {tabs.map((tab) => (
                            <TabsTrigger
                                key={tab.value}
                                value={tab.value}
                                className="flex items-center gap-2"
                            >
                                <span>{tab.label}</span>
                                {tab.count > 0 && (
                                    <Badge
                                        variant="secondary"
                                        className="ml-1 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs"
                                    >
                                        {tab.count}
                                    </Badge>
                                )}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {/* Filters Card */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="grid gap-4 md:grid-cols-5">
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
                                    value={filters.client_id || "all"}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            "client_id",
                                            value === "all" ? "" : value
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los clientes" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los clientes
                                        </SelectItem>
                                        {clients.map((c) => (
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

                    {/* Tab Content with Data Table */}
                    {tabs.map((tab) => (
                        <TabsContent key={tab.value} value={tab.value}>
                            <DataTable
                                columns={getLeadsManagerColumns(
                                    handleDelete,
                                    handleRetry,
                                    handleCall,
                                    handleWhatsApp
                                )}
                                data={leads.data}
                                filterColumn="phone"
                            />
                        </TabsContent>
                    ))}
                </Tabs>
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

                        <div className="grid grid-cols-2 gap-4">
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
                                <Label htmlFor="country">País</Label>
                                <Input
                                    id="country"
                                    type="text"
                                    value={data.country}
                                    onChange={(e) =>
                                        setData("country", e.target.value)
                                    }
                                    placeholder="AR, ES, MX..."
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="campaign_id">Campaña</Label>
                            <Select
                                value={data.campaign_id}
                                onValueChange={(value) =>
                                    setData("campaign_id", value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar campaña (opcional)" />
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
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="client_id">Cliente</Label>
                            <Select
                                value={data.client_id}
                                onValueChange={(value) =>
                                    setData("client_id", value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cliente (opcional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={c.id.toString()}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
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

