import ConfirmDialog from "@/Components/Common/ConfirmDialog";
import { CSVImporterModal } from "@/Components/Common/CSVImporter";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import {
    hasNotificationPermission,
    notifyLeadDeleted,
    notifyLeadUpdated,
    notifyNewLead,
} from "@/lib/notifications";
import { Head, router, useForm } from "@inertiajs/react";
import {
    AlertTriangle,
    Archive,
    CheckCircle,
    Clock,
    FileUp,
    Inbox,
    Plus,
    TrendingUp,
} from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getLeadColumns } from "./columns";

export default function LeadsIndex({
    leads,
    campaigns,
    clients,
    filters,
    activeTab,
    tabCounts,
}) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isImportCSVOpen, setIsImportCSVOpen] = useState(false);
    const [deleteDialog, setDeleteDialog] = useState({
        open: false,
        id: null,
        name: "",
    });

    const { data, setData, post, processing, errors, reset } = useForm({
        phone: "",
        name: "",
        city: "",
        country: "",
        campaign_id: "",
        tab_placement: "inbox", // Controls automation_status + intention_status
        option_selected: "none", // "none" will be transformed to empty on submit
        source: "manual",
        notes: "",
    });

    // Tab placement options for the form (determines automation_status + intention_status)
    const tabPlacementOptions = [
        {
            value: "inbox",
            label: "Inbox",
            description: "New lead pending processing",
            badge: "bg-blue-100 text-blue-700",
        },
        {
            value: "active",
            label: "Active Pipeline",
            description: "Lead being actively worked (automation in progress)",
            badge: "bg-yellow-100 text-yellow-700",
        },
        {
            value: "sales_ready",
            label: "Sales Ready",
            description: "Ready for sales call (automation completed)",
            badge: "bg-green-100 text-green-700",
        },
    ];

    // Option selected values (for IVR campaigns)
    const optionValues = [
        { value: "none", label: "Sin opción" },
        { value: "1", label: "Opción 1" },
        { value: "2", label: "Opción 2" },
        { value: "i", label: "Opción I (Información)" },
        { value: "t", label: "Opción T (Transferencia)" },
    ];

    // Get selected campaign to check if it's IVR type
    const selectedCampaign = campaigns.find(
        (c) => c.id.toString() === data.campaign_id
    );

    // Tab configuration - Views (not states)
    const tabs = [
        {
            value: "inbox",
            label: "Inbox",
            count: tabCounts.inbox,
            icon: Inbox,
            description: "Leads nuevos pendientes de procesar",
        },
        {
            value: "active",
            label: "En Curso",
            count: tabCounts.active,
            icon: Clock,
            activeClass: "border-indigo-500 text-indigo-700",
            description: "Leads en proceso de calificación",
        },
        {
            value: "sales_ready",
            label: "Sales Ready",
            count: tabCounts.sales_ready,
            icon: TrendingUp,
            activeClass: "border-emerald-500 text-emerald-700",
            description: "Listos para contacto comercial",
        },
        {
            value: "closed",
            label: "Cerrados",
            count: tabCounts.closed,
            icon: Archive,
            activeClass: "border-gray-500 text-gray-700",
            description: "Leads finalizados",
        },
        {
            value: "errors",
            label: "Errores",
            count: tabCounts.errors,
            icon: AlertTriangle,
            activeClass: "border-red-500 text-red-700",
            description: "Leads con fallos de automatización",
        },
    ];

    const handleTabChange = (newTab) => {
        router.get(
            route("leads.index"),
            { ...filters, tab: newTab },
            { preserveState: true }
        );
    };

    const handleImportCSV = (importedData) => {
        router.post(
            route("leads.import-csv"),
            { leads: importedData },
            {
                onSuccess: (page) => {
                    setIsImportCSVOpen(false);
                    // Check for flashing messages from controller
                    if (page.props.flash?.success) {
                        toast.success(page.props.flash.success);
                    }
                    if (page.props.flash?.warning) {
                        toast.warning(page.props.flash.warning, {
                            duration: 5000,
                        });
                    }
                },
                onError: (errors) => {
                    console.error("Import Errors:", errors);
                    toast.error("Error al importar leads", {
                        description:
                            errors.error || "Ocurrió un error inesperado.",
                    });
                },
            }
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Transform data before submit - "none" should be null for option_selected
        const submitData = {
            phone: data.phone,
            name: data.name,
            city: data.city,
            country: data.country,
            campaign_id: data.campaign_id,
            tab_placement: data.tab_placement,
            option_selected:
                data.option_selected === "none" ? null : data.option_selected,
            source: data.source,
            notes: data.notes,
        };

        console.log("Submitting data:", submitData);

        router.post(route("leads.store"), submitData, {
            preserveScroll: true,
            onSuccess: (page) => {
                setIsCreateModalOpen(false);
                reset();
                if (page.props.flash?.success) {
                    toast.success(page.props.flash.success);
                } else {
                    toast.success("Lead creado exitosamente");
                }
            },
            onError: (errors) => {
                console.error("Validation Errors:", errors);

                // Check if there are validation errors
                if (errors && Object.keys(errors).length > 0) {
                    // Show validation errors
                    const errorMessages = Object.entries(errors)
                        .map(([field, msg]) => `${field}: ${msg}`)
                        .join("\n");
                    toast.error("Error de validación", {
                        description:
                            errorMessages ||
                            "Por favor verifica los campos del formulario.",
                    });
                } else {
                    // Generic error (like 302 redirect or internal error)
                    toast.error("Error interno", {
                        description:
                            "Ocurrió un error al crear el lead. Por favor intenta nuevamente.",
                    });
                }
                // Modal stays open so user can fix errors
            },
            onFinish: () => {
                // This runs after onSuccess/onError
                // We can use this to detect if something went wrong
                console.log("Request finished");
            },
        });
    };

    // Lead Action Handlers
    const handleView = (lead) => {
        router.visit(route("leads.show", lead.id));
    };

    const handleEdit = (lead) => {
        // TODO: Implement edit modal or redirect to edit page
        router.visit(route("leads.show", lead.id));
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

    const handleCall = (lead) => {
        router.post(
            route("leads.call-action", lead.id),
            {},
            {
                onSuccess: () => {
                    toast.success("Llamada iniciada");
                },
                onError: () => {
                    toast.error("Error al iniciar la llamada");
                },
            }
        );
    };

    const handleWhatsApp = (lead) => {
        router.post(
            route("leads.whatsapp-action", lead.id),
            {},
            {
                onSuccess: () => {
                    toast.success("WhatsApp enviado");
                },
                onError: () => {
                    toast.error("Error al enviar WhatsApp");
                },
            }
        );
    };

    const handleRetryAutomation = (lead) => {
        router.post(
            route("leads.retry-automation", lead.id),
            {},
            {
                onSuccess: () => {
                    toast.success("Campaña re-ejecutada correctamente");
                },
                onError: () => {
                    toast.error("Error al re-ejecutar la campaña");
                },
            }
        );
    };

    // Double click to open lead detail
    const handleRowDoubleClick = (lead) => {
        router.visit(route("leads.show", lead.id));
    };

    // Real-time updates via WebSocket
    useEffect(() => {
        const channel = window.Echo.channel("leads");

        channel.listen(".lead.updated", (event) => {
            const { lead, action } = event;

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

                if (action === "updated" || action === "deleted") {
                    return leads.data.some((l) => l.id === lead.id);
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

    // Get columns with all handlers
    const columns = getLeadColumns(activeTab, {
        onView: handleView,
        onEdit: handleEdit,
        onDelete: handleDelete,
        onCall: handleCall,
        onWhatsApp: handleWhatsApp,
        onRetryAutomation: handleRetryAutomation,
    });

    return (
        <AppLayout
            header={{
                title: "Leads Manager",
                subtitle: "Gestión unificada de leads",
                actions: (
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-sm h-8"
                            onClick={() => setIsImportCSVOpen(true)}
                        >
                            <FileUp className="h-4 w-4 mr-2" />
                            Importar CSV
                        </Button>
                        <Button
                            size="sm"
                            className="text-sm h-8 bg-indigo-600 hover:bg-indigo-700"
                            onClick={() => setIsCreateModalOpen(true)}
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            Nuevo Lead
                        </Button>
                    </div>
                ),
            }}
        >
            <Head title="Leads Manager" />

            {/* Main Card Container */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                {/* Header Section with padding */}
                <div className="p-6 space-y-4">
                    <div className="flex items-center gap-2 overflow-x-auto no-scrollbar">
                        {tabs.map((tab) => {
                            const isActive = activeTab === tab.value;
                            const hasCount = (tab.count || 0) > 0;
                            return (
                                <button
                                    key={tab.value}
                                    onClick={() => handleTabChange(tab.value)}
                                    title={tab.description}
                                    className={`flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg transition-all whitespace-nowrap border ${
                                        isActive
                                            ? tab.activeClass ||
                                              "border-gray-300 bg-gray-50 text-gray-900"
                                            : "border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                                    }`}
                                >
                                    <tab.icon className="h-4 w-4" />
                                    <span className="hidden sm:inline">{tab.label}</span>
                                    {hasCount && (
                                        <Badge
                                            variant="secondary"
                                            className={`ml-0.5 h-5 min-w-5 px-1.5 text-xs ${
                                                isActive && tab.value === "errors"
                                                    ? "bg-red-100 text-red-700"
                                                    : ""
                                            }`}
                                        >
                                            {tab.count}
                                        </Badge>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Leads Data Table */}
                <div className="px-6 pb-6">
                    <DataTable
                        columns={columns}
                        data={leads.data}
                        pagination={leads}
                        actions={
                            <DataTableFilters
                                searchPlaceholder="Buscar por teléfono o nombre..."
                                filters={[
                                    {
                                        key: "campaign_id",
                                        label: "Campaña",
                                        options: campaigns.map((c) => ({
                                            label: c.name,
                                            value: c.id.toString(),
                                        })),
                                    },
                                    {
                                        key: "client_id",
                                        label: "Cliente",
                                        options: clients.map((c) => ({
                                            label: c.name,
                                            value: c.id.toString(),
                                        })),
                                    },
                                ]}
                                values={{
                                    search: filters.search || "",
                                    campaign_id: filters.campaign_id || "all",
                                    client_id: filters.client_id || "all",
                                }}
                                onChange={(values) => {
                                    router.get(
                                        route("leads.index"),
                                        {
                                            ...route().params,
                                            ...values,
                                            tab: activeTab,
                                        },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        }
                                    );
                                }}
                                onClear={() => {
                                    router.get(
                                        route("leads.index"),
                                        { tab: activeTab },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        }
                                    );
                                }}
                            />
                        }
                    />
                </div>
            </div>

            {/* Create Lead Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent className="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>Add Manual Lead</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData("name", e.target.value)
                                    }
                                    placeholder="John Doe"
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone *</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData("phone", e.target.value)
                                    }
                                    placeholder="+1234567890"
                                />
                                {errors.phone && (
                                    <p className="text-sm text-red-500">
                                        {errors.phone}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="city">City</Label>
                                <Input
                                    id="city"
                                    value={data.city}
                                    onChange={(e) =>
                                        setData("city", e.target.value)
                                    }
                                    placeholder="New York"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="country">
                                    Country (2 chars)
                                </Label>
                                <Input
                                    id="country"
                                    value={data.country}
                                    onChange={(e) =>
                                        setData("country", e.target.value)
                                    }
                                    placeholder="US"
                                    maxLength={2}
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="campaign_id">Campaign *</Label>
                            <Select
                                value={
                                    data.campaign_id
                                        ? data.campaign_id.toString()
                                        : ""
                                }
                                onValueChange={(val) => {
                                    // Ensure we don't set NaN
                                    if (val) {
                                        setData("campaign_id", val);
                                    }
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select campaign" />
                                </SelectTrigger>
                                <SelectContent>
                                    {campaigns.map((campaign) => (
                                        <SelectItem
                                            key={campaign.id}
                                            value={campaign.id.toString()}
                                        >
                                            {campaign.name}
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
                            <Label>Place in Tab *</Label>
                            <div className="grid grid-cols-3 gap-2">
                                {tabPlacementOptions.map((option) => (
                                    <div
                                        key={option.value}
                                        className={`
                                        cursor-pointer rounded-lg border p-3 transition-all hover:border-primary
                                        ${
                                            data.tab_placement === option.value
                                                ? "border-2 border-primary bg-primary/5"
                                                : "border-gray-200"
                                        }
                                    `}
                                        onClick={() =>
                                            setData(
                                                "tab_placement",
                                                option.value
                                            )
                                        }
                                    >
                                        <div className="mb-1 flex items-center justify-between">
                                            <Badge
                                                variant="outline"
                                                className={`text-[10px] uppercase ${option.badge}`}
                                            >
                                                {option.label}
                                            </Badge>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground">
                                            {option.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Show Option Selector only if campaign is IVR */}
                        {selectedCampaign?.type === "ivr" && (
                            <div className="space-y-2">
                                <Label htmlFor="option_selected">
                                    Option Selected
                                </Label>
                                <Select
                                    value={data.option_selected}
                                    onValueChange={(val) =>
                                        setData("option_selected", val)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select option" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {optionValues.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="notes">Notes</Label>
                            <textarea
                                id="notes"
                                className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Additional notes about this lead..."
                                value={data.notes}
                                onChange={(e) =>
                                    setData("notes", e.target.value)
                                }
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-3 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                className="bg-indigo-600 hover:bg-indigo-700"
                            >
                                {processing ? "Creating..." : "Create Lead"}
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
                title="Delete lead?"
                description={`Are you sure you want to delete "${deleteDialog.name}"? This action cannot be undone.`}
                confirmText="Delete"
                cancelText="Cancel"
                variant="destructive"
            />
            <CSVImporterModal
                open={isImportCSVOpen}
                onClose={() => setIsImportCSVOpen(false)}
                onComplete={handleImportCSV}
                fields={[
                    {
                        key: "phone",
                        label: "Teléfono",
                        required: true,
                        type: "phone",
                    },
                    { key: "name", label: "Nombre", required: true },
                    { key: "city", label: "Ciudad", required: false },
                    { key: "country", label: "País", required: false },
                    { key: "campaign_id", label: "ID Campaña", required: true },
                    { key: "notes", label: "Notas", required: false },
                ]}
                entityName="Leads"
                title="Importar Leads"
            />
        </AppLayout>
    );
}
