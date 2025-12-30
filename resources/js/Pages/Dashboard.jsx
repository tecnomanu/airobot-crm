import { Badge } from "@/Components/ui/badge";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import {
    Activity,
    ArrowUpRight,
    CheckCircle,
    Clock,
    DollarSign,
    Megaphone,
    Phone,
    TrendingUp,
    Users,
} from "lucide-react";

export default function Dashboard({
    summary,
    recentLeads,
    campaignPerformance,
    leadsByStatus,
    activeClients,
}) {
    const stats = [
        {
            title: "Leads Totales",
            value: summary?.total_leads || 0,
            subtitle: `${summary?.conversion_rate || 0}% convertidos`,
            icon: Users,
            trend: "+12%",
        },
        {
            title: "Campañas Activas",
            value: summary?.active_campaigns || 0,
            subtitle: "En ejecución",
            icon: Megaphone,
        },
        {
            title: "Llamadas Realizadas",
            value: summary?.total_calls || 0,
            subtitle: "Este mes",
            icon: Phone,
            trend: "+8%",
        },
        {
            title: "Costo Total",
            value: `$${Number(summary?.total_cost || 0).toFixed(2)}`,
            subtitle: "Inversión acumulada",
            icon: DollarSign,
        },
    ];

    const getStatusColor = (status) => {
        const colors = {
            pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
            in_progress: "bg-blue-100 text-blue-800 hover:bg-blue-100",
            contacted: "bg-purple-100 text-purple-800 hover:bg-purple-100",
            closed: "bg-green-100 text-green-800 hover:bg-green-100",
            invalid: "bg-red-100 text-red-800 hover:bg-red-100",
            active: "bg-green-100 text-green-800 hover:bg-green-100",
            inbox: "bg-blue-100 text-blue-800 hover:bg-blue-100",
            sales_ready: "bg-green-100 text-green-800 hover:bg-green-100",
        };
        return colors[status] || "bg-gray-100 text-gray-800 hover:bg-gray-100";
    };

    const getStatusLabel = (status) => {
        const labels = {
            pending: "Pendiente",
            in_progress: "En Progreso",
            contacted: "Contactado",
            closed: "Cerrado",
            invalid: "Inválido",
            active: "Activo",
            inbox: "Inbox",
            sales_ready: "Listo para Venta",
        };
        return labels[status] || status;
    };

    return (
        <AppLayout
            header={{
                title: "Dashboard",
                subtitle: "Resumen general de la plataforma",
            }}
        >
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                                {stat.trend && (
                                    <div className="mt-2 flex items-center text-xs text-green-600">
                                        <TrendingUp className="mr-1 h-3 w-3" />
                                        {stat.trend} vs mes anterior
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Leads Section */}
                <div className="space-y-6">
                    {/* Recent Leads - Full Width */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div>
                                <CardTitle>Leads Recientes</CardTitle>
                                <CardDescription>
                                    Últimos leads ingresados al sistema
                                </CardDescription>
                            </div>
                            <Link
                                href={route("leads.index")}
                                className="flex items-center text-sm font-medium text-primary hover:underline"
                            >
                                Ver todos
                                <ArrowUpRight className="ml-1 h-4 w-4" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Teléfono</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Campaña</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-right">
                                            Fecha
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentLeads && recentLeads.length > 0 ? (
                                        recentLeads.map((lead) => (
                                            <TableRow key={lead.id}>
                                                <TableCell className="font-medium">
                                                    {lead.phone}
                                                </TableCell>
                                                <TableCell>
                                                    {lead.name || "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {lead.campaign?.name || "-"}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={getStatusColor(
                                                            lead.status
                                                        )}
                                                    >
                                                        {lead.status_label ||
                                                            getStatusLabel(
                                                                lead.status
                                                            )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {new Date(
                                                        lead.created_at
                                                    ).toLocaleDateString(
                                                        "es-ES"
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="text-center text-muted-foreground"
                                            >
                                                No hay leads aún
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Leads by Status - 3 Stat Blocks */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4">
                            Leads por Estado
                        </h3>
                        <p className="text-sm text-muted-foreground mb-4">
                            Estados principales del pipeline
                        </p>
                        <div className="grid gap-4 md:grid-cols-3">
                            {(() => {
                                // Filtrar solo los 3 estados principales
                                const mainStates = [
                                    "in_progress",
                                    "sales_ready",
                                    "pending",
                                ];
                                const filteredStates =
                                    leadsByStatus?.filter((item) =>
                                        mainStates.includes(item.status)
                                    ) || [];

                                // Definir configuración de cada estado
                                const stateConfig = {
                                    in_progress: {
                                        icon: Activity,
                                        label: "En Proceso",
                                        color: "text-blue-600",
                                        bgColor: "bg-blue-50",
                                    },
                                    sales_ready: {
                                        icon: CheckCircle,
                                        label: "Listo para Venta",
                                        color: "text-green-600",
                                        bgColor: "bg-green-50",
                                    },
                                    pending: {
                                        icon: Clock,
                                        label: "Pendiente",
                                        color: "text-amber-600",
                                        bgColor: "bg-amber-50",
                                    },
                                };

                                return mainStates.map((status) => {
                                    const item = filteredStates.find(
                                        (s) => s.status === status
                                    );
                                    const config = stateConfig[status];
                                    const count = item?.count || 0;
                                    const Icon = config.icon;

                                    return (
                                        <Card key={status}>
                                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                                <CardTitle className="text-sm font-medium">
                                                    {config.label}
                                                </CardTitle>
                                                <div
                                                    className={`p-2 rounded-lg ${config.bgColor}`}
                                                >
                                                    <Icon
                                                        className={`h-4 w-4 ${config.color}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-2xl font-bold">
                                                    {count}
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {count === 1
                                                        ? "lead"
                                                        : "leads"}{" "}
                                                    en este estado
                                                </p>
                                            </CardContent>
                                        </Card>
                                    );
                                });
                            })()}
                        </div>
                    </div>
                </div>

                {/* Active Clients */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Clientes Activos</CardTitle>
                                <CardDescription>
                                    Clientes con campañas en ejecución
                                </CardDescription>
                            </div>
                            <Link
                                href={route("clients.index")}
                                className="flex items-center text-sm font-medium text-primary hover:underline"
                            >
                                Ver todos
                                <ArrowUpRight className="ml-1 h-4 w-4" />
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {activeClients && activeClients.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Empresa</TableHead>
                                        <TableHead className="text-right">
                                            Campañas
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activeClients.map((client) => (
                                        <TableRow key={client.id}>
                                            <TableCell className="font-medium">
                                                {client.name}
                                            </TableCell>
                                            <TableCell>
                                                {client.email || "-"}
                                            </TableCell>
                                            <TableCell>
                                                {client.company || "-"}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {client.campaigns_count || 0}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <p className="text-center text-muted-foreground">
                                No hay clientes registrados
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
