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
import {
    hasNotificationPermission,
    notifyLeadUpdated,
    notifyNewLead,
    notifyLeadDeleted,
} from "@/lib/notifications";
import { Head, router, useForm } from "@inertiajs/react";
import {
    Plus,
    Search,
    Filter,
    Download,
    Clock,
    Inbox,
    CheckCircle,
    FileUp,
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
    const [searchTerm, setSearchTerm] = useState(filters.search || "");
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
            badge: "bg-blue-100 text-blue-700"
        },
        { 
            value: "active", 
            label: "Active Pipeline", 
            description: "Lead being actively worked (automation in progress)",
            badge: "bg-yellow-100 text-yellow-700"
        },
        { 
            value: "sales_ready", 
            label: "Sales Ready", 
            description: "Ready for sales call (automation completed)",
            badge: "bg-green-100 text-green-700"
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
    const selectedCampaign = campaigns.find(c => c.id.toString() === data.campaign_id);

    // Tab configuration matching the UI mockups
    const tabs = [
        {
            value: "inbox",
            label: "Inbox",
            count: tabCounts.inbox,
            icon: Inbox,
            activeColor: "text-gray-900",
        },
        {
            value: "active",
            label: "Active Pipeline",
            count: tabCounts.active,
            icon: Clock,
            activeColor: "text-indigo-600",
            countColor: "bg-indigo-100 text-indigo-700",
        },
        {
            value: "sales_ready",
            label: "Sales Ready",
            count: tabCounts.sales_ready,
            icon: CheckCircle,
            activeColor: "text-emerald-600",
            countColor: "bg-emerald-100 text-emerald-700",
        },
    ];

    const handleTabChange = (newTab) => {
        router.get(
            route("leads.index"),
            { ...filters, tab: newTab },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("leads.index"),
            { ...filters, tab: activeTab, search: searchTerm },
            { preserveState: true }
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
            option_selected: data.option_selected === "none" ? null : data.option_selected,
            source: data.source,
            notes: data.notes,
        };
        
        console.log("Submitting data:", submitData);
        
        router.post(route("leads.store"), submitData, {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success("Lead creado exitosamente");
            },
            onError: (errors) => {
                console.error("Validation Errors:", JSON.stringify(errors, null, 2));
                // Show all errors
                const errorMessages = Object.entries(errors)
                    .map(([field, msg]) => `${field}: ${msg}`)
                    .join("\n");
                toast.error("Error de validación", {
                    description: errorMessages || "Error desconocido",
                });
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
        router.post(route("leads.call-action", lead.id), {}, {
            onSuccess: () => {
                toast.success("Llamada iniciada");
            },
            onError: () => {
                toast.error("Error al iniciar la llamada");
            },
        });
    };

    const handleWhatsApp = (lead) => {
        router.post(route("leads.whatsapp-action", lead.id), {}, {
                onSuccess: () => {
                toast.success("WhatsApp enviado");
                },
                onError: () => {
                toast.error("Error al enviar WhatsApp");
            },
        });
    };

    const handleRetryAutomation = (lead) => {
        router.post(route("leads.retry-automation", lead.id), {}, {
                onSuccess: () => {
                toast.success("Campaña re-ejecutada correctamente");
                },
                onError: () => {
                toast.error("Error al re-ejecutar la campaña");
            },
        });
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
            }}
        >
            <Head title="Leads Manager" />

            {/* Main Card Container */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                {/* Header Section with padding */}
                <div className="p-6 space-y-4">
                    {/* Row 1: Tabs */}
                    <div className="flex items-center gap-4 overflow-x-auto no-scrollbar">
                        {tabs.map((tab) => (
                            <button
                                key={tab.value}
                                onClick={() => handleTabChange(tab.value)}
                                className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-all whitespace-nowrap border ${
                                    activeTab === tab.value
                                        ? "bg-gray-100 border-gray-200 text-gray-900"
                                        : "border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                                }`}
                            >
                                <tab.icon className="h-4 w-4" />
                                <span>{tab.label}</span>
                                {tab.count > 0 && (
                                    <span
                                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                            activeTab === tab.value
                                                ? "bg-gray-200 text-gray-700"
                                                : "bg-gray-100 text-gray-600"
                                        }`}
                                    >
                                        {tab.count}
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>

                    {/* Row 2: Action Buttons */}
                    <div className="flex items-center gap-3">
                            <Button
                                variant="outline"
                            size="sm"
                            className="text-sm h-9"
                        >
                            <FileUp className="h-4 w-4 mr-2" />
                            Import CSV
                        </Button>
                        <Button
                            size="sm"
                            className="text-sm h-9 bg-indigo-600 hover:bg-indigo-700"
                            onClick={() => setIsCreateModalOpen(true)}
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            New Lead
                            </Button>
                        </div>

                    {/* Row 3: Search Bar */}
                    <div className="flex items-center gap-3">
                        <form
                            onSubmit={handleSearch}
                            className="flex-1 max-w-md relative"
                        >
                            <input
                                type="text"
                                placeholder="Search leads..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-4 pr-10 py-2.5 text-sm bg-indigo-50 border-0 rounded-lg focus:ring-2 focus:ring-indigo-500 placeholder-gray-500"
                            />
                            <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        </form>

                        <button className="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <Filter className="h-4 w-4" />
                        </button>
                        <button className="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <Download className="h-4 w-4" />
                        </button>
                    </div>

                    {/* Hint for double-click */}
                    <p className="text-xs text-gray-400">
                        Tip: Doble click en una fila para ver detalles del lead
                    </p>
                </div>

                {/* Data Table with padding */}
                <div className="px-6 pb-6">
                <DataTable
                        columns={columns}
                    data={leads.data}
                        filterColumn="name"
                        onRowDoubleClick={handleRowDoubleClick}
                />
                </div>
            </div>

            {/* Create Lead Modal */}
            <Dialog
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            >
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="text-base">Add Manual Lead</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Row 1: Name & Phone */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="name" className="text-xs">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData("name", e.target.value)}
                                    placeholder="John Doe"
                                    className="h-8 text-sm"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="phone" className="text-xs">Phone *</Label>
                                <Input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData("phone", e.target.value)}
                                    placeholder="+34600111222"
                                    className="h-8 text-sm"
                                />
                                {errors.phone && (
                                    <p className="text-[10px] text-red-500">{errors.phone}</p>
                                )}
                            </div>
                        </div>

                        {/* Row 2: City & Country */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="city" className="text-xs">City</Label>
                                <Input
                                    id="city"
                                    type="text"
                                    value={data.city}
                                    onChange={(e) => setData("city", e.target.value)}
                                    placeholder="Madrid"
                                    className="h-8 text-sm"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="country" className="text-xs">Country (2 chars)</Label>
                                <Input
                                    id="country"
                                    type="text"
                                    maxLength={2}
                                    value={data.country}
                                    onChange={(e) => setData("country", e.target.value.toUpperCase())}
                                    placeholder="ES"
                                    className="h-8 text-sm uppercase"
                                />
                            </div>
                        </div>

                        {/* Row 3: Campaign (Required) */}
                        <div className="space-y-1">
                            <Label htmlFor="campaign_id" className="text-xs">Campaign *</Label>
                            <Select
                                value={data.campaign_id}
                                onValueChange={(value) => setData("campaign_id", value)}
                            >
                                <SelectTrigger className="h-8 text-sm">
                                    <SelectValue placeholder="Select a campaign" />
                                </SelectTrigger>
                                <SelectContent>
                                    {campaigns.map((c) => (
                                        <SelectItem key={c.id} value={c.id}>
                                            {c.name} {c.is_dynamic && "(IVR)"}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.campaign_id && (
                                <p className="text-[10px] text-red-500">{errors.campaign_id}</p>
                            )}
                        </div>

                        {/* Row 4: Tab Placement */}
                        <div className="space-y-2">
                            <Label className="text-xs font-medium">Place in Tab *</Label>
                            <div className="grid grid-cols-3 gap-2">
                                {tabPlacementOptions.map((tab) => (
                                    <button
                                        key={tab.value}
                                        type="button"
                                        onClick={() => setData("tab_placement", tab.value)}
                                        className={`p-3 rounded-lg border-2 text-left transition-all ${
                                            data.tab_placement === tab.value
                                                ? "border-indigo-500 bg-indigo-50"
                                                : "border-gray-200 hover:border-gray-300"
                                        }`}
                                    >
                                        <Badge className={`${tab.badge} text-[10px] mb-1`}>
                                            {tab.label}
                                        </Badge>
                                        <p className="text-[10px] text-gray-500 mt-1">
                                            {tab.description}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Row 5: Option Selected (for IVR) */}
                        <div className="space-y-1">
                            <Label htmlFor="option_selected" className="text-xs">
                                Option Selected {selectedCampaign?.is_dynamic && <Badge variant="outline" className="text-[9px] ml-1">IVR Campaign</Badge>}
                            </Label>
                            <Select
                                value={data.option_selected}
                                onValueChange={(value) => setData("option_selected", value)}
                            >
                                <SelectTrigger className="h-8 text-sm">
                                    <SelectValue placeholder="Sin opción" />
                                </SelectTrigger>
                                <SelectContent>
                                    {optionValues.map((o) => (
                                        <SelectItem key={o.value || "none"} value={o.value}>
                                            {o.label}
                                    </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {selectedCampaign?.is_dynamic && (
                                <p className="text-[10px] text-blue-600">
                                    Esta campaña es IVR - la opción determinará la acción a ejecutar
                                </p>
                            )}
                        </div>

                        {/* Row 6: Notes */}
                        <div className="space-y-1">
                            <Label htmlFor="notes" className="text-xs">Notes</Label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData("notes", e.target.value)}
                                placeholder="Additional notes about this lead..."
                                rows={2}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring resize-none"
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
        </AppLayout>
    );
}
