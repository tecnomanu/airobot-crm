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
    clients,
    campaigns,
    filters,
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
            subtitle: "Llamadas registradas",
        },
        {
            title: "Duración Total",
            value: `${Math.floor((totals?.duration || 0) / 60)} min`,
            icon: Clock,
            subtitle: "Tiempo total de llamadas",
        },
        {
            title: "Costo Total",
            value: `$${(totals?.cost || 0).toFixed(2)}`,
            icon: DollarSign,
            subtitle: "Inversión en llamadas",
        },
    ];

    /**
     * Escucha eventos de nuevas llamadas en tiempo real
     */
    useEffect(() => {
        const channel = window.Echo.channel('lead-calls');

        channel.listen('.call.created', (event) => {
            const { call } = event;
            
            console.log('Evento de llamada recibido:', { call });

            // Recargar solo si estamos en la primera página sin filtros específicos
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

            // Notificación nativa del navegador
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

            <div className="space-y-6">

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-3">
                    {stats.map((stat) => (
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {stat.title}
                                </CardTitle>
                                <stat.icon className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {stat.value}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {stat.subtitle}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-5">
                            <form
                                onSubmit={handleSearch}
                                className="flex gap-2"
                            >
                                <Input
                                    placeholder="Buscar teléfono..."
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
                                    <SelectItem value="completed">
                                        Completada
                                    </SelectItem>
                                    <SelectItem value="no_answer">
                                        Sin Respuesta
                                    </SelectItem>
                                    <SelectItem value="hung_up">
                                        Colgó
                                    </SelectItem>
                                    <SelectItem value="failed">
                                        Fallida
                                    </SelectItem>
                                    <SelectItem value="busy">
                                        Ocupado
                                    </SelectItem>
                                    <SelectItem value="voicemail">
                                        Buzón de Voz
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
                    columns={getCallHistoryColumns()}
                    data={calls.data}
                    filterColumn="phone"
                />
            </div>
        </AppLayout>
    );
}
