import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import {
    ArrowLeft,
    Calendar,
    MapPin,
    MessageSquare,
    Phone,
    User,
} from "lucide-react";

export default function LeadShow({ lead }) {
    const getStatusColor = (status) => {
        const colors = {
            pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
            in_progress: "bg-blue-100 text-blue-800 hover:bg-blue-100",
            contacted: "bg-purple-100 text-purple-800 hover:bg-purple-100",
            closed: "bg-green-100 text-green-800 hover:bg-green-100",
            invalid: "bg-red-100 text-red-800 hover:bg-red-100",
        };
        return colors[status] || "bg-gray-100 text-gray-800";
    };

    const formatDate = (dateString) => {
        if (!dateString) return "-";
        const date = new Date(dateString);
        return date.toLocaleString("es-ES", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <AppLayout
            header={{
                title: lead.name || "Lead sin nombre",
                subtitle: (
                    <span className="flex items-center gap-2">
                        <Phone className="h-4 w-4" />
                        {lead.phone}
                    </span>
                ),
                backButton: {
                    href: route("leads.index"),
                    variant: "outline",
                },
                actions: (
                    <Badge className={getStatusColor(lead.status)}>
                        {lead.status_label}
                    </Badge>
                ),
            }}
        >
            <Head title={`Lead - ${lead.name || lead.phone}`} />

            <div className="space-y-6">

                {/* Información Principal */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Datos del Lead */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Lead</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-start gap-3">
                                <User className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Nombre
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {lead.name || "-"}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Phone className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Teléfono
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {lead.phone}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Ciudad
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {lead.city || "-"}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <MessageSquare className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Fuente
                                    </p>
                                    <p className="text-sm text-muted-foreground capitalize">
                                        {lead.source_label || "-"}
                                    </p>
                                </div>
                            </div>

                            {lead.option_selected && (
                                <div className="flex items-start gap-3">
                                    <div className="h-5 w-5 text-muted-foreground mt-0.5">
                                        📋
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            Opción Seleccionada
                                        </p>
                                        <Badge variant="outline">
                                            Opción {lead.option_selected}
                                        </Badge>
                                    </div>
                                </div>
                            )}

                            {lead.tags && lead.tags.length > 0 && (
                                <div className="flex items-start gap-3">
                                    <div className="h-5 w-5 text-muted-foreground mt-0.5">
                                        🏷️
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            Tags
                                        </p>
                                        <div className="flex flex-wrap gap-2 mt-1">
                                            {lead.tags.map((tag, index) => (
                                                <Badge
                                                    key={index}
                                                    variant="secondary"
                                                >
                                                    {tag}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Campaña y Fechas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de Campaña</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {lead.campaign && (
                                <div>
                                    <p className="text-sm font-medium mb-2">
                                        Campaña
                                    </p>
                                    <Link
                                        href={route(
                                            "campaigns.show",
                                            lead.campaign.id
                                        )}
                                        className="text-blue-600 hover:underline"
                                    >
                                        {lead.campaign.name}
                                    </Link>
                                    {lead.campaign.client && (
                                        <p className="text-sm text-muted-foreground mt-1">
                                            Cliente: {lead.campaign.client.name}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex items-start gap-3">
                                <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Fecha de Creación
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatDate(lead.created_at)}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        Última Actualización
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatDate(lead.updated_at)}
                                    </p>
                                </div>
                            </div>

                            {lead.sent_at && (
                                <div className="flex items-start gap-3">
                                    <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            Fecha de Envío
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDate(lead.sent_at)}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {lead.webhook_sent && (
                                <div>
                                    <Badge
                                        variant="outline"
                                        className="bg-green-50"
                                    >
                                        ✓ Enviado a webhook del cliente
                                    </Badge>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Notas */}
                {lead.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Notas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                {lead.notes}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Intención (si existe) */}
                {lead.intention && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Lead Intention</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium">
                                        Status:
                                    </span>
                                    {lead.intention === "interested" ||
                                    lead.intention === "not_interested" ? (
                                        <Badge
                                            className={
                                                lead.intention === "interested"
                                                    ? "bg-green-100 text-green-800 hover:bg-green-100"
                                                    : "bg-red-100 text-red-800 hover:bg-red-100"
                                            }
                                        >
                                            {lead.intention === "interested"
                                                ? "Interested"
                                                : "Not Interested"}
                                        </Badge>
                                    ) : (
                                        <span className="text-sm text-muted-foreground">
                                            {lead.intention}
                                        </span>
                                    )}
                                </div>
                                {lead.intention_origin && (
                                    <div>
                                        <span className="text-sm font-medium">
                                            Origin:{" "}
                                        </span>
                                        <span className="text-sm text-muted-foreground capitalize">
                                            {lead.intention_origin}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Chat History - Interactions */}
                {lead.interactions && lead.interactions.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Chat History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {lead.interactions.map((interaction) => (
                                    <div
                                        key={interaction.id}
                                        className={`flex ${
                                            interaction.direction === "inbound"
                                                ? "justify-start"
                                                : "justify-end"
                                        }`}
                                    >
                                        <div
                                            className={`max-w-[70%] rounded-lg px-4 py-3 ${
                                                interaction.direction ===
                                                "inbound"
                                                    ? "bg-gray-100 text-gray-900"
                                                    : "bg-blue-600 text-white"
                                            }`}
                                        >
                                            <div className="flex items-center gap-2 mb-1">
                                                <Badge
                                                    variant="outline"
                                                    className={`text-xs ${
                                                        interaction.direction ===
                                                        "inbound"
                                                            ? "bg-white"
                                                            : "bg-blue-700 text-white border-blue-500"
                                                    }`}
                                                >
                                                    {interaction.direction ===
                                                    "inbound"
                                                        ? "👤 Lead"
                                                        : "🤖 AIRobot"}
                                                </Badge>
                                                <span
                                                    className={`text-xs ${
                                                        interaction.direction ===
                                                        "inbound"
                                                            ? "text-gray-500"
                                                            : "text-blue-200"
                                                    }`}
                                                >
                                                    {new Date(
                                                        interaction.created_at
                                                    ).toLocaleString("en-US", {
                                                        month: "short",
                                                        day: "numeric",
                                                        hour: "2-digit",
                                                        minute: "2-digit",
                                                    })}
                                                </span>
                                            </div>
                                            <p className="text-sm whitespace-pre-wrap break-words">
                                                {interaction.content}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Historial de Llamadas */}
                {lead.call_histories && lead.call_histories.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de Llamadas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {lead.call_histories.map((call) => (
                                    <div
                                        key={call.id}
                                        className="border-l-2 border-primary pl-4 py-2"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {formatDate(call.call_date)}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Duración:{" "}
                                                    {Math.floor(
                                                        call.duration_seconds /
                                                            60
                                                    )}
                                                    m{" "}
                                                    {call.duration_seconds % 60}
                                                    s
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                {call.status}
                                            </Badge>
                                        </div>
                                        {call.recording_url && (
                                            <a
                                                href={call.recording_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-xs text-blue-600 hover:underline mt-2 inline-block"
                                            >
                                                🎧 Escuchar grabación
                                            </a>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Acciones */}
                <div className="flex gap-3">
                    <Link href={route("leads.index")}>
                        <Button variant="outline">Volver al Listado</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
