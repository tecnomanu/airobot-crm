import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/Layouts/AppLayout";
import { hasNotificationPermission, notifyLeadIntention } from "@/lib/notifications";
import { Head, router } from "@inertiajs/react";
import { Search, X } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getLeadIntencionColumns } from "./columns";

export default function LeadsIntencionIndex({ leads, campaigns, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || "");

    const handleFilterChange = (name, value) => {
        router.get(
            route("leads-manager.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("leads-manager.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
        router.get(route("leads-intencion.index"), {}, { preserveState: true });
    };

    /**
     * Escucha eventos de cambios de intención en tiempo real
     */
    useEffect(() => {
        const channel = window.Echo.channel('leads');

        channel.listen('.lead.updated', (event) => {
            const { lead, action } = event;
            
            // Solo procesar si tiene intención definida
            if (!lead.intention || lead.intention === 'undecided') {
                return;
            }

            console.log('Evento de intención recibido:', { action, lead });

            // Verificar si el lead está visible en la página actual
            const isVisible = leads.data.some((l) => l.id === lead.id);
            const isIntentionUpdate = action === 'updated' && lead.intention;

            if (isIntentionUpdate && (isVisible || !filters.page || filters.page === 1)) {
                router.reload({
                    preserveState: true,
                    preserveScroll: true,
                    only: ['leads'],
                    onSuccess: () => {
                        toast.success(
                            `Intención detectada: ${lead.name || lead.phone}`
                        );
                    }
                });

                // Notificación nativa del navegador
                if (hasNotificationPermission()) {
                    notifyLeadIntention(lead, lead.intention);
                }
            }
        });

        return () => {
            channel.stopListening('.lead.updated');
        };
    }, [leads.data, filters]);

    return (
        <AppLayout
            header={{
                title: "Leads with Intention",
                subtitle:
                    "Leads captured through WhatsApp and AI Agent with registered intention",
            }}
        >
            <Head title="Leads with Intention" />

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
                                    placeholder="Search by phone or name..."
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
                                    <SelectValue placeholder="All campaigns" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All campaigns
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
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="in_progress">
                                        In Progress
                                    </SelectItem>
                                    <SelectItem value="contacted">
                                        Contacted
                                    </SelectItem>
                                    <SelectItem value="closed">
                                        Closed
                                    </SelectItem>
                                    <SelectItem value="invalid">
                                        Invalid
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                                className="w-full"
                            >
                                <X className="mr-2 h-4 w-4" />
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <DataTable
                    columns={getLeadIntencionColumns()}
                    data={leads.data}
                    filterColumn="phone"
                />
            </div>
        </AppLayout>
    );
}
