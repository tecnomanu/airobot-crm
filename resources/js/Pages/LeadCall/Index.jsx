import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
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
import { hasNotificationPermission, notifyCallCompleted } from "@/lib/notifications";
import { Head, router } from "@inertiajs/react";
import { Clock, DollarSign, Phone, Search, X } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getCallHistoryColumns } from "./columns";

export default function CallHistoryIndex({
    calls,
    clients = [],
    campaigns = [],
    filters = {},
    totals = { calls: 0, duration: 0, cost: 0 },
}) {
    const [searchTerm, setSearchTerm] = useState(filters.search || "");

    const handleFilterChange = (name, value) => {
        router.get(
            route("lead-calls.index"),
            { ...filters, [name]: value },
            { preserveState: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("lead-calls.index"),
            { ...filters, search: searchTerm },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearchTerm("");
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
        const channel = window.Echo.channel('lead-calls');

        channel.listen('.call.created', (event) => {
            const { call } = event;

            const isFirstPage = !filters.page || filters.page === 1;
            const shouldReload = isFirstPage && !filters.search;

            if (shouldReload) {
                router.reload({
                    preserveState: true,
                    preserveScroll: true,
                    only: ['calls', 'totals'],
                    onSuccess: () => {
                        const statusLabels = {
                            completed: 'Completada',
                            no_answer: 'Sin respuesta',
                            hung_up: 'Colgó',
                            failed: 'Fallida',
                            busy: 'Ocupado',
                            voicemail: 'Buzón',
                        };
                        
                        toast.info(
                            `Nueva llamada: ${call.phone} - ${statusLabels[call.status] || call.status}`
                        );
                    }
                });
            }

            if (hasNotificationPermission()) {
                notifyCallCompleted(call);
            }
        });

        return () => {
            channel.stopListening('.call.created');
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
                                <div className={`p-2.5 rounded-lg ${stat.color}`}>
                                    <stat.icon className="h-5 w-5" />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

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
                                    placeholder="Buscar teléfono..."
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
                                value={filters.campaign_id || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange(
                                        "campaign_id",
                                        value === "all" ? "" : value
                                    )
                                }
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todas las campañas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todas las campañas
                                    </SelectItem>
                                    {campaigns.map((c) => (
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
                                    <SelectItem value="completed">Completada</SelectItem>
                                    <SelectItem value="no_answer">Sin Respuesta</SelectItem>
                                    <SelectItem value="hung_up">Colgó</SelectItem>
                                    <SelectItem value="failed">Fallida</SelectItem>
                                    <SelectItem value="busy">Ocupado</SelectItem>
                                    <SelectItem value="voicemail">Buzón de Voz</SelectItem>
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
                            columns={getCallHistoryColumns()}
                            data={calls?.data || []}
                            filterColumn="phone"
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
