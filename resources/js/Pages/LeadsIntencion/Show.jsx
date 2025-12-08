import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import {
    CheckCircle,
    Clock,
    ExternalLink,
    Globe,
    Mail,
    MessageSquare,
    Phone,
    PhoneCall,
    User,
    XCircle,
} from "lucide-react";

export default function LeadIntencionShow({ lead }) {
    // Agrupar interacciones por canal
    const groupedInteractions = () => {
        if (!lead.interactions || lead.interactions.length === 0) {
            return {};
        }

        return lead.interactions.reduce((acc, interaction) => {
            const channel = interaction.channel;
            if (!acc[channel]) {
                acc[channel] = [];
            }
            acc[channel].push(interaction);
            return acc;
        }, {});
    };

    const channels = groupedInteractions();
    const channelKeys = Object.keys(channels);

    const getChannelIcon = (channel) => {
        switch (channel) {
            case "whatsapp":
                return <MessageSquare className="h-4 w-4" />;
            case "call":
                return <PhoneCall className="h-4 w-4" />;
            case "email":
                return <Mail className="h-4 w-4" />;
            case "sms":
                return <MessageSquare className="h-4 w-4" />;
            case "web":
                return <Globe className="h-4 w-4" />;
            default:
                return <MessageSquare className="h-4 w-4" />;
        }
    };

    const getChannelLabel = (channel) => {
        const labels = {
            whatsapp: "WhatsApp",
            call: "Llamadas",
            email: "Email",
            sms: "SMS",
            web: "Web",
        };
        return labels[channel] || channel;
    };

    const getIntentionIcon = () => {
        const intention = lead.intention;

        if (intention === "interested") {
            return <CheckCircle className="h-6 w-6 text-green-600" />;
        }

        if (intention === "not_interested") {
            return <XCircle className="h-6 w-6 text-red-600" />;
        }

        return <Clock className="h-6 w-6 text-yellow-600" />;
    };

    const getIntentionBadge = () => {
        const intention = lead.intention;

        if (intention === "interested") {
            return (
                <Badge className="bg-green-100 text-green-800 hover:bg-green-100 text-base px-3 py-1">
                    Interesado
                </Badge>
            );
        }

        if (intention === "not_interested") {
            return (
                <Badge className="bg-red-100 text-red-800 hover:bg-red-100 text-base px-3 py-1">
                    No Interesado
                </Badge>
            );
        }

        return (
            <Badge variant="outline" className="text-base px-3 py-1">
                Pendiente de Análisis
            </Badge>
        );
    };

    const getStatusBadge = (status) => {
        const colors = {
            pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
            in_progress: "bg-blue-100 text-blue-800 hover:bg-blue-100",
            contacted: "bg-purple-100 text-purple-800 hover:bg-purple-100",
            closed: "bg-green-100 text-green-800 hover:bg-green-100",
            invalid: "bg-red-100 text-red-800 hover:bg-red-100",
        };
        return (
            <Badge className={colors[status] || "bg-gray-100 text-gray-800"}>
                {lead.status_label}
            </Badge>
        );
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return "-";
        const date = new Date(dateString);
        return date.toLocaleString("es-ES", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    const renderChannelInteractions = (channel, interactions) => {
        if (channel === "call") {
            // Vista especial para llamadas
            return (
                <div className="space-y-3">
                    {interactions.map((interaction, index) => {
                        const isInbound = interaction.direction === "inbound";
                        return (
                            <div
                                key={interaction.id || index}
                                className="flex items-start gap-3 p-4 rounded-lg border bg-card hover:bg-accent/5 transition-colors"
                            >
                                <div
                                    className={`p-2 rounded-full ${
                                        isInbound
                                            ? "bg-blue-100"
                                            : "bg-green-100"
                                    }`}
                                >
                                    <PhoneCall
                                        className={`h-5 w-5 ${
                                            isInbound
                                                ? "text-blue-600"
                                                : "text-green-600"
                                        }`}
                                    />
                                </div>
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Badge
                                            variant={
                                                isInbound
                                                    ? "default"
                                                    : "secondary"
                                            }
                                        >
                                            {isInbound
                                                ? "Entrante"
                                                : "Saliente"}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDateTime(
                                                interaction.created_at
                                            )}
                                        </span>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {interaction.content ||
                                            "Llamada registrada"}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            );
        }

        // Vista de chat para WhatsApp, SMS, Email, etc.
        return (
            <div className="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                {interactions.map((interaction, index) => {
                    const isInbound = interaction.direction === "inbound";
                    return (
                        <div
                            key={interaction.id || index}
                            className={`flex ${
                                isInbound ? "justify-start" : "justify-end"
                            }`}
                        >
                            <div
                                className={`max-w-[70%] rounded-lg p-4 ${
                                    isInbound
                                        ? "bg-gray-100 text-gray-900"
                                        : "bg-blue-500 text-white"
                                }`}
                            >
                                <div className="flex items-center gap-2 mb-2">
                                    <Badge
                                        variant="outline"
                                        className={
                                            isInbound
                                                ? "bg-white text-gray-700 border-gray-300"
                                                : "bg-blue-600 text-white border-blue-400"
                                        }
                                    >
                                        {isInbound ? "Lead" : "AIRobot"}
                                    </Badge>
                                    <span
                                        className={`text-xs ${
                                            isInbound
                                                ? "text-gray-500"
                                                : "text-blue-100"
                                        }`}
                                    >
                                        {formatDateTime(interaction.created_at)}
                                    </span>
                                </div>
                                <p className="text-sm whitespace-pre-wrap break-words">
                                    {interaction.content}
                                </p>
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <AppLayout
            header={{
                title: "Detalle de Intención",
                subtitle: "Análisis completo de interacciones y comportamiento",
                backButton: {
                    onClick: () => router.visit(route("leads-manager.index")),
                    variant: "outline",
                },
                actions: (
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 text-xs px-2"
                        onClick={() =>
                            router.visit(route("leads.show", lead.id))
                        }
                    >
                        <ExternalLink className="h-3.5 w-3.5 mr-1.5" />
                        Ver Perfil Completo
                    </Button>
                ),
            }}
        >
            <Head title={`Lead Intention - ${lead.phone}`} />

            <div className="space-y-6 max-w-6xl mx-auto">
                {/* Tabs principales */}
                <Tabs defaultValue="overview" className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="overview">
                            <User className="mr-2 h-4 w-4" />
                            Resumen
                        </TabsTrigger>
                        <TabsTrigger value="interactions">
                            <MessageSquare className="mr-2 h-4 w-4" />
                            Interacciones ({lead.interactions?.length || 0})
                        </TabsTrigger>
                    </TabsList>

                    {/* Tab: Resumen */}
                    <TabsContent value="overview" className="space-y-6 mt-6">
                        {/* Intención Detectada */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-3">
                                    {getIntentionIcon()}
                                    Intención Detectada
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-start justify-between">
                                    <div className="space-y-3">
                                        {getIntentionBadge()}
                                        {lead.intention &&
                                            lead.intention !== "interested" &&
                                            lead.intention !==
                                                "not_interested" && (
                                                <div className="mt-4 p-4 bg-gray-50 rounded-lg max-w-2xl">
                                                    <p className="text-sm text-gray-700">
                                                        <strong>
                                                            Contenido:
                                                        </strong>{" "}
                                                        {lead.intention}
                                                    </p>
                                                </div>
                                            )}
                                    </div>
                                    <div className="text-right text-sm space-y-2">
                                        <div className="flex items-center justify-end gap-2">
                                            <span className="font-medium text-foreground">
                                                Origen:
                                            </span>
                                            {lead.intention_origin ? (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        lead.intention_origin ===
                                                        "agent_ia"
                                                            ? "bg-blue-50 text-blue-700 border-blue-200"
                                                            : lead.intention_origin ===
                                                              "whatsapp"
                                                            ? "bg-green-50 text-green-700 border-green-200"
                                                            : "bg-gray-50 text-gray-700 border-gray-200"
                                                    }
                                                >
                                                    {lead.intention_origin ===
                                                        "agent_ia" &&
                                                        "🤖 Agente IA"}
                                                    {lead.intention_origin ===
                                                        "whatsapp" &&
                                                        "💬 WhatsApp"}
                                                    {lead.intention_origin ===
                                                        "manual" && "👤 Manual"}
                                                    {lead.intention_origin ===
                                                        "ivr" && "📞 IVR"}
                                                    {![
                                                        "agent_ia",
                                                        "whatsapp",
                                                        "manual",
                                                        "ivr",
                                                    ].includes(
                                                        lead.intention_origin
                                                    ) && lead.intention_origin}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    N/A
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center justify-end gap-2">
                                            <span className="font-medium text-foreground">
                                                Estado:
                                            </span>
                                            {lead.intention_status ? (
                                                <Badge
                                                    className={
                                                        lead.intention_status ===
                                                        "finalized"
                                                            ? "bg-green-100 text-green-800 hover:bg-green-100"
                                                            : "bg-yellow-100 text-yellow-800 hover:bg-yellow-100"
                                                    }
                                                >
                                                    {lead.intention_status ===
                                                    "finalized"
                                                        ? "✓ Finalizado"
                                                        : "⏳ Pendiente"}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    N/A
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Información de Contacto */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-3">
                                    <User className="h-5 w-5" />
                                    Información de Contacto
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Nombre
                                        </p>
                                        <p className="font-medium">
                                            {lead.name || "Sin nombre"}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Teléfono
                                        </p>
                                        <p className="font-medium flex items-center gap-2">
                                            <Phone className="h-4 w-4" />
                                            {lead.phone}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Campaña
                                        </p>
                                        <p className="font-medium">
                                            {lead.campaign?.name || "N/A"}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Estado
                                        </p>
                                        {getStatusBadge(lead.status)}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Fechas y Timeline */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-3">
                                    <Clock className="h-5 w-5" />
                                    Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Primer Contacto
                                        </p>
                                        <p className="font-medium">
                                            {formatDateTime(lead.created_at)}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">
                                            Última Actualización
                                        </p>
                                        <p className="font-medium">
                                            {formatDateTime(lead.updated_at)}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Interacciones */}
                    <TabsContent value="interactions" className="mt-6">
                        {channelKeys.length > 0 ? (
                            <Tabs
                                defaultValue={channelKeys[0]}
                                className="w-full"
                            >
                                <TabsList className="w-full justify-start">
                                    {channelKeys.map((channel) => (
                                        <TabsTrigger
                                            key={channel}
                                            value={channel}
                                            className="flex items-center gap-2"
                                        >
                                            {getChannelIcon(channel)}
                                            {getChannelLabel(channel)}
                                            <Badge
                                                variant="secondary"
                                                className="ml-1"
                                            >
                                                {channels[channel].length}
                                            </Badge>
                                        </TabsTrigger>
                                    ))}
                                </TabsList>

                                {channelKeys.map((channel) => (
                                    <TabsContent
                                        key={channel}
                                        value={channel}
                                        className="mt-6"
                                    >
                                        <Card>
                                            <CardHeader>
                                                <CardTitle className="flex items-center gap-3">
                                                    {getChannelIcon(channel)}
                                                    {getChannelLabel(channel)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                {renderChannelInteractions(
                                                    channel,
                                                    channels[channel]
                                                )}
                                            </CardContent>
                                        </Card>
                                    </TabsContent>
                                ))}
                            </Tabs>
                        ) : (
                            <Card>
                                <CardContent className="py-12">
                                    <div className="text-center text-muted-foreground">
                                        <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p className="text-lg font-medium">
                                            No hay interacciones registradas
                                        </p>
                                        <p className="text-sm mt-2">
                                            Las interacciones aparecerán aquí
                                            cuando el lead responda
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
