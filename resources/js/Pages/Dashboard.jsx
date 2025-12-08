import { Badge } from "@/components/ui/badge";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import {
    ArrowUpRight,
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
        };
        return colors[status] || "bg-gray-100 text-gray-800 hover:bg-gray-100";
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

                <div className="grid gap-4 md:grid-cols-7">
                    {/* Recent Leads */}
                    <Card className="col-span-4">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Leads Recientes</CardTitle>
                                    <CardDescription>
                                        Últimos leads ingresados al sistema
                                    </CardDescription>
                                </div>
                                <Link
                                    href={route("leads-manager.index")}
                                    className="flex items-center text-sm font-medium text-primary hover:underline"
                                >
                                    Ver todos
                                    <ArrowUpRight className="ml-1 h-4 w-4" />
                                </Link>
                            </div>
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
                                                        {lead.status_label}
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

                    {/* Leads by Status */}
                    <Card className="col-span-3">
                        <CardHeader>
                            <CardTitle>Leads por Estado</CardTitle>
                            <CardDescription>
                                Distribución actual de leads
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {leadsByStatus && leadsByStatus.length > 0 ? (
                                leadsByStatus.map((item) => (
                                    <div
                                        key={item.status}
                                        className="flex items-center justify-between"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                className={getStatusColor(
                                                    item.status
                                                )}
                                            >
                                                {item.label}
                                            </Badge>
                                        </div>
                                        <span className="text-2xl font-bold">
                                            {item.count}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-muted-foreground">
                                    No hay datos disponibles
                                </p>
                            )}
                        </CardContent>
                    </Card>
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
