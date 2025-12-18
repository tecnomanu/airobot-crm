import AppLayout from "@/Layouts/AppLayout";
import { Head } from "@inertiajs/react";
import { Users, Megaphone, Phone, DollarSign, Clock } from "lucide-react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";

export default function ClientShow({ client, overview }) {
    const stats = [
        {
            title: "Campañas Totales",
            value: overview?.total_campaigns || 0,
            subtitle: `${overview?.active_campaigns || 0} activas`,
            icon: Megaphone,
        },
        {
            title: "Total Leads",
            value: overview?.total_leads || 0,
            subtitle: `${overview?.conversion_rate || 0}% conversión`,
            icon: Users,
        },
        {
            title: "Llamadas",
            value: overview?.total_calls || 0,
            subtitle: `${overview?.total_duration_minutes || 0} min`,
            icon: Phone,
        },
        {
            title: "Costo Total",
            value: `$${(overview?.total_cost || 0).toFixed(2)}`,
            subtitle: "Inversión acumulada",
            icon: DollarSign,
        },
    ];

    const getStatusColor = (status) => {
        const colors = {
            active: "bg-green-100 text-green-800 hover:bg-green-100",
            inactive: "bg-red-100 text-red-800 hover:bg-red-100",
        };
        return colors[status] || "bg-gray-100 text-gray-800 hover:bg-gray-100";
    };

    return (
        <AppLayout
            header={{
                title: client.name,
                subtitle: client.company,
                badges: [
                    {
                        label: client.status_label,
                        className: getStatusColor(client.status),
                    },
                ],
            }}
        >
            <Head title={`Cliente: ${client.name}`} />

            <div className="space-y-6">

                {/* Client Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Cliente</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">Email</p>
                            <p className="text-sm">{client.email || "-"}</p>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">
                                Teléfono
                            </p>
                            <p className="text-sm">{client.phone || "-"}</p>
                        </div>
                        {client.notes && (
                            <div className="col-span-2">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Notas
                                </p>
                                <p className="text-sm">{client.notes}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    {stats.map((stat) => (
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {stat.title}
                                </CardTitle>
                                <stat.icon className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stat.subtitle}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Additional Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Resumen de Actividad</CardTitle>
                        <CardDescription>
                            Métricas generales de todas las campañas del cliente
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">
                                    Leads Convertidos
                                </span>
                                <span className="font-medium">
                                    {overview?.converted_leads || 0}
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">
                                    Tasa de Conversión
                                </span>
                                <span className="font-medium">
                                    {overview?.conversion_rate || 0}%
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">
                                    Duración Total Llamadas
                                </span>
                                <span className="font-medium">
                                    {overview?.total_duration_minutes || 0} minutos
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
