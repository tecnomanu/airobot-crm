import { DataTable } from "@/Components/ui/data-table";
import DataTableFilters from "@/Components/ui/data-table-filters";
import AppLayout from "@/Layouts/AppLayout";
import {
    hasNotificationPermission,
    notifyCallCompleted,
} from "@/lib/notifications";
import { Head, router } from "@inertiajs/react";
import { Clock, DollarSign, Phone } from "lucide-react";
import { useEffect } from "react";
import { toast } from "sonner";
import { getCallHistoryColumns } from "./columns";

export default function CallHistoryIndex({
    calls,
    clients = [],
    campaigns = [],
    filters = {},
    totals = { calls: 0, duration: 0, cost: 0 },
}) {
    const handleFilterChange = (name, value) => {
        router.get(
            route("lead-calls.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        router.get(route("lead-calls.index"), {}, { preserveState: true });
    };

    const stats = [
        {
            title: "Total Llamadas",
            value: totals?.calls || 0,
            icon: Phone,
            color: "text-blue-600 bg-blue-50",
        },
        {
            title: "Duración Total",
            value: `${Math.floor((totals?.duration || 0) / 60)} min`,
            icon: Clock,
            color: "text-green-600 bg-green-50",
        },
        {
            title: "Costo Total",
            value: `$${(totals?.cost || 0).toFixed(2)}`,
            icon: DollarSign,
            color: "text-amber-600 bg-amber-50",
        },
    ];

    useEffect(() => {
        const channel = window.Echo.channel("lead-calls");

        channel.listen(".call.created", (event) => {
            const { call } = event;

            const isFirstPage = !filters.page || filters.page === 1;
            const shouldReload = isFirstPage && !filters.search;

            if (shouldReload) {
                router.reload({
                    preserveState: true,
                    preserveScroll: true,
                    only: ["calls", "totals"],
                    onSuccess: () => {
                        const statusLabels = {
                            completed: "Completada",
                            no_answer: "Sin respuesta",
                            hung_up: "Colgó",
                            failed: "Fallida",
                            busy: "Ocupado",
                            voicemail: "Buzón",
                        };

                        toast.info(
                            `Nueva llamada: ${call.phone} - ${
                                statusLabels[call.status] || call.status
                            }`
                        );
                    },
                });
            }

            if (hasNotificationPermission()) {
                notifyCallCompleted(call);
            }
        });

        return () => {
            channel.stopListening(".call.created");
        };
    }, [calls.data, filters]);

    return (
        <AppLayout
            header={{
                title: "Historial de Llamadas",
                subtitle: "Registro completo de todas las llamadas realizadas",
            }}
        >
            <Head title="Historial de Llamadas" />

            <div className="space-y-4">
                {/* Stats Cards */}
                <div className="grid gap-3 md:grid-cols-3">
                    {stats.map((stat) => (
                        <div
                            key={stat.title}
                            className="bg-white rounded-xl border border-gray-200 shadow-sm p-4"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 font-medium">
                                        {stat.title}
                                    </p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">
                                        {stat.value}
                                    </p>
                                </div>
                                <div
                                    className={`p-2.5 rounded-lg ${stat.color}`}
                                >
                                    <stat.icon className="h-5 w-5" />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Main Card Container */}
                <div className="bg-white rounded-xl shadow-sm">
                    {/* Table with Actions (Filters) */}
                    <div className="p-6">
                        <DataTable
                            columns={getCallHistoryColumns()}
                            data={calls?.data || []}
                            actions={
                                <DataTableFilters
                                    filters={[
                                        {
                                            type: "search",
                                            name: "search",
                                            placeholder: "Buscar teléfono...",
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
                                            name: "campaign_id",
                                            placeholder: "Todas las campañas",
                                            allLabel: "Todas las campañas",
                                            options: campaigns,
                                        },
                                        {
                                            type: "select",
                                            name: "status",
                                            placeholder: "Todos los estados",
                                            allLabel: "Todos",
                                            className: "w-[180px]",
                                            options: [
                                                {
                                                    value: "completed",
                                                    label: "Completada",
                                                },
                                                {
                                                    value: "no_answer",
                                                    label: "Sin Respuesta",
                                                },
                                                {
                                                    value: "hung_up",
                                                    label: "Colgó",
                                                },
                                                {
                                                    value: "failed",
                                                    label: "Fallida",
                                                },
                                                {
                                                    value: "busy",
                                                    label: "Ocupado",
                                                },
                                                {
                                                    value: "voicemail",
                                                    label: "Buzón de Voz",
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
            </div>
        </AppLayout>
    );
}
